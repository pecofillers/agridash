<?php

namespace App\Http\Controllers;

use App\Models\Bloque;
use App\Models\CicloSiembra;
use App\Models\Produccion;
use App\Models\Variedad;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ComparadorSiembrasController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('bloques') && !is_array($request->bloques)) {
            $request->merge(['bloques' => [$request->bloques]]);
        }

        if ($request->filled('variedades') && !is_array($request->variedades)) {
            $request->merge(['variedades' => [$request->variedades]]);
        }

        $request->validate([
            'bloques' => 'nullable|array|max:50',
            'bloques.*' => 'required|integer|distinct|exists:dim_bloques,ID_Bloque',
            'variedades' => 'nullable|array|max:50',
            'variedades.*' => 'required|integer|distinct|exists:dim_variedades,ID_Variedad',
            'texto' => 'nullable|string|max:150',
            'ciclos' => 'nullable|array|max:50',
            'ciclos.*' => 'required|integer|distinct|exists:dim_ciclos_siembras,ID_Ciclo_Siembra',
        ]);
        $bloques = Bloque::orderBy('Codigo_Bloque')->get();
        $variedades = Variedad::orderBy('Nombre_Variedad')->get();
        $ciclos = collect();
        if ($request->boolean('buscar')) {
            $query = CicloSiembra::with(['bloque', 'variedad', 'siembras'])->whereHas('siembras');
            $query->when($request->filled('bloques'), fn ($q) => $q->whereIn('ID_Bloque', $request->bloques))
                ->when($request->filled('variedades'), fn ($q) => $q->whereIn('ID_Variedad', $request->variedades));
            if ($request->filled('texto')) {
                $texto = '%'.$request->texto.'%';
                $query->where(function ($q) use ($texto) {
                    $q->whereHas('bloque', fn ($b) => $b->where('Codigo_Bloque', 'like', $texto)->orWhere('Nombre_Bloque', 'like', $texto))
                        ->orWhereHas('variedad', fn ($v) => $v->where('Nombre_Variedad', 'like', $texto)->orWhere('Color', 'like', $texto));
                });
            }
            $ciclos = $query->orderByDesc('Fecha_Siembra')->get();
            foreach ($ciclos as $ciclo) {
                $ciclo->Codigo_Bloque = $ciclo->bloque?->Codigo_Bloque;
                $ciclo->nombre_variedad = $this->nombreVariedad($ciclo);
                $ciclo->cantidad_camas = $ciclo->siembras->pluck('ID_Ubicacion')->unique()->count();
                $ciclo->cantidad_plantas = $ciclo->siembras->sum('Cantidad_Plantas');
                $ciclo->fecha_pinch = $ciclo->siembras->min('Fecha_Pinch');
                $ciclo->fecha_hormona = $ciclo->siembras->min('Fecha_Hormona');
                $ciclo->fecha_erradicacion = $ciclo->siembras->contains(fn ($s) => !$s->Fecha_Erradicacion)
                    ? null : $ciclo->siembras->max('Fecha_Erradicacion');
            }
        }
        $reporte = null;
        if ($request->filled('ciclos')) {
            $seleccion = CicloSiembra::with(['bloque', 'variedad', 'siembras'])
                ->whereIn('ID_Ciclo_Siembra', $request->ciclos)->orderBy('Fecha_Siembra')->get();
            $reporte = $this->generarReporte($seleccion);
        }
        return view('agronomia.comparador_siembras', compact('bloques', 'variedades', 'ciclos', 'reporte'));
    }

    private function nombreVariedad(CicloSiembra $ciclo): string
    {
        return $ciclo->variedad
            ? implode(' - ', array_filter([$ciclo->variedad->Nombre_Variedad, $ciclo->variedad->Color]))
            : 'N/D';
    }

    private function generarReporte($ciclos): array
    {
        $ids = $ciclos->flatMap(fn ($c) => $c->siembras->pluck('ID_Siembra'));
        $producciones = Produccion::whereIn('ID_Siembra', $ids)->get()->groupBy('ID_Siembra');
        $filas = [];
        $maxSemana = 0;
        foreach ($ciclos as $ciclo) {
            if (!$ciclo->Fecha_Siembra || $ciclo->siembras->isEmpty()) {
                throw ValidationException::withMessages(['ciclos' => 'Cada ciclo debe tener fecha de siembra y camas asociadas.']);
            }
            // El origen contiene semanas ISO completas, no producción diaria.
            $inicio = CarbonImmutable::instance($ciclo->Fecha_Siembra)->startOfWeek();
            $plantas = $ciclo->siembras->sum('Cantidad_Plantas');
            $semanas = [];
            foreach ($ciclo->siembras as $siembra) {
                foreach ($producciones->get($siembra->ID_Siembra, collect()) as $p) {
                    $semanasAnio = CarbonImmutable::create($p->Anio, 12, 28)->isoWeeksInYear();
                    if ($p->Semana < 1 || $p->Semana > $semanasAnio || !$p->Anio) {
                        continue;
                    }
                    $fecha = CarbonImmutable::create((int)$p->Anio, 1, 1)
                        ->setISODate((int)$p->Anio, (int)$p->Semana, 1)
                        ->startOfDay();
                    if ($fecha->isoWeekYear !== (int) $p->Anio || $fecha->isoWeek !== (int) $p->Semana
                        || $fecha->lt($inicio) || $fecha->gt(CarbonImmutable::now()->startOfWeek())
                        || ($siembra->Fecha_Siembra && $fecha->lt(CarbonImmutable::instance($siembra->Fecha_Siembra)->startOfWeek()))
                        || ($siembra->Fecha_Erradicacion && $fecha->gt(CarbonImmutable::instance($siembra->Fecha_Erradicacion)->startOfWeek()))) {
                        continue;
                    }
                    $numero = (int) $inicio->diffInWeeks($fecha) + 1;
                    $semanas[$numero] ??= ['produccion' => 0, 'plantas' => $plantas, 'indice' => null, 'calendario' => $fecha->format('o-W')];
                    $semanas[$numero]['produccion'] += (int) $p->Total;
                    $maxSemana = max($maxSemana, $numero);
                }
            }
            foreach ($semanas as &$semana) {
                $semana['indice'] = $plantas > 0 ? $semana['produccion'] / $plantas : null;
            }
            unset($semana);
            $filas[] = [
                'bloque' => $ciclo->bloque?->Codigo_Bloque ?? 'N/D',
                'variedad' => $this->nombreVariedad($ciclo),
                'fecha' => $ciclo->Fecha_Siembra,
                'pinch' => $ciclo->siembras->min('Fecha_Pinch'),
                'hormona' => $ciclo->siembras->min('Fecha_Hormona'),
                'erradicacion' => $ciclo->siembras->contains(fn ($s) => !$s->Fecha_Erradicacion) ? null : $ciclo->siembras->max('Fecha_Erradicacion'),
                'camas' => $ciclo->siembras->pluck('ID_Ubicacion')->unique()->count(),
                'plantas' => $plantas,
                'semanas' => $semanas,
            ];
        }
        $eje = $maxSemana > 0 ? range(1, $maxSemana) : [];
        $grafica = collect($filas)->map(fn ($fila) => [
            'label' => 'Bloque '.$fila['bloque'].' · '.$fila['variedad'].' · '.$fila['fecha']->format('d/m/Y'),
            'data' => array_map(fn ($n) => $fila['semanas'][$n]['indice'] ?? null, $eje),
        ])->values()->all();
        return ['ciclos' => $filas, 'semanas' => $eje, 'grafica' => $grafica];
    }
}
