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
        $filtroSeccion = $request->input('seccion', 'todas');
        
        // 🔄 Nuevos parámetros de ordenamiento
        $ordenNaves = $request->input('orden_naves', 'asc');
        $ordenCamas = $request->input('orden_camas', 'asc');

        $bloques = Ubicacion::select('Bloque')->distinct()->orderBy('Bloque')->pluck('Bloque');

        // 1. Traer ubicaciones aplicando el orden dinámico seleccionado por el usuario
        $ubicaciones = Ubicacion::where('Bloque', $bloqueSel)
            ->where('Estado', 'ACTIVA')
            ->orderByRaw('CAST(Nave AS UNSIGNED) ' . $ordenNaves)
            ->orderByRaw('CAST(Cama AS UNSIGNED) ' . $ordenCamas)
            ->get();

        // Aplicar filtros espaciales (pares, impares, tercios)
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

        // Si el usuario ordenó de forma descendente en colecciones filtradas, ajustamos la colección
        if ($ordenCamas === 'desc') {
            $ubicaciones = $ubicaciones->sortByDesc(fn($u) => (int)$u->Cama);
        }

        $idsUbicaciones = $ubicaciones->pluck('ID_Ubicacion');
        
        $siembras = Siembra::with('variedad')
            ->whereIn('ID_Ubicacion', $idsUbicaciones)
            ->where('Fecha_Siembra', '<=', $fechaConsulta)
            ->where(function($query) use ($fechaConsulta) {
                $query->whereNull('Fecha_Erradicacion')
                      ->orWhere('Fecha_Erradicacion', '0000-00-00')
                      ->orWhere('Fecha_Erradicacion', '=', '')
                      ->orWhere('Fecha_Erradicacion', '>', $fechaConsulta);
            })
            ->get()
            ->keyBy(fn($item) => (string)$item->ID_Ubicacion);

        $variedades = Variedad::orderBy('Nombre_Variedad')->get();

        return view('agronomia.planos_siembra', compact(
            'bloques', 'bloqueSel', 'fechaConsulta', 'filtroSeccion',
            'ordenNaves', 'ordenCamas',
            'ubicaciones', 'siembras', 'variedades'
        ));
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