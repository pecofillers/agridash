@extends('layouts.app')

@section('title', 'Registro de Produccion')

@section('content')
<!-- BLOQUE DE ALERTAS Y NOTIFICACIONES -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<h2 class="page-title mb-4">👨‍🌾 REGISTRO DE PRODUCCION</h2>

<div class="card card-dashboard p-4 mb-4 bg-light border-primary">
    <div class="row align-items-center">
        <div class="col-md-12 mb-3">
            <h5>📚 Carga y Descarga Masiva de Produccion (Bloque_#.xlsx)</h5>
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

        <div class="col-md-6 border-end pe-md-4 mb-3 mb-md-0">
            <h6 class="text-warning fw-bold">🧪 Prueba Piloto: Un solo Excel</h6>
            <form action="{{ route('produccion.sincronizar_bloque') }}" method="POST" class="row g-2">
                @csrf
                <div class="col-md-8">
                    <select name="bloque" class="form-select form-select-sm" required>
                        <option value="">Selecciona el bloque a sincronizar...</option>
                        @foreach (\App\Models\Ubicacion::bloques() as $b)
                            <option value="1">Bloque 1</option>
                            <option value="2">Bloque 2</option>
                            <option value="3">Bloque 3</option>
                            <option value="4">Bloque 4</option>
                            <option value="5">Bloque 5</option>
                            <option value="6">Bloque 6</option>
                            <option value="7">Bloque 7</option>
                            <option value="8">Bloque 8</option>
                            <option value="9">Bloque 9</option>
                            <option value="10">Bloque 10</option>
                            <option value="11">Bloque 11</option>
                            <option value="12">Bloque 12</option>
                            <option value="13">Bloque 13</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success btn-sm w-100">🔄 Sincronizar</button>
                </div>
            </form>

            <!-- 🆕 Botón para sincronizar TODO -->
            <form action="{{ route('produccion.sincronizar_todo') }}" method="POST" class="mt-3"
                onsubmit="return confirm('Esto va a descargar y procesar TODOS los Excel de OneDrive configurados. Puede tardar varios minutos. ¿Continuar?');">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm w-100">
                    🔄🌐 Sincronizar TODOS los Bloques
                </button>
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