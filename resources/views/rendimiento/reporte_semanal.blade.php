@extends('layouts.app')

@section('title', 'Reporte Semanal')

@push('styles')
<style>
    .chart-container { position: relative; width: 100%; margin-bottom: 1.5rem; min-height: 350px; }
    .chart-card { background: var(--secondary-background-color); border: 1px solid rgba(128,128,128,.15); border-radius: 12px; padding: 1.25rem; }
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

@if ($semanaSel && $fechaRef)
    <p class="text-muted">📊 Rendimiento de la semana <strong>{{ $semanaSel }}</strong> (inicia el lunes {{ $fechaRef->format('d/m/Y') }})</p>

    @php
        $labores = ['DESHOJE', 'CORTE LIMONIUM', 'CORTE STATICE'];
        $u = \App\Http\Controllers\RendimientoController::UMBRALES;
    @endphp

    @foreach ($labores as $lab)
        @php
            $data = $detalle->where('Tipo_Labor', $lab);
            if ($data->isEmpty()) { continue; }
            $nombres = $data->pluck('Nombre_Colaborador')->values();
            $valores = $data->pluck('Rendimiento_Promedio')->values();
            $colores = $data->pluck('Color')->values();
            $umbral = $u[$lab] ?? ['verde' => 0, 'naranja' => 0];
        @endphp
        <div class="chart-card mb-4">
            <h5 class="mb-3">{{ $lab }} — Semana {{ $semanaSel }}
                <small class="text-muted">🟢≥{{ $umbral['verde'] }} | 🟠≥{{ $umbral['naranja'] }} | 🔴<{{ $umbral['naranja'] }}</small>
            </h5>
            <div class="chart-container">
                <canvas id="semana-{{ \Illuminate\Support\Str::slug($lab) }}"></canvas>
            </div>
        </div>

        <div class="card card-dashboard p-3 mb-4">
            <h6 class="mb-3">📊 Tabla — {{ $lab }}</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Colaborador</th><th>Total Cantidad</th><th>Total Horas</th><th>Rend. Promedio</th><th>Registros</th></tr></thead>
                    <tbody>
                        @foreach ($data as $d)
                            <tr>
                                <td>{{ $d['Nombre_Colaborador'] }}</td>
                                <td>{{ $d['Total_Cantidad'] }}</td>
                                <td>{{ $d['Total_Horas'] }} hrs</td>
                                <td><span style="color:{{ $d['Color'] }};font-weight:bold">{{ $d['Rendimiento_Promedio'] }}</span></td>
                                <td>{{ $d['Registros'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if ($detalle->isNotEmpty())
        <div class="card card-dashboard p-4">
            <h5 class="mb-3">📋 Detalle Completo de la Semana</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead><tr><th>Colaborador</th><th>Labor</th><th>Total Cantidad</th><th>Total Horas</th><th>Rend. Promedio</th><th>Registros</th></tr></thead>
                    <tbody>
                        @foreach ($detalle as $d)
                            <tr>
                                <td>{{ $d['Nombre_Colaborador'] }}</td>
                                <td>{{ $d['Tipo_Labor'] }}</td>
                                <td>{{ $d['Total_Cantidad'] }}</td>
                                <td>{{ $d['Total_Horas'] }} hrs</td>
                                <td><span style="color:{{ $d['Color'] ?? '#333' }};font-weight:bold">{{ $d['Rendimiento_Promedio'] }}</span></td>
                                <td>{{ $d['Registros'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@elseif ($semanaSel)
    <div class="alert alert-info">No hay registros para la semana seleccionada.</div>
@else
    <div class="alert alert-info">Selecciona una semana para ver el reporte.</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
// Helper: crea un dataset de linea horizontal para marcar un umbral
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

    @if ($semanaSel && $detalle->isNotEmpty())
        @foreach ($labores ?? [] as $lab)
            @php
                $data = $detalle->where('Tipo_Labor', $lab);
                $umbral = $u[$lab] ?? ['verde' => 0, 'naranja' => 0];
                $numb = $data->count();
            @endphp
            @if ($data->isNotEmpty())
            (function () {
                const ctx = document.getElementById('semana-{{ \Illuminate\Support\Str::slug($lab) }}');
                if (!ctx) return;
                const n = {{ $numb }};
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($data->pluck('Nombre_Colaborador')->values()) !!},
                        datasets: [{
                            label: '{{ $lab }} (u/hr)',
                            data: {!! json_encode($data->pluck('Rendimiento_Promedio')->values()) !!},
                            backgroundColor: {!! json_encode($data->pluck('Color')->values()) !!},
                            borderRadius: 6,
                        },
// Linea del objetivo (verde)
                        lineaUmbral({{ $umbral['verde'] }}, '#2e7d32', 'OBJETIVO {{ $umbral['verde'] }}', n),
                        // Linea del minimo (naranja)
                        lineaUmbral({{ $umbral['naranja'] }}, '#f57c00', 'MINIMO {{ $umbral['naranja'] }}', n)
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ctx.dataset.label === '{{ $lab }} (u/hr)' ? ctx.raw + ' u/hr' : ctx.dataset.label
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(128,128,128,.15)' }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            })();
            @endif
        @endforeach
    @endif
});
</script>
@endpush
