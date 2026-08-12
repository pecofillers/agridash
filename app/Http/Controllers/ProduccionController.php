<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Ubicacion;
use Illuminate\Http\Request;

class ProduccionController extends Controller
{
public function index()
    {
        $ubicaciones = Ubicacion::orderBy('Bloque')->orderBy('Nave')->orderBy('Cama')->get();
        $registros = collect();

        $idUbicacion = request('ID_Ubicacion');
        $semana = request('semana');
        $anio = request('anio');

        if ($idUbicacion) {
            $query = Produccion::where('ID_Ubicacion', $idUbicacion);
            if ($semana) {
                $query->where('Semana', $semana);
            }
            if ($anio) {
                $query->where('Anio', $anio);
            }
            $registros = $query->with('ubicacion')->orderBy('Semana')->get();
        }

        // Obtenemos las labores dinámicas
        $labores = \App\Models\Labor::orderBy('Nombre_Labor')->get();

        return view('rendimiento.index', compact('submodulos', 'colaboradores', 'grupos', 'supervisores', 'rolNombre', 'rendimientos', 'labores'));
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'ID_Ubicacion' => 'required|integer|exists:dim_ubicaciones,ID_Ubicacion',
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

        Produccion::create(array_merge($request->only(['ID_Ubicacion', 'Semana', 'Anio']), $valores, ['Total' => $total]));

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
