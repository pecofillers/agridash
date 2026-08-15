@extends('layouts.app')

@section('title', 'Agronomia')

@section('content')
<h2 class="page-title mb-4">🌱 EXPEDIENTE Y GESTION DE CAMAS</h2>

<div class="card card-dashboard p-4 mb-4 bg-light border-success">
    <div class="row align-items-center">
        <div class="col-md-12 mb-3">
            <h5>📚 Carga/Descarga Masiva de Siembras (Toda la Finca)</h5>
            <p class="text-muted small mb-0">
                Cada pestaña/hoja del Excel corresponde a un Bloque (ej: <i>BLOQUE 1, BLOQUE 2...</i>).
            </p>
        </div>

        <div class="col-md-6 border-end pe-md-4 mb-3 mb-md-0">
            <h6>📥 Descargar Plantilla Histórica</h6>
            <p class="small text-muted mb-2">Descarga un solo archivo con todas las siembras divididas por bloques en pestañas.</p>
            <a href="{{ route('agronomia.exportar_multibloque') }}" class="btn btn-outline-success btn-sm w-100">
                📥 Descargar Todas las Siembras
            </a>
        </div>

        <div class="col-md-6 ps-md-4">
            <h6>📤 Sincronizar Excel Actualizado</h6>
            <form action="{{ route('agronomia.importar_multibloque') }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-1">
                @csrf
                <div class="col-8">
                    <input type="file" name="archivo_excel" class="form-control form-control-sm" accept=".xlsx, .xls" required>
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        🚀 Sincronizar Finca
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card card-dashboard p-4 mb-4">
    <h5>🔍 Buscar Historial de Cama</h5>
    <form method="GET" action="{{ route('agronomia.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-9">
                <label class="form-label">Selecciona una Ubicación</label>
                <select name="ID_Ubicacion" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($ubicaciones as $u)
                        <option value="{{ $u->ID_Ubicacion }}" {{ ($idUbicacion ?? '') == $u->ID_Ubicacion ? 'selected' : '' }}>
                            {{ $u->Bloque }} / {{ $u->Nave }} / {{ $u->Cama }}
                        </option>
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
                <div class="col-md-3">
                    <div class="text-muted small">Ubicación</div>
                    <div class="fs-4 fw-bold">{{ $activa->ubicacion->Bloque }} / {{ $activa->ubicacion->Nave }} / {{ $activa->ubicacion->Cama }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Variedad Actual</div>
                    <div class="fs-4 fw-bold">{{ $activa->variedad->Nombre_Variedad ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Densidad</div>
                    <div class="fs-4 fw-bold">{{ number_format($activa->Densidad_Plantacion, 1) }} pt/m²</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Estado</div>
                    <div class="fs-4 fw-bold text-success">{{ $activa->Estado_Siembra }} (Ciclo {{ $activa->Ciclo_Actual }})</div>
                </div>
            </div>
        </div>
    @endif
    <div class="card card-dashboard p-4">
        <h5>📖 Historial Completo</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Ubicación</th><th>Variedad</th><th>Estado</th><th>Ciclo</th><th>Fecha Siembra</th><th>Fecha Fin</th><th>Plantas</th><th>Metros</th><th>Densidad</th></tr></thead>
                <tbody>
                    @foreach ($historial as $h)
                        <tr>
                            <td>{{ $h->ubicacion->Bloque }} / {{ $h->ubicacion->Nave }} / {{ $h->ubicacion->Cama }}</td>
                            <td>{{ $h->variedad->Nombre_Variedad ?? $h->ID_Variedad }}</td>
                            <td>{{ $h->Estado_Siembra }}</td>
                            <td>{{ $h->Ciclo_Actual }}</td>
                            <td>{{ $h->Fecha_Siembra ? $h->Fecha_Siembra->format('Y-m-d') : '' }}</td>
                            <td>{{ $h->Fecha_Fin ? $h->Fecha_Fin->format('Y-m-d') : 'ACTIVA' }}</td>
                            <td>{{ number_format($h->Cantidad_Plantas, 0) }}</td>
                            <td>{{ $h->Metros_Lineales }}</td>
                            <td>{{ $h->Densidad_Plantacion }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif ($idUbicacion)
    <p class="text-muted">Esta ubicación es nueva y no tiene historial registrado.</p>
@endif

<div class="card card-dashboard p-4 mt-4">
    <h5>🪴 Registrar Nueva Siembra</h5>
    <form method="POST" action="{{ route('agronomia.siembra') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Ubicación</label>
                <select name="ID_Ubicacion" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach ($ubicaciones as $u)
                        <option value="{{ $u->ID_Ubicacion }}" {{ ($idUbicacion ?? '') == $u->ID_Ubicacion ? 'selected' : '' }}>
                            {{ $u->Bloque }} / {{ $u->Nave }} / {{ $u->Cama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <label class="form-label">Estado de Siembra</label>
                <select name="Estado_Siembra" class="form-select">
                    <option value="SEMBRADA" selected>SEMBRADA</option>
                    <option value="EN_PRODUCCION">EN PRODUCCIÓN</option>
                    <option value="ERRADICADA">ERRADICADA</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ciclo Actual</label>
                <input type="number" name="Ciclo_Actual" min="1" value="1" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha Pinch (Opcional)</label>
                <input type="date" name="Fecha_Pinch" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha Hormona (Opcional)</label>
                <input type="date" name="Fecha_Hormona" class="form-control">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-success w-100">🌱 GUARDAR SIEMBRA</button>
            </div>
        </div>
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