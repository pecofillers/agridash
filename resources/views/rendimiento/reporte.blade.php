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
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
        @endif
    </form>
    <div class="mt-3 text-muted small">
        Rango aplicado: <strong>{{ $fechaInicioStr }}</strong> a <strong>{{ $fechaFinStr }}</strong>
    </div>
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
                </table>
            </div>
        </div>
    @endforeach

    <div class="card card-dashboard p-4">
        <h5 class="mb-3">📋 Detalle Consolidado de Registros</h5>
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
                            <td>{{ $r->labor->Nombre_Labor ?? 'N/D' }}</td>
                            <td>{{ $r->Hora_Inicio }}</td>
                            <td>{{ $r->Hora_Fin }}</td>
                            <td>{{ $r->Horas_Trabajadas }}</td>
                            <td>{{ $r->Cantidad }}</td>
                            <td><strong>{{ $r->Rendimiento_Hora }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
</script>
@endpush