<?php

namespace App\Http\Controllers;

use App\Models\Siembra;
use App\Models\Ubicacion;
use App\Models\Variedad;
use Illuminate\Http\Request;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AgronomiaController extends Controller
{
    public function index()
    {
        $ubicaciones = Ubicacion::orderBy('Bloque')->orderBy('Nave')->orderBy('Cama')->get();
        $variedades = Variedad::orderBy('Nombre_Variedad')->get();
        $historial = collect();

        $idUbicacion = request('ID_Ubicacion');

        if ($idUbicacion) {
            $historial = Siembra::where('ID_Ubicacion', $idUbicacion)
                ->with('variedad', 'ubicacion')
                ->orderBy('Fecha_Siembra')
                ->get();
        }

        return view('agronomia.index', compact('ubicaciones', 'variedades', 'historial', 'idUbicacion'));
    }

    public function registrarSiembra(Request $request)
    {
        $request->validate([
            'ID_Ubicacion' => 'required|integer|exists:dim_ubicaciones,ID_Ubicacion',
            'ID_Variedad' => 'required|integer',
            'Fecha_Siembra' => 'required|date',
            'Cantidad_Plantas' => 'required|integer|min:1',
            'Metros_Lineales' => 'required|numeric|min:0.1',
            'Estado_Siembra' => 'nullable|string|max:50',
            'Ciclo_Actual' => 'nullable|integer|min:1',
            'Fecha_Pinch' => 'nullable|date',
            'Fecha_Hormona' => 'nullable|date',
            'Fecha_Erradicacion' => 'nullable|date',
        ]);

        // Cerrar siembra activa de esta ubicación y cambiar su estado
        Siembra::where('ID_Ubicacion', $request->ID_Ubicacion)
            ->whereNull('Fecha_Fin')
            ->update([
                'Fecha_Fin' => now(),
                'Estado_Siembra' => 'TERMINADA'
            ]);

        $densidad = $request->Cantidad_Plantas / $request->Metros_Lineales;

        Siembra::create([
            'ID_Ubicacion' => $request->ID_Ubicacion,
            'ID_Variedad' => $request->ID_Variedad,
            'Fecha_Siembra' => $request->Fecha_Siembra,
            'Cantidad_Plantas' => $request->Cantidad_Plantas,
            'Metros_Lineales' => $request->Metros_Lineales,
            // (La base de datos calculará la densidad por sí sola)
            'Estado_Siembra' => $request->Estado_Siembra ?? 'SEMBRADA',
            'Ciclo_Actual' => $request->Ciclo_Actual ?? 1,
            'Fecha_Pinch' => $request->Fecha_Pinch,
            'Fecha_Hormona' => $request->Fecha_Hormona,
            'Fecha_Erradicacion' => $request->Fecha_Erradicacion,
        ]);

        return back()->with('success', 'Siembra registrada correctamente.');
    }

    public function crearVariedad(Request $request)
    {
        $request->validate([
            'Nombre_Variedad' => 'required|string|max:100',
            'Color' => 'nullable|string|max:50',
            'Ciclo_Dias' => 'nullable|integer|min:1',
        ]);

        Variedad::create($request->only(['Nombre_Variedad', 'Color', 'Ciclo_Dias']));

        return back()->with('success', 'Variedad registrada.');
    }

    // DESCARGAR EXCEL MULTI-BLOQUE (TODA LA FINCA)
    public function exportarSiembrasMultiBloque()
    {
        // Obtenemos todos los bloques registrados en la finca
        $bloques = \App\Models\Ubicacion::select('Bloque')->distinct()->orderBy('Bloque')->pluck('Bloque');

        if ($bloques->isEmpty()) {
            return back()->withErrors("No hay bloques registrados en el sistema.");
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheetIndex = 0;

        // Ya no necesitamos la columna 'bloque' porque la pestaña define el bloque
        $cabeceras = ['nave', 'cama', 'variedad', 'fecha_siembra', 'fecha_fin', 'plantas', 'metros', 'estado', 'ciclo', 'fecha_pinch', 'fecha_hormona', 'fecha_erradicacion'];

        foreach ($bloques as $bloque) {
            $sheet = $sheetIndex === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();

            // Limpiamos el nombre para la pestaña (ej. si dice "BLOQUE 1" en BD, la pestaña se llama "BLOQUE 1")
            $tituloHoja = substr($bloque, 0, 31);
            $sheet->setTitle($tituloHoja);

            // Escribir cabeceras
            $col = 'A';
            foreach ($cabeceras as $c) {
                $sheet->setCellValue($col . '1', $c);
                $col++;
            }

            // Consultar las siembras SOLO de este bloque
            $siembras = \App\Models\Siembra::whereHas('ubicacion', function($q) use ($bloque) {
                $q->where('Bloque', $bloque);
            })->with(['ubicacion', 'variedad'])->orderBy('Fecha_Siembra', 'desc')->get();

            // Llenar datos
            $fila = 2;
            foreach ($siembras as $s) {
                $sheet->setCellValue('A' . $fila, $s->ubicacion ? $s->ubicacion->Nave : '');
                $sheet->setCellValue('B' . $fila, $s->ubicacion ? $s->ubicacion->Cama : '');
                $sheet->setCellValue('C' . $fila, $s->variedad ? $s->variedad->Nombre_Variedad : '');
                $sheet->setCellValue('D' . $fila, $s->Fecha_Siembra ? $s->Fecha_Siembra->format('Y-m-d') : '');
                $sheet->setCellValue('E' . $fila, $s->Fecha_Fin ? $s->Fecha_Fin->format('Y-m-d') : '');
                $sheet->setCellValue('F' . $fila, $s->Cantidad_Plantas);
                $sheet->setCellValue('G' . $fila, $s->Metros_Lineales);
                $sheet->setCellValue('H' . $fila, $s->Estado_Siembra);
                $sheet->setCellValue('I' . $fila, $s->Ciclo_Actual);
                $sheet->setCellValue('J' . $fila, $s->Fecha_Pinch ? $s->Fecha_Pinch->format('Y-m-d') : '');
                $sheet->setCellValue('K' . $fila, $s->Fecha_Hormona ? $s->Fecha_Hormona->format('Y-m-d') : '');
                $sheet->setCellValue('L' . $fila, $s->Fecha_Erradicacion ? $s->Fecha_Erradicacion->format('Y-m-d') : '');
                $fila++;
            }
            $sheetIndex++;
        }

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Historial_Siembras_Finca.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // IMPORTAR EXCEL MULTI-BLOQUE
    public function importarSiembrasMultiBloque(Request $request)
    {
        $request->validate(['archivo_excel' => 'required|file|mimes:xlsx,xls']);

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('archivo_excel')->getPathname());
            $guardados = 0;
            $memoriaUbicaciones = [];
            $memoriaVariedades = [];

            // Función para interpretar fechas (tanto numéricas de Excel como de texto)
            $parseDate = function($val) {
                if (empty($val)) return null;
                if (is_numeric($val)) return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
                return date('Y-m-d', strtotime(str_replace('/', '-', $val))); 
            };

            // Recorremos TODAS las pestañas del Excel
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $nombreHoja = trim($sheet->getTitle()); // Ej: "BLOQUE 1" o "1"
                $soloNumeroBloque = preg_replace('/[^0-9]/', '', $nombreHoja);

                $filas = $sheet->toArray();
                if (count($filas) <= 1) continue;

                $cabeceras = array_map('strtolower', array_map('trim', array_map('strval', $filas[0])));
                
                // Índices de columnas (Ya no buscamos la columna "bloque")
                $idxNave = array_search('nave', $cabeceras);
                $idxCama = array_search('cama', $cabeceras);
                $idxVariedad = array_search('variedad', $cabeceras);
                $idxFSiembra = array_search('fecha_siembra', $cabeceras);
                $idxFFin = array_search('fecha_fin', $cabeceras);
                $idxPlantas = array_search('plantas', $cabeceras);
                $idxMetros = array_search('metros', $cabeceras);
                $idxEstado = array_search('estado', $cabeceras);
                $idxCiclo = array_search('ciclo', $cabeceras);
                $idxPinch = array_search('fecha_pinch', $cabeceras);
                $idxHormona = array_search('fecha_hormona', $cabeceras);
                $idxErradicacion = array_search('fecha_erradicacion', $cabeceras);

                // Si la hoja no tiene al menos nave, cama y variedad, la ignoramos
                if ($idxNave === false || $idxCama === false || $idxVariedad === false) continue;

                for ($i = 1; $i < count($filas); $i++) {
                    $row = $filas[$i];

                    $nave = trim((string)($row[$idxNave] ?? ''));
                    $cama = trim((string)($row[$idxCama] ?? ''));
                    $variedad = trim((string)($row[$idxVariedad] ?? ''));

                    if (!$nave || !$cama || !$variedad) continue;

                    $fechaSiembra = $idxFSiembra !== false ? $parseDate($row[$idxFSiembra]) : null;
                    if (!$fechaSiembra) continue;

                    $plantas = $idxPlantas !== false ? (int)$row[$idxPlantas] : 0;
                    $metros = $idxMetros !== false ? (float)$row[$idxMetros] : 0;
                    
                    // Buscamos la Ubicacion (Bloque lo tomamos del nombre de la pestaña)
                    $llaveUbi = "{$nombreHoja}_{$nave}_{$cama}";
                    if (!array_key_exists($llaveUbi, $memoriaUbicaciones)) {
                        $ubi = \App\Models\Ubicacion::where('Cama', $cama)
                            ->where('Nave', $nave)
                            ->where(function($q) use ($nombreHoja, $soloNumeroBloque) {
                                $q->where('Bloque', $nombreHoja)
                                  ->orWhere('Bloque', $soloNumeroBloque)
                                  ->orWhere('Bloque', 'BLOQUE ' . $soloNumeroBloque)
                                  ->orWhere('Bloque', 'Bloque ' . $soloNumeroBloque);
                            })->first();
                        $memoriaUbicaciones[$llaveUbi] = $ubi ? $ubi->ID_Ubicacion : null;
                    }
                    $idUbi = $memoriaUbicaciones[$llaveUbi];

                    // Variedad Cache (Crea la variedad si no existe)
                    if (!array_key_exists($variedad, $memoriaVariedades)) {
                        $var = \App\Models\Variedad::where('Nombre_Variedad', $variedad)->first();
                        if (!$var) $var = \App\Models\Variedad::create(['Nombre_Variedad' => $variedad]);
                        $memoriaVariedades[$variedad] = $var->ID_Variedad;
                    }
                    $idVar = $memoriaVariedades[$variedad];

                    // Guardamos la Siembra
                    if ($idUbi && $idVar && $metros > 0) {
                        \App\Models\Siembra::updateOrCreate(
                            [
                                'ID_Ubicacion' => $idUbi,
                                'ID_Variedad' => $idVar,
                                'Fecha_Siembra' => $fechaSiembra,
                            ],
                            [
                                'Fecha_Fin' => $idxFFin !== false ? $parseDate($row[$idxFFin]) : null,
                                'Cantidad_Plantas' => $plantas,
                                'Metros_Lineales' => $metros,
                                // (La base de datos calculará la densidad por sí sola)
                                'Estado_Siembra' => $idxEstado !== false && !empty($row[$idxEstado]) ? strtoupper(trim($row[$idxEstado])) : 'SEMBRADA',
                                'Ciclo_Actual' => $idxCiclo !== false && !empty($row[$idxCiclo]) ? (int)$row[$idxCiclo] : 1,
                                'Fecha_Pinch' => $idxPinch !== false ? $parseDate($row[$idxPinch]) : null,
                                'Fecha_Hormona' => $idxHormona !== false ? $parseDate($row[$idxHormona]) : null,
                                'Fecha_Erradicacion' => $idxErradicacion !== false ? $parseDate($row[$idxErradicacion]) : null,
                            ]
                        );
                        $guardados++;
                    }
                }
            }

            return back()->with('success', "¡Proceso completado! Se sincronizaron $guardados registros de siembra de todos los bloques.");
        } catch (\Exception $e) {
            return back()->withErrors('Error procesando el archivo: ' . $e->getMessage());
        }
    }

    // ==================================================================
    // SECCIÓN DE REPORTES, CONSOLIDADOS Y EXPORTACIONES
    // ==================================================================

    private function obtenerDataConsolidadaBloque($bloqueSeleccionado)
    {
        $query = \App\Models\Siembra::with(['variedad', 'ubicacion']);
        
        if ($bloqueSeleccionado !== 'TODOS') {
            $query->whereHas('ubicacion', function ($q) use ($bloqueSeleccionado) {
                $q->where('Bloque', $bloqueSeleccionado);
            });
        }
        $siembras = $query->get();

        $grupos = $siembras->groupBy(function ($item) {
            $fSiembra = $item->Fecha_Siembra ? $item->Fecha_Siembra->format('Y-m-d') : 'Sin Fecha';
            $fPinch = $item->Fecha_Pinch ? $item->Fecha_Pinch->format('Y-m-d') : 'Sin Pinch';
            return $fSiembra . '_' . $item->ID_Variedad . '_' . $fPinch;
        })->sortByDesc(function ($grupo) {
            return $grupo->first()->Fecha_Siembra ? $grupo->first()->Fecha_Siembra->format('Y-m-d') : '0000-00-00';
        });

        $consolidado = collect();

        foreach ($grupos as $grupo) {
            $primera = $grupo->first();
            $idsUbicaciones = $grupo->pluck('ID_Ubicacion')->unique();
            $totalPlantas = $grupo->sum('Cantidad_Plantas');
            $numeroCamas = $idsUbicaciones->count();

            // Obtenemos la producción detallada POR SEMANA para esta siembra
            $producciones = \App\Models\Produccion::whereIn('ID_Ubicacion', $idsUbicaciones)
                ->selectRaw('Anio, Semana, SUM(Total) as TotalSemana')
                ->groupBy('Anio', 'Semana')
                ->get();

            $totalTallos = 0;
            $prodSemanal = [];
            foreach ($producciones as $p) {
                $totalTallos += $p->TotalSemana;
                // Clave formato: 2024-S32 (Para que se ordene bien)
                $key = $p->Anio . '-S' . str_pad($p->Semana, 2, '0', STR_PAD_LEFT);
                $prodSemanal[$key] = $p->TotalSemana;
            }

            $fSiembraFormatted = $primera->Fecha_Siembra ? $primera->Fecha_Siembra->format('d/m/Y') : 'N/A';

            $consolidado->push((object) [
                'fecha_siembra'  => $fSiembraFormatted,
                'variedad'       => $primera->variedad ? $primera->variedad->Nombre_Variedad : 'N/A',
                'color'          => $primera->variedad ? $primera->variedad->Color : 'N/A',
                'fecha_fin'      => $primera->Fecha_Fin ? $primera->Fecha_Fin->format('d/m/Y') : 'ACTIVA',
                'fecha_pinch'    => $primera->Fecha_Pinch ? $primera->Fecha_Pinch->format('d/m/Y') : 'Sin Pinch',
                'numero_camas'   => $numeroCamas,
                'numero_plantas' => $totalPlantas,
                'ciclo'          => $primera->Ciclo_Actual ?? 1,
                'total_tallos'   => $totalTallos,
                'produccion_semanal' => $prodSemanal // Guardamos el historial de la curva
            ]);
        }

        return $consolidado;
    }

    // ==================================================================
    // SECCIÓN ÚNICA DE COMPARATIVA Y CONSOLIDADOS (WEB, EXCEL Y PDF)
    // ==================================================================

    public function consolidadoBloque(Request $request)
    {
        $accion = $request->input('accion', 'ver'); // Saber qué botón presionó el usuario

        $bloques = \App\Models\Ubicacion::bloques();
        $variedades = \App\Models\Variedad::orderBy('Nombre_Variedad')->get();
        
        $bloqueSel = $request->input('bloque');
        $variedadSel = $request->input('variedad_id');
        $anioSel = $request->input('anio');
        // Capturar los IDs (que ahora pueden venir separados por coma) y aplanarlos en un solo arreglo
        $idsRaw = $request->input('siembras_ids', []);
        $siembrasSeleccionadasIds = [];

        foreach ((array) $idsRaw as $raw) {
            if (!empty($raw)) {
                $siembrasSeleccionadasIds = array_merge($siembrasSeleccionadasIds, explode(',', $raw));
            }
        }
        $siembrasSeleccionadasIds = array_unique(array_filter($siembrasSeleccionadasIds));

        $aniosDisponibles = \App\Models\Siembra::whereNotNull('Fecha_Siembra')
            ->selectRaw('YEAR(Fecha_Siembra) as anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');

        $querySiembras = \App\Models\Siembra::with(['ubicacion', 'variedad']);
        if ($bloqueSel && $bloqueSel !== 'TODOS') {
            $querySiembras->whereHas('ubicacion', function ($q) use ($bloqueSel) { $q->where('Bloque', $bloqueSel); });
        }
        if ($variedadSel) { $querySiembras->where('ID_Variedad', $variedadSel); }
        if ($anioSel) { $querySiembras->whereYear('Fecha_Siembra', $anioSel); }

        $siembrasDisponibles = $querySiembras->orderBy('Fecha_Siembra', 'desc')->get();
        $lotesDisponibles = $siembrasDisponibles->groupBy(function ($item) {
            return ($item->Fecha_Siembra ? $item->Fecha_Siembra->format('Y-m-d') : 'Sin Fecha') . '_' . $item->ID_Variedad . '_' . ($item->ubicacion ? $item->ubicacion->Bloque : 'Sin Bloque');
        });

        // Generar Datos Comparativos
        $datosAnalisis = $this->obtenerDatosComparativaAvanzada($siembrasSeleccionadasIds);

        // Si el usuario presionó el botón de EXCEL
        if ($accion === 'excel') {
            if ($datosAnalisis['consolidado']->isEmpty()) return back()->withErrors('Debes seleccionar al menos una siembra.');
            return $this->generarExcelComparativa($datosAnalisis);
        }

        // Si el usuario presionó el botón de PDF
        if ($accion === 'pdf') {
            if ($datosAnalisis['consolidado']->isEmpty()) return back()->withErrors('Debes seleccionar al menos una siembra.');
            return view('agronomia.reporte_pdf_comparativa', $datosAnalisis);
        }

        // Si es la vista WEB normal
        return view('agronomia.consolidado_bloque', array_merge([
            'bloques' => $bloques, 'variedades' => $variedades, 'aniosDisponibles' => $aniosDisponibles,
            'bloqueSel' => $bloqueSel, 'variedadSel' => $variedadSel, 'anioSel' => $anioSel,
            'lotesDisponibles' => $lotesDisponibles, 'siembrasSeleccionadasIds' => $siembrasSeleccionadasIds,
        ], $datosAnalisis));
    }

    private function obtenerDatosComparativaAvanzada($siembrasSeleccionadasIds)
    {
        $consolidado = collect(); $detalleCamas = collect();
        $chartLabels = []; $chartDatasets = []; $maxSemanasRelativasGlobal = 0;

        if (!empty($siembrasSeleccionadasIds)) {
            $siembrasElegidas = \App\Models\Siembra::whereIn('ID_Siembra', $siembrasSeleccionadasIds)->with(['ubicacion', 'variedad'])->get();
            $gruposElegidos = $siembrasElegidas->groupBy(function ($item) {
                return ($item->Fecha_Siembra ? $item->Fecha_Siembra->format('Y-m-d') : 'SF') . '_' . $item->ID_Variedad . '_' . ($item->ubicacion ? $item->ubicacion->Bloque : 'SB');
            });

            foreach ($gruposElegidos as $grupo) {
                $primera = $grupo->first();
                $idsUbicaciones = $grupo->pluck('ID_Ubicacion')->unique();
                
                foreach ($grupo as $s) {
                    $tallosCama = \App\Models\Produccion::where('ID_Ubicacion', $s->ID_Ubicacion)->sum('Total');
                    $detalleCamas->push((object) [
                        'bloque' => $s->ubicacion ? $s->ubicacion->Bloque : 'N/A',
                        'nave' => $s->ubicacion ? $s->ubicacion->Nave : 'N/A', 'cama' => $s->ubicacion ? $s->ubicacion->Cama : 'N/A',
                        'variedad' => $s->variedad ? $s->variedad->Nombre_Variedad : 'N/A',
                        'fecha_siembra' => $s->Fecha_Siembra ? $s->Fecha_Siembra->format('d/m/Y') : 'N/A',
                        'plantas' => $s->Cantidad_Plantas, 'total_tallos' => $tallosCama, 'estado' => $s->Estado_Siembra ?? 'SEMBRADA',
                    ]);
                }

                $producciones = \App\Models\Produccion::whereIn('ID_Ubicacion', $idsUbicaciones)->selectRaw('Anio, Semana, SUM(Total) as TotalSemana')->groupBy('Anio', 'Semana')->orderBy('Anio')->orderBy('Semana')->get();
                $totalTallosLote = 0; $prodSemanal = []; $maxSemanaDeEsteLote = 0;

                if ($producciones->count() > 0) {
                    $anioInicio = $producciones->first()->Anio; $semanaInicio = $producciones->first()->Semana;
                    foreach ($producciones as $p) {
                        $totalTallosLote += $p->TotalSemana;
                        $semanaRelativa = ($p->Semana - $semanaInicio) + (($p->Anio - $anioInicio) * 52) + 1;
                        $prodSemanal['Semana ' . $semanaRelativa] = ($prodSemanal['Semana ' . $semanaRelativa] ?? 0) + $p->TotalSemana;
                        $maxSemanaDeEsteLote = $semanaRelativa;
                        if ($semanaRelativa > $maxSemanasRelativasGlobal) $maxSemanasRelativasGlobal = $semanaRelativa;
                    }
                }

                $consolidado->push((object) [
                    'fecha_siembra' => $primera->Fecha_Siembra ? $primera->Fecha_Siembra->format('d/m/Y') : 'N/A',
                    'variedad' => $primera->variedad ? $primera->variedad->Nombre_Variedad : 'N/A',
                    'color' => $primera->variedad ? $primera->variedad->Color : 'N/A',
                    'bloque' => $primera->ubicacion ? $primera->ubicacion->Bloque : 'Varios',
                    'numero_camas' => $idsUbicaciones->count(), 'numero_plantas' => $grupo->sum('Cantidad_Plantas'),
                    'ciclo' => $primera->Ciclo_Actual ?? 1, 'total_tallos' => $totalTallosLote,
                    'produccion_semanal' => $prodSemanal, 'max_semana_lote' => $maxSemanaDeEsteLote
                ]);
            }

            for ($i = 1; $i <= $maxSemanasRelativasGlobal; $i++) { $chartLabels[] = 'Semana ' . $i; }

            foreach ($consolidado as $c) {
                $data = [];
                for ($i = 1; $i <= $maxSemanasRelativasGlobal; $i++) {
                    $data[] = $i > $c->max_semana_lote ? null : ($c->produccion_semanal['Semana ' . $i] ?? 0);
                }
                if ($c->total_tallos > 0) {
                    $chartDatasets[] = [ 'label' => "{$c->fecha_siembra} - {$c->variedad} ({$c->bloque})", 'data'  => $data ];
                }
            }
        }

        return compact('consolidado', 'detalleCamas', 'chartLabels', 'chartDatasets', 'maxSemanasRelativasGlobal');
    }

    private function generarExcelComparativa($datos)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comparativa Siembras');

        // Título del documento
        $sheet->setCellValue('A1', 'REPORTE COMPARATIVO DE RENDIMIENTO DE SIEMBRAS');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '198754']]
        ]);

        // Cabeceras: Info del Lote + Métrica + Semanas Relativas
        $cabeceras = ['F. Siembra', 'Variedad', 'Color', 'Bloque', 'Camas', 'Plantas', 'Ciclo', 'Total Tallos', 'Métrica'];
        for ($i = 1; $i <= $datos['maxSemanasRelativasGlobal']; $i++) { 
            $cabeceras[] = 'Sem ' . $i; 
        }

        $col = 'A';
        foreach ($cabeceras as $c) {
            $sheet->setCellValue($col . '3', $c);
            $col++;
        }

        // Estilizado de Cabeceras
        $sheet->getStyle('A3:' . $sheet->getHighestColumn() . '3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);

        // Filas con Datos (2 filas por cada lote)
        $fila = 4;
        foreach ($datos['consolidado'] as $c) {
            $cantPlantas = $c->numero_plantas;

            // --- FILA 1: TALLOS TOTALES ---
            $sheet->setCellValue('A' . $fila, $c->fecha_siembra);
            $sheet->setCellValue('B' . $fila, $c->variedad);
            $sheet->setCellValue('C' . $fila, $c->color);
            $sheet->setCellValue('D' . $fila, $c->bloque);
            $sheet->setCellValue('E' . $fila, $c->numero_camas);
            $sheet->setCellValue('F' . $fila, $cantPlantas);
            $sheet->setCellValue('G' . $fila, 'C.' . $c->ciclo);
            $sheet->setCellValue('H' . $fila, $c->total_tallos);
            $sheet->setCellValue('I' . $fila, 'Tallos');

            // --- FILA 2: RENDIMIENTO (t/planta) ---
            $filaRend = $fila + 1;
            $sheet->setCellValue('A' . $filaRend, $c->fecha_siembra);
            $sheet->setCellValue('B' . $filaRend, $c->variedad);
            $sheet->setCellValue('C' . $filaRend, $c->color);
            $sheet->setCellValue('D' . $filaRend, $c->bloque);
            $sheet->setCellValue('E' . $filaRend, $c->numero_camas);
            $sheet->setCellValue('F' . $filaRend, $cantPlantas);
            $sheet->setCellValue('G' . $filaRend, 'C.' . $c->ciclo);
            $sheet->setCellValue('H' . $filaRend, $cantPlantas > 0 ? round($c->total_tallos / $cantPlantas, 2) : 0);
            $sheet->setCellValue('I' . $filaRend, 't/planta');

            // Agrupar filas A-G para mejorar lectura
            $sheet->mergeCells("A{$fila}:A{$filaRend}");
            $sheet->mergeCells("B{$fila}:B{$filaRend}");
            $sheet->mergeCells("C{$fila}:C{$filaRend}");
            $sheet->mergeCells("D{$fila}:D{$filaRend}");
            $sheet->mergeCells("E{$fila}:E{$filaRend}");
            $sheet->mergeCells("F{$fila}:F{$filaRend}");
            $sheet->mergeCells("G{$fila}:G{$filaRend}");

            // Llenar columnas semanales
            $colIndex = 10; // Columna J
            for ($i = 1; $i <= $datos['maxSemanasRelativasGlobal']; $i++) {
                $semKey = 'Semana ' . $i;
                $tallosSem = $i > $c->max_semana_lote ? null : ($c->produccion_semanal[$semKey] ?? 0);

                // Celda de Tallos
                $valTallos = $tallosSem !== null ? $tallosSem : '-';
                $sheet->setCellValueByColumnAndRow($colIndex, $fila, $valTallos);

                // Celda de Rendimiento t/planta
                $valRend = ($tallosSem !== null && $cantPlantas > 0) ? round($tallosSem / $cantPlantas, 2) : '-';
                $sheet->setCellValueByColumnAndRow($colIndex, $filaRend, $valRend);

                $colIndex++;
            }

            // Fondo suave verde para la fila de t/planta
            $sheet->getStyle("I{$filaRend}:" . $sheet->getHighestColumn() . $filaRend)->applyFromArray([
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
                'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']]
            ]);

            // Formato de bordes
            $sheet->getStyle("A{$fila}:" . $sheet->getHighestColumn() . $filaRend)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('E0E0E0');

            $fila += 2;
        }

        // Autoajustar ancho de columnas
        foreach (range('A', $sheet->getHighestColumn()) as $column) { 
            $sheet->getColumnDimension($column)->setAutoSize(true); 
        }

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "Comparativa_Siembras.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}