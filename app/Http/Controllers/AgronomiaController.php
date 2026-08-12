<?php

namespace App\Http\Controllers;

use App\Models\Siembra;
use App\Models\Ubicacion;
use App\Models\Variedad;
use Illuminate\Http\Request;

class AgronomiaController extends Controller
{
    public function index()
    {
        $ubicaciones = Ubicacion::orderBy('Bloque')->orderBy('Nave')->orderBy('Cama')->get();
        $variedades = Variedad::orderBy('Nombre_Variedad')->get();
        $historial = collect();

        $idUbicacion = request('ID_Ubicacion');

        if ($idUbicacion) {
            $historial = Siembra::where('ID_Ubicacion', $idUbicacion)
                ->with('variedad', 'ubicacion')
                ->orderBy('Fecha_Siembra')
                ->get();
        }

        return view('agronomia.index', compact('ubicaciones', 'variedades', 'historial', 'idUbicacion'));
    }

    public function registrarSiembra(Request $request)
    {
        $request->validate([
            'ID_Ubicacion' => 'required|integer|exists:dim_ubicaciones,ID_Ubicacion',
            'ID_Variedad' => 'required|integer',
            'Fecha_Siembra' => 'required|date',
            'Cantidad_Plantas' => 'required|integer|min:1',
            'Metros_Lineales' => 'required|numeric|min:0.1',
        ]);

        // Cerrar siembra activa de esta ubicación
        Siembra::where('ID_Ubicacion', $request->ID_Ubicacion)
            ->whereNull('Fecha_Fin')
            ->update(['Fecha_Fin' => now()]);

        $densidad = $request->Cantidad_Plantas / $request->Metros_Lineales;

        Siembra::create([
            'ID_Ubicacion' => $request->ID_Ubicacion,
            'ID_Variedad' => $request->ID_Variedad,
            'Fecha_Siembra' => $request->Fecha_Siembra,
            'Cantidad_Plantas' => $request->Cantidad_Plantas,
            'Metros_Lineales' => $request->Metros_Lineales,
            'Densidad_Plantacion' => round($densidad, 2),
        ]);

        return back()->with('success', 'Siembra registrada correctamente.');
    }

    public function crearVariedad(Request $request)
    {
        $request->validate([
            'Nombre_Variedad' => 'required|string|max:100',
            'Color' => 'nullable|string|max:50',
            'Ciclo_Dias' => 'nullable|integer|min:1',
        ]);

        Variedad::create($request->only(['Nombre_Variedad', 'Color', 'Ciclo_Dias']));

        return back()->with('success', 'Variedad registrada.');
    }
}
