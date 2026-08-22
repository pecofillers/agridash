<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

class UbicacionesController extends Controller
{
    public function index(Request $request)
    {
        $bloques = Ubicacion::bloques();
        $naves = [];

        // Si se seleccionó un bloque, buscamos sus naves para el filtro
        if ($request->filled('Bloque')) {
            $naves = Ubicacion::naves($request->Bloque);
        }

        // filtros y paginación
        $ubicaciones = Ubicacion::query()
            ->when($request->filled('Bloque'), function ($q) use ($request) {
                return $q->where('Bloque', $request->Bloque);
            })
            ->when($request->filled('Nave'), function ($q) use ($request) {
                return $q->where('Nave', $request->Nave);
            })
            ->orderBy('Bloque')
            ->orderBy('Nave')
            ->orderBy('Cama')
            ->paginate(50) // ¡Paginación! No carga todo de golpe
            ->withQueryString(); // Mantiene los filtros al cambiar de página

        return view('ubicaciones.index', compact('ubicaciones', 'bloques', 'naves'));
    }

    public function crear(Request $request)
    {
        $request->validate([
            'Bloque' => 'required|string|max:15',
            'Nave' => 'required|string|max:15',
            'Cama' => 'required|string|max:15',
            'Metros_Lineales' => 'required|numeric',
            'Cuadros' => 'required|integer|min:0',
        ]);

        $bloqueStr = str_pad($request->Bloque, 2, '0', STR_PAD_LEFT);
        $naveStr   = str_pad($request->Nave, 2, '0', STR_PAD_LEFT);
        $camaStr   = str_pad($request->Cama, 2, '0', STR_PAD_LEFT);

        $idGenerado = (int) ($bloqueStr . $naveStr . $camaStr);

        // Validamos que este ID no exista ya en la base de datos
        if (Ubicacion::where('ID_Ubicacion', $idGenerado)->exists()) {
            return back()->withErrors(['error' => 'Esta ubicación (Bloque/Nave/Cama) ya existe en el sistema.']);
        }

        Ubicacion::create([
            'ID_Ubicacion'    => $idGenerado,
            'Bloque' => $request->Bloque,
            'Nave' => $request->Nave,
            'Cama' => $request->Cama,
            'Metros_Lineales' => $request->Metros_Lineales ?? 0,
            'Cuadros' => $request->Cuadros ?? 0,
            'Estado' => 'ACTIVA',
        ]);

        return back()->with('success', 'Ubicación creada correctamente.');
    }

    public function actualizar(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);

        $request->validate([
            'Bloque' => 'required|string|max:15',
            'Nave' => 'required|string|max:15',
            'Cama' => 'required|string|max:15',
            'Metros_Lineales' => 'required|numeric',
            'Estado' => 'required|string',
            'Cuadros' => 'required|integer|min:0',
        ]);

        $ubicacion->update([
            'Bloque' => $request->Bloque,
            'Nave' => $request->Nave,
            'Cama' => $request->Cama,
            'Metros_Lineales' => $request->Metros_Lineales,
            'Estado' => $request->Estado,
            'Cuadros' => $request->Cuadros,
        ]);

        return back()->with('success', 'Ubicación actualizada correctamente.');
    }
}