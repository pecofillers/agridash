@extends('layouts.app')

@section('title', 'Comparativa y Consolidado de Siembras')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <span class="p-2 bg-success text-white rounded-3 fs-5 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">📊</span>
                Comparativa y Consolidado de Siembras
            </h2>
            <p class="text-muted small mb-0 mt-1">Selecciona fechas de siembra específicas para comparar rendimiento entre temporadas</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4 border-start border-4 border-primary">
            <h6 class="fw-bold text-dark mb-3">1️⃣ Filtrar Opciones de Siembra</h6>
            <form method="GET" action="{{ route('agronomia.consolidado_bloque') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">📍 Bloque</label>
                        <select name="bloque" class="form-select border-2">
                            <option value="TODOS" {{ ($bloqueSel ?? 'TODOS') == 'TODOS' ? 'selected' : '' }}>-- Todos los Bloques --</option>
                            @foreach ($bloques as $b)
                                <option value="{{ $b }}" {{ ($bloqueSel ?? '') == $b ? 'selected' : '' }}>Bloque {{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-secondary">🌸 Variedad</label>
                        <select name="variedad_id" class="form-select border-2">
                            <option value="">-- Todas las Variedades --</option>
                            @foreach ($variedades as $v)
                                <option value="{{ $v->ID_Variedad }}" {{ ($variedadSel ?? '') == $v->ID_Variedad ? 'selected' : '' }}>
                                    {{ $v->Nombre_Variedad }} ({{ $v->Color }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">📅 Año de Siembra</label>
                        <select name="anio" class="form-select border-2">
                            <option value="">-- Todos los Años --</option>
                            @foreach ($aniosDisponibles as $a)
                                <option value="{{ $a }}" {{ ($anioSel ?? '') == $a ? 'selected' : '' }}>Año {{ $a }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            🔍 Buscar Siembras
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-4 border-start border-4 border-success">
            <h6 class="fw-bold text-dark mb-2">2️⃣ Selecciona las Fechas de Siembra a Comparar</h6>
            <p class="text-muted small mb-3">Marca los checkboxes de las siembras específicas que deseas graficar y analizar simultáneamente:</p>

            <form method="GET" action="{{ route('agronomia.consolidado_bloque') }}">
                <input type="hidden" name="bloque" value="{{ $bloqueSel }}">
                <input type="hidden" name="variedad_id" value="{{ $variedadSel }}">
                <input type="hidden" name="anio" value="{{ $anioSel }}">

                @if ($lotesDisponibles->count())
                    <div class="table-responsive style-scroll mb-3" style="max-height: 250px; overflow-y: auto;">
                        <div class="row g-2">
                            @foreach ($lotesDisponibles as $key => $grupo)
                                @php
                                    $primera = $grupo->first();
                                    $idsGrupo = $grupo->pluck('ID_Siembra')->toArray();
                                    $fSiembraText = $primera->Fecha_Siembra ? $primera->Fecha_Siembra->format('d/m/Y') : 'Sin Fecha';
                                    
                                    // Verificamos si al menos uno de los IDs de este lote está marcado
                                    $estaMarcado = count(array_intersect($idsGrupo, $siembrasSeleccionadasIds)) > 0;
                                    
                                    // Empaquetamos todos los IDs del lote en una sola cadena (ej: "14,15")
                                    $valorUnico = implode(',', $idsGrupo);
                                @endphp

                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 border rounded-3 bg-light d-flex align-items-center gap-3">
                                        <input class="form-check-input flex-shrink-0" 
                                            type="checkbox" 
                                            name="siembras_ids[]" 
                                            value="{{ $valorUnico }}" 
                                            id="lote_{{ $loop->index }}" 
                                            {{ $estaMarcado ? 'checked' : '' }}>

                                        <label class="form-check-label w-100 small cursor-pointer" for="lote_{{ $loop->index }}">
                                            <strong class="text-success fs-6 d-block">📅 {{ $fSiembraText }}</strong>
                                            <span class="text-dark fw-bold">{{ $primera->variedad ? $primera->variedad->Nombre_Variedad : 'N/A' }}</span> <br>
                                            <span class="text-muted">📍 {{ $primera->ubicacion ? $primera->ubicacion->Bloque : 'N/A' }} | Camas: {{ $grupo->count() }} | Plantas: {{ number_format($grupo->sum('Cantidad_Plantas')) }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-end align-items-center gap-2 flex-wrap">
                        <button type="submit" name="accion" value="ver" class="btn btn-primary px-3 py-2 fw-semibold rounded-2 shadow-sm">
                            🔍 Generar Reporte
                        </button>
                        @if (!empty($siembrasSeleccionadasIds) && $consolidado->count())
                            <button type="submit" name="accion" value="excel" class="btn btn-success px-3 py-2 fw-semibold rounded-2 shadow-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Descargar Excel
                            </button>
                            <button type="submit" name="accion" value="pdf" formtarget="_blank" class="btn btn-outline-danger px-3 py-2 fw-semibold rounded-2 shadow-sm">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Ver PDF
                            </button>
                        @endif
                    </div>
                @else
                    <div class="alert alert-warning text-center m-0">
                        No se encontraron siembras que coincidan con el Bloque, Variedad o Año seleccionados.
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if (!empty($siembrasSeleccionadasIds) && $consolidado->count())
        @php
            $totalCamas = $detalleCamas->count();
            $totalPlantas = $detalleCamas->sum('plantas');
            $totalTallos = $detalleCamas->sum('total_tallos');
            $promedio = $totalPlantas > 0 ? number_format($totalTallos / $totalPlantas, 2) : '0';
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-primary">
                    <span class="text-muted small fw-bold text-uppercase">Camas Seleccionadas</span>
                    <h3 class="fw-bold text-dark my-1">{{ $totalCamas }}</h3>
                    <span class="text-xs text-primary fw-semibold">📍 Total Camas</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-info">
                    <span class="text-muted small fw-bold text-uppercase">Plantas Sembradas</span>
                    <h3 class="fw-bold text-dark my-1">{{ number_format($totalPlantas) }}</h3>
                    <span class="text-xs text-info fw-semibold">🌱 Total Matas</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-success">
                    <span class="text-muted small fw-bold text-uppercase">Tallos Extraídos</span>
                    <h3 class="fw-bold text-success my-1">{{ number_format($totalTallos) }}</h3>
                    <span class="text-xs text-success fw-semibold">🌸 Producción Acumulada</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-4 border-warning">
                    <span class="text-muted small fw-bold text-uppercase">Rendimiento</span>
                    <h3 class="fw-bold text-dark my-1">{{ $promedio }} <span class="fs-6 text-muted">t/planta</span></h3>
                    <span class="text-xs text-warning fw-semibold">📈 Promedio Lote</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark m-0">📈 Curva Comparativa de Producción Semanal</h5>
                <small class="text-muted">Comparación directa de la curva de floración entre las siembras seleccionadas</small>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="graficaProduccionSiembra"></canvas>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark m-0">🔢 Matriz de Producción Semanal y Rendimiento</h5>
                    <small class="text-muted">Desglose semanal en volumen (Tallos) y productividad individual (t/planta = Tallos ÷ Plantas)</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-bordered align-middle mb-0 text-center table-sm" style="font-size: 0.85rem;">
                        <thead class="table-light text-secondary sticky-top">
                            <tr>
                                <th class="text-start ps-4 py-2" style="min-width: 260px; background-color: #f8fafc;">Lote / Siembra</th>
                                <th class="py-2 px-2" style="min-width: 80px; background-color: #f8fafc;">Métrica</th>
                                @foreach($chartLabels as $lbl)
                                    <th class="py-2 px-2 text-nowrap" style="background-color: #f8fafc;">{{ str_replace('Semana ', 'Sem ', $lbl) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $colores = ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6610f2', '#fd7e14', '#20c997', '#0d6efd', '#d63384']; 
                            @endphp

                            @foreach($consolidado as $index => $c)
                                @php 
                                    $color = $colores[$index % count($colores)]; 
                                @endphp

                                <tr class="border-top border-2">
                                    <td rowspan="2" class="text-start ps-4 fw-bold align-middle bg-white" style="color: {{ $color }};">
                                        ● {{ $c->fecha_siembra }} - {{ $c->variedad }} <br>
                                        <small class="text-muted fw-normal">📍 {{ $c->bloque }} | {{ number_format($c->numero_plantas) }} plantas</small>
                                    </td>
                                    <td class="fw-bold bg-light text-secondary small py-1">Tallos</td>
                                    @for($i = 1; $i <= $maxSemanasRelativasGlobal; $i++)
                                        @php
                                            $semKey = 'Semana ' . $i;
                                            $tallosSem = $i > $c->max_semana_lote ? null : ($c->produccion_semanal[$semKey] ?? 0);
                                        @endphp
                                        <td class="fw-bold text-dark py-1">
                                            {{ $tallosSem !== null ? number_format($tallosSem) : '-' }}
                                        </td>
                                    @endfor
                                </tr>

                                <tr class="bg-light-subtle">
                                    <td class="fw-bold text-success small py-1" style="background-color: #f0fdf4;">t/planta</td>
                                    @for($i = 1; $i <= $maxSemanasRelativasGlobal; $i++)
                                        @php
                                            $semKey = 'Semana ' . $i;
                                            $tallosSem = $i > $c->max_semana_lote ? null : ($c->produccion_semanal[$semKey] ?? 0);
                                            $rendimiento = ($tallosSem !== null && $c->numero_plantas > 0) 
                                                ? number_format($tallosSem / $c->numero_plantas, 2) 
                                                : null;
                                        @endphp
                                        <td class="text-success fw-bold small py-1" style="background-color: #f0fdf4;">
                                            {{ $rendimiento !== null ? $rendimiento : '-' }}
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold text-dark m-0">📋 Consolidado por Lote de Siembra</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary text-uppercase small fw-bold">
                        <tr>
                            <th class="ps-4 py-3">Fecha Siembra</th>
                            <th class="py-3">Variedad</th>
                            <th class="py-3">Color</th>
                            <th class="py-3">Bloque</th>
                            <th class="text-center py-3">Camas</th>
                            <th class="text-center py-3">Plantas</th>
                            <th class="text-center py-3">Ciclo</th>
                            <th class="text-end pe-4 py-3">Total Tallos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consolidado as $c)
                            <tr>
                                <td class="ps-4 py-3"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold fs-6">{{ $c->fecha_siembra }}</span></td>
                                <td class="fw-bold text-dark">{{ $c->variedad }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $c->color }}</span></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $c->bloque }}</span></td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $c->numero_camas }}</span></td>
                                <td class="text-center fw-medium">{{ number_format($c->numero_plantas) }}</td>
                                <td class="text-center"><span class="badge rounded-pill bg-info-subtle text-info border px-2 py-1">Ciclo {{ $c->ciclo }}</span></td>
                                <td class="text-end pe-4 text-success fw-bold fs-6">{{ number_format($c->total_tallos) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold text-dark m-0">🛏️ Detalle Individual de Camas Integrantes</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="bg-dark text-white text-uppercase small fw-bold">
                        <tr>
                            <th class="ps-4 py-3">Bloque / Nave / Cama</th>
                            <th class="py-3">Variedad</th>
                            <th class="py-3">Fecha Siembra</th>
                            <th class="text-center py-3">Plantas</th>
                            <th class="text-center py-3">Estado</th>
                            <th class="text-end pe-4 py-3">Tallos Extraídos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detalleCamas as $dc)
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-primary">{{ $dc->bloque }} / Nave {{ $dc->nave }} / Cama {{ $dc->cama }}</td>
                                <td class="fw-bold text-dark">{{ $dc->variedad }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $dc->fecha_siembra }}</span></td>
                                <td class="text-center">{{ number_format($dc->plantas) }}</td>
                                <td class="text-center"><span class="badge bg-info text-dark">{{ $dc->estado }}</span></td>
                                <td class="text-end pe-4 text-success fw-bold fs-6">{{ number_format($dc->total_tallos) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('graficaProduccionSiembra').getContext('2d');
                const labels = {!! json_encode($chartLabels) !!};
                const rawDatasets = {!! json_encode($chartDatasets) !!};

                const colors = ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6610f2', '#fd7e14', '#20c997', '#0d6efd', '#d63384'];

                const datasets = rawDatasets.map((ds, index) => {
                    let color = colors[index % colors.length];
                    return {
                        label: ds.label,
                        data: ds.data,
                        borderColor: color,
                        backgroundColor: color,
                        borderWidth: 2.5,
                        tension: 0.3,
                        pointRadius: 4,
                        fill: false
                    };
                });

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, position: 'top' },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 10,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Tallos Cosechados' } },
                            x: { title: { display: true, text: 'Semanas de Producción' } }
                        }
                    }
                });
            });
        </script>
    @endif
</div>
@endsection