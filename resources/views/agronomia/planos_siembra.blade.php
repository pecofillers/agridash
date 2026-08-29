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

    /* Contenedor responsivo */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-height: 75vh;
        border: 1px solid #dee2e6;
        position: relative;
    }

    /* Cabeceras normales (Fijas arriba) */
    .table thead th {
        position: -webkit-sticky;
        position: sticky;
        top: 0;
        background-color: #2e7d32 !important;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
    }

    /* ANCHOS POR DEFECTO (Escritorio/Computadora) */
    .sticky-check { position: -webkit-sticky !important; position: sticky !important; left: 0 !important; width: 40px; min-width: 40px; max-width: 40px; z-index: 5; background-color: #f8f9fa !important; border-right: 1px solid #dee2e6; }
    .sticky-nave { position: -webkit-sticky !important; position: sticky !important; left: 40px !important; width: 60px; min-width: 60px; max-width: 60px; z-index: 5; background-color: #f8f9fa !important; }
    .sticky-cama { position: -webkit-sticky !important; position: sticky !important; left: 100px !important; width: 65px; min-width: 65px; max-width: 65px; z-index: 5; background-color: #f8f9fa !important; border-right: 2px solid #b0bec5 !important; }
    
    thead .sticky-check, thead .sticky-nave, thead .sticky-cama { z-index: 15 !important; background-color: #2e7d32 !important; }

    /* 📱 OPTIMIZACIÓN PARA CELULARES */
    @media (max-width: 768px) {
        .card-movil-full {
            margin-left: -15px !important;
            margin-right: -15px !important;
            border-radius: 0 !important;
            border-left: 0 !important;
            border-right: 0 !important;
        }
        
        /* Permitir que el texto baje de línea en celulares para acortar el ancho */
        .table-excel th { 
            padding: 8px 4px !important;
            font-size: 0.65rem !important; 
            white-space: normal !important; 
            line-height: 1.1;
            min-width: 50px; /* Ancho mínimo para que no se aplaste demasiado */
        }
        
        .table-excel td { padding: 6px 4px !important; font-size: 0.75rem !important; }
        
        .sticky-check { width: 32px !important; min-width: 32px !important; max-width: 32px !important; }
        .sticky-nave { left: 32px !important; width: 42px !important; min-width: 42px !important; max-width: 42px !important; }
        .sticky-cama { left: 74px !important; width: 45px !important; min-width: 45px !important; max-width: 45px !important; }
    }
</style>
@endpush

@section('content')
<h2 class="page-title mb-4">🗺️ PLANO DE SIEMBRAS</h2>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<!-- Filtros Superiores con Ordenamiento -->
<div class="card p-3 mb-4 shadow-sm border-0 bg-light">
    <form method="GET" action="{{ route('plano_siembra.index') }}" class="row g-2 align-items-end">
        
        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1">Bloque</label>
            <select name="bloque" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($bloques as $b)
                    <option value="{{ $b }}" {{ $bloqueSel == $b ? 'selected' : '' }}>Bloque {{ $b }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1">Nave</label>
            <select name="nave" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todas las Naves</option>
                @foreach($naves as $n)
                    <option value="{{ $n }}" {{ $naveSel == $n ? 'selected' : '' }}>Nave {{ $n }}</option>
                @endforeach
            </select>
        </div>

        {{-- 🟢 Filtro Unificado: Fecha de Siembra o Vacías --}}
        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1">Siembra / Estado</label>
            <select name="fecha_siembra_filtro" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todas las camas</option>
                <option value="vacias" {{ $fechaSiembraFiltro === 'vacias' ? 'selected' : '' }} class="fw-bold text-danger">⚠️ Mostrar solo Vacías</option>
                <optgroup label="Filtrar por Fecha de Siembra">
                    @foreach($fechasSiembra as $fs)
                        <option value="{{ $fs }}" {{ $fechaSiembraFiltro == $fs ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($fs)->format('d/m/Y') }}
                        </option>
                    @endforeach
                </optgroup>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1">Lados / Sección</label>
            <select name="seccion" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="todas" {{ $filtroSeccion == 'todas' ? 'selected' : '' }}>Todas</option>
                <option value="pares" {{ $filtroSeccion == 'pares' ? 'selected' : '' }}>Pares (Derecha)</option>
                <option value="impares" {{ $filtroSeccion == 'impares' ? 'selected' : '' }}>Impares (Izquierda)</option>
                <option value="tercio1" {{ $filtroSeccion == 'tercio1' ? 'selected' : '' }}>Sección 1</option>
                <option value="tercio2" {{ $filtroSeccion == 'tercio2' ? 'selected' : '' }}>Sección 2</option>
                <option value="tercio3" {{ $filtroSeccion == 'tercio3' ? 'selected' : '' }}>Sección 3</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted mb-1">Consultar estado al día:</label>
            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ $fechaConsulta }}" onchange="this.form.submit()">
        </div>
        
        <div class="col-md-1 ms-auto mt-2">
            <label class="form-label fw-bold small text-muted mb-1">Orden Naves</label>
            <select name="orden_naves" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="asc" {{ ($ordenNaves ?? 'asc') == 'asc' ? 'selected' : '' }}>Asc</option>
                <option value="desc" {{ ($ordenNaves ?? 'asc') == 'desc' ? 'selected' : '' }}>Desc</option>
            </select>
        </div>
        <div class="col-md-1 mt-2">
            <label class="form-label fw-bold small text-muted mb-1">Orden Camas</label>
            <select name="orden_camas" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="asc" {{ ($ordenCamas ?? 'asc') == 'asc' ? 'selected' : '' }}>Asc</option>
                <option value="desc" {{ ($ordenCamas ?? 'asc') == 'desc' ? 'selected' : '' }}>Desc</option>
            </select>
        </div>
        
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">🔍 Filtrar</button>
            <a href="{{ route('plano_siembra.index', ['bloque' => $bloqueSel]) }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold">Limpiar</a>
        </div>

    </form>
</div>

{{--  FORMULARIO DE EDICIÓN MASIVA --}}
<form method="POST" action="{{ route('plano_siembra.actualizar_masivo') }}">
    @csrf
    @method('PUT')
    
    {{-- Barra de Herramientas Masiva --}}
    <div class="card card-dashboard p-3 mb-3 border-primary bg-light shadow-sm card-movil-full">
        
        {{-- ENCABEZADO CLICABLE (Oculta/Muestra el panel) --}}
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2" 
             data-bs-toggle="collapse" href="#panelEdicionRapida" role="button" aria-expanded="false" style="cursor: pointer;">
            <h6 class="text-primary mb-0 fw-bold">
                ⚡ Edición Rápida 
                <span class="d-inline d-md-none text-muted fw-normal" style="font-size: 0.65rem;">(Toca para abrir/cerrar) 🔽</span>
            </h6>
            {{-- Contador Dinámico --}}
            <span id="contador-seleccionadas" class="badge bg-secondary px-3 py-2 shadow-sm" style="font-size: 0.75rem;">
                0 camas
            </span>
        </div>
        
        {{-- CONTENEDOR COLAPSABLE --}}
        <div class="collapse mt-2" id="panelEdicionRapida">
            <div class="row g-2 align-items-end">
                {{-- Nota el uso de "col-6" para que ocupen la mitad de la pantalla en celulares --}}
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-muted mb-0" style="font-size: 0.7rem;">Variedad</label>
                    <select name="ID_Variedad" class="form-select form-select-sm">
                        <option value="">-- Sin cambio --</option>
                        @foreach ($variedades as $v)
                            <option value="{{ $v->ID_Variedad }}">{{ $v->Nombre_Variedad }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-muted mb-0" style="font-size: 0.7rem;">Estado</label>
                    <select name="Estado_Siembra" class="form-select form-select-sm">
                        <option value="">-- Sin cambio --</option>
                        <option value="SEMBRADA">SEMBRADA</option>
                        <option value="EN_PRODUCCION">EN PRODUCCIÓN</option>
                        <option value="ERRADICADA">ERRADICADA</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-muted mb-0" style="font-size: 0.7rem;">Cant. Plantas</label>
                    <input type="number" name="Cantidad_Plantas" class="form-control form-control-sm" placeholder="Plantas">
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-success mb-0" style="font-size: 0.7rem;">F. Siembra</label>
                    <input type="date" name="Fecha_Siembra" class="form-control form-control-sm border-success">
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-muted mb-0" style="font-size: 0.7rem;">F. Pinch</label>
                    <input type="date" name="Fecha_Pinch" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-muted mb-0" style="font-size: 0.7rem;">F. Hormona</label>
                    <input type="date" name="Fecha_Hormona" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="small fw-bold text-danger mb-0" style="font-size: 0.7rem;">F. Erradicación</label>
                    <input type="date" name="Fecha_Erradicacion" class="form-control form-control-sm border-danger">
                </div>
                <div class="col-6 col-md-2 ms-auto">
                    <button type="submit" id="btn-guardar-masivo" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm" disabled onclick="return confirm('¿Aplicar los cambios a TODAS las camas seleccionadas?')">
                        💾 Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Lineal Exacta -->
    <div class="card shadow-sm border-0 overflow-hidden card-movil-full">
        <div class="table-responsive" style="max-height: 72vh;">
            <table class="table table-bordered table-excel mb-0 align-middle">
                <thead>
                    <tr>
                        {{-- Checkbox de Seleccionar Todo y Cabeceras Fijas --}}
                        <th class="sticky-check text-center"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                        <th class="sticky-nave">NAVE</th>
                        <th class="sticky-cama">CAMA</th>
                        
                        {{-- 🟢 Títulos Abreviados para ahorrar ancho --}}
                        <th title="Cuadros de la cama">CUADROS</th>
                        <th title="Fecha de Siembra">F. SIEMBRA</th>
                        <th>VARIEDAD</th>
                        <th title="Fecha de Pinch">F. PINCH</th>
                        <th title="Fecha de Hormona">F. HORMONA</th>
                        <th title="Fecha de Erradicación">F. ERRADIC.</th>
                        <th title="Estado de la Siembra">ESTADO</th>
                        <th title="Plantas Sembradas">PLANTAS</th>
                        <th title="Medidas de la cama">LONGITUD</th>
                        <th>ACCIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ubicaciones as $u)
                        @php 
                            $cultivo = $siembras->get($u->ID_Ubicacion); 
                        @endphp
                        <tr class="{{ $cultivo ? 'row-ocupada' : 'row-vacia' }}">
                            
                            {{--Checkbox individual --}}
                            <td class="sticky-check text-center bg-light">
                                <input type="checkbox" name="ubicaciones_ids[]" value="{{ $u->ID_Ubicacion }}" class="form-check-input check-cama">
                            </td>
                            
                            <td class="sticky-nave fw-bold bg-light">{{ $u->Nave }}</td>
                            <td class="sticky-cama fw-bold bg-light">{{ $u->Cama }}</td>
                            
                            <td>{{ $u->Cuadros ?? '-' }}</td>
                            
                            @if($cultivo)
                                <td>{{ $cultivo->Fecha_Siembra ? \Carbon\Carbon::parse($cultivo->Fecha_Siembra)->format('d/m/Y') : '-' }}</td>
                                
                                <td class="fw-bold text-success text-start px-2">
                                    {{ $cultivo->variedad->Nombre_Variedad ?? 'N/D' }}
                                    @if(!empty($cultivo->variedad->Color))
                                        {{ $cultivo->variedad->Color }}
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($cultivo->Fecha_Pinch) && $cultivo->Fecha_Pinch !== '0000-00-00' && $cultivo->Fecha_Pinch !== '0000-00-00 00:00:00' && strpos($cultivo->Fecha_Pinch, '-0001') === false)
                                        {{ \Carbon\Carbon::parse($cultivo->Fecha_Pinch)->format('d/m/Y') }}
                                    @else
                                        SIN PINCHAR
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($cultivo->Fecha_Hormona) && $cultivo->Fecha_Hormona !== '0000-00-00' && $cultivo->Fecha_Hormona !== '0000-00-00 00:00:00' && strpos($cultivo->Fecha_Hormona, '-0001') === false)
                                        {{ \Carbon\Carbon::parse($cultivo->Fecha_Hormona)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
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
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $u->ID_Ubicacion }}">
                                        ✏️ Editar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalNuevaSiembra{{ $u->ID_Ubicacion }}">
                                        🌱 Sembrar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>

{{--  MODALES AL FINAL DE LA PÁGINA (Fuera de la tabla para que Bootstrap funcione bien) --}}
@foreach($ubicaciones as $u)
    @php 
        $cultivo = $siembras->get($u->ID_Ubicacion); 
    @endphp
    @if($cultivo)
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        //  LÓGICA DE CHECKBOXES Y CONTADOR DINÁMICO
        const checkAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.check-cama');
        const contador = document.getElementById('contador-seleccionadas');
        const btnGuardarMasivo = document.getElementById('btn-guardar-masivo');

        function actualizarContador() {
            const seleccionadas = document.querySelectorAll('.check-cama:checked').length;
            
            // Actualiza el texto
            contador.textContent = seleccionadas + (seleccionadas === 1 ? ' cama seleccionada' : ' camas seleccionadas');
            
            // Cambia colores y activa/desactiva el botón de guardar
            if(seleccionadas > 0) {
                btnGuardarMasivo.removeAttribute('disabled');
                contador.classList.replace('bg-secondary', 'bg-primary');
            } else {
                btnGuardarMasivo.setAttribute('disabled', 'true');
                contador.classList.replace('bg-primary', 'bg-secondary');
            }
        }

        if(checkAll) {
            checkAll.addEventListener('change', function () {
                checkboxes.forEach(chk => chk.checked = checkAll.checked);
                actualizarContador();
            });
        }

        checkboxes.forEach(chk => {
            chk.addEventListener('change', actualizarContador);
        });

        // Ejecutar al inicio por si el navegador dejó cajas marcadas tras refrescar
        actualizarContador();

        //  RESTAURAR SCROLL (Evita que recargue arriba del todo)
        window.addEventListener('beforeunload', function() {
            localStorage.setItem('scrollPosY', window.scrollY);
            const tableContainer = document.querySelector('.table-responsive');
            if(tableContainer) {
                localStorage.setItem('scrollPosX', tableContainer.scrollLeft);
                localStorage.setItem('scrollTableY', tableContainer.scrollTop);
            }
        });

        if (localStorage.getItem('scrollPosY') !== null) {
            window.scrollTo(0, localStorage.getItem('scrollPosY'));
            
            const tableContainer = document.querySelector('.table-responsive');
            if(tableContainer) {
                tableContainer.scrollLeft = localStorage.getItem('scrollPosX') || 0;
                tableContainer.scrollTop = localStorage.getItem('scrollTableY') || 0;
            }
            
            localStorage.removeItem('scrollPosY');
            localStorage.removeItem('scrollPosX');
            localStorage.removeItem('scrollTableY');
        }

        // Validación de fechas en formularios de modales (individuales)
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