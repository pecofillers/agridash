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
        <div class="col-md-6">
            <label class="form-label">📅 Selecciona la semana</label>
            <select name="semana" class="form-select" onchange="this.form.submit()">
                <option value="">-- Seleccionar --</option>
                @foreach ($semanas as $sem)
                    <option value="{{ $sem }}" {{ $semanaSel == $sem ? 'selected' : '' }}>{{ $sem }}</option>
                @endforeach
            </select>
        </div>
    </form>
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
        @endphp

        <div class="chart-card mb-4">
            <h5 class="mb-3">📊 Rendimiento: {{ $lab }}
                <small class="text-muted">Semana {{ $semanaSel }}</small>
            </h5>
            
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
                                <td>{{ number_format($d['Total_Cantidad'], 2) }}</td>
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
                </table>
            </div>
        </div>
    @endforeach

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
</script>
@endpush