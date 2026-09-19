<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Bloque;
use App\Models\Variedad;
use App\Models\Siembra;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProduccionReporteController extends Controller
{
    public function index(Request $request)
    {
        $años = Produccion::select('Anio')
            ->distinct()
            ->orderByDesc('Anio')
            ->pluck('Anio');

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        $variedades = Variedad::orderBy('Nombre_Variedad')
            ->get();

        $bloques = Bloque::orderBy('Codigo_Bloque')
            ->get();

        $datos = collect();

        if ($request->filled(['anio', 'mes'])) {

            $datos = $this->generarReporte(
                $request->anio,
                $request->mes,
                $request->bloque,
                $request->variedad
            );
        }

        return view(
            'agronomia.reporte_produccion',
            compact(
                'años',
                'meses',
                'variedades',
                'bloques',
                'datos'
            )
        );
    }

    private function generarReporte(
        $anio,
        $mes,
        $idBloque = null,
        $idVariedad = null
    ) {

        $inicioMes = Carbon::create($anio, $mes, 1)
            ->startOfMonth();

        $finMes = Carbon::create($anio, $mes, 1)
            ->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | 1. Obtener SIEMBRAS ACTIVAS DEL MES
        |--------------------------------------------------------------------------
        */

        $siembras = Siembra::with([
            'ubicacion.bloque',
            'ciclo.variedad'
        ])

            ->whereDate(
                'Fecha_Siembra',
                '<=',
                $finMes
            )

            ->where(function ($q) use ($inicioMes) {

                $q->whereNull('Fecha_Erradicacion')
                    ->orWhereDate(
                        'Fecha_Erradicacion',
                        '>=',
                        $inicioMes
                    );
            })

            ->whereHas(
                'ubicacion',
                function ($q) use ($idBloque) {

                    if ($idBloque) {

                        $q->where(
                            'ID_Bloque',
                            $idBloque
                        );
                    }
                }
            )

            ->whereHas(
                'ciclo',
                function ($q) use ($idVariedad) {

                    if ($idVariedad) {

                        $q->where(
                            'ID_Variedad',
                            $idVariedad
                        );
                    }
                }
            )

            ->get();

        /*
|--------------------------------------------------------------------------
| 2. Buscar producción asociada a las camas
|--------------------------------------------------------------------------
*/

        $produccion = Produccion::whereIn(
            'ID_Siembra',
            $siembras->pluck('ID_Siembra')->unique()
        )
            ->where('Anio', $anio)
            ->whereIn(
                'Semana',
                $this->semanasDelMes($anio, $mes)
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Índice por siembra
        |--------------------------------------------------------------------------
        */

        $produccionSiembra = $produccion
            ->groupBy('ID_Siembra');

        /*
        |--------------------------------------------------------------------------
        | 3. Agrupar resultado por bloque
        |--------------------------------------------------------------------------
        */

        return $siembras
            ->groupBy(fn($s) => $s->ubicacion->ID_Bloque)
            ->map(function ($items, $idBloque) use ($produccion, $produccionSiembra, $anio, $mes) {

                $primera = $items->first();


                /*
        |--------------------------------------------------------------------------
        | PRODUCCIÓN DEL BLOQUE
        |--------------------------------------------------------------------------
        */

                $produccionBloque = collect();


                foreach ($items as $siembra) {

                    $prod = $produccionSiembra
                        ->get($siembra->ID_Siembra, collect());

                    // Filtrar producción que esté dentro del periodo de la siembra
                    $prod = $prod->filter(function ($p) use ($siembra) {
                        $fecha = Carbon::create($p->Anio, 1, 1)
                            ->setISODate($p->Anio, $p->Semana, 1)
                            ->startOfDay();

                        // No producción antes de la siembra
                        if ($siembra->Fecha_Siembra && $fecha->lt($siembra->Fecha_Siembra)) {
                            return false;
                        }

                        // No producción después de erradicación
                        if ($siembra->Fecha_Erradicacion && $fecha->gt($siembra->Fecha_Erradicacion)) {
                            return false;
                        }

                        return true;
                    });

                    $produccionBloque =
                        $produccionBloque->merge($prod);
                }


                $semanasBloque = collect();

                $semanasMes = $this->semanasDelMes($anio, $mes);

                foreach ($semanasMes as $index => $semana) {
                    $semanasBloque[$semana] =
                        $produccionBloque
                        ->where('Semana', $semana)
                        ->sum('Total');
                }


                $totalBloque = $produccionBloque->sum('Total');


                /*
        |--------------------------------------------------------------------------
        | DETALLE DE SIEMBRAS
        |--------------------------------------------------------------------------
        */

                $detalleSiembras = $items
                    ->groupBy('ID_Ciclo_Siembra')
                    ->map(function ($grupoSiembra) use ($produccionSiembra, $anio, $mes) {

                        $primera = $grupoSiembra->first();

                        $idsSiembras = $grupoSiembra
                            ->pluck('ID_Siembra');


                        $produccionCiclo = collect();

                        foreach ($grupoSiembra as $siembra) {

                            $prod = $produccionSiembra
                                ->get($siembra->ID_Siembra, collect());

                            // Filtrar producción que esté dentro del periodo de la siembra
                            $prod = $prod->filter(function ($p) use ($siembra) {
                                $fecha = Carbon::create($p->Anio, 1, 1)
                                    ->setISODate($p->Anio, $p->Semana, 1)
                                    ->startOfDay();

                                // No producción antes de la siembra
                                if ($siembra->Fecha_Siembra && $fecha->lt($siembra->Fecha_Siembra)) {
                                    return false;
                                }

                                // No producción después de erradicación
                                if ($siembra->Fecha_Erradicacion && $fecha->gt($siembra->Fecha_Erradicacion)) {
                                    return false;
                                }

                                return true;
                            });

                            $produccionCiclo =
                                $produccionCiclo->merge($prod);
                        }

                        $semanas = [];

                        $semanasMes = $this->semanasDelMes($anio, $mes);

                        foreach ($semanasMes as $semana) {
                            $semanas[$semana] =
                                $produccionCiclo
                                ->where('Semana', $semana)
                                ->sum('Total');
                        }


                        return [

                            'bloque' =>
                            $primera
                                ->ubicacion
                                ->bloque
                                ->Codigo_Bloque,


                            'camas' =>
                            $grupoSiembra
                                ->pluck('ID_Ubicacion')
                                ->unique()
                                ->count(),


                            'fecha_siembra' =>
                            $primera->Fecha_Siembra,


                            'plantas' =>
                            $grupoSiembra
                                ->sum('Cantidad_Plantas'),


                            'semanas' =>
                            $semanas,


                            'total' =>
                            array_sum($semanas)

                        ];
                    })
                    ->values();


                return [

                    /*
            |--------------------------------------------------------------------------
            | TABLA PRINCIPAL
            |--------------------------------------------------------------------------
            */

                    'bloque' =>
                    $primera
                        ->ubicacion
                        ->bloque
                        ->Codigo_Bloque,


                    'camas' =>
                    $items
                        ->pluck('ID_Ubicacion')
                        ->unique()
                        ->count(),


                    'plantas' =>
                    $items
                        ->sum('Cantidad_Plantas'),


                    'semanas' =>
                    $semanasBloque,


                    'total' =>
                    $totalBloque,


                    'tallos_planta' =>
                    $items->sum('Cantidad_Plantas') > 0
                        ? round(
                            $totalBloque /
                                $items->sum('Cantidad_Plantas'),
                            2
                        )
                        : 0,


                    /*
            |--------------------------------------------------------------------------
            | TABLA DETALLE
            |--------------------------------------------------------------------------
            */

                    'siembras' =>
                    $detalleSiembras

                ];
            });
    }

    private function semanasDelMes($anio, $mes)
    {
        $semanas = [];

        $inicio = Carbon::create($anio, $mes, 1);
        $fin = $inicio->copy()->endOfMonth();

        while ($inicio <= $fin) {
            $semana = $inicio->weekOfYear;

            if (!in_array($semana, $semanas)) {
                $semanas[] = $semana;
            }

            $inicio->addDay();
        }

        return $semanas;
    }
}
