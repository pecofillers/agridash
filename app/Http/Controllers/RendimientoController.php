<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Labor;
use App\Models\RendimientoLabor;
use App\Models\RendimientoDetalle;
use App\Models\Usuario;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RendimientoController extends Controller
{
    public const UMBRALES = [
        'DESHOJE' => ['verde' => 3.5, 'naranja' => 3],
        'CORTE LIMONIUM' => ['verde' => 300, 'naranja' => 250],
        'CORTE STATICE' => ['verde' => 400, 'naranja' => 350],
    ];

    public function index(Request $request)
    {
        $usuarioAuth = auth()->user();
        $rolId = $usuarioAuth?->ID_Rol;
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $username = $usuarioAuth?->Username;

        $submodulos = Rbac::submodulosVisibles($rolId, 'rendimiento_colaboradores');
        $hoy = date('Y-m-d');

        $queryUsuarios = Usuario::with('grupo')->where('Estado', 'ACTIVO');
        $queryRendimientos = RendimientoLabor::with(['usuario', 'grupo', 'labor', 'detalles'])
            ->whereDate('Fecha', $hoy);

        // Si NO es Administrador ni Superadmin, filtramos exclusivamente por los grupos del supervisor
        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $gruposSupervisor = $this->gruposDelSupervisor($username);
            
            $queryUsuarios->whereIn('ID_Grupo', $gruposSupervisor);
            $queryRendimientos->whereIn('ID_Grupo', $gruposSupervisor);
        }

        $usuarios = $queryUsuarios->orderBy('Nombre')->orderBy('Apellidos')->get();
        $grupos = Grupo::with('usuarios')->orderBy('Nombre_Grupo')->get();
        $labores = Labor::orderBy('Nombre_Labor')->get();

        $rendimientos = $queryRendimientos->latest('ID_Rendimiento')->get();

        $supervisores = Usuario::whereHas('rol', function ($query) {
            $query->where('Nombre_Rol', 'SUPERVISOR');
        })->orderBy('Nombre')->get();

        return view('rendimiento.index', compact('submodulos', 'usuarios', 'grupos', 'supervisores', 'rolNombre', 'rendimientos', 'labores'));
    }

    public function actualizarLabor(Request $request, $id)
    {
        $request->validate([
            'Fecha' => 'required|date',
            'ID_Usuario' => 'required|integer|exists:dim_usuarios,ID_Usuario',
            'ID_Labor' => 'required|integer|exists:dim_labores,ID_Labor',
            'Hora_Inicio' => 'required', 
            'Hora_Fin' => 'required',
            // 'Cantidad' ya no es strictly required aquí porque podría venir el array 'variantes'
        ]);

        $registro = RendimientoLabor::findOrFail($id);
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $usuarioAuth = auth()->user();

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($usuarioAuth?->Username);
            if (!in_array($registro->ID_Grupo, $grupos, true)) {
                return back()->with('error', 'No tienes permisos para modificar este registro.');
            }
        }

        $fecha = Carbon::parse($request->Fecha)->toDateString();

        try {
            $tInicio = Carbon::parse($fecha . ' ' . $request->Hora_Inicio);
            $tFin = Carbon::parse($fecha . ' ' . $request->Hora_Fin);
        } catch (\Exception $e) {
            return back()->with('error', 'Formato de hora inválido.');
        }

        if ($tFin->lt($tInicio)) {
            $tFin->addDay();
        }

        $horas = $tInicio->diffInSeconds($tFin) / 3600.0;

        if ($horas <= 0) {
            return back()->with('error', 'Verifica que la hora fin sea posterior a la hora de inicio.');
        }

        // Recibimos las variantes (o la cantidad simple) igual que en el método de crear
        $variantesInput = $request->input('variantes');
        
        if (!$variantesInput && $request->has('Cantidad')) {
            $variantesInput = [
                ['nombre' => 'General', 'cantidad' => $request->Cantidad]
            ];
        }

        if (!$variantesInput) {
            return back()->with('error', 'Debes ingresar al menos una cantidad o variante.');
        }

        // Calcular la cantidad total sumando las variantes editadas
        $cantidadTotal = collect($variantesInput)->sum('cantidad');

        if ($cantidadTotal <= 0) {
            return back()->with('error', 'La cantidad total debe ser mayor a cero.');
        }

        $rend = round($cantidadTotal / $horas, 2);

        DB::transaction(function () use ($registro, $request, $fecha, $horas, $cantidadTotal, $rend, $variantesInput) {
            
            // 1. Actualizar la cabecera
            $registro->update([
                'Fecha' => $fecha,
                'ID_Usuario' => $request->ID_Usuario,
                'ID_Labor' => $request->ID_Labor,
                'Hora_Inicio' => $request->Hora_Inicio,
                'Hora_Fin' => $request->Hora_Fin,
                'Horas_Trabajadas' => round($horas, 2),
                'Cantidad' => $cantidadTotal,
                'Rendimiento_Hora' => $rend,
            ]);

            // 2. Eliminar el detalle anterior
            RendimientoDetalle::where('ID_Rendimiento', $registro->ID_Rendimiento)->delete();

            // 3. Crear el nuevo detalle con las variantes modificadas
            foreach ($variantesInput as $var) {
                $cantVar = (float) ($var['cantidad'] ?? 0);
                if ($cantVar > 0) {
                    RendimientoDetalle::create([
                        'ID_Rendimiento' => $registro->ID_Rendimiento,
                        'Nombre_Variante' => $var['nombre'] ?? 'General',
                        'Cantidad' => $cantVar,
                    ]);
                }
            }
        });

        return back()->with('success', 'Registro y variantes actualizados correctamente.');
    }

    public function eliminarLabor($id)
    {
        $registro = RendimientoLabor::findOrFail($id);
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $usuarioAuth = auth()->user();

        // Validar permisos del supervisor
        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($usuarioAuth?->Username);
            if (!in_array($registro->ID_Grupo, $grupos, true)) {
                return back()->with('error', 'No tienes permisos para eliminar este registro.');
            }
        }

        // Eliminar el registro (gracias a 'onDelete cascade', los detalles de variantes se borran solos)
        $registro->delete();

        return back()->with('success', 'Registro eliminado correctamente.');
    }

    public function grupos()
    {
        $usuarioAuth = auth()->user();
        $rolId = $usuarioAuth?->ID_Rol;
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $username = $usuarioAuth?->Username;

        $submodulos = Rbac::submodulosVisibles($rolId, 'rendimiento_colaboradores');

        $queryUsuarios = Usuario::with('grupo')->where('Estado', 'ACTIVO');
        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $gruposSupervisor = $this->gruposDelSupervisor($username);
            $queryUsuarios->whereIn('ID_Grupo', $gruposSupervisor);
        }

        $usuarios = $queryUsuarios->orderBy('Nombre')->orderBy('Apellidos')->get();
        $grupos = Grupo::with('usuarios')->orderBy('Nombre_Grupo')->get();
        
        $supervisores = Usuario::whereHas('rol', function ($query) {
            $query->where('Nombre_Rol', 'SUPERVISOR');
        })->orderBy('Nombre')->get();

        $usuariosSinGrupo = Usuario::with('grupo')->where('Estado', 'ACTIVO')->whereNull('ID_Grupo')->orderBy('Nombre')->orderBy('Apellidos')->get();

        return view('rendimiento.grupos', compact('submodulos', 'usuarios', 'grupos', 'supervisores', 'usuariosSinGrupo', 'rolNombre'));
    }

    public function gestionLabores(Request $request)
    {
        $usuarioAuth = auth()->user();
        $rolId = $usuarioAuth?->ID_Rol;
        $rolNombre = session('rol_nombre', 'OPERARIO');

        // Cargamos los submódulos visibles para mantener el menú superior activado
        $submodulos = Rbac::submodulosVisibles($rolId, 'rendimiento_colaboradores');

        $labores = Labor::orderBy('Nombre_Labor')->get();
        
        $laborSeleccionada = null;
        if ($request->has('editar')) {
            $laborSeleccionada = Labor::find($request->editar);
        }

        return view('rendimiento.labores', compact('submodulos', 'labores', 'laborSeleccionada', 'rolNombre'));
    }

    public function guardarLaborCatalogo(Request $request)
    {
        $request->validate([
            'ID_Labor' => 'nullable|integer|exists:dim_labores,ID_Labor',
            'Nombre_Labor' => 'required|string|max:100',
            'Unidad_Medida' => 'required|string|max:50',
            'Umbral_Verde' => 'required|numeric|min:0',
            'Umbral_Naranja' => 'required|numeric|min:0',
            'Variantes' => 'nullable|string' // <--- Validación agregada
        ]);

        // Preparamos los datos incluyendo las variantes
        $datos = [
            'Nombre_Labor' => trim($request->Nombre_Labor),
            'Unidad_Medida' => trim($request->Unidad_Medida),
            'Umbral_Verde' => (float) $request->Umbral_Verde,
            'Umbral_Naranja' => (float) $request->Umbral_Naranja,
            'Variantes' => $request->Variantes ? trim($request->Variantes) : null, // <--- Guardamos las variantes
        ];

        if ($request->filled('ID_Labor')) {
            // Modo Edición
            $labor = Labor::findOrFail($request->ID_Labor);
            $labor->update($datos);
            $mensaje = 'Labor y variantes actualizadas correctamente.';
        } else {
            // Modo Creación
            Labor::create($datos);
            $mensaje = 'Labor registrada correctamente.';
        }

        return redirect()->route('rendimiento.labores')->with('success', $mensaje);
    }

    public function registrarLabor(Request $request)
    {
        $request->validate([
            'Fecha' => 'required|date',
            'ID_Usuario' => 'required|integer|exists:dim_usuarios,ID_Usuario',
            'ID_Labor' => 'required|integer|exists:dim_labores,ID_Labor',
            'Hora_Inicio' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'Hora_Fin' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $rolNombre = session('rol_nombre', 'OPERARIO');
        $usuarioAuth = auth()->user();
        $username = $usuarioAuth?->Username;

        $usuarioAsignado = Usuario::with('grupo')->findOrFail($request->ID_Usuario);

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$usuarioAsignado->ID_Grupo || !in_array($usuarioAsignado->ID_Grupo, $grupos, true)) {
                return back()->with('error', 'Solo puedes registrar labores para usuarios de tu grupo asignado.');
            }
        }

        $fecha = Carbon::parse($request->Fecha)->toDateString();

        try {
            $tInicio = Carbon::parse($fecha . ' ' . $request->Hora_Inicio);
            $tFin = Carbon::parse($fecha . ' ' . $request->Hora_Fin);
        } catch (\Exception $e) {
            return back()->with('error', 'Formato de hora inválido.');
        }

        if ($tFin->lt($tInicio)) {
            $tFin->addDay();
        }

        $horas = $tInicio->diffInSeconds($tFin) / 3600.0;

        if ($horas <= 0) {
            return back()->with('error', 'Verifica que la hora fin sea posterior a la hora de inicio.');
        }

        $variantesInput = $request->input('variantes');
        
        if (!$variantesInput && $request->has('Cantidad')) {
            $variantesInput = [
                ['nombre' => 'General', 'cantidad' => $request->Cantidad]
            ];
        }

        if (!$variantesInput) {
            return back()->with('error', 'Debes ingresar al menos una cantidad o variante.');
        }

        $cantidadTotal = collect($variantesInput)->sum('cantidad');

        if ($cantidadTotal <= 0) {
            return back()->with('error', 'La cantidad total debe ser mayor a cero.');
        }

        $rendimientoHora = round($cantidadTotal / $horas, 2);

        $nuevoRegistro = null;

        DB::transaction(function () use ($request, $fecha, $usuarioAsignado, $horas, $cantidadTotal, $rendimientoHora, $variantesInput, &$nuevoRegistro) {
            
            $nuevoRegistro = RendimientoLabor::create([
                'Fecha' => $fecha,
                'ID_Usuario' => $request->ID_Usuario,
                'ID_Grupo' => $usuarioAsignado->ID_Grupo,
                'ID_Labor' => $request->ID_Labor,
                'Hora_Inicio' => $request->Hora_Inicio,
                'Hora_Fin' => $request->Hora_Fin,
                'Horas_Trabajadas' => round($horas, 2),
                'Cantidad' => $cantidadTotal,
                'Rendimiento_Hora' => $rendimientoHora,
            ]);

            foreach ($variantesInput as $var) {
                $cantVar = (float) ($var['cantidad'] ?? 0);
                if ($cantVar > 0) {
                    RendimientoDetalle::create([
                        'ID_Rendimiento' => $nuevoRegistro->ID_Rendimiento,
                        'Nombre_Variante' => $var['nombre'] ?? 'General',
                        'Cantidad' => $cantVar,
                    ]);
                }
            }
        });

        return back()
            ->with('success', 'Registro y desglose de variantes guardados exitosamente.')
            ->with('ultimo_registro', $nuevoRegistro);
    }

    public function crearGrupo(Request $request)
    {
        $request->validate([
            'Nombre_Grupo' => 'required|string|max:100',
            'Supervisor_Asignado' => 'required|string',
        ]);

        $supervisor = Usuario::where('Username', $request->Supervisor_Asignado)->first();

        Grupo::create([
            'Nombre_Grupo' => strtoupper(trim($request->Nombre_Grupo)),
            'ID_Supervisor' => $supervisor->ID_Usuario ?? null,
        ]);

        return back()->with('success', 'Grupo creado correctamente.');
    }

    public function agregarUsuario(Request $request)
    {
        $request->validate([
            'ID_Usuario' => 'required|integer|exists:dim_usuarios,ID_Usuario',
            'ID_Grupo' => 'required|integer|exists:dim_grupos,ID_Grupo',
        ]);

        Usuario::where('ID_Usuario', $request->ID_Usuario)->update(['ID_Grupo' => $request->ID_Grupo]);
        return back()->with('success', 'Usuario asignado al grupo exitosamente.');
    }

    public function quitarUsuario(Request $request)
    {
        $request->validate(['ID_Usuario' => 'required|integer']);
        Usuario::where('ID_Usuario', $request->ID_Usuario)->update(['ID_Grupo' => null]);
        return back()->with('success', 'Usuario removido del grupo.');
    }

    public function actualizarSupervisorGrupo(Request $request)
    {
        $request->validate([
            'ID_Grupo' => 'required|integer|exists:dim_grupos,ID_Grupo',
            'Supervisor_Asignado' => 'required|string|max:50',
        ]);

        $supervisor = Usuario::where('Username', $request->Supervisor_Asignado)->first();

        Grupo::where('ID_Grupo', $request->ID_Grupo)->update([
            'ID_Supervisor' => $supervisor->ID_Usuario ?? null,
        ]);

        return back()->with('success', 'Supervisor del grupo actualizado correctamente.');
    }

    private function gruposDelSupervisor(?string $supervisorUser): array
    {
        if (!$supervisorUser) return [];
        $usuario = Usuario::where('Username', $supervisorUser)->first();
        if (!$usuario) return [];
        return Grupo::where('ID_Supervisor', $usuario->ID_Usuario)->pluck('ID_Grupo')->all();
    }

    private function queryReporte($fechaDesde, $fechaHasta, $rolNombre, $username, $labor, $usuarioFiltro) 
    {
        $q = RendimientoLabor::query()->whereBetween('Fecha', [$fechaDesde, $fechaHasta]);

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) return collect();
            $q->whereIn('ID_Grupo', $grupos);
        }

        if ($labor !== 'TODAS') {
            $q->whereHas('labor', function($query) use ($labor) {
                $query->where('Nombre_Labor', $labor);
            });
        }

        if ($usuarioFiltro !== 'TODOS') {
            $q->whereHas('usuario', function($query) use ($usuarioFiltro) {
                $query->whereRaw("TRIM(CONCAT(IFNULL(Nombre, ''), ' ', IFNULL(Apellidos, ''))) = ?", [$usuarioFiltro])
                      ->orWhere('Username', $usuarioFiltro);
            });
        }

        return $q;
    }

    private function usuariosVisibles(): array
    {
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $usuarioAuth = auth()->user();
        $username = $usuarioAuth?->Username;

        $query = Usuario::where('Estado', 'ACTIVO');

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            $query->whereIn('ID_Grupo', $grupos);
        }

        return $query->get()
            ->map(fn ($u) => trim(($u->Nombre ?? '') . ' ' . ($u->Apellidos ?? '')) ?: $u->Username)
            ->unique()
            ->values()
            ->all();
    }

    // En la vista de reporte, permitimos recibir el filtro de grupo
    public function reporte(Request $request)
    {
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $usuarioAuth = auth()->user();
        $username = $usuarioAuth?->Username;

        $filtroTiempo = $request->input('periodo', 'Esta Semana');
        $filtroLabor = $request->input('labor', 'TODAS');
        $filtroPersona = $request->input('persona', 'TODOS');
        $filtroGrupo = $request->input('grupo', 'TODOS');

        $hoy = Carbon::today();
        $fechaInicio = $hoy->copy()->startOfWeek();
        $fechaFin = $hoy->copy();
        if ($filtroTiempo === 'Hoy') {
            $fechaInicio = $hoy->copy();
        } elseif ($filtroTiempo === 'Este Mes') {
            $fechaInicio = $hoy->copy()->startOfMonth();
        } elseif ($filtroTiempo === 'Personalizado') {
            $fechaInicio = Carbon::parse($request->input('desde', $hoy->copy()->subDays(30)->toDateString()));
            $fechaFin = Carbon::parse($request->input('hasta', $hoy->toDateString()));
        }

        $fechaInicioStr = $fechaInicio->toDateString();
        $fechaFinStr = $fechaFin->toDateString();

        // Modificamos la consulta para soportar el filtro de grupo si es ADMIN/SUPERADMIN
        $q = RendimientoLabor::query()->whereBetween('Fecha', [$fechaInicioStr, $fechaFinStr]);

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) {
                $registros = collect();
            } else {
                $q->whereIn('ID_Grupo', $grupos);
            }
        } else {
            if ($filtroGrupo !== 'TODOS') {
                $q->where('ID_Grupo', $filtroGrupo);
            }
        }

        if ($filtroLabor !== 'TODAS') {
            $q->whereHas('labor', function($query) use ($filtroLabor) {
                $query->where('Nombre_Labor', $filtroLabor);
            });
        }

        if ($filtroPersona !== 'TODOS') {
            $q->whereHas('usuario', function($query) use ($filtroPersona) {
                $query->whereRaw("TRIM(CONCAT(IFNULL(Nombre, ''), ' ', IFNULL(Apellidos, ''))) = ?", [$filtroPersona])
                    ->orWhere('Username', $filtroPersona);
            });
        }

        $registros = $q->with(['usuario', 'labor', 'detalles', 'grupo'])->orderByDesc('Fecha')->orderByDesc('Hora_Inicio')->get();

        $meta = 15.0;
        $catalogoLabores = Labor::orderBy('Nombre_Labor')->get();
        foreach ($catalogoLabores as $l) {
            if ($l->Nombre_Labor === $filtroLabor) {
                $meta = (float) $l->Umbral_Verde;
                break;
            }
        }

        $usuariosFiltro = $this->usuariosVisibles();
        $gruposFiltro = Grupo::with('supervisor')->orderBy('Nombre_Grupo')->get();
        
        $supervisorNombre = 'Todos los supervisores (Grupos múltiples)';
        if ($filtroGrupo !== 'TODOS') {
            $infoGrupo = DB::table('dim_grupos as g')
                ->leftJoin('dim_usuarios as u', 'g.ID_Supervisor', '=', 'u.ID_Usuario')
                ->where('g.ID_Grupo', $filtroGrupo)
                ->select('u.Nombre', 'u.Apellidos', 'u.Username')
                ->first();

            if ($infoGrupo) {
                $supervisorNombre = trim(($infoGrupo->Nombre ?? '') . ' ' . ($infoGrupo->Apellidos ?? '')) ?: ($infoGrupo->Username ?? 'No asignado');
            }
        }

        return view('rendimiento.reporte', compact('registros', 'filtroTiempo', 'filtroLabor', 'filtroPersona', 'filtroGrupo', 'fechaInicioStr', 'fechaFinStr', 'meta', 'usuariosFiltro', 'gruposFiltro', 'catalogoLabores', 'rolNombre', 'supervisorNombre'));
    }

    public function reporteSemanal(Request $request)
    {
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $usuarioAuth = auth()->user();
        $username = $usuarioAuth?->Username;

        $semanaSel = $request->input('semana');
        $filtroGrupo = $request->input('grupo', 'TODOS'); // <--- CAPTURAR EL GRUPO
        $semanas = $this->semanasDisponibles($rolNombre, $username, $filtroGrupo);

        $resumen = collect();
        $detalle = collect();
        $fechaRef = null;

        if ($semanaSel && preg_match('/^(\d{4})-S(\d{2})$/', $semanaSel, $m)) {
            $anio = (int) $m[1];
            $semana = (int) $m[2];

            $base = $this->queryReporteBaseSemana($anio, $semana, $rolNombre, $username, $filtroGrupo);

            if ($base) {
                $registros = $base->with(['usuario', 'labor', 'detalles', 'grupo'])->orderBy('Fecha')->orderBy('Hora_Inicio')->get();

                $resumen = $registros->groupBy(function($r) {
                    return trim(($r->usuario->Nombre ?? '') . ' ' . ($r->usuario->Apellidos ?? '')) ?: ($r->usuario->Username ?? 'Desconocido');
                })->map(function ($regs) {
                    return $this->agruparPorLabor($regs);
                });

                $detalle = $registros->groupBy([
                    function($r) {
                        return trim(($r->usuario->Nombre ?? '') . ' ' . ($r->usuario->Apellidos ?? '')) ?: ($r->usuario->Username ?? 'Desconocido');
                    },
                    function($r) {
                        return $r->labor->Nombre_Labor ?? 'Sin Labor';
                    }
                ])->map(function ($regs) {
                    return $regs->map(function ($laborRegs) {
                        return $this->calcularResumenLabor($laborRegs);
                    });
                })->flatten(1)->values();

                $dt = new \DateTime();
                $dt->setISODate($anio, $semana, 1);
                $fechaRef = Carbon::instance($dt);
            }
        }

        $laborCatalogo = Labor::orderBy('Nombre_Labor')->get();
        $gruposFiltro = Grupo::orderBy('Nombre_Grupo')->get(); 

        // --- CONSULTA DEL SUPERVISOR ---
        $supervisorNombre = 'No asignado';
        if ($filtroGrupo !== 'TODOS') {
            // Buscamos el grupo y su supervisor directamente de la base de datos
            $infoGrupo = DB::table('dim_grupos as g')
                ->leftJoin('dim_usuarios as u', 'g.ID_Supervisor', '=', 'u.ID_Usuario')
                ->where('g.ID_Grupo', $filtroGrupo)
                ->select('u.Nombre', 'u.Apellidos', 'u.Username')
                ->first();

            if ($infoGrupo) {
                $supervisorNombre = trim(($infoGrupo->Nombre ?? '') . ' ' . ($infoGrupo->Apellidos ?? '')) ?: ($infoGrupo->Username ?? 'N/D');
            }
        } else {
            $supervisorNombre = 'Todos los supervisores (Grupos múltiples)';
        }

        return view('rendimiento.reporte_semanal', compact(
            'semanas', 
            'semanaSel', 
            'resumen', 
            'detalle', 
            'fechaRef', 
            'laborCatalogo', 
            'gruposFiltro', 
            'filtroGrupo', 
            'rolNombre',
            'supervisorNombre'
        ));
    }

    private function queryReporteBaseSemana(int $anio, int $semana, string $rolNombre, ?string $username, string $filtroGrupo = 'TODOS')
    {
        $q = RendimientoLabor::query()
            ->whereYear('Fecha', $anio)
            ->whereRaw('WEEK(Fecha, 3) = ?', [$semana]);

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) return null;
            $q->whereIn('ID_Grupo', $grupos);
        } else {
            if ($filtroGrupo !== 'TODOS') {
                $q->where('ID_Grupo', $filtroGrupo);
            }
        }

        return $q;
    }

    private function semanasDisponibles(string $rolNombre, ?string $username): array
    {
        $base = RendimientoLabor::query()->select(DB::raw('YEAR(Fecha) AS Anio'), DB::raw('WEEK(Fecha, 3) AS Semana'))->distinct();

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) return [];
            $base->whereIn('ID_Grupo', $grupos);
        }

        $rows = $base->orderByDesc('Anio')->orderByDesc('Semana')->get();
        return $rows->map(fn ($r) => sprintf('%04d-S%02d', $r->Anio, $r->Semana))->values()->all();
    }

    private function agruparPorLabor($registros)
    {
        return $registros->groupBy(function($r) {
            return $r->labor->Nombre_Labor ?? 'Sin Labor';
        })->map(function ($regs) {
            return $this->calcularResumenLabor($regs);
        })->values();
    }

    private function calcularResumenLabor($regs)
    {
        $sumCantidad = $regs->sum('Cantidad');
        $sumHoras = $regs->sum('Horas_Trabajadas');
        $promedio = $regs->count() ? round($regs->avg('Rendimiento_Hora'), 1) : 0;

        $first = $regs->first();
        $nombreUsuario = trim(($first->usuario->Nombre ?? '') . ' ' . ($first->usuario->Apellidos ?? '')) ?: ($first->usuario->Username ?? 'Desconocido');
        
        $laborModel = $first->labor;
        $nombreLabor = $laborModel->Nombre_Labor ?? 'Sin Labor';

        // NUEVO: Agrupamos y sumamos las variantes para este usuario en la semana
        $variantes = [];
        foreach ($regs as $r) {
            if ($r->detalles) {
                foreach ($r->detalles as $det) {
                    if ($det->Nombre_Variante !== 'General') {
                        if (!isset($variantes[$det->Nombre_Variante])) {
                            $variantes[$det->Nombre_Variante] = 0;
                        }
                        $variantes[$det->Nombre_Variante] += $det->Cantidad;
                    }
                }
            }
        }

        return [
            'Nombre_Usuario' => $nombreUsuario,
            'Tipo_Labor' => $nombreLabor,
            'Total_Cantidad' => round($sumCantidad, 1),
            'Total_Horas' => round($sumHoras, 2),
            'Rendimiento_Promedio' => $promedio,
            'Registros' => $regs->count(),
            'Color' => $this->colorSemaforo($promedio, $laborModel),
            'Variantes' => $variantes, // Pasamos las variantes a la vista
        ];
    }

    private function colorSemaforo(float $rend, $laborModel): string
    {
        if ($laborModel) {
            if ($rend >= (float) $laborModel->Umbral_Verde) return '#2e7d32';
            if ($rend >= (float) $laborModel->Umbral_Naranja) return '#f57c00';
            return '#d32f2f';
        }
        return '#d32f2f';
    }

    public function eliminarLaborCatalogo($id)
    {
        try {
            $labor = Labor::findOrFail($id);
            $labor->delete();
            return back()->with('success', 'Labor eliminada correctamente del catálogo.');
        } catch (\Illuminate\Database\QueryException $e) {
            // El código 23000 es el error de llave foránea de SQL. Si la labor ya tiene rendimientos guardados, no se puede borrar.
            if ($e->getCode() == "23000") {
                return back()->with('error', 'No se puede eliminar esta labor porque ya tiene registros de rendimiento asociados. (En su lugar, podrías cambiarle el nombre).');
            }
            return back()->with('error', 'Ocurrió un error al intentar eliminar la labor.');
        }
    }
}