<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Grupo;
use App\Models\RendimientoLabor;
use App\Models\Usuario;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RendimientoController extends Controller
{
    // Umbrales de semaforo por labor (replican UMBRALES_SEMAFORO de Python)
    public const UMBRALES = [
        'DESHOJE' => ['verde' => 3.5, 'naranja' => 3],
        'CORTE LIMONIUM' => ['verde' => 300, 'naranja' => 250],
        'CORTE STATICE' => ['verde' => 400, 'naranja' => 350],
    ];

    public function index()
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = auth()->user();
        $rolId = $usuario?->ID_Rol;
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $username = $usuario?->Username;

        $submodulos = Rbac::submodulosVisibles($rolId, 'rendimiento_colaboradores');
        
        // Obtenemos la fecha de hoy para el filtro
        $hoy = date('Y-m-d'); 

        // Colaboradores visibles según rol
        if (in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $colaboradores = Colaborador::active()->with('grupo')->get();
            $grupos = Grupo::orderBy('Nombre_Grupo')->get();
            
            // Filtramos por la fecha de hoy
            $rendimientos = RendimientoLabor::with(['colaborador', 'grupo'])
                ->whereDate('Fecha', $hoy)
                ->latest('ID_Rendimiento')
                ->get();
        } else {
            $colaboradores = Colaborador::active()->whereHas('grupo', function ($q) use ($username) {
                $q->where('Supervisor_Asignado', $username);
            })->with('grupo')->get();
            $grupos = Grupo::where('Supervisor_Asignado', $username)->orderBy('Nombre_Grupo')->get();
            
            $gruposIds = $grupos->pluck('ID_Grupo');
            
            // Filtramos por los grupos del supervisor Y la fecha de hoy
            $rendimientos = RendimientoLabor::with(['colaborador', 'grupo'])
                ->whereIn('ID_Grupo', $gruposIds)
                ->whereDate('Fecha', $hoy)
                ->latest('ID_Rendimiento')
                ->get();
        }

        // AQUÍ EL CAMBIO: Filtramos para traer únicamente los usuarios con rol SUPERVISOR
        $supervisores = Usuario::whereHas('rol', function ($query) {
            $query->where('Nombre_Rol', 'SUPERVISOR');
        })->pluck('Username');

        return view('rendimiento.index', compact('submodulos', 'colaboradores', 'grupos', 'supervisores', 'rolNombre', 'rendimientos'));
    }

    public function grupos()
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = auth()->user();
        $rolId = $usuario?->ID_Rol;
        $rolNombre = session('rol_nombre', 'OPERARIO');
        $username = $usuario?->Username;

        $submodulos = Rbac::submodulosVisibles($rolId, 'rendimiento_colaboradores');

        if (in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $colaboradores = Colaborador::with('grupo')->orderBy('Nombre_Colaborador')->get();
            $grupos = Grupo::orderBy('Nombre_Grupo')->get();
        } else {
            $colaboradores = Colaborador::whereHas('grupo', function ($q) use ($username) {
                $q->where('Supervisor_Asignado', $username);
            })->with('grupo')->get();
            $grupos = Grupo::where('Supervisor_Asignado', $username)->orderBy('Nombre_Grupo')->get();
        }

        // AQUÍ EL CAMBIO: Filtramos para traer únicamente los usuarios con rol SUPERVISOR
        $supervisores = Usuario::whereHas('rol', function ($query) {
            $query->where('Nombre_Rol', 'SUPERVISOR');
        })->pluck('Username');

        // AQUÍ EL CAMBIO: Obtenemos solo los colaboradores activos que NO tienen grupo
        $colaboradoresSinGrupo = Colaborador::where('Estado', 'ACTIVO')
            ->whereNull('ID_Grupo')
            ->orderBy('Nombre_Colaborador')
            ->get();

        // Agregamos la nueva variable compact('...','colaboradoresSinGrupo',...)
        return view('rendimiento.grupos', compact('submodulos', 'colaboradores', 'grupos', 'supervisores', 'colaboradoresSinGrupo', 'rolNombre'));
    }

    public function registrarLabor(Request $request)
    {
        $request->validate([
            'Fecha' => 'required|date',
            'ID_Colaborador' => 'required|integer',
            'Tipo_Labor' => 'required|string',
            'Hora_Inicio' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'Hora_Fin' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'Cantidad' => 'required|numeric|min:0.01',
        ]);

        $rolNombre = session('rol_nombre', 'OPERARIO');
        /** @var \App\Models\Usuario|null $usuario */
        $usuario = auth()->user();
        $username = $usuario?->Username;

        $colab = Colaborador::with('grupo')->findOrFail($request->ID_Colaborador);

        // Punto 2: Validar que el colaborador pertenezca al grupo del supervisor
        // (solo aplica a roles no administradores).
        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$colab->ID_Grupo || !in_array($colab->ID_Grupo, $grupos, true)) {
                return back()->with('error', 'Solo puedes registrar labor de colaboradores de tu propio grupo.');
            }
        }

        // Punto 1: Calcular horas correctamente con fecha (soporta cruce de medianoche).
        $fecha = Carbon::parse($request->Fecha)->toDateString();

        try {
            $tInicio = Carbon::parse($fecha . ' ' . $request->Hora_Inicio);
            $tFin = Carbon::parse($fecha . ' ' . $request->Hora_Fin);
        } catch (\Exception $e) {
            return back()->with('error', 'Formato de hora invalido.');
        }

        // Si la hora de fin es menor que la de inicio, asumimos cruce de medianoche.
        if ($tFin->lt($tInicio)) {
            $tFin->addDay();
        }

        $horas = $tInicio->diffInSeconds($tFin) / 3600.0;
        $cantidad = (float) $request->Cantidad;

        // Validar condiciones de negocio (igual que Python: horas>0 y cantidad>0)
        if ($horas <= 0 || $cantidad <= 0) {
            return back()->with('error', 'Verifica las horas y que la cantidad sea mayor a cero.');
        }

        $unidad = $request->Tipo_Labor === 'DESHOJE' ? 'CUADROS' : 'TALLOS';
        $rend = round($cantidad / $horas, 2);

        // Evitar duplicados
        $existe = RendimientoLabor::where('Fecha', $fecha)
            ->where('ID_Colaborador', $request->ID_Colaborador)
            ->where('Tipo_Labor', $request->Tipo_Labor)
            ->where('Hora_Inicio', $request->Hora_Inicio)
            ->where('Hora_Fin', $request->Hora_Fin)
            ->exists();

        if ($existe) {
            return back()->with('error', 'Ya existe un registro con el mismo colaborador, fecha, labor y horario.');
        }

        $nuevoRegistro = RendimientoLabor::create([
            'Fecha' => $fecha,
            'ID_Colaborador' => $colab->ID_Colaborador,
            'Nombre_Colaborador' => $colab->Nombre_Colaborador,
            'ID_Grupo' => $colab->ID_Grupo,
            'Supervisor' => $colab->grupo?->Supervisor_Asignado,
            'Tipo_Labor' => $request->Tipo_Labor,
            'Unidad_Medida' => $unidad,
            'Hora_Inicio' => $request->Hora_Inicio,
            'Hora_Fin' => $request->Hora_Fin,
            'Horas_Trabajadas' => round($horas, 2),
            'Cantidad' => $cantidad,
            'Rendimiento_Hora' => $rend,
        ]);

        return back()
            ->with('success', 'Registro guardado exitosamente.')
            ->with('ultimo_registro', $nuevoRegistro);
    }

    public function crearGrupo(Request $request)
    {
        $request->validate([
            'Nombre_Grupo' => 'required|string|max:100',
            'Supervisor_Asignado' => 'required|string',
        ]);

        Grupo::create([
            'Nombre_Grupo' => strtoupper(trim($request->Nombre_Grupo)),
            'Supervisor_Asignado' => $request->Supervisor_Asignado,
        ]);

        return back()->with('success', 'Grupo creado correctamente.');
    }

    // AQUÍ EL CAMBIO: Ahora en lugar de crear, actualizamos al colaborador usando el selector.
    public function agregarColaborador(Request $request)
    {
        $request->validate([
            'ID_Colaborador' => 'required|integer|exists:dim_colaboradores,ID_Colaborador',
            'ID_Grupo' => 'required|integer|exists:dim_grupos,ID_Grupo',
        ]);

        // Actualizamos el colaborador existente asignándole el nuevo ID_Grupo
        Colaborador::where('ID_Colaborador', $request->ID_Colaborador)
            ->update(['ID_Grupo' => $request->ID_Grupo]);

        return back()->with('success', 'Persona asignada al grupo exitosamente.');
    }

    public function quitarColaborador(Request $request)
    {
        $request->validate(['ID_Colaborador' => 'required|integer']);
        Colaborador::where('ID_Colaborador', $request->ID_Colaborador)->update(['ID_Grupo' => null]);
        return back()->with('success', 'Colaborador removido del grupo.');
    }

    // Punto 5: Reasignar supervisor de un grupo (replica el data_editor del Python).
    public function actualizarSupervisorGrupo(Request $request)
    {
        $request->validate([
            'ID_Grupo' => 'required|integer|exists:dim_grupos,ID_Grupo',
            'Supervisor_Asignado' => 'required|string|max:50',
        ]);

        Grupo::where('ID_Grupo', $request->ID_Grupo)->update([
            'Supervisor_Asignado' => $request->Supervisor_Asignado,
        ]);

        return back()->with('success', 'Supervisor del grupo actualizado correctamente.');
    }

    // ------------------------------------------------------------------
    // Helpers internos (replican acceso por rol de Python)
    // ------------------------------------------------------------------

    /** IDs de grupo que administra el supervisor actual. */
    private function gruposDelSupervisor(?string $supervisor): array
    {
        if (!$supervisor) {
            return [];
        }
        return Grupo::where('Supervisor_Asignado', $supervisor)->pluck('ID_Grupo')->all();
    }

    /** Consulta base de rendimientos con filtro de rol, labor y persona. */
    private function queryReporte(
        string $fechaDesde,
        string $fechaHasta,
        string $rolNombre,
        ?string $username,
        string $labor = 'TODAS',
        string $colaborador = 'TODOS'
    ) {
        $q = RendimientoLabor::query()->whereBetween('Fecha', [$fechaDesde, $fechaHasta]);

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) {
                return collect(); // sin grupos asignados -> vacio
            }
            $q->whereIn('ID_Grupo', $grupos);
        }

        if ($labor !== 'TODAS') {
            $q->where('Tipo_Labor', $labor);
        }

        if ($colaborador !== 'TODOS') {
            $q->where('Nombre_Colaborador', $colaborador);
        }

        return $q;
    }

    /** Lista de colaboradores visibles (para filtro de persona). */
    private function colaboradoresVisibles(): array
    {
        $rolNombre = session('rol_nombre', 'OPERARIO');
        /** @var \App\Models\Usuario|null $usuario */
        $usuario = auth()->user();
        $username = $usuario?->Username;

        if (in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            return Colaborador::active()->orderBy('Nombre_Colaborador')->pluck('Nombre_Colaborador')->unique()->all();
        }

        $grupos = $this->gruposDelSupervisor($username);
        return Colaborador::active()->whereIn('ID_Grupo', $grupos)
            ->orderBy('Nombre_Colaborador')->pluck('Nombre_Colaborador')->unique()->all();
    }

    // ------------------------------------------------------------------
    // REPORTE Y GRAFICAS (semáforo con umbrales fijos)
    // ------------------------------------------------------------------
    public function reporte(Request $request)
    {
        $rolNombre = session('rol_nombre', 'OPERARIO');
        /** @var \App\Models\Usuario|null $usuario */
        $usuario = auth()->user();
        $username = $usuario?->Username;

        $filtroTiempo = $request->input('periodo', 'Esta Semana');
        $filtroLabor = $request->input('labor', 'TODAS');
        $filtroPersona = $request->input('persona', 'TODOS');

        // Calcular rango de fechas segun periodo
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

        $registros = $this->queryReporte($fechaInicioStr, $fechaFinStr, $rolNombre, $username, $filtroLabor, $filtroPersona)
            ->orderByDesc('Fecha')
            ->orderByDesc('Hora_Inicio')
            ->get();

        // Meta segun labor (para grafica individual)
        $meta = 15.0;
        foreach (self::UMBRALES as $lab => $u) {
            if ($lab === $filtroLabor) {
                $meta = $u['verde'];
            }
        }

        $colaboradores = $this->colaboradoresVisibles();

        // Punto 3: Histórico diario individual (cuando se filtra por persona).
        // Replica la lógica del Python: agrupa por fecha y calcula rendimiento medio del día.
        $historicoDiario = collect();
        if ($filtroPersona !== 'TODOS' && $registros->isNotEmpty()) {
            $historicoDiario = $registros
                ->where('Nombre_Colaborador', $filtroPersona)
                ->groupBy(fn ($r) => $r->Fecha->toDateString())
                ->map(function ($regs) {
                    return [
                        'fecha' => $regs->first()->Fecha->format('d/m'),
                        'rend' => round($regs->avg('Rendimiento_Hora'), 1),
                    ];
                })
                ->sortKeys()
                ->values();
        }

        return view('rendimiento.reporte', compact('registros', 'filtroTiempo', 'filtroLabor', 'filtroPersona', 'fechaInicioStr', 'fechaFinStr', 'meta', 'colaboradores', 'historicoDiario'));
    }

    // ------------------------------------------------------------------
    // REPORTE SEMANAL POR COLABORADOR
    // ------------------------------------------------------------------
    public function reporteSemanal(Request $request)
    {
        $rolNombre = session('rol_nombre', 'OPERARIO');
        /** @var \App\Models\Usuario|null $usuario */
        $usuario = auth()->user();
        $username = $usuario?->Username;

        // Semanas disponibles
        $semanaSel = $request->input('semana');
        $semanas = $this->semanasDisponibles($rolNombre, $username);

        $resumen = collect();
        $detalle = collect();
        $fechaRef = null;

        if ($semanaSel && preg_match('/^(\d{4})-S(\d{2})$/', $semanaSel, $m)) {
            $anio = (int) $m[1];
            $semana = (int) $m[2];

            $base = $this->queryReporteBaseSemana($anio, $semana, $rolNombre, $username);

            if ($base) {
                $registros = $base->orderBy('Fecha')->orderBy('Hora_Inicio')->get();

                // Grupo por colaborador y labor
                $resumen = $registros->groupBy('Nombre_Colaborador')->map(function ($regs) {
                    return $this->agruparPorLabor($regs);
                });

                $detalle = $registros->groupBy(['Nombre_Colaborador', 'Tipo_Labor'])->map(function ($regs) {
                    return $regs->map(function ($laborRegs) {
                        return $this->calcularResumenLabor($laborRegs);
                    });
                })->flatten(1)->values();

                // Obtener el lunes de la semana ISO (año, semana, día=1 => lunes)
                $dt = new \DateTime();
                $dt->setISODate($anio, $semana, 1);
                $fechaRef = Carbon::instance($dt);
            }
        }

        return view('rendimiento.reporte_semanal', compact('semanas', 'semanaSel', 'resumen', 'detalle', 'fechaRef'));
    }

    private function semanasDisponibles(string $rolNombre, ?string $username): array
    {
        $base = RendimientoLabor::query()->select(DB::raw('YEAR(Fecha) AS Anio'), DB::raw('WEEK(Fecha, 3) AS Semana'))->distinct();

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) {
                return [];
            }
            $base->whereIn('ID_Grupo', $grupos);
        }

        $rows = $base->orderByDesc('Anio')->orderByDesc('Semana')->get();

        return $rows->map(fn ($r) => sprintf('%04d-S%02d', $r->Anio, $r->Semana))->values()->all();
    }

    private function queryReporteBaseSemana(int $anio, int $semana, string $rolNombre, ?string $username)
    {
        $q = RendimientoLabor::query()
            ->whereYear('Fecha', $anio)
            ->whereRaw('WEEK(Fecha, 3) = ?', [$semana]);

        if (!in_array($rolNombre, ['ADMIN', 'SUPERADMIN'])) {
            $grupos = $this->gruposDelSupervisor($username);
            if (!$grupos) {
                return null;
            }
            $q->whereIn('ID_Grupo', $grupos);
        }

        return $q;
    }

    /** Agrupa registros de un colaborador por labor con resumen. */
    private function agruparPorLabor($registros)
    {
        return $registros->groupBy('Tipo_Labor')->map(function ($regs) {
            return $this->calcularResumenLabor($regs);
        })->values();
    }

    private function calcularResumenLabor($regs)
    {
        $sumCantidad = $regs->sum('Cantidad');
        $sumHoras = $regs->sum('Horas_Trabajadas');
        $promedio = $regs->count() ? round($regs->avg('Rendimiento_Hora'), 1) : 0;

        return [
            'Nombre_Colaborador' => $regs->first()->Nombre_Colaborador,
            'Tipo_Labor' => $regs->first()->Tipo_Labor,
            'Total_Cantidad' => round($sumCantidad, 1),
            'Total_Horas' => round($sumHoras, 2),
            'Rendimiento_Promedio' => $promedio,
            'Registros' => $regs->count(),
            'Color' => $this->colorSemaforo($promedio, $regs->first()->Tipo_Labor),
        ];
    }

    /** Devuelve el color del semáforo segun umbrales fijos. */
    private function colorSemaforo(float $rend, string $labor): string
    {
        $u = self::UMBRALES[$labor] ?? ['verde' => 0, 'naranja' => 0];
        if ($rend >= $u['verde']) {
            return '#2e7d32';
        }
        if ($rend >= $u['naranja']) {
            return '#f57c00';
        }
        return '#d32f2f';
    }
}