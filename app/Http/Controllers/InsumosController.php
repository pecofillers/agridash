<?php

namespace App\Http\Controllers;

use App\Models\InsumoDiario;
use Illuminate\Http\Request;

class InsumosController extends Controller
{
    public function index(Request $request)
    {
        $query = InsumoDiario::orderBy('fecha', 'desc');

        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->hasta);
        }

        $registros = $query->paginate(15)->withQueryString();

        $queryGrafica = InsumoDiario::orderBy('fecha', 'asc');
        if ($request->filled('desde')) {
            $queryGrafica->whereDate('fecha', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $queryGrafica->whereDate('fecha', '<=', $request->hasta);
        }
        $registrosGrafica = $request->filled('desde') || $request->filled('hasta')
            ? $queryGrafica->get()
            : $queryGrafica->orderBy('fecha', 'desc')->take(60)->get()->sortBy('fecha')->values();

        return view('agronomia.insumos', compact('registros', 'registrosGrafica'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|unique:insumos_diarios,fecha',
            'amortiguador_medida_visual' => 'nullable|numeric|min:0',
            'amortiguador_medida_registro' => 'nullable|numeric|min:0',
            'agrofeed_medida_visual' => 'nullable|numeric|min:0',
            'agrofeed_medida_registro' => 'nullable|numeric|min:0',
            'agua_lectura' => 'nullable|numeric|min:0',
        ]);

        InsumoDiario::create([
            'fecha' => $request->fecha,
            'amortiguador_medida_visual' => $request->amortiguador_medida_visual,
            'amortiguador_medida_registro' => $request->amortiguador_medida_registro,
            'agrofeed_medida_visual' => $request->agrofeed_medida_visual,
            'agrofeed_medida_registro' => $request->agrofeed_medida_registro,
            'agua_lectura' => $request->agua_lectura,
        ]);

        $this->recalcularDiferencias($request->fecha);
        $this->recalcularDiferenciasSiguiente($request->fecha);

        return back()->with('success', 'Registro del ' . $request->fecha . ' guardado correctamente.');
    }

    public function edit(InsumoDiario $insumo)
    {
        return view('agronomia.insumos_editar', compact('insumo'));
    }

    public function update(Request $request, InsumoDiario $insumo)
    {
        $request->validate([
            'amortiguador_medida_visual' => 'nullable|numeric|min:0',
            'amortiguador_medida_registro' => 'nullable|numeric|min:0',
            'agrofeed_medida_visual' => 'nullable|numeric|min:0',
            'agrofeed_medida_registro' => 'nullable|numeric|min:0',
            'agua_lectura' => 'nullable|numeric|min:0',
        ]);

        $insumo->update([
            'amortiguador_medida_visual' => $request->amortiguador_medida_visual,
            'amortiguador_medida_registro' => $request->amortiguador_medida_registro,
            'agrofeed_medida_visual' => $request->agrofeed_medida_visual,
            'agrofeed_medida_registro' => $request->agrofeed_medida_registro,
            'agua_lectura' => $request->agua_lectura,
        ]);

        $this->recalcularDiferencias($insumo->fecha);
        $this->recalcularDiferenciasSiguiente($insumo->fecha);

        return redirect()->route('agronomia.insumos')->with('success', 'Registro del ' . $insumo->fecha->format('d/m/Y') . ' actualizado correctamente.');
    }

    /**
     * Recalcula amortiguador/agrofeed/agua (hoy - día anterior) del registro en $fecha.
     */
    private function recalcularDiferencias($fecha)
    {
        $actual = InsumoDiario::where('fecha', $fecha)->first();
        if (!$actual) return;

        $anterior = InsumoDiario::where('fecha', '<', $fecha)->orderBy('fecha', 'desc')->first();
        if (!$anterior) {
            $actual->update([
                'amortiguador_diferencia_visual' => null,
                'amortiguador_diferencia_registro' => null,
                'agrofeed_diferencia_visual' => null,
                'agrofeed_diferencia_registro' => null,
                'agua_diferencia_registro' => null,
            ]);
            return;
        }

        // Visual: nivel que baja al consumirse -> "lo que estaba" menos "lo que hay"
        $actual->amortiguador_diferencia_visual = ($actual->amortiguador_medida_visual !== null && $anterior->amortiguador_medida_visual !== null)
            ? $anterior->amortiguador_medida_visual - $actual->amortiguador_medida_visual : null;

        $actual->agrofeed_diferencia_visual = ($actual->agrofeed_medida_visual !== null && $anterior->agrofeed_medida_visual !== null)
            ? $anterior->agrofeed_medida_visual - $actual->agrofeed_medida_visual : null;

        // Registro: contador acumulado que sube -> "hoy" menos "ayer"
        $actual->amortiguador_diferencia_registro = ($actual->amortiguador_medida_registro !== null && $anterior->amortiguador_medida_registro !== null)
            ? $actual->amortiguador_medida_registro - $anterior->amortiguador_medida_registro : null;

        $actual->agrofeed_diferencia_registro = ($actual->agrofeed_medida_registro !== null && $anterior->agrofeed_medida_registro !== null)
            ? $actual->agrofeed_medida_registro - $anterior->agrofeed_medida_registro : null;

        $actual->agua_diferencia_registro = ($actual->agua_lectura !== null && $anterior->agua_lectura !== null)
            ? $actual->agua_lectura - $anterior->agua_lectura : null;

        $actual->save();
    }

    /**
     * Recalcula el registro INMEDIATO SIGUIENTE a $fecha, porque depende de este.
     */
    private function recalcularDiferenciasSiguiente($fecha)
    {
        $siguiente = InsumoDiario::where('fecha', '>', $fecha)->orderBy('fecha', 'asc')->first();
        if ($siguiente) {
            $this->recalcularDiferencias($siguiente->fecha);
        }
    }
}