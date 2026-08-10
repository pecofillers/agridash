@extends('layouts.app')

@section('title', 'Agronomia')

@section('content')
<h2 class="page-title mb-4">🌱 EXPEDIENTE Y GESTION DE CAMAS</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>🔍 Buscar Historial de Cama</h5>
    <form method="GET" action="{{ route('agronomia.index') }}">
        <div class="row g-3 align-items-end">
<div class="col-md-3">
                <label class="form-label">Bloque</label>
                <select name="bloque" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($bloques as $b)
                        <option value="{{ $b }}" {{ ($bloque ?? '') == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nave</label>
                <select name="nave" class="form-select">
                    <option value="">-- Seleccionar --</option>
                    @foreach (request('bloque') ? \App\Models\Ubicacion::naves(request('bloque')) : [] as $n)
                        <option value="{{ $n }}" {{ ($nave ?? '') == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cama</label>
                <select name="cama" class="form-select">
                    <option value="">-- Seleccionar --</option>
                    @foreach (request('bloque') && request('nave') ? \App\Models\Ubicacion::camas(request('bloque'), request('nave')) : [] as $c)
                        <option value="{{ $c }}" {{ ($cama ?? '') == $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">🔍 VER HISTORIAL</button>
            </div>
        </div>
    </form>
</div>

@if ($historial->count())
    @php $activa = $historial->firstWhere('Fecha_Fin', null); @endphp
    @if ($activa)
        <div class="card card-dashboard p-4 mb-4">
            <h5>🌱 Siembra Activa</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Variedad Actual</div>
                    <div class="fs-4 fw-bold">{{ $activa->variedad->Nombre_Variedad ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Plantas Vivas</div>
                    <div class="fs-4 fw-bold">{{ number_format($activa->Cantidad_Plantas, 0) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Densidad</div>
                    <div class="fs-4 fw-bold">{{ number_format($activa->Densidad_Plantacion, 1) }} pt/m²</div>
                </div>
            </div>
        </div>
    @endif
    <div class="card card-dashboard p-4">
        <h5>📖 Historial Completo</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Variedad</th><th>Fecha Siembra</th><th>Fecha Fin</th><th>Plantas</th><th>Metros</th><th>Densidad</th></tr></thead>
                <tbody>
                    @foreach ($historial as $h)
                        <tr>
                            <td>{{ $h->variedad->Nombre_Variedad ?? $h->ID_Variedad }}</td>
                            <td>{{ $h->Fecha_Siembra }}</td>
                            <td>{{ $h->Fecha_Fin ?? 'ACTIVA' }}</td>
                            <td>{{ number_format($h->Cantidad_Plantas, 0) }}</td>
                            <td>{{ $h->Metros_Lineales }}</td>
                            <td>{{ $h->Densidad_Plantacion }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif ($bloque && $nave && $cama)
    <p class="text-muted">Esta cama es nueva y no tiene historial registrado.</p>
@endif

<div class="card card-dashboard p-4 mt-4">
    <h5>🪴 Registrar Nueva Siembra</h5>
    <form method="POST" action="{{ route('agronomia.siembra') }}">
        @csrf
<div class="row g-3">
            <div class="col-md-2">
<label class="form-label">Bloque</label>
                <select name="Bloque" class="form-select" required>
                    @foreach ($bloques as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Nave</label>
                <input type="text" name="Nave" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cama</label>
                <input type="text" name="Cama" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Variedad</label>
                <select name="ID_Variedad" class="form-select" required>
                    @foreach ($variedades as $v)<option value="{{ $v->ID_Variedad }}">{{ $v->Nombre_Variedad }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha Siembra</label>
                <input type="date" name="Fecha_Siembra" class="form-control" required value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Plantas</label>
                <input type="number" name="Cantidad_Plantas" min="1" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Metros Lineales</label>
                <input type="number" name="Metros_Lineales" min="0.1" step="0.1" class="form-control" required>
            </div>
        </div>
        <button class="btn btn-success mt-3">🌱 GUARDAR SIEMBRA</button>
    </form>
</div>

<div class="card card-dashboard p-4 mt-4">
    <h5>🌸 Catalogo de Variedades</h5>
    <form method="POST" action="{{ route('agronomia.variedad') }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-4"><input type="text" name="Nombre_Variedad" class="form-control" placeholder="Nombre (ej: Freedom)" required></div>
        <div class="col-md-3"><input type="text" name="Color" class="form-control" placeholder="Color principal"></div>
        <div class="col-md-3"><input type="number" name="Ciclo_Dias" min="1" class="form-control" placeholder="Ciclo (dias)"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Guardar</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>ID</th><th>Nombre</th><th>Color</th><th>Ciclo (dias)</th></tr></thead>
            <tbody>
                @foreach ($variedades as $v)
                    <tr><td>{{ $v->ID_Variedad }}</td><td>{{ $v->Nombre_Variedad }}</td><td>{{ $v->Color }}</td><td>{{ $v->Ciclo_Dias }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
