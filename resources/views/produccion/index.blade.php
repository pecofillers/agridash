@extends('layouts.app')

@section('title', 'Registro de Produccion')

@section('content')
<h2 class="page-title mb-4">👨‍🌾 REGISTRO DE PRODUCCION</h2>

<div class="card card-dashboard p-4 mb-4 bg-light border-primary">
    <div class="row align-items-center">
        <div class="col-md-12 mb-3">
            <h5>📚 Carga y Descarga Masiva Multi-Nave (.xlsx)</h5>
            <p class="text-muted small mb-0">
                Cada pestaña/hoja del Excel corresponde a una Nave (ej: <i>Nave 1, Nave 2...</i>).
            </p>
        </div>

        <div class="col-md-6 border-end pe-md-4 mb-3 mb-md-0">
            <h6>📥 Descargar Historial de un Bloque</h6>
            <form action="{{ route('produccion.exportar_multinave') }}" method="GET" class="row g-2 align-items-end mt-1">
                <div class="col-8">
                    <label class="form-label small fw-bold">Selecciona el Bloque</label>
                    <select name="bloque_exportar" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar Bloque --</option>
                        @foreach (\App\Models\Ubicacion::bloques() as $b)
                            <option value="{{ $b }}">Bloque {{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        📥 Descargar
                    </button>
                </div>
            </form>
        </div>

        <div class="col-md-6 ps-md-4">
            <h6>📤 Subir/Actualizar un Bloque</h6>
            <form action="{{ route('produccion.importar_multinave') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end mt-1">
                @csrf
                <div class="col-5">
                    <label class="form-label small fw-bold">Bloque a Cargar</label>
                    <select name="bloque_default" class="form-select form-select-sm" required>
                        <option value="">-- Bloque --</option>
                        @foreach (\App\Models\Ubicacion::bloques() as $b)
                            <option value="{{ $b }}">Bloque {{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-bold">Excel (.xlsx)</label>
                    <input type="file" name="archivo_excel" class="form-control form-control-sm" accept=".xlsx, .xls" required>
                </div>
                <div class="col-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        🚀 Subir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card card-dashboard p-4 mb-4">
    <form method="GET" action="{{ route('produccion.index') }}">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">📍 Selecciona una Ubicación</label>
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
                <label class="form-label">Semana (Filtro)</label>
                <input type="number" name="semana" min="1" max="53" value="{{ $semana ?? '' }}" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-md-3">
                <label class="form-label">Año (Filtro)</label>
                <input type="number" name="anio" value="{{ $anio ?? '' }}" class="form-control" placeholder="Opcional">
            </div>
        </div>
    </form>
</div>

@if ($idUbicacion)
<div class="card card-dashboard p-4 mb-4">
    <h5>📝 INGRESAR O ADICIONAR PRODUCCIÓN</h5>
    <p class="text-muted small">Usa los días como calculadora. Si ya hay datos en esta semana, se <strong>sumarán</strong> al total existente.</p>
    <form method="POST" action="{{ route('produccion.guardar') }}">
        @csrf
        <input type="hidden" name="ID_Ubicacion" value="{{ $idUbicacion }}">
        <div class="row g-3 mt-1">
            <div class="col-md-3">
                <label class="form-label">Semana #</label>
                <input type="number" name="Semana" min="1" max="53" class="form-control" value="{{ $semana ?? date('W') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Año</label>
                <select name="Anio" class="form-select">
                    @foreach ([date('Y')+1, date('Y'), date('Y')-1, date('Y')-2] as $a)
                        <option value="{{ $a }}" {{ ($a == ($anio ?? date('Y'))) ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-2 mt-3">
            @foreach (['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo'] as $d)
                <div class="col-md-2">
                    <label class="form-label small">{{ $d }}</label>
                    <input type="number" name="{{ $d }}" min="0" value="0" class="form-control form-control-sm text-success">
                </div>
            @endforeach
            <div class="col-md-2">
                <label class="form-label small text-danger">Bajas Nuevas</label>
                <input type="number" name="Bajas" min="0" value="0" class="form-control form-control-sm border-danger">
            </div>
        </div>
        <button class="btn btn-success mt-4" type="submit">➕ GUARDAR / ADICIONAR</button>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 REGISTROS CONSOLIDADOS DE LA CAMA</h5>
    @if ($registros->count())
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ubicación</th>
                        <th>Semana</th>
                        <th>Año</th>
                        <th class="text-danger">Total Bajas</th>
                        <th class="text-success fs-6">Gran Total</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $r)
                        <tr>
                            <td>{{ $r->ubicacion->Bloque }} / {{ $r->ubicacion->Nave }} / {{ $r->ubicacion->Cama }}</td>
                            <td>{{ $r->Semana }}</td>
                            <td>{{ $r->Anio }}</td>
                            <td class="text-danger fw-bold">{{ $r->Bajas }}</td>
                            <td class="text-success fw-bold fs-6">{{ $r->Total }}</td>
                            <td><a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editar{{ $r->ID_Produccion }}">Corregir</a></td>
                        </tr>
                        <tr class="collapse bg-light" id="editar{{ $r->ID_Produccion }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('produccion.actualizar') }}" class="p-2">
                                    @csrf
                                    <input type="hidden" name="ID_Produccion" value="{{ $r->ID_Produccion }}">
                                    <p class="small text-muted mb-2">Aquí corriges el total directamente si hubo un error en la suma:</p>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label small text-danger">Corregir Bajas</label>
                                            <input type="number" name="Bajas" value="{{ $r->Bajas }}" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small text-success">Corregir Total</label>
                                            <input type="number" name="Total" value="{{ $r->Total }}" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-sm btn-primary w-100">🔄 Actualizar</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted">No hay registros para esta ubicación.</p>
    @endif
</div>
@endif
@endsection