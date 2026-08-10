<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Ubicacion;
use Illuminate\Http\Request;

class ProduccionController extends Controller
{
public function index()
    {
        $bloques = Ubicacion::bloques();
        $naves = collect();
        $camas = collect();
        $registros = collect();

        $bloque = request('bloque');
        $nave = request('nave');

        if ($bloque) {
            $naves = Ubicacion::naves($bloque);
        }
        if ($bloque && $nave) {
            $camas = Ubicacion::camas($bloque, $nave);
            $registros = Produccion::where('Bloque', $bloque)->where('Nave', $nave)->orderBy('Semana')->get();
        }

        return view('produccion.index', compact('bloques', 'naves', 'camas', 'registros', 'bloque', 'nave'));
    }

    /** Metodo auxiliar para que las vistas iteren pluck como valores simples. */
    public function bloques()
    {
        return Ubicacion::bloques();
    }

    public function naves($bloque)
    {
        return Ubicacion::naves($bloque);
    }

    public function camas($bloque, $nave)
    {
        return Ubicacion::camas($bloque, $nave);
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'Bloque' => 'required|string',
            'Nave' => 'required|string',
            'Cama' => 'required|string',
            'Semana' => 'required|integer|min:1|max:53',
            'Anio' => 'required|integer',
        ]);

        $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo', 'Bajas'];
        $valores = [];
        $total = 0;
        foreach ($dias as $d) {
            $v = (int) $request->input($d, 0);
            $valores[$d] = $v;
            $total += $v;
        }

        Produccion::create(array_merge($request->only(['Bloque', 'Nave', 'Cama', 'Semana', 'Anio']), $valores, ['Total' => $total]));

        return back()->with('success', 'Registro guardado exitosamente.');
    }

    public function actualizar(Request $request)
    {
        $request->validate(['ID_Produccion' => 'required|integer']);

        $prod = Produccion::findOrFail($request->ID_Produccion);
        $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo', 'Bajas'];
        $total = 0;
        $data = [];
        foreach ($dias as $d) {
            $v = (int) $request->input($d, 0);
            $data[$d] = $v;
            $total += $v;
        }
        $data['Total'] = $total;
        $prod->update($data);

        return back()->with('success', 'Registro actualizado.');
    }
}
