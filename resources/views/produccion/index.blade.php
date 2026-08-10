@extends('layouts.app')

@section('title', 'Registro de Produccion')

@section('content')
<h2 class="page-title mb-4">👨‍🌾 REGISTRO DE PRODUCCION</h2>

<div class="card card-dashboard p-4 mb-4">
    <form method="GET" action="{{ route('produccion.index') }}">
        <div class="row g-3">
<div class="col-md-4">
                <label class="form-label">🏢 Bloque</label>
                <select name="bloque" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($bloques as $b)
                        <option value="{{ $b }}" {{ ($bloque ?? '') == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">⛺ Nave</label>
                <select name="nave" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($naves as $n)
                        <option value="{{ $n }}" {{ ($nave ?? '') == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cama</label>
                <select name="cama" class="form-select">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($camas as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

@if ($bloque && $nave)
<div class="card card-dashboard p-4 mb-4">
    <h5>📝 INGRESAR NUEVO REGISTRO — {{ $bloque }} / {{ $nave }}</h5>
    <form method="POST" action="{{ route('produccion.guardar') }}">
        @csrf
        <input type="hidden" name="Bloque" value="{{ $bloque }}">
        <input type="hidden" name="Nave" value="{{ $nave }}">
        <div class="row g-3 mt-2">
            <div class="col-md-3">
<label class="form-label">Cama</label>
                <select name="Cama" class="form-select" required>
                    @foreach ($camas as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Semana #</label>
                <input type="number" name="Semana" min="1" max="53" class="form-control" value="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Anio</label>
                <select name="Anio" class="form-select">
                    @foreach ([2026, 2025, 2024, 2023] as $anio)
                        <option value="{{ $anio }}" {{ $anio == date('Y') ? 'selected' : '' }}>{{ $anio }}</option>
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
    <h5>📋 REGISTROS DE {{ $bloque }} / {{ $nave }}</h5>
    @if ($registros->count())
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Cama</th><th>Sem</th><th>Anio</th>
                        <th>Lun</th><th>Mar</th><th>Mie</th><th>Jue</th><th>Vie</th><th>Sab</th><th>Dom</th><th>Bajas</th><th>Total</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $r)
                        <tr>
                            <td>{{ $r->Cama }}</td><td>{{ $r->Semana }}</td><td>{{ $r->Anio }}</td>
                            <td>{{ $r->Lunes }}</td><td>{{ $r->Martes }}</td><td>{{ $r->Miercoles }}</td>
                            <td>{{ $r->Jueves }}</td><td>{{ $r->Viernes }}</td><td>{{ $r->Sabado }}</td>
                            <td>{{ $r->Domingo }}</td><td>{{ $r->Bajas }}</td><td><strong>{{ $r->Total }}</strong></td>
                            <td><a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editar{{ $r->ID_Produccion }}">Editar</a></td>
                        </tr>
                        <tr class="collapse" id="editar{{ $r->ID_Produccion }}">
                            <td colspan="12">
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
        <p class="text-muted">No hay registros para esta nave.</p>
    @endif
</div>
@endif
@endsection
