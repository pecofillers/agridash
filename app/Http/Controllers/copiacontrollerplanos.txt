<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ubicacion;
use App\Models\Siembra;
use App\Models\Variedad;
use Carbon\Carbon;

class PlanoSiembraController extends Controller
{
    public function index(Request $request)
    {
        $fechaConsulta = $request->input('fecha', date('Y-m-d'));
        $bloqueSel = $request->input('bloque', '1');
        $naveSel = $request->input('nave', ''); 
        $filtroSeccion = $request->input('seccion', 'todas');
        $fechaSiembraFiltro = $request->input('fecha_siembra_filtro', '');
        
        $ordenNaves = $request->input('orden_naves', 'asc');
        $ordenCamas = $request->input('orden_camas', 'asc');

        // 1. Obtener Bloques
        $bloques = Ubicacion::select('Bloque')->distinct()->orderBy('Bloque')->pluck('Bloque');
        
        // 2. Obtener Naves del bloque seleccionado
        $naves = Ubicacion::where('Bloque', $bloqueSel)
            ->select('Nave')->distinct()
            ->orderByRaw('CAST(Nave AS UNSIGNED) asc')
            ->pluck('Nave');

        // 3. Fechas únicas de siembra SOLO del bloque actual y sin fechas erróneas
        $camasDelBloque = Ubicacion::where('Bloque', $bloqueSel)->pluck('ID_Ubicacion');

        $fechasSiembra = Siembra::whereIn('ID_Ubicacion', $camasDelBloque)
            ->whereNotNull('Fecha_Siembra')
            ->where('Fecha_Siembra', '!=', '0000-00-00')
            ->where('Fecha_Siembra', 'not like', '%-0001%')
            ->pluck('Fecha_Siembra')
            ->map(function ($fecha) {
                return \Carbon\Carbon::parse($fecha)->format('Y-m-d'); 
            })
            ->unique()
            ->sortDesc()
            ->values();

        // 4. Construir consulta base de ubicaciones
        $queryUbicaciones = Ubicacion::where('Bloque', $bloqueSel)->where('Estado', 'ACTIVA');
        
        if (!empty($naveSel)) {
            $queryUbicaciones->where('Nave', $naveSel);
        }

        $queryUbicaciones->orderByRaw('CAST(Nave AS UNSIGNED) ' . $ordenNaves)
                         ->orderByRaw('CAST(Cama AS UNSIGNED) ' . $ordenCamas);
        
        $ubicaciones = $queryUbicaciones->get();

        // Filtro de secciones espaciales
        if ($filtroSeccion === 'pares') {
            $ubicaciones = $ubicaciones->filter(fn($u) => (int)$u->Cama % 2 == 0);
        } elseif ($filtroSeccion === 'impares') {
            $ubicaciones = $ubicaciones->filter(fn($u) => (int)$u->Cama % 2 != 0);
        } elseif ($filtroSeccion === 'tercio1') {
            $ubicaciones = $ubicaciones->filter(fn($u) => (int)$u->Cama % 3 == 1);
        } elseif ($filtroSeccion === 'tercio2') {
            $ubicaciones = $ubicaciones->filter(fn($u) => (int)$u->Cama % 3 == 2);
        } elseif ($filtroSeccion === 'tercio3') {
            $ubicaciones = $ubicaciones->filter(fn($u) => (int)$u->Cama % 3 == 0);
        }

        $idsUbicaciones = $ubicaciones->pluck('ID_Ubicacion');
        
        // 5. Consultar siembras activas a la fecha elegida
        $querySiembras = Siembra::with('variedad')
            ->whereIn('ID_Ubicacion', $idsUbicaciones)
            ->where('Fecha_Siembra', '<=', $fechaConsulta)
            ->where(function($query) use ($fechaConsulta) {
                $query->whereNull('Fecha_Erradicacion')
                      ->orWhere('Fecha_Erradicacion', '0000-00-00')
                      ->orWhere('Fecha_Erradicacion', '=', '')
                      ->orWhere('Fecha_Erradicacion', '>', $fechaConsulta);
            });

        $siembras = $querySiembras->get()->keyBy(fn($item) => (string)$item->ID_Ubicacion);

        // 6. Aplicar lógica combinada: Fecha de Siembra o Camas Vacías
        if ($fechaSiembraFiltro === 'vacias') {
            $ubicaciones = $ubicaciones->filter(fn($u) => !isset($siembras[$u->ID_Ubicacion]));
        } elseif (!empty($fechaSiembraFiltro)) {
            $ubicaciones = $ubicaciones->filter(function($u) use ($siembras, $fechaSiembraFiltro) {
                // Si la cama no tiene siembra, la descartamos
                if (!isset($siembras[$u->ID_Ubicacion])) return false;
                
                // Formateamos la fecha de la siembra activa a Y-m-d para comparar manzanas con manzanas
                $fechaCama = \Carbon\Carbon::parse($siembras[$u->ID_Ubicacion]->Fecha_Siembra)->format('Y-m-d');
                
                return $fechaCama === $fechaSiembraFiltro;
            });
        }

        $variedades = Variedad::orderBy('Nombre_Variedad')->get();

        return view('agronomia.planos_siembra', compact(
            'bloques', 'bloqueSel', 'naves', 'naveSel', 'fechasSiembra',
            'fechaConsulta', 'filtroSeccion', 'fechaSiembraFiltro', 'ordenNaves', 'ordenCamas',
            'ubicaciones', 'siembras', 'variedades'
        ));
    }

    public function actualizarMasivo(Request $request)
    {
        $request->validate([
            'ubicaciones_ids' => 'required|array|min:1',
            'ID_Variedad' => 'nullable|exists:dim_variedades,ID_Variedad',
            'Estado_Siembra' => 'nullable|string',
            'Cantidad_Plantas' => 'nullable|numeric|min:0',
            'Fecha_Siembra' => 'nullable|date',
            'Fecha_Pinch' => 'nullable|date',
            'Fecha_Hormona' => 'nullable|date',
            'Fecha_Erradicacion' => 'nullable|date',
        ]);

        $datosActualizar = [];
        
        if ($request->filled('ID_Variedad')) $datosActualizar['ID_Variedad'] = $request->ID_Variedad;
        if ($request->filled('Estado_Siembra')) $datosActualizar['Estado_Siembra'] = $request->Estado_Siembra;
        if ($request->filled('Cantidad_Plantas')) $datosActualizar['Cantidad_Plantas'] = $request->Cantidad_Plantas;
        
        // Formateo explícito de fechas para evitar bloqueos en la base de datos
        if ($request->filled('Fecha_Siembra')) $datosActualizar['Fecha_Siembra'] = \Carbon\Carbon::parse($request->Fecha_Siembra)->format('Y-m-d');
        if ($request->filled('Fecha_Pinch')) $datosActualizar['Fecha_Pinch'] = \Carbon\Carbon::parse($request->Fecha_Pinch)->format('Y-m-d');
        if ($request->filled('Fecha_Hormona')) $datosActualizar['Fecha_Hormona'] = \Carbon\Carbon::parse($request->Fecha_Hormona)->format('Y-m-d');
        
        if ($request->filled('Fecha_Erradicacion')) {
            $datosActualizar['Fecha_Erradicacion'] = \Carbon\Carbon::parse($request->Fecha_Erradicacion)->format('Y-m-d');
            $datosActualizar['Estado_Siembra'] = 'ERRADICADA';
        }

        if (empty($datosActualizar)) {
            return back()->withErrors('Debes escribir al menos un dato (ej: Fecha Pinch) para actualizar.');
        }

        $camasAfectadas = 0;

        foreach ($request->ubicaciones_ids as $idUbi) {
            
            // Búsqueda blindada: Ordenamos por ID_Siembra para agarrar siempre el cultivo más reciente de esa cama
            $siembra = Siembra::where('ID_Ubicacion', $idUbi)
                ->where(function($query) {
                    $query->whereNull('Fecha_Erradicacion')
                          ->orWhere('Fecha_Erradicacion', '0000-00-00')
                          ->orWhere('Fecha_Erradicacion', '0000-00-00 00:00:00')
                          ->orWhere('Fecha_Erradicacion', '=', '');
                })
                ->orderBy('ID_Siembra', 'desc')
                ->first();
            
            if ($siembra) {
                // Usamos fill y save para que Laravel respete estrictamente los casts de tu modelo
                $siembra->fill($datosActualizar)->save();
                $camasAfectadas++;
            } else {
                // Solo crea uno nuevo si la cama está vacía y mandaron Variedad + Fecha Siembra
                if (isset($datosActualizar['Fecha_Siembra']) && isset($datosActualizar['ID_Variedad'])) {
                    $datosActualizar['ID_Ubicacion'] = $idUbi;
                    Siembra::create($datosActualizar);
                    $camasAfectadas++;
                }
            }
        }

        if ($camasAfectadas === 0) {
            return back()->withErrors('Ninguna cama fue actualizada. Asegúrate de que las camas seleccionadas ya tengan una siembra activa o envía Variedad y Fecha de Siembra para sembrarlas.');
        }

        return back()->with('success', $camasAfectadas . ' camas fueron actualizadas correctamente.');
    }

    public function actualizarSiembra(Request $request, $idUbicacion)
    {
        $request->validate([
            'ID_Variedad' => 'required|exists:dim_variedades,ID_Variedad',
            'Cantidad_Plantas' => 'required|numeric|min:0',
            'Estado_Siembra' => 'required|string',
            'Fecha_Siembra' => 'required|date',
            'Fecha_Pinch' => 'nullable|date',
            'Fecha_Hormona' => 'nullable|date',
            'Fecha_Erradicacion' => 'nullable|date',
        ]);

        $siembra = Siembra::where('ID_Ubicacion', $idUbicacion)
            ->whereNull('Fecha_Erradicacion')
            ->first();

        $datos = $request->all();
        // VALIDACIÓN Y AUTOMATIZACIÓN DE LA FECHA Y ESTADO DE ERRADICACIÓN
        if (!empty($datos['Fecha_Erradicacion'])) {
            // Si el usuario puso una fecha de erradicación, forzamos el estado a ERRADICADA
            $datos['Estado_Siembra'] = 'ERRADICADA';
        } else {
            // Si está vacía, aseguramos que se guarde como NULL para que la cama quede activa/disponible
            $datos['Fecha_Erradicacion'] = null;
        }

        if ($siembra) {
            $siembra->update($datos);
        } else {
            $datos['ID_Ubicacion'] = $idUbicacion;
            Siembra::create($datos);
        }

        return back()->with('success', 'Registro de siembra actualizado correctamente.');
    }
}