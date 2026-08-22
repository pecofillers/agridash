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
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editarSiembra{{ $activa->ID_Siembra }}">
                    ✏️ Editar / Finalizar / Erradicar
                </button>
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

        <div class="modal fade" id="editarSiembra{{ $activa->ID_Siembra }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('agronomia.actualizar', $activa->ID_Siembra) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">✏️ Gestionar Siembra: Cama {{ $activa->ubicacion->Cama }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Variedad</label>
                                    <select name="ID_Variedad" class="form-select" required>
                                        @foreach ($variedades as $v)
                                            <option value="{{ $v->ID_Variedad }}" {{ $activa->ID_Variedad == $v->ID_Variedad ? 'selected' : '' }}>
                                                {{ $v->Nombre_Variedad }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Estado de Siembra</label>
                                    <select name="Estado" class="form-select">
                                        <option value="ACTIVA" {{ $u->Estado == 'ACTIVA' ? 'selected' : '' }}>ACTIVA</option>
                                        <option value="INACTIVA" {{ $u->Estado == 'INACTIVA' ? 'selected' : '' }}>INACTIVA</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ciclo Actual</label>
                                    <input type="number" name="Ciclo_Actual" value="{{ $activa->Ciclo_Actual }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Cantidad Plantas</label>
                                    <input type="number" name="Cantidad_Plantas" value="{{ $activa->Cantidad_Plantas }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Erradicación</label>
                                    <input type="date" name="Fecha_Erradicacion" value="{{ $activa->Fecha_Erradicacion ? $activa->Fecha_Erradicacion->format('Y-m-d') : '' }}" class="form-control">
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
                            <td>
                                <span class="badge" style="background-color: {{ $h->variedad->Color ?? '#6c757d' }}; color: #fff;">
                                    {{ $h->variedad->Color ?? 'N/A' }}
                                </span>
                            </td>
                            <td>{{ $h->Estado_Siembra }}</td>
                            <td>{{ $h->Ciclo_Actual }}</td>
                            <td>{{ $h->Fecha_Siembra ? $h->Fecha_Siembra->format('Y-m-d') : '' }}</td>
                            <td><span class="badge bg-dark">{{ $h->Fecha_Erradicacion ? $h->Fecha_Erradicacion->format('Y-m-d') : '-' }}</span></td>
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

                        @if (!empty($h->ID_Siembra))
                            <div class="modal fade" id="editarSiembra{{ $h->ID_Siembra }}" tabindex="-1">
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
                                                        <label class="form-label">Fecha Erradicación</label>
                                                        <input type="date" name="Fecha_Erradicacion" value="{{ $h->Fecha_Erradicacion ? $h->Fecha_Erradicacion->format('Y-m-d') : '' }}" class="form-control">
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
            <div class="col-md-2">
                <label class="form-label">Bloque</label>
                <select id="filtro_bloque" class="form-select">
                    <option value="">-- Bloque --</option>
                    @foreach ($bloques as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Nave</label>
                <select id="filtro_nave" class="form-select" disabled>
                    <option value="">-- Nave --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cama</label>
                <select id="filtro_cama" name="ID_Ubicacion" class="form-select" required disabled>
                    <option value="">-- Cama --</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Variedad y Color</label>
                <select name="ID_Variedad" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach ($variedades as $v)
                        <option value="{{ $v->ID_Variedad }}">{{ $v->Nombre_Variedad }} - {{ $v->Color }}</option>
                    @endforeach
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pasamos todas las ubicaciones desde PHP a JavaScript
        const ubicaciones = @json($ubicaciones);
        
        const selBloque = document.getElementById('filtro_bloque');
        const selNave = document.getElementById('filtro_nave');
        const selCama = document.getElementById('filtro_cama');

        // Cuando cambia el Bloque
        selBloque.addEventListener('change', function() {
            // Reiniciamos Nave y Cama
            selNave.innerHTML = '<option value="">-- Nave --</option>';
            selCama.innerHTML = '<option value="">-- Cama --</option>';
            selCama.disabled = true;

            if (this.value) {
                // Filtramos las naves únicas de ese bloque
                const naves = [...new Set(ubicaciones.filter(u => u.Bloque == this.value).map(u => u.Nave))];
                naves.forEach(n => selNave.innerHTML += `<option value="${n}">${n}</option>`);
                selNave.disabled = false;
            } else {
                selNave.disabled = true;
            }
        });

        // Cuando cambia la Nave
        selNave.addEventListener('change', function() {
            // Reiniciamos Cama
            selCama.innerHTML = '<option value="">-- Cama --</option>';
            
            if (this.value) {
                // Filtramos las camas exactas de ese Bloque y Nave
                const camas = ubicaciones.filter(u => u.Bloque == selBloque.value && u.Nave == this.value);
                camas.forEach(c => {
                    selCama.innerHTML += `<option value="${c.ID_Ubicacion}">Cama ${c.Cama} (${c.Metros_Lineales}m)</option>`;
                });
                selCama.disabled = false;
            } else {
                selCama.disabled = true;
            }
        });
    });
</script>
@endpush