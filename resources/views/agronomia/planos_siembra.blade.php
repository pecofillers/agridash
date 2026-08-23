@extends('layouts.app')

@section('title', 'Hoja de Vida y Control de Bloques')

@push('styles')
<style>
    .table-excel { font-size: 0.8rem; border-color: #b0bec5 !important; }
    .table-excel th { background-color: #2e7d32 !important; color: white; text-align: center; vertical-align: middle; padding: 10px 6px; white-space: nowrap; }
    .table-excel td { vertical-align: middle; padding: 8px 6px; text-align: center; }
    .row-vacia { background-color: #fdfdfe; }
    .row-ocupada { background-color: #ffffff; }
    .badge-estado { font-size: 0.7rem; padding: 4px 8px; }
</style>
@endpush

@section('content')
<h2 class="page-title mb-4">📊 HOJA DE VIDA DE BLOQUES Y SIEMBRAS</h2>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filtros Superiores con Ordenamiento -->
<div class="card p-3 mb-4 shadow-sm border-0 bg-light">
    <form method="GET" action="{{ route('plano_siembra.index') }}" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label fw-bold small">Bloque</label>
            <select name="bloque" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($bloques as $b)
                    <option value="{{ $b }}" {{ $bloqueSel == $b ? 'selected' : '' }}>Bloque {{ $b }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold small">Sección / Lados</label>
            <select name="seccion" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="todas" {{ $filtroSeccion == 'todas' ? 'selected' : '' }}>Todas las camas</option>
                <option value="pares" {{ $filtroSeccion == 'pares' ? 'selected' : '' }}>Pares (Derecha)</option>
                <option value="impares" {{ $filtroSeccion == 'impares' ? 'selected' : '' }}>Impares (Izquierda)</option>
                <option value="tercio1" {{ $filtroSeccion == 'tercio1' ? 'selected' : '' }}>Sección 1</option>
                <option value="tercio2" {{ $filtroSeccion == 'tercio2' ? 'selected' : '' }}>Sección 2</option>
                <option value="tercio3" {{ $filtroSeccion == 'tercio3' ? 'selected' : '' }}>Sección 3</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold small">Orden Naves</label>
            <select name="orden_naves" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="asc" {{ ($ordenNaves ?? 'asc') == 'asc' ? 'selected' : '' }}>Ascendente (1 al max)</option>
                <option value="desc" {{ ($ordenNaves ?? 'asc') == 'desc' ? 'selected' : '' }}>Descendente (max al 1)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold small">Orden Camas</label>
            <select name="orden_camas" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="asc" {{ ($ordenCamas ?? 'asc') == 'asc' ? 'selected' : '' }}>Ascendente (1, 2, 3...)</option>
                <option value="desc" {{ ($ordenCamas ?? 'asc') == 'desc' ? 'selected' : '' }}>Descendente (...3, 2, 1)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small">Consultar estado al día:</label>
            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ $fechaConsulta }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-1">
            <button class="btn btn-primary btn-sm w-100">Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabla Lineal Exacta de 12 Columnas -->
<div class="card shadow-sm border-0 overflow-hidden">
    <div class="table-responsive" style="max-height: 72vh;">
        <table class="table table-bordered table-excel mb-0 align-middle">
            <thead>
                <tr>
                    <th>CAMA</th>
                    <th>NAVE</th>
                    <th>CUADROS</th>
                    <th>FECHA DE SIEMBRA</th>
                    <th>VARIEDAD</th>
                    <th>FECHA DE PINCH</th>
                    <th>FECHA DE HORMONA</th>
                    <th>Fecha Erradicación</th>
                    <th>Estado Siembra</th>
                    <th>PLANTAS SEMBRADAS</th>
                    <th>MEDIDAS DE LA CAMA</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ubicaciones as $u)
                    @php 
                        $cultivo = $siembras->get($u->ID_Ubicacion); 
                    @endphp
                    <tr class="{{ $cultivo ? 'row-ocupada' : 'row-vacia' }}">
                        <td class="fw-bold bg-light">{{ $u->Cama }}</td>
                        <td class="fw-bold bg-light">{{ $u->Nave }}</td>
                        <td>{{ $u->Cuadros ?? '-' }}</td>
                        
                        @if($cultivo)
                            <td>{{ $cultivo->Fecha_Siembra ? \Carbon\Carbon::parse($cultivo->Fecha_Siembra)->format('d/m/Y') : '-' }}</td>
                            <td class="fw-bold text-success text-start px-2">{{ $cultivo->variedad->Nombre_Variedad ?? 'N/D' }}</td>
                            <td>{{ $cultivo->Fecha_Pinch && $cultivo->Fecha_Pinch != '0000-00-00' ? \Carbon\Carbon::parse($cultivo->Fecha_Pinch)->format('d/m/Y') : 'SIN PINCHAR' }}</td>
                            <td>{{ $cultivo->Fecha_Hormona && $cultivo->Fecha_Hormona != '0000-00-00' ? \Carbon\Carbon::parse($cultivo->Fecha_Hormona)->format('d/m/Y') : '-' }}</td>
                            <td class="text-danger">
                                @if(!empty($cultivo->Fecha_Erradicacion) && $cultivo->Fecha_Erradicacion !== '0000-00-00' && $cultivo->Fecha_Erradicacion !== '0000-00-00 00:00:00' && strpos($cultivo->Fecha_Erradicacion, '-0001') === false)
                                    {{ \Carbon\Carbon::parse($cultivo->Fecha_Erradicacion)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td><span class="badge bg-success badge-estado">{{ $cultivo->Estado_Siembra }}</span></td>
                            <td class="fw-bold">{{ number_format($cultivo->Cantidad_Plantas, 0) }}</td>
                        @else
                            <td colspan="7" class="text-muted fst-italic bg-white text-center">
                                <span class="badge bg-secondary badge-estado me-2">DISPONIBLE</span> Cama disponible / Sin siembra activa
                            </td>
                        @endif

                        <td>{{ number_format($u->Metros_Lineales, 1) }} mt</td>
                        
                        <td class="text-center">
                            @if($cultivo)
                                <button class="btn btn-sm btn-outline-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $u->ID_Ubicacion }}">
                                    ✏️ Editar
                                </button>
                            @else
                                <button class="btn btn-sm btn-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalNuevaSiembra{{ $u->ID_Ubicacion }}">
                                    🌱 Sembrar
                                </button>
                            @endif
                        </td>
                    </tr>

                    @if($cultivo)
                        <!-- MODAL DE EDICIÓN CON VALIDACIÓN JS -->
                        <div class="modal fade text-start" id="modalEditar{{ $u->ID_Ubicacion }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('plano_siembra.actualizar_siembra', $u->ID_Ubicacion) }}" method="POST" class="form-validar-fechas">
                                        @csrf @method('PUT')
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">Editar Siembra (Nave {{ $u->Nave }} - Cama {{ $u->Cama }})</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label class="form-label fw-bold small">Variedad</label>
                                                <select name="ID_Variedad" class="form-select form-select-sm" required>
                                                    @foreach($variedades as $v)
                                                        <option value="{{ $v->ID_Variedad }}" {{ $cultivo->ID_Variedad == $v->ID_Variedad ? 'selected' : '' }}>
                                                            {{ $v->Nombre_Variedad }} ({{ $v->Color }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Plantas Sembradas</label>
                                                    <input type="number" name="Cantidad_Plantas" class="form-control form-control-sm" value="{{ $cultivo->Cantidad_Plantas }}" required>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Estado Siembra</label>
                                                    <select name="Estado_Siembra" class="form-select form-select-sm">
                                                        <option value="SEMBRADA" {{ $cultivo->Estado_Siembra == 'SEMBRADA' ? 'selected' : '' }}>SEMBRADA</option>
                                                        <option value="EN_PRODUCCION" {{ $cultivo->Estado_Siembra == 'EN_PRODUCCION' ? 'selected' : '' }}>EN PRODUCCIÓN</option>
                                                        <option value="ERRADICADA" {{ $cultivo->Estado_Siembra == 'ERRADICADA' ? 'selected' : '' }}>ERRADICADA</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small text-success">Fecha de Siembra</label>
                                                    <input type="date" name="Fecha_Siembra" class="input-siembra form-control form-control-sm border-success" value="{{ $cultivo->Fecha_Siembra ? \Carbon\Carbon::parse($cultivo->Fecha_Siembra)->format('Y-m-d') : '' }}" required>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Fecha de Pinch</label>
                                                    <input type="date" name="Fecha_Pinch" class="input-pinch form-control form-control-sm" value="{{ $cultivo->Fecha_Pinch && $cultivo->Fecha_Pinch != '0000-00-00' ? \Carbon\Carbon::parse($cultivo->Fecha_Pinch)->format('Y-m-d') : '' }}">
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Fecha de Hormona</label>
                                                    <input type="date" name="Fecha_Hormona" class="input-hormona form-control form-control-sm" value="{{ $cultivo->Fecha_Hormona && $cultivo->Fecha_Hormona != '0000-00-00' ? \Carbon\Carbon::parse($cultivo->Fecha_Hormona)->format('Y-m-d') : '' }}">
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small text-danger">Fecha Erradicación</label>
                                                    <input type="date" name="Fecha_Erradicacion" class="form-control form-control-sm border-danger" value="{{ $cultivo->Fecha_Erradicacion && $cultivo->Fecha_Erradicacion != '0000-00-00' ? \Carbon\Carbon::parse($cultivo->Fecha_Erradicacion)->format('Y-m-d') : '' }}">
                                                    <small class="text-muted" style="font-size: 0.65rem;">Al poner fecha, libera la cama.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-success btn-sm w-100 btn-guardar">Guardar Cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- MODAL DE NUEVA SIEMBRA CON VALIDACIÓN JS -->
                        <div class="modal fade text-start" id="modalNuevaSiembra{{ $u->ID_Ubicacion }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('plano_siembra.actualizar_siembra', $u->ID_Ubicacion) }}" method="POST" class="form-validar-fechas">
                                        @csrf @method('PUT')
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">🌱 Registrar Nueva Siembra (Nave {{ $u->Nave }} - Cama {{ $u->Cama }})</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label class="form-label fw-bold small">Variedad</label>
                                                <select name="ID_Variedad" class="form-select form-select-sm" required>
                                                    <option value="">Seleccione la variedad...</option>
                                                    @foreach($variedades as $v)
                                                        <option value="{{ $v->ID_Variedad }}">{{ $v->Nombre_Variedad }} ({{ $v->Color }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Plantas Sembradas</label>
                                                    <input type="number" name="Cantidad_Plantas" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Estado Siembra</label>
                                                    <select name="Estado_Siembra" class="form-select form-select-sm">
                                                        <option value="SEMBRADA" selected>SEMBRADA</option>
                                                        <option value="EN_PRODUCCION">EN PRODUCCIÓN</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small text-success">Fecha de Siembra</label>
                                                    <input type="date" name="Fecha_Siembra" class="input-siembra form-control form-control-sm border-success" value="{{ date('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Fecha de Pinch</label>
                                                    <input type="date" name="Fecha_Pinch" class="input-pinch form-control form-control-sm">
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="form-label fw-bold small">Fecha de Hormona</label>
                                                    <input type="date" name="Fecha_Hormona" class="input-hormona form-control form-control-sm">
                                                </div>
                                                <input type="hidden" name="Fecha_Erradicacion" value="">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary btn-sm w-100 btn-guardar">Guardar Nueva Siembra</button>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Validación de fechas en formularios de modales
        document.querySelectorAll('.form-validar-fechas').forEach(form => {
            form.addEventListener('submit', function (e) {
                const siembraInput = form.querySelector('.input-siembra');
                const pinchInput = form.querySelector('.input-pinch');
                const hormonaInput = form.querySelector('.input-hormona');

                if (!siembraInput || !siembraInput.value) return;

                const fechaSiembra = new Date(siembraInput.value);

                if (pinchInput && pinchInput.value) {
                    const fechaPinch = new Date(pinchInput.value);
                    if (fechaPinch < fechaSiembra) {
                        e.preventDefault();
                        alert('⚠️ Error: La Fecha de Pinch no puede ser anterior a la Fecha de Siembra.');
                        pinchInput.focus();
                        return false;
                    }
                }

                if (hormonaInput && hormonaInput.value) {
                    const fechaHormona = new Date(hormonaInput.value);
                    if (fechaHormona < fechaSiembra) {
                        e.preventDefault();
                        alert('⚠️ Error: La Fecha de Hormona no puede ser anterior a la Fecha de Siembra.');
                        hormonaInput.focus();
                        return false;
                    }
                }
            });
        });
    });
</script>
@endpush

@endsection