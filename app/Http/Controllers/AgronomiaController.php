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
        $bloques = Ubicacion::bloques();
        $variedades = Variedad::orderBy('Nombre_Variedad')->get();
        $historial = collect();

        $bloque = request('bloque');
        $nave = request('nave');
        $cama = request('cama');

        if ($bloque && $nave && $cama) {
            $historial = Siembra::where('Bloque', $bloque)
                ->where('Nave', $nave)
                ->where('Cama', $cama)
                ->with('variedad')
                ->orderBy('Fecha_Siembra')
                ->get();
        }

        return view('agronomia.index', compact('bloques', 'variedades', 'historial', 'bloque', 'nave', 'cama'));
    }

    public function registrarSiembra(Request $request)
    {
        $request->validate([
            'Bloque' => 'required|string',
            'Nave' => 'required|string',
            'Cama' => 'required|string',
            'ID_Variedad' => 'required|integer',
            'Fecha_Siembra' => 'required|date',
            'Cantidad_Plantas' => 'required|integer|min:1',
            'Metros_Lineales' => 'required|numeric|min:0.1',
        ]);

        // Cerrar siembra activa de esta cama
        Siembra::where('Bloque', $request->Bloque)
            ->where('Nave', $request->Nave)
            ->where('Cama', $request->Cama)
            ->whereNull('Fecha_Fin')
            ->update(['Fecha_Fin' => now()]);

        $densidad = $request->Cantidad_Plantas / $request->Metros_Lineales;

        Siembra::create([
            'Bloque' => $request->Bloque,
            'Nave' => $request->Nave,
            'Cama' => $request->Cama,
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
