<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionesController extends Controller
{
    public function index()
    {
        $ubicaciones = Ubicacion::orderBy('Bloque')->orderBy('Nave')->orderBy('Cama')->get();
        $bloques = Ubicacion::bloques();
        return view('ubicaciones.index', compact('ubicaciones', 'bloques'));
    }

    public function crear(Request $request)
    {
        $request->validate([
            'Bloque' => 'required|string|max:50',
            'Nave' => 'required|string|max:50',
            'Cama' => 'required|string|max:50',
        ]);

        Ubicacion::create([
            'Bloque' => $request->Bloque,
            'Nave' => $request->Nave,
            'Cama' => $request->Cama,
            'Estado' => 'ACTIVO',
        ]);

        return back()->with('success', 'Ubicacion creada correctamente.');
    }
}
