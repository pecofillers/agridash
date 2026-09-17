<?php

namespace App\Http\Controllers;

use App\Models\Bloque;
use App\Models\Siembra;
use App\Models\Produccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HistoricoSiembrasController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['siembra' => 'nullable|json']);
        $bloques = Bloque::where('Estado', 'ACTIVO')
            ->orderBy('Codigo_Bloque')
            ->get();

        $reporte = null;

        if ($request->siembra) {
            $ids = json_decode($request->siembra, true);
            Validator::make(['ids' => $ids], [
                'ids' => 'required|array|min:1|max:5000',
                'ids.*' => 'required|integer|distinct',
            ])->validate();

            if (is_array($ids) && !empty($ids)) {
                $reporte = $this->generarReporte($ids);
            }
        }

        return view(
            'agronomia.historico_siembras',
            compact('bloques', 'reporte')
        );
    }

    public function siembrasBloque($bloque)
    {
        $bloque = Bloque::where(
            'Codigo_Bloque',
            $bloque
        )->firstOrFail();

        return Siembra::with([
            'ubicacion',
            'variedad'
        ])
            ->whereHas('ubicacion', function ($q) use ($bloque) {
                $q->where(
                    'ID_Bloque',
                    $bloque->ID_Bloque
                );
            })
            ->whereNotNull('Fecha_Siembra')
            ->get()
            ->groupBy(function ($s) {
                return implode('|', [
                    $s->Fecha_Siembra->format('Y-m-d'),
                    $s->ID_Variedad,
                    $s->ID_Ciclo_Siembra
                ]);
            })
            ->map(function ($grupo) {
                $primera = $grupo->first();
                $variedad = $primera->variedad;

                $nombreVariedad = $variedad
                    ? $variedad->Nombre_Variedad .
                        (!empty($variedad->Color)
                            ? ' - ' . $variedad->Color
                            : '')
                    : 'N/D';

                return [
                    'ids' => $grupo
                        ->pluck('ID_Siembra')
                        ->unique()
                        ->values(),

                    'fecha' => $primera->Fecha_Siembra,

                    'texto' =>
                        $primera->Fecha_Siembra->format('d/m/Y')
                        . ' | '
                        . $nombreVariedad
                        . ' | Camas '
                        . $grupo->count()
                        . ' | Plantas '
                        . number_format(
                            $grupo->sum('Cantidad_Plantas'),
                            0,
                            ',',
                            '.'
                        )
                ];
            })
            ->sortByDesc('fecha')
            ->values();
    }

    private function generarReporte($ids)
    {
        $siembras = Siembra::with([
            'ubicacion',
            'ubicacion.bloque',
            'variedad',
            'ciclo'
        ])
            ->whereIn('ID_Siembra', $ids)
            ->get()
            ->sortBy([
                ['ubicacion.Nave', 'asc'],
                ['ubicacion.Cama', 'asc'],
            ])
            ->values();

        if ($siembras->isEmpty()) {
            return null;
        }

        $grupos = $siembras->map(fn ($s) => implode('|', [
            $s->ubicacion?->ID_Bloque, $s->Fecha_Siembra?->format('Y-m-d'),
            $s->ID_Variedad, $s->ID_Ciclo_Siembra,
        ]))->unique();
        if ($siembras->count() !== count($ids) || $grupos->count() !== 1) {
            throw ValidationException::withMessages(['siembra' => 'Selecciona las camas de una sola siembra.']);
        }

        $producciones = Produccion::whereIn(
            'ID_Siembra',
            $ids
        )
            ->orderBy('Anio')
            ->orderBy('Semana')
            ->get([
                'ID_Produccion',
                'ID_Ubicacion',
                'ID_Siembra',
                'Anio',
                'Semana',
                'Bajas',
                'Total',
            ])->groupBy('ID_Siembra');

        $filas = [];
        $produccionSemanal = [];

        foreach ($siembras as $siembra) {
            $fila = [
                'siembra' => $siembra,
                'semanas' => [],
                'total' => 0
            ];

            if (!$siembra->Fecha_Siembra) {
                $filas[] = $fila;
                continue;
            }

            $inicio = $this->numeroSemana(
                $siembra->Fecha_Siembra->isoWeekYear,
                $siembra->Fecha_Siembra->isoWeek
            );

            $fin = null;

            if ($siembra->Fecha_Erradicacion) {
                $fin = $this->numeroSemana(
                    $siembra->Fecha_Erradicacion->isoWeekYear,
                    $siembra->Fecha_Erradicacion->isoWeek
                );
            }

            $prod = $producciones
                ->get($siembra->ID_Siembra, collect())
                ->filter(function ($p) use ($inicio, $fin) {
                    $actual = $this->numeroSemana(
                        (int) $p->Anio,
                        (int) $p->Semana
                    );

                    if ($actual < $inicio) {
                        return false;
                    }

                    if ($fin !== null && $actual > $fin) {
                        return false;
                    }

                    return true;
                });

            foreach ($prod as $p) {
                $clave = $this->claveSemana(
                    $p->Anio,
                    $p->Semana
                );

                $total = (int) $p->Total;

                $fila['semanas'][$clave] =
                    ($fila['semanas'][$clave] ?? 0)
                    + $total;

                $fila['total'] += $total;

                $produccionSemanal[$clave] =
                    ($produccionSemanal[$clave] ?? 0)
                    + $total;
            }

            $filas[] = $fila;
        }

        ksort($produccionSemanal);

        $semanas = array_keys($produccionSemanal);

        foreach ($filas as &$fila) {
            $semanasFila = [];

            foreach ($semanas as $semana) {
                $semanasFila[$semana] =
                    $fila['semanas'][$semana] ?? 0;
            }

            $fila['semanas'] = $semanasFila;

            $fila['total'] = array_sum(
                $semanasFila
            );
        }

        unset($fila);

        $totalSemanas = [];

        foreach ($semanas as $semana) {
            $totalSemanas[$semana] =
                $produccionSemanal[$semana] ?? 0;
        }

        $totalPlantas = $siembras->sum(
            'Cantidad_Plantas'
        );

        $produccionPlanta = [];

        foreach ($totalSemanas as $semana => $total) {
            $produccionPlanta[$semana] =
                $totalPlantas > 0
                    ? $total / $totalPlantas
                    : 0;
        }

        $primera = $siembras->first();

        $resumen = [
            'fecha' => $primera->Fecha_Siembra
                ? $primera->Fecha_Siembra->format('d/m/Y')
                : '-',

            'variedad' => $primera->variedad
                ? $primera->variedad->Nombre_Variedad .
                    (!empty($primera->variedad->Color)
                        ? ' - ' . $primera->variedad->Color
                        : '')
                : 'N/D',

            'camas' => $siembras->pluck('ID_Ubicacion')->unique()->count(),

            'plantas' => $totalPlantas,

            'produccionPlanta' => $produccionPlanta
        ];

        return [
            'filas' => $filas,
            'semanas' => $semanas,
            'totalSemanas' => $totalSemanas,
            'resumen' => $resumen
        ];
    }

    private function claveSemana(
        int $anio,
        int $semana
    ): string {
        return sprintf(
            '%04d-%02d',
            $anio,
            $semana
        );
    }

    private function numeroSemana(
        int $anio,
        int $semana
    ): int {
        return ($anio * 100) + $semana;
    }
}