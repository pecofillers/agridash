@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">Insumos Diarios: Amortiguador, Agrofeed y Agua</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Registrar lectura del día</div>
        <div class="card-body">
            <form method="POST" action="{{ route('agronomia.insumos.guardar') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ old('fecha', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amortiguador - Medida Visual (Litros)</label>
                        <input type="number" placeholder="Ej: 1300" step="0.01" name="amortiguador_medida_visual" class="form-control" value="{{ old('amortiguador_medida_visual') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amortiguador - Medida Registro (m³)</label>
                        <input type="number" placeholder="Ej: 520,16" step="0.01" name="amortiguador_medida_registro" class="form-control" value="{{ old('amortiguador_medida_registro') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agrofeed - Medida Visual (Litros)</label>
                        <input type="number" placeholder="Ej: 4300" step="0.01" name="agrofeed_medida_visual" class="form-control" value="{{ old('agrofeed_medida_visual') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agrofeed - Medida Registro (m³)</label>
                        <input type="number" placeholder="Ej: 488,11" step="0.01" name="agrofeed_medida_registro" class="form-control" value="{{ old('agrofeed_medida_registro') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Agua - Lectura del contador (m³)</label>
                        <input type="number" placeholder="Ej: 361000" step="0.01" name="agua_lectura" class="form-control" value="{{ old('agua_lectura') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Guardar registro</button>
                </div>
                <small class="text-muted d-block mt-2">
                    Ingresa la medida visual tal como la ves en el tanque (litros) y la medida del medidor en metros cúbicos (m³).
                </small>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Análisis visual
            <small class="text-muted d-block">
                Diferencias día a día de Amortiguador y Agrofeed, todo convertido a litros (la diferencia de registro se multiplica ×1000).
            </small>
        </div>
        <div class="card-body">
            <canvas id="graficaInsumos" height="100"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Historial de registros</span>
                <form method="GET" action="{{ route('agronomia.insumos') }}" class="d-flex gap-2 align-items-end">
                    <div>
                        <label class="form-label mb-0 small">Desde</label>
                        <input type="date" name="desde" class="form-control form-control-sm" value="{{ request('desde') }}">
                    </div>
                    <div>
                        <label class="form-label mb-0 small">Hasta</label>
                        <input type="date" name="hasta" class="form-control form-control-sm" value="{{ request('hasta') }}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                    @if(request('desde') || request('hasta'))
                        <a href="{{ route('agronomia.insumos') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-sm align-middle">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Amortig. Visual (L)</th>
                        <th>Amortig. Registro (m³)</th>
                        <th>Amortig. Dif. Visual (L)</th>
                        <th>Amortig. Dif. Registro (m³)</th>
                        <th>Agrofeed Visual (L)</th>
                        <th>Agrofeed Registro (m³)</th>
                        <th>Agrofeed Dif. Visual (L)</th>
                        <th>Agrofeed Dif. Registro (m³)</th>
                        <th>Agua Lectura (m³)</th>
                        <th>Agua Dif. Registro (m³)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registros as $r)
                        <tr>
                            <td>{{ $r->fecha->format('d/m/Y') }}</td>
                            <td>{{ $r->amortiguador_medida_visual ?? '-' }}</td>
                            <td>{{ $r->amortiguador_medida_registro ?? '-' }}</td>
                            <td>{{ $r->amortiguador_diferencia_visual ?? '-' }}</td>
                            <td>{{ $r->amortiguador_diferencia_registro ?? '-' }}</td>
                            <td>{{ $r->agrofeed_medida_visual ?? '-' }}</td>
                            <td>{{ $r->agrofeed_medida_registro ?? '-' }}</td>
                            <td>{{ $r->agrofeed_diferencia_visual ?? '-' }}</td>
                            <td>{{ $r->agrofeed_diferencia_registro ?? '-' }}</td>
                            <td>{{ $r->agua_lectura ?? '-' }}</td>
                            <td>{{ $r->agua_diferencia_registro ?? '-' }}</td>
                            <td>
                                <a href="{{ route('agronomia.insumos.editar', $r->id) }}" class="btn btn-sm btn-outline-warning">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted">No hay registros para el rango seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="d-flex justify-content-center mt-3">
                {{ $registros->links() }}
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const labels = @json($registrosGrafica->pluck('fecha')->map(fn($f) => \Carbon\Carbon::parse($f)->format('d/m')));

    const amortiguadorDifRegistroL = @json($registrosGrafica->pluck('amortiguador_diferencia_registro'))
        .map(v => v === null ? null : v * 1000);

    const agrofeedDifRegistroL = @json($registrosGrafica->pluck('agrofeed_diferencia_registro'))
        .map(v => v === null ? null : v * 1000);

    const aguaDifRegistroL = @json($registrosGrafica->pluck('agua_diferencia_registro'))
        .map(v => v === null ? null : v * 1000);

    new Chart(document.getElementById('graficaInsumos'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Amortiguador - Dif. Visual (L)', data: @json($registrosGrafica->pluck('amortiguador_diferencia_visual')), borderColor: '#2e7d32', tension: 0.3, yAxisID: 'yInsumos' },
                { label: 'Amortiguador - Dif. Registro (L equiv.)', data: amortiguadorDifRegistroL, borderColor: '#66bb6a', tension: 0.3, yAxisID: 'yInsumos' },
                { label: 'Agrofeed - Dif. Visual (L)', data: @json($registrosGrafica->pluck('agrofeed_diferencia_visual')), borderColor: '#f9a825', tension: 0.3, yAxisID: 'yInsumos' },
                { label: 'Agrofeed - Dif. Registro (L equiv.)', data: agrofeedDifRegistroL, borderColor: '#fdd835', tension: 0.3, yAxisID: 'yInsumos' },
                { label: 'Agua - Dif. Registro (L equiv.)', data: aguaDifRegistroL, borderColor: '#1e88e5', tension: 0.3, yAxisID: 'yAgua', borderDash: [5, 3] },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                yInsumos: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Amortiguador / Agrofeed (L)' }
                },
                yAgua: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Agua (L)' },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + (context.parsed.y !== null ? context.parsed.y.toFixed(2) + ' L' : 'Sin dato');
                        }
                    }
                }
            }
        }
    });
</script>

@endsection