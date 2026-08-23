@extends('layouts.app')

@section('title', 'Agronomia')

@section('content')
<h2 class="page-title mb-4">🌱 EXPEDIENTE Y GESTION DE CAMAS</h2>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

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
            <p class="small text-muted mb-2">Descarga un archivo con las siembras actuales divididas por bloques en pestañas.</p>
            <a href="{{ route('agronomia.exportar_multibloque') }}" class="btn btn-outline-success btn-sm w-100">
                📥 Descargar Siembras Activas
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
            <div class="col-md-3">
                <label class="form-label">Bloque</label>
                <select name="Bloque" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Todos --</option>
                    @foreach ($bloques as $b)
                        <option value="{{ $b }}" {{ request('Bloque') == $b ? 'selected' : '' }}>Bloque {{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nave</label>
                <select name="Nave" class="form-select" onchange="this.form.submit()" {{ !request('Bloque') ? 'disabled' : '' }}>
                    <option value="">-- Todas --</option>
                    @if(isset($naves))
                        @foreach ($naves as $n)
                            <option value="{{ $n }}" {{ request('Nave') == $n ? 'selected' : '' }}>Nave {{ $n }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cama Específica</label>
                <select name="ID_Ubicacion" class="form-select" {{ !request('Nave') ? 'disabled' : '' }}>
                    <option value="">-- Seleccionar Cama --</option>
                    @if(isset($camasFiltradas))
                        @foreach ($camasFiltradas as $u)
                            <option value="{{ $u->ID_Ubicacion }}" {{ request('ID_Ubicacion') == $u->ID_Ubicacion ? 'selected' : '' }}>
                                Cama {{ $u->Cama }} ({{ number_format($u->Metros_Lineales, 1) }}m)
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" {{ !request('Nave') ? 'disabled' : '' }}>🔍 BUSCAR</button>
            </div>
        </div>
    </form>
</div>

@if ($historial->count())
    @if ($activa && isset($activa->ID_Siembra))
        <div class="card card-dashboard p-4 mb-4 border-primary">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">🌱 Siembra Activa</h5>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Ubicación</div>
                    <div class="fs-5 fw-bold">{{ $activa->ubicacion->Bloque }} / {{ $activa->ubicacion->Nave }} / {{ $activa->ubicacion->Cama }}</div>
                    <div class="small text-muted">{{ number_format($activa->ubicacion->Metros_Lineales, 2) }} mts | {{ $activa->ubicacion->Cuadros ?? 0 }} cuadros</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Variedad Actual</div>
                    <div class="fs-5 fw-bold">{{ $activa->variedad->Nombre_Variedad ?? 'N/A' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Densidad</div>
                    <div class="fs-5 fw-bold">{{ number_format($activa->Densidad_Plantacion, 1) }} pt/m²</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Estado</div>
                    <div class="fs-5 fw-bold text-success">{{ $activa->Estado_Siembra }} (Ciclo {{ $activa->Ciclo_Actual }})</div>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-dashboard p-4">
        <h5>📖 Historial Completo</h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Ubicación</th>
                        <th>Variedad</th>
                        <th>Color</th>
                        <th>Estado</th>
                        <th>Ciclo</th>
                        <th>F. Siembra</th>
                        <th>F. Pinch</th>
                        <th>F. Hormona</th>
                        <th>F. Erradicación</th>
                        <th>Plantas</th>
                        <th>Densidad</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historial as $h)
                        <tr>
                            <td>{{ $h->ubicacion->Bloque ?? 'N/A' }} / {{ $h->ubicacion->Nave ?? 'N/A' }} / {{ $h->ubicacion->Cama ?? 'N/A' }}</td>
                            <td>{{ $h->variedad->Nombre_Variedad ?? $h->ID_Variedad }}</td>
                            <td>{{ $h->variedad->Color ?? 'N/A' }}</td>
                            <td>{{ $h->Estado_Siembra }}</td>
                            <td>{{ $h->Ciclo_Actual }}</td>
                            <td>{{ !empty($h->Fecha_Siembra) ? \Carbon\Carbon::parse($h->Fecha_Siembra)->format('Y-m-d') : '' }}</td>
                            <td>{{ !empty($h->Fecha_Pinch) && strpos($h->Fecha_Pinch, '-0001') === false ? \Carbon\Carbon::parse($h->Fecha_Pinch)->format('Y-m-d') : '-' }}</td>
                            <td>{{ !empty($h->Fecha_Hormona) && strpos($h->Fecha_Hormona, '-0001') === false ? \Carbon\Carbon::parse($h->Fecha_Hormona)->format('Y-m-d') : '-' }}</td>
                            <td>
                                <span class="badge bg-dark">
                                    @if(!empty($h->Fecha_Erradicacion) && $h->Fecha_Erradicacion !== '0000-00-00' && $h->Fecha_Erradicacion !== '0000-00-00 00:00:00' && strpos($h->Fecha_Erradicacion, '-0001') === false)
                                        {{ \Carbon\Carbon::parse($h->Fecha_Erradicacion)->format('Y-m-d') }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </td>
                            <td>{{ number_format($h->Cantidad_Plantas, 0) }}</td>
                            <td>{{ $h->Densidad_Plantacion }}</td>
                            <td class="text-center">
                                @if (!empty($h->ID_Siembra))
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarSiembra{{ $h->ID_Siembra }}">
                                        ✏️ Editar
                                    </button>
                                @else
                                    <span class="text-muted small">Sin ID</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🟢 MODALES FUERA DE LA TABLA (Para que Bootstrap los abra correctamente) --}}
    @foreach ($historial as $h)
        @if (!empty($h->ID_Siembra))
            <div class="modal fade" id="editarSiembra{{ $h->ID_Siembra }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('agronomia.actualizar', $h->ID_Siembra) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">✏️ Editar Siembra: Cama {{ $h->ubicacion->Cama ?? '' }} (Ciclo {{ $h->Ciclo_Actual }})</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-start">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Variedad</label>
                                        <select name="ID_Variedad" class="form-select" required>
                                            @foreach ($variedades as $v)
                                                <option value="{{ $v->ID_Variedad }}" {{ $h->ID_Variedad == $v->ID_Variedad ? 'selected' : '' }}>
                                                    {{ $v->Nombre_Variedad }} ({{ $v->Color }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Estado de Siembra</label>
                                        <select name="Estado_Siembra" class="form-select">
                                            <option value="SEMBRADA" {{ $h->Estado_Siembra == 'SEMBRADA' ? 'selected' : '' }}>SEMBRADA</option>
                                            <option value="EN_PRODUCCION" {{ $h->Estado_Siembra == 'EN_PRODUCCION' ? 'selected' : '' }}>EN PRODUCCIÓN</option>
                                            <option value="ERRADICADA" {{ $h->Estado_Siembra == 'ERRADICADA' ? 'selected' : '' }}>ERRADICADA</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ciclo Actual</label>
                                        <input type="number" name="Ciclo_Actual" value="{{ $h->Ciclo_Actual }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cantidad Plantas</label>
                                        <input type="number" name="Cantidad_Plantas" value="{{ $h->Cantidad_Plantas }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha Pinch</label>
                                        <input type="date" name="Fecha_Pinch" value="{{ !empty($h->Fecha_Pinch) && strpos($h->Fecha_Pinch, '-0001') === false ? \Carbon\Carbon::parse($h->Fecha_Pinch)->format('Y-m-d') : '' }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha Hormona</label>
                                        <input type="date" name="Fecha_Hormona" value="{{ !empty($h->Fecha_Hormona) && strpos($h->Fecha_Hormona, '-0001') === false ? \Carbon\Carbon::parse($h->Fecha_Hormona)->format('Y-m-d') : '' }}" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fecha Erradicación</label>
                                        <input type="date" name="Fecha_Erradicacion" value="{{ !empty($h->Fecha_Erradicacion) && $h->Fecha_Erradicacion !== '0000-00-00' && strpos($h->Fecha_Erradicacion, '-0001') === false ? \Carbon\Carbon::parse($h->Fecha_Erradicacion)->format('Y-m-d') : '' }}" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

@elseif ($idUbicacion)
    <p class="text-muted">Esta ubicación es nueva y no tiene historial registrado.</p>
@endif

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