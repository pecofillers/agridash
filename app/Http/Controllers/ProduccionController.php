<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Ubicacion;
use App\Models\Siembra;
use App\Models\Bloque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProduccionController extends Controller
{
    public function index()
    {
        $bloques = Bloque::orderBy('Codigo_Bloque')->get();

        return view('produccion.index', compact('bloques'));
    }

    public function sincronizar_bloque(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $request->validate([
            'bloque' => 'required|string',
        ]);

        $codigoBloque = trim($request->bloque);

        $bloque = Bloque::where(
            'Codigo_Bloque',
            $codigoBloque
        )->first();

        if (!$bloque) {
            $msg = "El Bloque $codigoBloque no existe.";

            return $request->wantsJson()
                ? response()->json([
                    'success' => false,
                    'mensaje' => $msg,
                ], 404)
                : back()->withErrors($msg);
        }

        if (empty($bloque->url_onedrive)) {
            $msg = "No hay un enlace de OneDrive configurado para el Bloque $codigoBloque.";

            return $request->wantsJson()
                ? response()->json([
                    'success' => false,
                    'mensaje' => $msg,
                ], 422)
                : back()->withErrors($msg);
        }

        $this->actualizarEstadoSync([
            'activo' => true,
            'tipo' => 'bloque',
            'progreso' => '0/1',
            'mensaje' => "Descargando Excel del Bloque $codigoBloque...",
            'error' => null,
            'inicio' => now()->toDateTimeString(),
            'fin' => null,
        ]);

        $tempExcel = storage_path(
            'app/temp_excel_' . time() . '.xlsx'
        );

        try {
            $response = $this->descargarArchivo(
                $bloque->url_onedrive
            );

            if (!$response->successful()) {
                $msg = "No se pudo descargar el Excel del Bloque $codigoBloque.";

                $this->actualizarEstadoSync([
                    'activo' => false,
                    'error' => $msg,
                    'fin' => now()->toDateTimeString(),
                ]);

                return $request->wantsJson()
                    ? response()->json([
                        'success' => false,
                        'mensaje' => $msg,
                    ], 422)
                    : back()->withErrors($msg);
            }

            file_put_contents(
                $tempExcel,
                $response->body()
            );

            $this->actualizarEstadoSync([
                'mensaje' => "Procesando Excel del Bloque $codigoBloque...",
            ]);

            $resultado = $this->procesarExcelDeBloque(
                $tempExcel,
                $codigoBloque
            );

            $this->eliminarArchivo($tempExcel);

            $mensaje = "Sincronización exitosa. Bloque $codigoBloque: "
                . $resultado['guardados']
                . " guardados, "
                . $resultado['omitidos']
                . " omitidos, "
                . $resultado['sin_siembra']
                . " sin siembra.";

            $this->actualizarEstadoSync([
                'activo' => false,
                'progreso' => '1/1',
                'registros_guardados' => $resultado['guardados'],
                'registros_omitidos' => $resultado['omitidos'],
                'registros_sin_siembra' => $resultado['sin_siembra'],
                'mensaje' => $mensaje,
                'error' => null,
                'fin' => now()->toDateTimeString(),
            ]);

            return $request->wantsJson()
                ? response()->json([
                    'success' => true,
                    'mensaje' => $mensaje,
                ])
                : back()->with('success', $mensaje);
        } catch (\Throwable $e) {
            $this->eliminarArchivo($tempExcel);

            $msg = "Error en la sincronización: {$e->getMessage()}";

            $this->actualizarEstadoSync([
                'activo' => false,
                'error' => $msg,
                'fin' => now()->toDateTimeString(),
            ]);

            return $request->wantsJson()
                ? response()->json([
                    'success' => false,
                    'mensaje' => $msg,
                ], 500)
                : back()->withErrors($msg);
        }
    }

    public function sincronizarTodo(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $bloques = Bloque::whereNotNull('url_onedrive')
            ->where('url_onedrive', '<>', '')
            ->orderBy('Codigo_Bloque')
            ->get();

        if ($bloques->isEmpty()) {
            $msg = 'No hay ningún enlace de OneDrive configurado todavía.';

            return $request->wantsJson()
                ? response()->json([
                    'success' => false,
                    'mensaje' => $msg,
                ], 422)
                : back()->withErrors($msg);
        }

        $total = $bloques->count();

        $this->actualizarEstadoSync([
            'activo' => true,
            'tipo' => 'todos',
            'progreso' => "0/$total",
            'mensaje' => "Iniciando sincronización de $total bloques...",
            'error' => null,
            'inicio' => now()->toDateTimeString(),
            'fin' => null,
        ]);

        $resumen = [];
        $errores = [];
        $totalGuardados = 0;
        $totalOmitidos = 0;
        $totalSinSiembra = 0;

        foreach ($bloques as $i => $bloque) {
            $numero = $i + 1;
            $codigoBloque = $bloque->Codigo_Bloque;

            $this->actualizarEstadoSync([
                'progreso' => "$numero/$total",
                'mensaje' => "Procesando Bloque $codigoBloque ($numero de $total)...",
            ]);

            $tempExcel = storage_path(
                'app/temp_excel_bloque'
                    . $codigoBloque
                    . '_'
                    . time()
                    . '.xlsx'
            );

            try {
                $response = $this->descargarArchivo(
                    $bloque->url_onedrive
                );

                if (!$response->successful()) {
                    $errores[] =
                        "Bloque $codigoBloque: no se pudo descargar el archivo.";

                    continue;
                }

                file_put_contents(
                    $tempExcel,
                    $response->body()
                );

                $resultado = $this->procesarExcelDeBloque(
                    $tempExcel,
                    $codigoBloque
                );

                $totalGuardados += $resultado['guardados'];
                $totalOmitidos += $resultado['omitidos'];
                $totalSinSiembra += $resultado['sin_siembra'];

                $resumen[] =
                    "Bloque $codigoBloque: "
                    . $resultado['guardados']
                    . " guardados, "
                    . $resultado['omitidos']
                    . " omitidos, "
                    . $resultado['sin_siembra']
                    . " sin siembra.";
            } catch (\Throwable $e) {
                $errores[] =
                    "Bloque $codigoBloque: {$e->getMessage()}";
            }

            $this->eliminarArchivo($tempExcel);
        }

        $mensaje =
            "Sincronización masiva completada. "
            . "Total: <strong>$totalGuardados</strong> guardados, "
            . "<strong>$totalOmitidos</strong> omitidos, "
            . "<strong>$totalSinSiembra</strong> sin siembra.<br>"
            . implode('<br>', $resumen);

        if ($errores) {
            $mensaje .=
                "<br><br>Bloques con error:<br>"
                . implode('<br>', $errores);
        }

        $this->actualizarEstadoSync([
            'activo' => false,
            'progreso' => "$total/$total",
            'registros_guardados' => $totalGuardados,
            'registros_omitidos' => $totalOmitidos,
            'registros_sin_siembra' => $totalSinSiembra,
            'mensaje' => strip_tags(
                str_replace('<br>', ' | ', $mensaje)
            ),
            'error' => $errores
                ? implode(' | ', $errores)
                : null,
            'fin' => now()->toDateTimeString(),
        ]);

        return $request->wantsJson()
            ? response()->json([
                'success' => empty($errores),
                'mensaje' => $mensaje,
            ])
            : back()->with('success', $mensaje);
    }

    public function exportarExcelMultiNave(Request $request)
    {
        $codigoBloque = trim(
            $request->bloque_exportar
        );

        if (!$codigoBloque) {
            return back()->withErrors(
                'Selecciona un bloque.'
            );
        }

        $bloque = Bloque::where(
            'Codigo_Bloque',
            $codigoBloque
        )->first();

        if (!$bloque) {
            return back()->withErrors(
                "El Bloque $codigoBloque no existe."
            );
        }

        $idBloque = $bloque->ID_Bloque;

        $ubicaciones = Ubicacion::where(
            'ID_Bloque',
            $idBloque
        )
            ->orderBy('Nave')
            ->orderBy('Cama')
            ->get([
                'ID_Ubicacion',
                'Nave',
                'Cama',
            ]);

        if ($ubicaciones->isEmpty()) {
            return back()->withErrors(
                'No hay ubicaciones registradas en este bloque.'
            );
        }

        $naves = $ubicaciones
            ->pluck('Nave')
            ->unique()
            ->values();

        $semanas = Produccion::whereIn(
            'ID_Ubicacion',
            $ubicaciones->pluck('ID_Ubicacion')
        )
            ->select('Anio', 'Semana')
            ->distinct()
            ->orderBy('Anio')
            ->orderBy('Semana')
            ->get();

        if ($semanas->isEmpty()) {
            return back()->withErrors(
                'No hay producción registrada para este bloque.'
            );
        }

        $producciones = Produccion::whereIn(
            'ID_Ubicacion',
            $ubicaciones->pluck('ID_Ubicacion')
        )
            ->get([
                'ID_Ubicacion',
                'Anio',
                'Semana',
                'Bajas',
                'Total',
            ])
            ->keyBy(
                fn($p) => implode('|', [
                    $p->ID_Ubicacion,
                    $p->Anio,
                    $p->Semana,
                ])
            );

        $spreadsheet = new Spreadsheet();

        foreach ($naves as $index => $nave) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $numeroNave = preg_replace(
                '/[^0-9]/',
                '',
                $nave
            );

            $sheet->setTitle(
                substr("Nave $numeroNave", 0, 31)
            );

            $camas = $ubicaciones
                ->where('Nave', $nave)
                ->sortBy('Cama')
                ->values();

            $filaActual = 1;

            foreach ($camas as $indexCama => $ubicacion) {
                $cama = $ubicacion->Cama;

                $sheet->setCellValue(
                    "A$filaActual",
                    "CAMA $cama"
                );

                $sheet->getStyle("A$filaActual")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFFF0000');

                $filaConceptos = $filaActual;

                if ($indexCama === 0) {
                    $sheet->setCellValue(
                        "B$filaActual",
                        'SEMANA'
                    );

                    $colIndex = 3;

                    foreach ($semanas as $sem) {
                        $letra =
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                                $colIndex
                            );

                        $sheet->setCellValue(
                            "$letra$filaActual",
                            "{$sem->Anio} - {$sem->Semana}"
                        );

                        $colIndex++;
                    }

                    $filaConceptos++;
                    $desplazamiento = 12;
                } else {
                    $desplazamiento = 11;
                }

                $conceptos = [
                    'LUNES',
                    'MARTES',
                    'MIERCOLES',
                    'JUEVES',
                    'VIERNES',
                    'SABADO',
                    'DOMINGO',
                    'BAJAS',
                    'TOTAL',
                    'ACUMULADO',
                ];

                foreach ($conceptos as $i => $concepto) {
                    $sheet->setCellValue(
                        'B' . ($filaConceptos + $i),
                        $concepto
                    );
                }

                $colIndex = 3;
                $acumulado = 0;

                foreach ($semanas as $sem) {
                    $letra =
                        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                            $colIndex
                        );

                    $registro = $producciones->get(
                        implode('|', [
                            $ubicacion->ID_Ubicacion,
                            $sem->Anio,
                            $sem->Semana,
                        ])
                    );

                    $bajas = $registro?->Bajas ?? '';
                    $total = $registro?->Total ?? 0;

                    $acumulado += (int) $total;

                    $sheet->setCellValue(
                        "{$letra}" . ($filaConceptos + 7),
                        $bajas
                    );

                    $sheet->setCellValue(
                        "{$letra}" . ($filaConceptos + 8),
                        $total
                    );

                    $sheet->setCellValue(
                        "{$letra}" . ($filaConceptos + 9),
                        $acumulado > 0
                            ? $acumulado
                            : ''
                    );

                    $colIndex++;
                }

                $filaActual += $desplazamiento;
            }
        }

        return response()->streamDownload(
            function () use ($spreadsheet) {
                (new Xlsx($spreadsheet))
                    ->save('php://output');
            },
            "Bloque_{$codigoBloque}.xlsx"
        );
    }

    public function configuracionEnlaces()
    {
        $bloques = Bloque::orderBy('Codigo_Bloque')->get();

        return view(
            'configuracion.configuracion_enlaces',
            compact('bloques')
        );
    }

    public function guardarEnlace(Request $request)
    {
        $request->validate([
            'bloque' => 'required|string|exists:dim_bloques,Codigo_Bloque',
            'url_onedrive' => 'required|url|max:500',
        ]);

        $bloque = Bloque::where(
            'Codigo_Bloque',
            $request->bloque
        )->firstOrFail();

        $bloque->update([
            'url_onedrive' => $request->url_onedrive,
        ]);

        return back()->with(
            'success',
            "El enlace del {$bloque->Nombre_Bloque} se guardó correctamente."
        );
    }

    public function crearBloque(Request $request)
    {
        $request->validate([
            'Codigo_Bloque' => [
                'required',
                'string',
                'max:10',
                'unique:dim_bloques,Codigo_Bloque',
            ],
            'Nombre_Bloque' => 'nullable|string|max:40',
            'Descripcion' => 'nullable|string',
        ]);

        $codigo = trim(
            $request->Codigo_Bloque
        );

        Bloque::create([
            'Codigo_Bloque' => $codigo,
            'Nombre_Bloque' => $request->Nombre_Bloque
                ?: 'Bloque ' . $codigo,
            'Descripcion' => $request->Descripcion,
            'Estado' => 'ACTIVO',
        ]);

        return back()->with(
            'success',
            "El Bloque $codigo fue creado correctamente."
        );
    }

    public function estadoSincronizacion()
    {
        return response()->json(
            Cache::get(
                'produccion_sync_status',
                [
                    'activo' => false,
                    'mensaje' => 'Sin sincronizaciones recientes.',
                ]
            )
        );
    }

    private function actualizarEstadoSync(array $datos): void
    {
        $actual = Cache::get(
            'produccion_sync_status',
            []
        );

        Cache::put(
            'produccion_sync_status',
            array_merge($actual, $datos),
            now()->addHour()
        );
    }

    private function descargarArchivo(string $url)
    {
        $urlBase = explode('?', $url)[0];

        return Http::withHeaders([
            'User-Agent' =>
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
            'Accept' =>
                'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ])
            ->withOptions([
                'allow_redirects' => true,
                'verify' => false,
            ])
            ->timeout(120)
            ->get($urlBase . '?download=1');
    }

    private function eliminarArchivo(?string $ruta): void
    {
        if ($ruta && file_exists($ruta)) {
            unlink($ruta);
        }
    }

    private function procesarExcelDeBloque(
        string $rutaExcel,
        string $codigoBloque
    ): array {
        $spreadsheet = IOFactory::load($rutaExcel);

        $bloque = Bloque::where(
            'Codigo_Bloque',
            $codigoBloque
        )->first();

        if (!$bloque) {
            throw new \Exception(
                "El Bloque $codigoBloque no existe."
            );
        }

        $ubicaciones = Ubicacion::where(
            'ID_Bloque',
            $bloque->ID_Bloque
        )
            ->get([
                'ID_Ubicacion',
                'Nave',
                'Cama',
            ]);

        $ubicacionesIndex = [];

        foreach ($ubicaciones as $ubicacion) {
            $ubicacionesIndex[
                $this->claveUbicacion(
                    $ubicacion->Nave,
                    $ubicacion->Cama
                )
            ] = $ubicacion;
        }

        $siembras = Siembra::whereIn(
            'ID_Ubicacion',
            $ubicaciones->pluck('ID_Ubicacion')
        )
            ->whereNotNull('Fecha_Siembra')
            ->orderBy('Fecha_Siembra')
            ->get([
                'ID_Siembra',
                'ID_Ubicacion',
                'Fecha_Siembra',
                'Fecha_Erradicacion',
            ])
            ->groupBy('ID_Ubicacion');

        $producciones = [];
        $omitidos = 0;
        $sinSiembra = 0;

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $numeroNave = preg_replace(
                '/[^0-9]/',
                '',
                trim($sheet->getTitle())
            );

            if (!$numeroNave) {
                continue;
            }

            $filas = $sheet->toArray();

            if (count($filas) < 3) {
                continue;
            }

            $semanas = [];

            foreach ($filas[0] as $col => $cabecera) {
                if ($col < 2) {
                    continue;
                }

                $cabecera = trim(
                    (string) $cabecera
                );

                if (preg_match(
                    '/^(\d{4})\s*-\s*(\d{1,2})$/',
                    $cabecera,
                    $matches
                )) {
                    $semanas[$col] = [
                        'anio' => (int) $matches[1],
                        'semana' => (int) $matches[2],
                    ];
                }
            }

            if (!$semanas) {
                continue;
            }

            $camaActual = null;
            $valores = [];

            foreach ($filas as $fila) {
                $primeraColumna = trim(
                    (string) ($fila[0] ?? '')
                );

                if (
                    stripos(
                        $primeraColumna,
                        'CAMA'
                    ) !== false
                ) {
                    if ($camaActual !== null) {
                        $this->agregarProduccionesCama(
                            $producciones,
                            $omitidos,
                            $sinSiembra,
                            $ubicacionesIndex,
                            $siembras,
                            $numeroNave,
                            $camaActual,
                            $valores
                        );
                    }

                    $camaActual = preg_replace(
                        '/[^0-9]/',
                        '',
                        $primeraColumna
                    );

                    $valores = [];
                }

                if (!$camaActual) {
                    continue;
                }

                $concepto = strtoupper(
                    trim(
                        (string) ($fila[1] ?? '')
                    )
                );

                if (
                    !in_array(
                        $concepto,
                        ['TOTAL', 'BAJAS'],
                        true
                    )
                ) {
                    continue;
                }

                foreach ($semanas as $col => $semana) {
                    $clave =
                        $semana['anio'] .
                        '|' .
                        $semana['semana'];

                    if (!isset($valores[$clave])) {
                        $valores[$clave] = [
                            'anio' => $semana['anio'],
                            'semana' => $semana['semana'],
                            'total' => 0,
                            'bajas' => 0,
                        ];
                    }

                    $valores[$clave][
                        strtolower($concepto)
                    ] = (int) (
                        $fila[$col] ?? 0
                    );
                }
            }

            if ($camaActual !== null) {
                $this->agregarProduccionesCama(
                    $producciones,
                    $omitidos,
                    $sinSiembra,
                    $ubicacionesIndex,
                    $siembras,
                    $numeroNave,
                    $camaActual,
                    $valores
                );
            }
        }

        foreach (
            array_chunk($producciones, 1000)
            as $lote
        ) {
            Produccion::upsert(
                $lote,
                [
                    'ID_Ubicacion',
                    'Anio',
                    'Semana',
                ],
                [
                    'ID_Siembra',
                    'Bajas',
                    'Total',
                ]
            );
        }

        return [
            'guardados' => count($producciones),
            'omitidos' => $omitidos,
            'sin_siembra' => $sinSiembra,
        ];
    }

    private function agregarProduccionesCama(
        array &$producciones,
        int &$omitidos,
        int &$sinSiembra,
        $ubicacionesIndex,
        $siembras,
        string $numeroNave,
        string $cama,
        array $valores
    ): void {
        $claveUbicacion = $this->claveUbicacion(
            $numeroNave,
            $cama
        );

        $ubicacion = $ubicacionesIndex[
            $claveUbicacion
        ] ?? null;

        if (!$ubicacion) {
            $omitidos += count($valores);
            return;
        }

        $siembrasUbicacion =
            $siembras->get(
                $ubicacion->ID_Ubicacion,
                collect()
            );

        foreach ($valores as $valor) {
            $total = (int) $valor['total'];
            $bajas = (int) $valor['bajas'];

            $idSiembra =
                $this->buscarSiembraEnColeccion(
                    $siembrasUbicacion,
                    $valor['anio'],
                    $valor['semana']
                );

            if (!$idSiembra) {
                $sinSiembra++;
            }

            $producciones[] = [
                'ID_Ubicacion' =>
                    $ubicacion->ID_Ubicacion,

                'ID_Siembra' =>
                    $idSiembra,

                'Anio' =>
                    $valor['anio'],

                'Semana' =>
                    $valor['semana'],

                'Bajas' =>
                    $bajas,

                'Total' =>
                    $total,
            ];
        }
    }

    private function claveUbicacion(
        $nave,
        $cama
    ): string {
        $nave = preg_replace(
            '/[^0-9]/',
            '',
            (string) $nave
        );

        $cama = preg_replace(
            '/[^0-9]/',
            '',
            (string) $cama
        );

        return "{$nave}|{$cama}";
    }

    private function buscarSiembraEnColeccion(
        $siembras,
        int $anio,
        int $semana
    ): ?int {
        try {
            $fechaSemana = now()
                ->setISODate(
                    $anio,
                    $semana,
                    1
                )
                ->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        $siembra = $siembras
            ->filter(function ($siembra) use (
                $fechaSemana
            ) {
                if (
                    !$siembra->Fecha_Siembra ||
                    $siembra->Fecha_Siembra->gt(
                        $fechaSemana
                    )
                ) {
                    return false;
                }

                if (
                    $siembra->Fecha_Erradicacion &&
                    $siembra->Fecha_Erradicacion->lt(
                        $fechaSemana
                    )
                ) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('Fecha_Siembra')
            ->first();

        return $siembra
            ? (int) $siembra->ID_Siembra
            : null;
    }
}