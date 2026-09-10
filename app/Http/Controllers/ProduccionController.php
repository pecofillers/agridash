<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Ubicacion;
use App\Models\Planilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\File; 
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

    public function sincronizarBloque(Request $request)
    {
        set_time_limit(0); 
        ini_set('memory_limit', '2048M');

        $request->validate([
            'bloque' => 'required|string'
        ]);

        $bloqueSelected = $request->input('bloque');
        
        // 1. Buscamos el enlace guardado en la tabla planillas
        $planilla = Planilla::where('bloque', $bloqueSelected)->first();

        if (!$planilla || empty($planilla->url_onedrive)) {
            return back()->withErrors("No hay un enlace de OneDrive configurado para el Bloque $bloqueSelected.");
        }

        // 2. Preparamos el enlace para descarga
        $urlBase = explode('?', $planilla->url_onedrive)[0];
        $urlDescarga = $urlBase . '?download=1';
        $tempExcel = storage_path('app/temp_excel_' . time() . '.xlsx');

        try {
            // 3. Descargamos disfrazados de navegador
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ])->withOptions([
                'allow_redirects' => true,
                'verify'          => false,
            ])->timeout(120)->get($urlDescarga);

            if (!$response->successful()) {
                return back()->withErrors("No se pudo descargar el Excel del Bloque $bloqueSelected desde OneDrive.");
            }
            
            file_put_contents($tempExcel, $response->body());

            // ... [AQUÍ VA EL RESTO DEL BUCLE FOREACH QUE YA TENÍAMOS PARA LEER LAS NAVES Y CAMAS] ...

            unlink($tempExcel);
            return back()->with('success', "¡Sincronización exitosa! Se actualizaron los datos del Bloque $bloqueSelected.");

        } catch (\Exception $e) {
            if(file_exists($tempExcel)) unlink($tempExcel);
            return back()->withErrors("Error en la sincronización: " . $e->getMessage());
        }
    }

    // 🚀 IMPORTAR CARPETA COMPLETA DESDE ENLACE DE ONEDRIVE
    public function importarCarpetaOneDrive(Request $request)
    {
        // Damos tiempo y memoria ilimitada
        set_time_limit(0); 
        ini_set('memory_limit', '2048M'); 

        $request->validate(['enlace_onedrive' => 'required|url']);

        // Limpiamos el enlace y forzamos la descarga
        $urlOriginal = $request->input('enlace_onedrive');
        $urlBase = explode('?', $urlOriginal)[0];
        $urlDescarga = $urlBase . '?download=1';
        
        $tempZipPath = storage_path('app/temp_onedrive_' . time() . '.zip');
        $extractFolder = storage_path('app/temp_extract_' . time());

        try {
            // 1. Descargar la carpeta disfrazando a Laravel de Google Chrome
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ])->withOptions([
                'allow_redirects' => true,
                'verify'          => false,
            ])->timeout(120)->get($urlDescarga);

            if (!$response->successful()) {
                return back()->withErrors('Microsoft bloqueó la descarga de la carpeta. Verifica el enlace.');
            }

            file_put_contents($tempZipPath, $response->body());

            // 2. Descomprimir el archivo ZIP de OneDrive
            $zip = new \ZipArchive;
            if ($zip->open($tempZipPath) === TRUE) {
                $zip->extractTo($extractFolder);
                $zip->close();
            } else {
                throw new \Exception("El archivo descargado no es un formato válido o OneDrive lo entregó corrupto.");
            }

            // 3. Recorrer todos los archivos Excel extraídos
            $archivosProcesados = 0;
            $totalRegistrosGuardados = 0;
            $archivos = \Illuminate\Support\Facades\File::allFiles($extractFolder);

            foreach ($archivos as $file) {
                if (!in_array($file->getExtension(), ['xlsx', 'xls'])) continue;

                $filename = $file->getFilename();
                
                // Detectar a qué bloque pertenece (Ej: "Bloque 1.xlsx")
                preg_match('/bloque\s*[_\-]?\s*([0-9a-zA-Z]+)/i', $filename, $matches);
                $bloqueDetectado = $matches[1] ?? null;

                if (!$bloqueDetectado) continue; 

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
                $archivosProcesados++;

                foreach ($spreadsheet->getAllSheets() as $sheet) {
                    $nombreHojaOriginal = trim($sheet->getTitle()); 
                    $soloNumeroNave = preg_replace('/[^0-9]/', '', $nombreHojaOriginal);
                    if(empty($soloNumeroNave)) continue;

                    $filas = $sheet->toArray();
                    if (count($filas) < 3) continue;

                    // Mapear semanas de la Fila 1
                    $semanas = [];
                    for ($col = 2; $col < count($filas[0]); $col++) {
                        $valorCabecera = trim($filas[0][$col] ?? '');
                        if (strpos($valorCabecera, '-') !== false) {
                            $partes = explode('-', $valorCabecera);
                            $semanas[$col] = ['anio' => (int) trim($partes[0]), 'semana' => (int) trim($partes[1])];
                        }
                    }

                    if (empty($semanas)) continue;
                    $camaActual = null;

                    for ($i = 0; $i < count($filas); $i++) {
                        $valorColA = trim($filas[$i][0] ?? '');
                        if (stripos($valorColA, 'CAMA') !== false) {
                            $camaActual = preg_replace('/[^0-9]/', '', $valorColA);
                        }

                        $concepto = strtoupper(trim($filas[$i][1] ?? ''));

                        if ($camaActual && in_array($concepto, ['TOTAL', 'BAJAS'])) {
                            $ubicacion = \App\Models\Ubicacion::where('Bloque', $bloqueDetectado)
                                ->where('Nave', $soloNumeroNave)
                                ->where('Cama', $camaActual)
                                ->first();

                            if (!$ubicacion) continue;

                            foreach ($semanas as $colIndex => $fecha) {
                                $cantidad = (int) ($filas[$i][$colIndex] ?? 0);
                                
                                $registro = \App\Models\Produccion::firstOrNew([
                                    'ID_Ubicacion' => $ubicacion->ID_Ubicacion,
                                    'Semana'       => $fecha['semana'],
                                    'Anio'         => $fecha['anio'],
                                ]);

                                if ($concepto === 'TOTAL') {
                                    $registro->Total = $cantidad;
                                } elseif ($concepto === 'BAJAS') {
                                    $registro->Bajas = $cantidad;
                                }
                                
                                $registro->save();
                                $totalRegistrosGuardados++;
                            }
                        }
                    }
                }
            }

            // 4. Limpieza del servidor
            \Illuminate\Support\Facades\File::deleteDirectory($extractFolder);
            if(file_exists($tempZipPath)) unlink($tempZipPath);

            return back()->with('success', "¡Sincronización masiva exitosa! Se procesaron $archivosProcesados archivos y se actualizaron $totalRegistrosGuardados registros en total.");

        } catch (\Exception $e) {
            // Limpieza en caso de error
            if(isset($extractFolder)) \Illuminate\Support\Facades\File::deleteDirectory($extractFolder);
            if(isset($tempZipPath) && file_exists($tempZipPath)) unlink($tempZipPath);
            
            return back()->withErrors("Error procesando la carpeta: " . $e->getMessage());
        }
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
        if (!$bloqueSelected) return back()->withErrors('Selecciona un bloque.');

        $naves = \App\Models\Ubicacion::where('Bloque', $bloqueSelected)
                    ->select('Nave')->distinct()->orderBy('Nave')->pluck('Nave');

        if ($naves->isEmpty()) return back()->withErrors('No hay naves registradas en este bloque.');

        // 1. Obtener las semanas registradas para generar las cabeceras
        $semanas = \App\Models\Produccion::whereHas('ubicacion', function($q) use($bloqueSelected) {
                        $q->where('Bloque', $bloqueSelected);
                    })->select('Anio', 'Semana')->distinct()
                      ->orderBy('Anio', 'desc')->orderBy('Semana', 'desc')
                      ->take(12)->get()->reverse()->values();

        $spreadsheet = new Spreadsheet();
        
        // 2. Crear una pestaña por Nave
        foreach ($naves as $index => $nave) {
            $sheet = $index === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle(substr("Nave " . preg_replace('/[^0-9]/', '', $nave), 0, 31));

            $camas = \App\Models\Ubicacion::where('Bloque', $bloqueSelected)
                        ->where('Nave', $nave)->orderBy('Cama')->pluck('Cama');

            $filaActual = 1;

            // 3. Dibujar la estructura por cada Cama
            foreach ($camas as $indexCama => $cama) {
                // Etiqueta roja de la cama
                $sheet->setCellValue('A' . $filaActual, 'CAMA ' . $cama);
                $sheet->getStyle('A' . $filaActual)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
                
                $filaConceptos = $filaActual;

                // 🌟 MAGIA AQUÍ: Solo la primera cama lleva la fila de "SEMANA"
                if ($indexCama === 0) {
                    $sheet->setCellValue('B' . $filaActual, 'SEMANA');
                    
                    // Imprimir Cabeceras de Semanas (2024 - 24, etc.)
                    $colIndex = 3; 
                    foreach ($semanas as $sem) {
                        $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $sheet->setCellValue($letra . $filaActual, $sem->Anio . ' - ' . $sem->Semana);
                        $colIndex++;
                    }
                    
                    $filaConceptos++; // Bajamos una fila para empezar a escribir 'LUNES'
                    $desplazamiento = 12; // 1 (Semana) + 10 (Lunes a Acumulado) + 1 (Fila en blanco)
                } else {
                    $desplazamiento = 11; // 10 (Lunes a Acumulado) + 1 (Fila en blanco)
                }
                
                // Imprimir Conceptos (Lunes a Acumulado)
                $conceptos = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO', 'BAJAS', 'TOTAL', 'ACUMULADO'];
                foreach ($conceptos as $i => $concepto) {
                    $sheet->setCellValue('B' . ($filaConceptos + $i), $concepto);
                }

                // 4. Llenar los datos consultando la BD
                $colIndex = 3;
                $acumulado = 0;
                foreach ($semanas as $sem) {
                    $letra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    
                    $registro = \App\Models\Produccion::whereHas('ubicacion', function($q) use($bloqueSelected, $nave, $cama) {
                        $q->where('Bloque', $bloqueSelected)->where('Nave', $nave)->where('Cama', $cama);
                    })->where('Anio', $sem->Anio)->where('Semana', $sem->Semana)->first();

                    $bajas = $registro ? $registro->Bajas : '';
                    $total = $registro ? $registro->Total : '';
                    $acumulado += (int)$total;

                    // Llenar Bajas, Total y Acumulado (usando el índice dinámico $filaConceptos)
                    $sheet->setCellValue($letra . ($filaConceptos + 7), $bajas); 
                    $sheet->setCellValue($letra . ($filaConceptos + 8), $total); 
                    $sheet->setCellValue($letra . ($filaConceptos + 9), $acumulado > 0 ? $acumulado : ''); 
                    
                    $colIndex++;
                }

                // Avanzamos las filas necesarias para la siguiente cama
                $filaActual += $desplazamiento; 
            }
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "Bloque_{$bloqueSelected}.xlsx");
    }

    public function probarEnlaceUnico(Request $request)
    {
        // Damos tiempo y memoria para la prueba
        set_time_limit(0); 
        ini_set('memory_limit', '2048M');

        $request->validate([
            'enlace' => 'required|url',
            'bloque' => 'required|string'
        ]);

        $bloqueSelected = $request->input('bloque');
        
        // Limpiamos el enlace de OneDrive y forzamos la descarga del archivo
        $urlBase = explode('?', $request->input('enlace'))[0];
        $urlDescarga = $urlBase . '?download=1';
        
        $tempExcel = storage_path('app/temp_excel_' . time() . '.xlsx');

        try {
            // 1. Descargar el archivo
            // Le decimos a Laravel que siga todas las redirecciones de OneDrive hasta llegar al archivo real
            // Engañamos a Microsoft haciéndole creer que Laravel es Google Chrome en Windows
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
            ])->withOptions([
                'allow_redirects' => true,
                'verify'          => false,
            ])->timeout(120)->get($urlDescarga);
            if (!$response->successful()) {
                return back()->withErrors('No se pudo descargar el Excel. Verifica que el enlace sea público.');
            }
            file_put_contents($tempExcel, $response->body());

            // 2. Procesar el archivo (1 Pestaña = 1 Nave)
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempExcel);
            $registrosGuardados = 0;

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $nave = preg_replace('/[^0-9]/', '', trim($sheet->getTitle()));
                if(empty($nave)) continue;

                $filas = $sheet->toArray();
                if (count($filas) < 3) continue;

                // Mapear Semanas (Fila 1)
                $semanas = [];
                for ($col = 2; $col < count($filas[0]); $col++) {
                    $cabecera = trim($filas[0][$col] ?? '');
                    if (strpos($cabecera, '-') !== false) {
                        $p = explode('-', $cabecera);
                        $semanas[$col] = ['anio' => (int)trim($p[0]), 'semana' => (int)trim($p[1])];
                    }
                }

                $camaActual = null;
                // Leer Filas
                for ($i = 0; $i < count($filas); $i++) {
                    $colA = trim($filas[$i][0] ?? '');
                    if (stripos($colA, 'CAMA') !== false) {
                        $camaActual = preg_replace('/[^0-9]/', '', $colA);
                    }

                    $concepto = strtoupper(trim($filas[$i][1] ?? ''));
                    if ($camaActual && in_array($concepto, ['TOTAL', 'BAJAS'])) {
                        $ubicacion = \App\Models\Ubicacion::where('Bloque', $bloqueSelected)
                            ->where('Nave', $nave)->where('Cama', $camaActual)->first();

                        if (!$ubicacion) continue;

                        foreach ($semanas as $colIndex => $fecha) {
                            $cantidad = (int) ($filas[$i][$colIndex] ?? 0);
                            
                            $registro = \App\Models\Produccion::firstOrNew([
                                'ID_Ubicacion' => $ubicacion->ID_Ubicacion,
                                'Semana' => $fecha['semana'], 'Anio' => $fecha['anio'],
                            ]);

                            if ($concepto === 'TOTAL') $registro->Total = $cantidad;
                            elseif ($concepto === 'BAJAS') $registro->Bajas = $cantidad;
                            
                            $registro->save();
                            $registrosGuardados++;
                        }
                    }
                }
            }

            // 3. Limpieza
            unlink($tempExcel);
            return back()->with('success', "¡Prueba exitosa! Se procesó el Excel y se guardaron $registrosGuardados registros para el Bloque $bloqueSelected.");

        } catch (\Exception $e) {
            if(file_exists($tempExcel)) unlink($tempExcel);
            return back()->withErrors("Error en la prueba: " . $e->getMessage());
        }
    }

    
    public function configuracionEnlaces()
    {
        // Traemos todos los enlaces guardados
        $planillas = Planilla::orderBy('bloque')->get();
        // Traemos los bloques existentes en tu finca para el menú desplegable
        $bloques = Ubicacion::select('Bloque')->distinct()->orderBy('Bloque')->pluck('Bloque');
        
        return view('configuracion.configuracion_enlaces', compact('planillas', 'bloques'));
    }

    // Guardar o actualizar un enlace en la base de datos
    public function guardarEnlace(Request $request)
    {
        $request->validate([
            'bloque' => 'required|string',
            'url_onedrive' => 'required|url'
        ]);

        Planilla::updateOrCreate(
            ['bloque' => $request->bloque],
            ['url_onedrive' => $request->url_onedrive]
        );

        return back()->with('success', 'El enlace del Bloque ' . $request->bloque . ' se guardó correctamente.');
    }
}