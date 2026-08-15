@extends('layouts.app')

@section('title', 'Reporte y Graficas')

@push('styles')
<style>
    .chart-container { position: relative; width: 100%; margin-bottom: 1.5rem; min-height: 350px; }
    .chart-card { background: var(--secondary-background-color); border: 1px solid rgba(128,128,128,.15); border-radius: 12px; padding: 1.25rem; }
    .legend-bar { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .85rem; }
    .dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
    .badge-umbral { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px; }
</style>
@endpush

@section('content')
<h2 class="page-title mb-4">📊 CONSOLIDADO DE RENDIMIENTO Y METRICAS</h2>

@php
    $opcionesPeriodo = [
        'Hoy' => 'Hoy', 
        'Esta Semana' => 'Esta Semana', 
        'Este Mes' => 'Este Mes', 
        'Personalizado' => 'Personalizado'
    ];
@endphp

<div class="card card-dashboard p-4 mb-4">
    <form method="GET" action="{{ route('rendimiento.reporte') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Período</label>
            <select name="periodo" class="form-select" onchange="this.form.submit()">
                @foreach ($opcionesPeriodo as $val => $lab)
                    <option value="{{ $val }}" {{ $filtroTiempo == $val ? 'selected' : '' }}>{{ $lab }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Labor</label>
            <select name="labor" class="form-select" onchange="this.form.submit()">
                <option value="TODAS" {{ $filtroLabor == 'TODAS' ? 'selected' : '' }}>TODAS</option>
                @foreach ($catalogoLabores as $lab)
                    <option value="{{ $lab->Nombre_Labor }}" {{ $filtroLabor == $lab->Nombre_Labor ? 'selected' : '' }}>{{ $lab->Nombre_Labor }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Persona</label>
            <select name="persona" class="form-select" onchange="this.form.submit()">
                <option value="TODOS" {{ $filtroPersona == 'TODOS' ? 'selected' : '' }}>TODOS</option>
                @foreach ($usuariosFiltro as $c)
                    <option value="{{ $c }}" {{ $filtroPersona == $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        @if ($filtroTiempo === 'Personalizado')
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" class="form-control" value="{{ request('desde', \Carbon\Carbon::parse($fechaInicioStr)->toDateString()) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" class="form-control" value="{{ request('hasta', $fechaFinStr) }}">
        </div>
        @endif
        <!-- Filtro de Grupo adicional para Administradores // Sirve para filtrar por grupo -->
        @if (in_array($rolNombre ?? '', ['ADMIN', 'SUPERADMIN']))
        <div class="col-md-3">
            <label class="form-label">Grupo de Trabajo</label>
            <select name="grupo" class="form-select" onchange="this.form.submit()">
                <option value="TODOS" {{ ($filtroGrupo ?? 'TODOS') == 'TODOS' ? 'selected' : '' }}>TODOS LOS GRUPOS</option>
                @foreach ($gruposFiltro ?? [] as $g)
                    <option value="{{ $g->ID_Grupo }}" {{ ($filtroGrupo ?? '') == $g->ID_Grupo ? 'selected' : '' }}>{{ $g->Nombre_Grupo }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
    <div class="mt-3 text-muted small">
        Rango aplicado: <strong>{{ $fechaInicioStr }}</strong> a <strong>{{ $fechaFinStr }}</strong>
    </div>

    @if(isset($filtroGrupo))
        <div class="mt-3 pt-3 border-top text-muted small">
            👤 Supervisor a cargo: <strong class="text-dark">{{ $supervisorNombre ?? 'No asignado' }}</strong>
        </div>
    @endif
</div>

@if ($registros->isNotEmpty())
    @if ($filtroPersona !== 'TODOS' && $historicoDiario->isNotEmpty())
        <div class="chart-card mb-4">
            <h5 class="mb-3">📈 Histórico Diario — {{ $filtroPersona }}
                <small class="text-muted">Evolución del rendimiento por día (línea roja = meta)</small>
            </h5>
            <div class="chart-container">
                <canvas id="chart-historico"></canvas>
            </div>
        </div>
    @endif

    @php
        $labores = $filtroLabor === 'TODAS' ? $catalogoLabores->pluck('Nombre_Labor')->values()->all() : [$filtroLabor];
        $umbralMap = $catalogoLabores->mapWithKeys(function ($item) {
            return [$item->Nombre_Labor => ['verde' => (float) $item->Umbral_Verde, 'naranja' => (float) $item->Umbral_Naranja]];
        });
    @endphp

    @foreach ($labores as $lab)
        @php
            $filtrados = $registros->filter(function($r) use ($lab) {
                return ($r->labor->Nombre_Labor ?? '') === $lab;
            });
            if ($filtrados->isEmpty()) { continue; }

            $agrupado = $filtrados->groupBy(function($r) {
                return trim(($r->usuario->Nombre ?? '') . ' ' . ($r->usuario->Apellidos ?? '')) ?: ($r->usuario->Username ?? 'N/D');
            })->map(function ($regs, $nombre) use ($lab, $umbralMap) {
                $rend = $regs->avg('Rendimiento_Hora');
                $u = $umbralMap[$lab] ?? ['verde' => 0, 'naranja' => 0];
                $color = $rend >= $u['verde'] ? '#2e7d32' : ($rend >= $u['naranja'] ? '#f57c00' : '#d32f2f');
                return [
                    'nombre' => $nombre,
                    'rend' => round($rend, 1),
                    'cantidad' => round($regs->sum('Cantidad'), 1),
                    'color' => $color,
                ];
            })->values();
            
            $uAnno = $umbralMap[$lab] ?? ['verde' => 0, 'naranja' => 0];

            // Cálculo de totales y ramos específicos por esta labor ---
            $totalCantidadLabor = $filtrados->sum('Cantidad');
            $totalHorasLabor = $filtrados->sum('Horas_Trabajadas'); // Obtenemos el total de horas
            
            // Cálculo real del promedio general: Cantidad Total / Horas Totales
            $promedioGeneralLabor = $totalHorasLabor > 0 ? ($totalCantidadLabor / $totalHorasLabor) : 0;
            
            // Definir divisor de ramos según la labor
            $divisorRamos = 10; // Valor por defecto
            $nombreLabUpper = strtoupper($lab);

            $aplicaRamos = !str_contains($nombreLabUpper, 'DESHOJE');
            if (str_contains($nombreLabUpper, 'LIMONIUM')) {
                $divisorRamos = 9;
            } elseif (str_contains($nombreLabUpper, 'STATICE')) {
                $divisorRamos = 10;
            }
            $totalRamosLabor = round($totalCantidadLabor / $divisorRamos, 0);
        @endphp

        <div class="chart-card mb-4">
            <h5 class="mb-3">🚦 Semáforo — {{ $lab }}
                <small class="text-muted">🟢≥{{ $uAnno['verde'] }} | 🟠≥{{ $uAnno['naranja'] }} | 🔴<{{ $uAnno['naranja'] }}</small>
            </h5>
            
            <div class="chart-container">
                <canvas id="chart-{{ \Illuminate\Support\Str::slug($lab) }}"></canvas>
            </div>
            
            <div class="legend-bar mb-3">
                <span><span class="dot" style="background:#2e7d32"></span>Óptimo</span>
                <span><span class="dot" style="background:#f57c00"></span>Medio</span>
                <span><span class="dot" style="background:#d32f2f"></span>Bajo</span>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-hover table-sm align-middle bg-white rounded">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rendimiento Promedio</th>
                            <th>Producción Acumulada</th>
                            <th>Umbrales de Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agrupado as $item)
                            <tr>
                                <td><strong>{{ $item['nombre'] }}</strong></td>
                                <td>
                                    <span class="badge" style="background-color: {{ $item['color'] }}; font-size:0.9rem;">
                                        {{ number_format($item['rend'], 2) }}
                                    </span>
                                </td>
                                <td>{{ number_format($item['cantidad'], 2) }}</td>
                                <td>
                                    <span class="badge-umbral bg-success text-white">🟢 {{ $uAnno['verde'] }}</span>
                                    <span class="badge-umbral bg-warning text-dark">🟠 {{ $uAnno['naranja'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <!-- NUEVA FILA DE TOTALES AL PIE DE CADA TABLA -->
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="text-end">TOTALES / PROMEDIO GENERAL:</td>
                            <td>
                                <span class="badge bg-primary" style="font-size:0.9rem;">
                                    {{ number_format($promedioGeneralLabor, 2) }} u/hr
                                </span>
                            </td>
                            <td>{{ number_format($totalCantidadLabor, 2) }} unidades</td>
                            <td class="text-info">
                                @if($aplicaRamos)
                                    🌸 {{ number_format($totalRamosLabor, 0) }} Ramos <small class="text-muted">(Base {{ $divisorRamos }})</small>
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    <div class="card card-dashboard p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="m-0">📋 Detalle Consolidado de Registros</h5>
            
            @php
                $totalesVariantes = [];
                foreach($registros as $r) {
                    if($r->detalles && $r->detalles->count() > 0) {
                        foreach($r->detalles as $det) {
                            $varName = $det->Nombre_Variante;
                            // Ignoramos la etiqueta 'General' de las labores simples
                            if($varName !== 'General') {
                                if(!isset($totalesVariantes[$varName])) {
                                    $totalesVariantes[$varName] = 0;
                                }
                                $totalesVariantes[$varName] += $det->Cantidad;
                            }
                        }
                    }
                }
            @endphp

            @if(count($totalesVariantes) > 0)
                <div class="d-flex flex-wrap gap-2 align-items-center p-2 bg-light border rounded">
                    <strong class="text-dark small m-0">🌸 TOTALES POR VARIANTE:</strong>
                    @foreach($totalesVariantes as $name => $total)
                        <span class="badge border" style="background-color: #fff; color: #333; border-color: #ccc !important; font-size: 0.85rem;">
                            {{ $name }}: <span style="color: #2e7d32; font-weight: bold;">{{ number_format($total, 2) }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead>
                    <tr><th>Fecha</th><th>Usuario</th><th>Labor</th><th>Inicio</th><th>Fin</th><th>Horas</th><th>Cantidad</th><th>Rend/Hora</th></tr>
                </thead>
                <tbody>
                    @foreach ($registros as $r)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($r->Fecha)->format('d/m/Y') }}</td>
                            <td>{{ trim(($r->usuario->Nombre ?? '') . ' ' . ($r->usuario->Apellidos ?? '')) ?: ($r->usuario->Username ?? 'N/D') }}</td>
                            <td><span class="badge bg-secondary">{{ $r->labor->Nombre_Labor ?? 'N/D' }}</span></td>
                            <td>{{ $r->Hora_Inicio }}</td>
                            <td>{{ $r->Hora_Fin }}</td>
                            <td>{{ $r->Horas_Trabajadas }}</td>
                            <td>
                                <strong>{{ $r->Cantidad }}</strong>
                                @if($r->detalles && $r->detalles->count() > 0)
                                    <div class="mt-1">
                                        @foreach($r->detalles as $det)
                                            @if($det->Nombre_Variante !== 'General')
                                                <span class="badge bg-light text-dark border me-1" style="font-size: 0.7rem;">
                                                    {{ $det->Nombre_Variante }}: {{ number_format($det->Cantidad, 0) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td><strong>{{ $r->Rendimiento_Hora }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @php
        // Cálculo dinámico de ramos por cada labor específica
        $totalRamosPorLabor = [];
        
        // Paso 1: Sumar todas las unidades por labor
        foreach($registros as $r) {
            $nombreLabor = strtoupper($r->labor->Nombre_Labor ?? 'OTRA');
            // Si la labor es Deshoje, saltamos este cálculo y no creamos tarjeta
            if (str_contains($nombreLabor, 'DESHOJE')) {
                continue; 
            }
            
            $cantidad = $r->Cantidad;
            
            // Definimos el divisor según la labor
            $divisor = 10; 
            if (str_contains($nombreLabor, 'LIMONIUM')) {
                $divisor = 9;
            } elseif (str_contains($nombreLabor, 'STATICE')) {
                $divisor = 10;
            }

            if (!isset($totalRamosPorLabor[$nombreLabor])) {
                $totalRamosPorLabor[$nombreLabor] = [
                    'cantidad' => 0, 
                    'ramos' => 0, 
                    'unidad' => $r->labor->Unidad_Medida ?? 'Unidades',
                    'divisor' => $divisor // Guardamos el divisor para usarlo luego
                ];
            }
            // Solo acumulamos la cantidad física, sin dividir ni redondear aún
            $totalRamosPorLabor[$nombreLabor]['cantidad'] += $cantidad;
        }

        // Paso 2: Calcular los ramos dividiendo y redondeando el TOTAL consolidado
        foreach($totalRamosPorLabor as $labNombre => $datos) {
            $totalRamosPorLabor[$labNombre]['ramos'] = round($datos['cantidad'] / $datos['divisor'], 0);
        }
    @endphp

    <!-- Tarjetas de Resumen por Labor y Ramos Estimados -->
    <div class="row mb-4">
        @foreach($totalRamosPorLabor as $labNombre => $info)
            <div class="col-md-4 mb-3">
                <div class="card p-3 bg-white border shadow-sm">
                    <span class="text-muted small">🌸 Ramos Estimados — <strong>{{ $labNombre }}</strong></span>
                    <h3 class="fw-bold text-info m-0">{{ number_format($info['ramos'], 0) }} <small class="fs-6 text-muted">ramos</small></h3>
                    <span class="text-muted small mt-1">Total unidades: {{ number_format($info['cantidad'], 2) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="#" onclick="window.print();" class="btn btn-outline-danger btn-sm">
            🖨️ Exportar / Imprimir PDF
        </a>
        <button onclick="exportarReporteCompletoExcel()" class="btn btn-success btn-sm">
            📊 Descargar Excel Completo
        </button>
    </div>
@else
    <div class="alert alert-info">No hay registros para los filtros seleccionados.</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function lineaUmbral(valor, color, etiqueta, n) {
        return {
            type: 'line',
            label: etiqueta,
            data: Array.from({ length: n }, () => valor),
            borderColor: color,
            borderWidth: 2,
            borderDash: [6, 4],
            pointRadius: 0,
            fill: false,
            tension: 0,
        };
    }

    @if ($registros->isNotEmpty())
        @if ($filtroPersona !== 'TODOS' && $historicoDiario->isNotEmpty())
            (function () {
                const ctx = document.getElementById('chart-historico');
                if (!ctx) return;
                const n = {!! json_encode($historicoDiario->count()) !!};
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($historicoDiario->pluck('fecha')->values()) !!},
                        datasets: [{
                            label: 'Rendimiento/Hora',
                            data: {!! json_encode($historicoDiario->pluck('rend')->values()) !!},
                            borderColor: '#1976d2',
                            backgroundColor: 'rgba(25,118,210,.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#1976d2',
                            tension: 0.3,
                            fill: true,
                        }, {
                            type: 'line',
                            label: 'META {{ $meta }}',
                            data: Array(n).fill({{ $meta }}),
                            borderColor: 'red',
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 0,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ctx.dataset.label === 'Rendimiento/Hora' ? ctx.raw + ' u/hr' : ctx.dataset.label
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(128,128,128,.15)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            })();
        @endif

        @foreach ($labores as $lab)
            @php
                $filtrados = $registros->filter(function($r) use ($lab) {
                    return ($r->labor->Nombre_Labor ?? '') === $lab;
                });
                $agrupado = $filtrados->groupBy(function($r) {
                    return trim(($r->usuario->Nombre ?? '') . ' ' . ($r->usuario->Apellidos ?? '')) ?: ($r->usuario->Username ?? 'N/D');
                })->map(function ($regs, $nombre) use ($lab, $umbralMap) {
                    $rend = $regs->avg('Rendimiento_Hora');
                    $u = $umbralMap[$lab] ?? ['verde' => 0, 'naranja' => 0];
                    $color = $rend >= $u['verde'] ? '#2e7d32' : ($rend >= $u['naranja'] ? '#f57c00' : '#d32f2f');
                    return ['nombre' => $nombre, 'rend' => round($rend, 1), 'color' => $color];
                })->values();
                $uAnno = $umbralMap[$lab] ?? ['verde' => 0, 'naranja' => 0];
                $numb = $agrupado->count();
            @endphp
            @if ($filtrados->isNotEmpty())
            (function () {
                const ctx = document.getElementById('chart-{{ \Illuminate\Support\Str::slug($lab) }}');
                if (!ctx) return;
                const n = {{ $numb }};
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($agrupado->pluck('nombre')) !!},
                        datasets: [{
                            label: '{{ $lab }} (u/hr)',
                            data: {!! json_encode($agrupado->pluck('rend')) !!},
                            backgroundColor: {!! json_encode($agrupado->pluck('color')) !!},
                            borderRadius: 6,
                        },
                        lineaUmbral({{ $uAnno['verde'] }}, '#2e7d32', 'OBJETIVO {{ $uAnno['verde'] }}', n),
                        lineaUmbral({{ $uAnno['naranja'] }}, '#f57c00', 'MÍNIMO {{ $uAnno['naranja'] }}', n)
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true },
                            tooltip: { callbacks: { label: (ctx) => ctx.dataset.label === '{{ $lab }} (u/hr)' ? ctx.raw + ' u/hr' : ctx.dataset.label } }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(128,128,128,.15)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            })();
            @endif
        @endforeach
    @endif
});

function exportarReporteCompletoExcel() {
    let tabla = document.querySelector('.table');
    if (!tabla) {
        alert('No hay datos para exportar.');
        return;
    }
    
    let html = `
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Consolidado de Rendimiento</title>
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px; }
                th { background-color: #2e7d32; color: #fff; }
            </style>
        </head>
        <body>
            <h3>Reporte Consolidado de Rendimiento</h3>
            <p>Rango de fechas: {{ $fechaInicioStr ?? '' }} al {{ $fechaFinStr ?? '' }} | Filtro Labor: {{ $filtroLabor ?? 'TODAS' }}</p>
            ${tabla.outerHTML}
        </body>
        </html>
    `;

    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = `Reporte_Rendimiento_{{ date('Y-m-d') }}.xls`;
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endpush