<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\ProduccionImport;
use App\Exports\ProduccionExport;

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
            $registros = $query->with('ubicacion')->orderBy('Semana', 'desc')->get();
        }

        return view('produccion.index', compact('registros', 'ubicaciones', 'idUbicacion', 'semana', 'anio'));
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'ID_Ubicacion' => 'required|integer|exists:dim_ubicaciones,ID_Ubicacion',
            'Semana' => 'required|integer|min:1|max:53',
            'Anio' => 'required|integer',
        ]);

        // 1. Sumamos los valores que digita de la planilla
        $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        $totalSemanalePlanilla = 0;
        
        foreach ($dias as $d) {
            $totalSemanalePlanilla += (int) $request->input($d, 0);
        }

        $bajasPlanilla = (int) $request->input('Bajas', 0);

        // 2. REEMPLAZAMOS O CREAMOS el registro con el valor consolidado de la semana
        Produccion::updateOrCreate(
            [
                'ID_Ubicacion' => $request->ID_Ubicacion,
                'Semana' => $request->Semana,
                'Anio' => $request->Anio,
            ],
            [
                'Total' => $totalSemanalePlanilla, // Reemplaza directamente el total
                'Bajas' => $bajasPlanilla         // Reemplaza directamente las bajas
            ]
        );

        return back()->with('success', 'Registro de la semana guardado/actualizado exitosamente con los datos de la planilla.');
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'ID_Produccion' => 'required|integer|exists:fact_produccion,ID_Produccion',
            'Total' => 'required|integer|min:0',
            'Bajas' => 'required|integer|min:0',
        ]);

        $prod = Produccion::findOrFail($request->ID_Produccion);
        
        $prod->update([
            'Total' => $request->Total,
            'Bajas' => $request->Bajas
        ]);

        return back()->with('success', 'Registro corregido exitosamente.');
    }

    // EXPORTAR CSV NATIVO (Ahora con Bloque, Nave y Cama)
    public function exportarCsv()
    {
        $nombreArchivo = 'historial_produccion.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$nombreArchivo",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Traemos los registros junto con los nombres de su ubicación
        $registros = \App\Models\Produccion::with('ubicacion')->orderBy('Anio', 'desc')->orderBy('Semana', 'desc')->get();

        $callback = function() use($registros) {
            $file = fopen('php://output', 'w');
            
            // Añadimos las cabeceras mucho más legibles
            fputcsv($file, ['id_ubicacion', 'bloque', 'nave', 'cama', 'semana', 'anio', 'bajas', 'total']);

            foreach ($registros as $r) {
                // Protegemos el código en caso de que una ubicación haya sido borrada
                $bloque = $r->ubicacion ? $r->ubicacion->Bloque : 'N/A';
                $nave   = $r->ubicacion ? $r->ubicacion->Nave : 'N/A';
                $cama   = $r->ubicacion ? $r->ubicacion->Cama : 'N/A';

                fputcsv($file, [$r->ID_Ubicacion, $bloque, $nave, $cama, $r->Semana, $r->Anio, $r->Bajas, $r->Total]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // IMPORTAR CSV NATIVO (Busca automáticamente por Bloque/Nave/Cama)
    public function importarCsv(Request $request)
    {
        $request->validate([
            'archivo_csv' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('archivo_csv');
        $handle = fopen($file->getPathname(), "r");
        
        // Omitimos la primera fila (cabeceras)
        fgetcsv($handle);

        // Memoria temporal para no hacer consultas repetidas a la BD y acelerar la carga
        $memoriaUbicaciones = [];

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Capturamos las celdas (asegurando que si están vacías no generen error)
            $idUbicacion = !empty($data[0]) ? (int)$data[0] : null;
            $bloque      = trim($data[1] ?? '');
            $nave        = trim($data[2] ?? '');
            $cama        = trim($data[3] ?? '');
            $semana      = !empty($data[4]) ? (int)$data[4] : null;
            $anio        = !empty($data[5]) ? (int)$data[5] : null;
            $bajas       = isset($data[6]) && $data[6] !== '' ? (int)$data[6] : 0;
            $total       = isset($data[7]) && $data[7] !== '' ? (int)$data[7] : 0;

            // MAGIA: Si el usuario dejó el ID vacío pero escribió el Bloque, Nave y Cama, lo buscamos
            if (!$idUbicacion && $bloque && $nave && $cama) {
                $llave = "{$bloque}_{$nave}_{$cama}";
                
                // Si no lo hemos buscado antes, lo consultamos a la BD
                if (!array_key_exists($llave, $memoriaUbicaciones)) {
                    $ubi = \App\Models\Ubicacion::where('Bloque', $bloque)
                                                ->where('Nave', $nave)
                                                ->where('Cama', $cama)
                                                ->first();
                    $memoriaUbicaciones[$llave] = $ubi ? $ubi->ID_Ubicacion : null;
                }
                
                // Asignamos el ID encontrado
                $idUbicacion = $memoriaUbicaciones[$llave];
            }

            // Si logramos obtener un ID válido, guardamos la producción
            if ($idUbicacion && $semana && $anio) {
                \App\Models\Produccion::updateOrCreate(
                    [
                        'ID_Ubicacion' => $idUbicacion,
                        'Semana'       => $semana,
                        'Anio'         => $anio,
                    ],
                    [
                        'Bajas' => $bajas,
                        'Total' => $total,
                    ]
                );
            }
        }

        fclose($handle);

        return back()->with('success', '¡Datos cargados! Las camas se identificaron automáticamente.');
    }

    // IMPORTAR EXCEL MULTI-HOJA (1 HOJA = 1 NAVE)
    public function importarExcelMultiNave(Request $request)
    {
        $request->validate([
            'archivo_excel'  => 'required|file|mimes:xlsx,xls',
            'bloque_default' => 'required|string', // Seleccionas el bloque en el formulario web
        ]);

        $bloqueSelected = trim($request->input('bloque_default'));
        $file = $request->file('archivo_excel');

        try {
            // Cargamos el libro de Excel completo con todas sus hojas
            $spreadsheet = IOFactory::load($file->getPathname());
            $totalRegistrosGuardados = 0;

            // Recorremos CADA PESTAÑA / HOJA del Excel (Cada hoja es una Nave)
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $nombreHojaOriginal = trim($sheet->getTitle()); // Ej: "NAVE 1", "Nave 15", "1"
                $soloNumeroNave     = preg_replace('/[^0-9]/', '', $nombreHojaOriginal); // Extrae solo el número

                // Convertimos la hoja actual a una matriz de filas
                $filas = $sheet->toArray();

                // Si la hoja está vacía o no tiene más de 1 fila (cabecera), la saltamos
                if (count($filas) <= 1) {
                    continue;
                }

                // Normalizamos las cabeceras de la Fila 1 (Convertir a minúsculas)
                $cabeceras = array_map('strtolower', array_map('trim', array_map('strval', $filas[0])));

                // Buscamos la posición de cada columna requerida
                $idxBloque = array_search('bloque', $cabeceras);
                $idxCama   = array_search('cama', $cabeceras);
                $idxSemana = array_search('semana', $cabeceras);
                $idxAnio   = array_search('anio', $cabeceras);
                $idxBajas  = array_search('bajas', $cabeceras);
                $idxTotal  = array_search('total', $cabeceras);

                // Recorremos las filas con datos (desde la Fila 2)
                for ($i = 1; $i < count($filas); $i++) {
                    $row = $filas[$i];

                    $cama   = isset($row[$idxCama]) ? trim((string)$row[$idxCama]) : null;
                    $semana = isset($row[$idxSemana]) ? (int)$row[$idxSemana] : null;
                    $anio   = isset($row[$idxAnio]) ? (int)$row[$idxAnio] : null;
                    $bajas  = isset($row[$idxBajas]) && $row[$idxBajas] !== '' ? (int)$row[$idxBajas] : 0;
                    $total  = isset($row[$idxTotal]) && $row[$idxTotal] !== '' ? (int)$row[$idxTotal] : 0;

                    // El bloque lo toma de la columna del Excel o del selector web
                    $bloque = ($idxBloque !== false && !empty($row[$idxBloque])) ? trim((string)$row[$idxBloque]) : $bloqueSelected;

                    // Si faltan datos clave en la fila, la ignoramos
                    if (empty($cama) || empty($semana) || empty($anio) || empty($bloque)) {
                        continue;
                    }

                    // Buscamos la ubicación (Bloque + Nave + Cama) tolerando variaciones en el nombre de la Nave
                    $ubicacion = \App\Models\Ubicacion::where('Bloque', $bloque)
                        ->where(function($query) use ($nombreHojaOriginal, $soloNumeroNave) {
                            $query->where('Nave', $nombreHojaOriginal)
                                  ->orWhere('Nave', $soloNumeroNave)
                                  ->orWhere('Nave', 'NAVE ' . $soloNumeroNave)
                                  ->orWhere('Nave', 'Nave ' . $soloNumeroNave);
                        })
                        ->where('Cama', $cama)
                        ->first();

                    // Si la cama existe en la BD, creamos o actualizamos la producción
                    if ($ubicacion) {
                        \App\Models\Produccion::updateOrCreate(
                            [
                                'ID_Ubicacion' => $ubicacion->ID_Ubicacion,
                                'Semana'       => $semana,
                                'Anio'         => $anio,
                            ],
                            [
                                'Bajas' => $bajas,
                                'Total' => $total,
                            ]
                        );
                        $totalRegistrosGuardados++;
                    }
                }
            }

            return back()->with('success', "¡Proceso completado! Se recorrieron todas las hojas (Naves) y se guardaron/actualizaron $totalRegistrosGuardados registros.");

        } catch (\Exception $e) {
            return back()->withErrors("Error al procesar el archivo Excel: " . $e->getMessage());
        }
    }

    // DESCARGAR EXCEL MULTI-HOJA (1 PESTAÑA POR NAVE DE UN BLOQUE)
    public function exportarExcelMultiNave(Request $request)
    {
        $bloqueSelected = $request->input('bloque_exportar');

        if (!$bloqueSelected) {
            return back()->withErrors('Debes seleccionar un Bloque para descargar.');
        }

        // Consultamos las naves asociadas a este bloque
        $naves = \App\Models\Ubicacion::where('Bloque', $bloqueSelected)
            ->select('Nave')
            ->distinct()
            ->orderBy('Nave')
            ->pluck('Nave');

        if ($naves->isEmpty()) {
            return back()->withErrors("No se encontraron naves registradas para el Bloque {$bloqueSelected}.");
        }

        // Creamos un nuevo libro de trabajo
        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($naves as $nave) {
            // Usamos la primera hoja por defecto o creamos una nueva
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            // Asignamos el nombre de la pestaña (ej: "Nave 1")
            $nombreLimpio = preg_replace('/[^0-9]/', '', $nave);
            $tituloHoja = !empty($nombreLimpio) ? "Nave " . $nombreLimpio : "Nave " . $nave;
            $sheet->setTitle(substr($tituloHoja, 0, 31)); // Máximo 31 caracteres permitido por Excel

            // Escribimos los encabezados en la Fila 1
            $sheet->setCellValue('A1', 'cama');
            $sheet->setCellValue('B1', 'semana');
            $sheet->setCellValue('C1', 'anio');
            $sheet->setCellValue('D1', 'bajas');
            $sheet->setCellValue('E1', 'total');

            // Consultamos la producción registrada para esta Nave y Bloque
            $registros = \App\Models\Produccion::whereHas('ubicacion', function ($q) use ($bloqueSelected, $nave) {
                $q->where('Bloque', $bloqueSelected)->where('Nave', $nave);
            })
            ->with('ubicacion')
            ->orderBy('Semana', 'desc')
            ->get();

            // Llenamos las filas con los datos de producción
            $fila = 2;
            foreach ($registros as $r) {
                $sheet->setCellValue('A' . $fila, $r->ubicacion->Cama);
                $sheet->setCellValue('B' . $fila, $r->Semana);
                $sheet->setCellValue('C' . $fila, $r->Anio);
                $sheet->setCellValue('D' . $fila, $r->Bajas);
                $sheet->setCellValue('E' . $fila, $r->Total);
                $fila++;
            }

            $sheetIndex++;
        }

        $nombreArchivo = "Produccion_Bloque_{$bloqueSelected}.xlsx";

        // Retornamos el archivo .xlsx para descarga directa en el navegador
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}