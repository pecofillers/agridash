@extends('layouts.app')

@section('title', 'Registro de Produccion')

@section('content')
<h2 class="page-title mb-4">👨‍🌾 REGISTRO DE PRODUCCION</h2>

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
    <h5>📝 INGRESAR NUEVO REGISTRO</h5>
    <form method="POST" action="{{ route('produccion.guardar') }}">
        @csrf
        <input type="hidden" name="ID_Ubicacion" value="{{ $idUbicacion }}">
        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <label class="form-label">Semana #</label>
                <input type="number" name="Semana" min="1" max="53" class="form-control" value="{{ $semana ?? 1 }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Año</label>
                <select name="Anio" class="form-select">
                    @foreach ([2026, 2025, 2024, 2023] as $anio)
                        <option value="{{ $anio }}" {{ ($anio == ($anio ?? date('Y'))) ? 'selected' : '' }}>{{ $anio }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-2 mt-3">
            @foreach (['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo','Bajas'] as $d)
                <div class="col-md-3">
                    <label class="form-label">{{ $d }}</label>
                    <input type="number" name="{{ $d }}" min="0" value="0" class="form-control">
                </div>
            @endforeach
        </div>
        <button class="btn btn-success mt-4" type="submit">💾 GUARDAR REGISTRO</button>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 REGISTROS</h5>
    @if ($registros->count())
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Ubicación</th><th>Sem</th><th>Anio</th>
                        <th>Lun</th><th>Mar</th><th>Mie</th><th>Jue</th><th>Vie</th><th>Sab</th><th>Dom</th><th>Bajas</th><th>Total</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $r)
                        <tr>
                            <td>{{ $r->ubicacion->Bloque }} / {{ $r->ubicacion->Nave }} / {{ $r->ubicacion->Cama }}</td>
                            <td>{{ $r->Semana }}</td><td>{{ $r->Anio }}</td>
                            <td>{{ $r->Lunes }}</td><td>{{ $r->Martes }}</td><td>{{ $r->Miercoles }}</td>
                            <td>{{ $r->Jueves }}</td><td>{{ $r->Viernes }}</td><td>{{ $r->Sabado }}</td>
                            <td>{{ $r->Domingo }}</td><td>{{ $r->Bajas }}</td><td><strong>{{ $r->Total }}</strong></td>
                            <td><a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editar{{ $r->ID_Produccion }}">Editar</a></td>
                        </tr>
                        <tr class="collapse" id="editar{{ $r->ID_Produccion }}">
                            <td colspan="13">
                                <form method="POST" action="{{ route('produccion.actualizar') }}">
                                    @csrf
                                    <input type="hidden" name="ID_Produccion" value="{{ $r->ID_Produccion }}">
                                    <div class="row g-2">
                                        @foreach (['Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo','Bajas'] as $d)
                                            <div class="col-md-3">
                                                <label class="form-label small">{{ $d }}</label>
                                                <input type="number" name="{{ $d }}" value="{{ $r->$d }}" class="form-control form-control-sm">
                                            </div>
                                        @endforeach
                                    </div>
                                    <button class="btn btn-sm btn-primary mt-2">🔄 Actualizar</button>
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
