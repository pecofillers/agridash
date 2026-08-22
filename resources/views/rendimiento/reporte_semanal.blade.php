@extends('layouts.app')

@section('title', 'Reporte Semanal')

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
<h2 class="page-title mb-4">📅 REPORTE SEMANAL DE RENDIMIENTO</h2>

<div class="card card-dashboard p-4 mb-4">
    <form method="GET" action="{{ route('rendimiento.reporteSemanal') }}" class="row g-3 align-items-end">    
        <div class="col-md-5">
            <label class="form-label">📅 Selecciona la semana</label>
            <select name="semana" class="form-select" onchange="this.form.submit()">
                <option value="">-- Seleccionar --</option>
                @foreach ($semanas as $sem)
                    <option value="{{ $sem }}" {{ $semanaSel == $sem ? 'selected' : '' }}>{{ $sem }}</option>
                @endforeach
            </select>
        </div>

        @if (in_array($rolNombre ?? '', ['ADMIN', 'SUPERADMIN']))
        <div class="col-md-5">
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

    @if(isset($filtroGrupo))
        <div class="mt-3 pt-3 border-top text-muted small">
            👤 Supervisor a cargo: <strong class="text-dark">{{ $supervisorNombre ?? 'No asignado' }}</strong>
        </div>
    @endif
</div>

@if ($semanaSel && isset($detalle) && count($detalle) > 0)
    @php
        // Agrupamos el detalle por Labor para mostrar su respectiva gráfica y su propia tabla de datos
        $graficasPorLabor = collect($detalle)->groupBy('Tipo_Labor');
        $umbralMap = collect($laborCatalogo ?? [])->mapWithKeys(function ($item) {
            return [$item->Nombre_Labor => ['verde' => (float) $item->Umbral_Verde, 'naranja' => (float) $item->Umbral_Naranja]];
        });
    @endphp

    @foreach ($graficasPorLabor as $lab => $dataLabor)
        @php
            $uAnno = $umbralMap[$lab] ?? ['verde' => 0, 'naranja' => 0];
            
            // Calculamos el total de variantes y producción de esta labor para la semana
            $totalesVariantesLabor = [];
            $totalCantidadLabor = 0;
            $totalHorasLabor = 0;
            $sumaRendimientos = 0;
            $contadorRendimientos = 0;

            foreach($dataLabor as $d) {
                $totalCantidadLabor += $d['Total_Cantidad'];

                if(isset($d['Total_Horas'])) {
                    $totalHorasLabor += $d['Total_Horas'];
                }

                if(!empty($d['Variantes'])) {
                    foreach($d['Variantes'] as $vName => $vCant) {
                        if(!isset($totalesVariantesLabor[$vName])) $totalesVariantesLabor[$vName] = 0;
                        $totalesVariantesLabor[$vName] += $vCant;
                    }
                }
            }

            $promedioGeneralLabor = $totalHorasLabor > 0 ? ($totalCantidadLabor / $totalHorasLabor) : 0;

            // Divisor dinámico de ramos (Base 9 para Limonium, Base 10 para Statice u otras)
            $divisorRamos = 10; 
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
            <h5 class="mb-3">📊 Rendimiento: {{ $lab }}
                <small class="text-muted">Semana {{ $semanaSel }}</small>
            </h5>

            @if(count($totalesVariantesLabor) > 0)
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 bg-light border rounded">
                    <strong class="text-dark small m-0">🌸 ACUMULADO SEMANAL:</strong>
                    @foreach($totalesVariantesLabor as $name => $total)
                        <span class="badge border" style="background-color: #fff; color: #333; border-color: #ccc !important; font-size: 0.85rem;">
                            {{ $name }}: <span style="color: #2e7d32; font-weight: bold;">{{ number_format($total, 2) }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
            
            <div class="chart-container">
                <canvas id="chart-{{ \Illuminate\Support\Str::slug($lab) }}"></canvas>
            </div>
            
            <div class="legend-bar mb-3">
                <span><span class="dot" style="background:#2e7d32"></span>Óptimo (≥{{ $uAnno['verde'] }})</span>
                <span><span class="dot" style="background:#f57c00"></span>Medio (≥{{ $uAnno['naranja'] }})</span>
                <span><span class="dot" style="background:#d32f2f"></span>Bajo (<{{ $uAnno['naranja'] }})</span>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-hover table-sm align-middle bg-white rounded">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Días Trabajados</th>
                            <th>Total Horas</th>
                            <th>Producción Total</th>
                            <th>Rendimiento Promedio</th>
                            <th>Umbrales de Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataLabor as $d)
                            <tr>
                                <td><strong>{{ $d['Nombre_Usuario'] }}</strong></td>
                                <td>{{ $d['Registros'] }}</td>
                                <td>{{ number_format($d['Total_Horas'], 2) }}</td>
                                <td>
                                    <strong>{{ number_format($d['Total_Cantidad'], 2) }}</strong>
                                    @if(!empty($d['Variantes']))
                                        <div class="mt-1">
                                            @foreach($d['Variantes'] as $vName => $vCant)
                                                <span class="badge bg-light text-dark border me-1" style="font-size: 0.7rem;">
                                                    {{ $vName }}: {{ number_format($vCant, 0) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge" style="background-color: {{ $d['Color'] }}; font-size:0.9rem;">
                                        {{ number_format($d['Rendimiento_Promedio'], 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-umbral bg-success text-white" title="Meta Verde">🟢 {{ $uAnno['verde'] }}</span>
                                    <span class="badge-umbral bg-warning text-dark" title="Mínimo Naranja">🟠 {{ $uAnno['naranja'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3" class="text-end">TOTALES / PROMEDIO GENERAL SEMANAL:</td>
                            <td>{{ number_format($totalCantidadLabor, 2) }} unidades</td>
                            <td>
                                <span class="badge bg-primary" style="font-size:0.9rem;">
                                    {{ number_format($promedioGeneralLabor, 2) }} u/hr
                                </span>
                            </td>
                            <td class="text-info">
                                @if($aplicaRamos)
                                    🌸 {{ number_format($totalRamosLabor, 0) }} Ramos <small class="text-muted">(Base {{ $divisorRamos }})</small>
                                @else
                                    <span class="text-muted small">N/A (No aplica)</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="#" onclick="window.print();" class="btn btn-outline-danger btn-sm">
            🖨️ Exportar / Imprimir PDF Semanal
        </a>
        <button onclick="exportarReporteSemanalExcel()" class="btn btn-success btn-sm">
            📊 Descargar Excel Semanal Completo
        </button>
    </div>

@elseif($semanaSel)
    <div class="alert alert-info">No hay datos registrados para la semana seleccionada.</div>
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

    @if (isset($graficasPorLabor))
        @foreach ($graficasPorLabor as $lab => $dataLabor)
            @php
                $uAnno = $umbralMap[$lab] ?? ['verde' => 0, 'naranja' => 0];
                $numb = count($dataLabor);
            @endphp
            (function () {
                const ctx = document.getElementById('chart-{{ \Illuminate\Support\Str::slug($lab) }}');
                if (!ctx) return;
                const n = {{ $numb }};
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(collect($dataLabor)->pluck('Nombre_Usuario')->values()) !!},
                        datasets: [{
                            label: '{{ $lab }} (u/hr)',
                            data: {!! json_encode(collect($dataLabor)->pluck('Rendimiento_Promedio')->values()) !!},
                            backgroundColor: {!! json_encode(collect($dataLabor)->pluck('Color')->values()) !!},
                            borderRadius: 6,
                        },
                        lineaUmbral({{ $uAnno['verde'] }}, '#2e7d32', 'OBJETIVO {{ $uAnno['verde'] }}', n),
                        lineaUmbral({{ $uAnno['naranja'] }}, '#f57c00', 'MÍNIMO {{ $uAnno['naranja'] }}', n)
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(128,128,128,.15)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            })();
        @endforeach
    @endif
});

function exportarReporteSemanalExcel() {
    let tablas = document.querySelectorAll('.table');
    if (tablas.length === 0) {
        alert('No hay datos para exportar.');
        return;
    }
    
    let htmlTablas = '';
    tablas.forEach((t, index) => {
        htmlTablas += `<br><h4>Tabla ${index + 1}</h4>` + t.outerHTML;
    });

    let html = `
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte Semanal de Rendimiento</title>
            <style>
                table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
                th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px; }
                th { background-color: #2e7d32; color: #fff; }
            </style>
        </head>
        <body>
            <h3>Reporte Consolidado Semanal de Rendimiento</h3>
            <p>Semana seleccionada: {{ $semanaSel ?? '' }}</p>
            ${htmlTablas}
        </body>
        </html>
    `;

    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = `Reporte_Semanal_{{ $semanaSel ?? date('Y-m-d') }}.xls`;
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endpush