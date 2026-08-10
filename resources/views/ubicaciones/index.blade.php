@extends('layouts.app')

@section('title', 'Administracion de Ubicaciones')

@section('content')
<h2 class="page-title mb-4">📍 ADMINISTRACION DE UBICACIONES</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>➕ Crear Nueva Ubicacion</h5>
    <form method="POST" action="{{ route('ubicaciones.crear') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Bloque</label>
                <input type="text" name="Bloque" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nave</label>
                <input type="text" name="Nave" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Cama</label>
                <input type="text" name="Cama" class="form-control" required>
            </div>
        </div>
        <button class="btn btn-success mt-3" type="submit">💾 Crear Ubicacion</button>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 Estructura de la Finca</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr><th>Bloque</th><th>Nave</th><th>Cama</th><th>Estado</th></tr>
            </thead>
            <tbody>
                @foreach ($ubicaciones as $u)
                    <tr>
                        <td>{{ $u->Bloque }}</td>
                        <td>{{ $u->Nave }}</td>
                        <td>{{ $u->Cama }}</td>
                        <td><span class="badge bg-success">{{ $u->Estado }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
