@extends('layouts.app')

@section('title', 'Rendimiento')

@section('content')
<h2 class="page-title mb-4">⏱️ RENDIMIENTO Y MANO DE OBRA</h2>

@if ($rolNombre != 'ADMIN' && $rolNombre != 'SUPERADMIN')
    <div class="alert alert-info">👋 Hola {{ auth()->user()->Username }}. Panel de gestion de rendimiento para tu grupo asignado.</div>
@endif

<div class="card card-dashboard p-4 mb-4">
    <h5>📝 Ingreso de Tiempos y Produccion</h5>
    <form method="POST" action="{{ route('rendimiento.registrar') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">📅 Fecha</label>
                <input type="date" name="Fecha" class="form-control" required value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">👤 Colaborador</label>
                <select name="ID_Colaborador" class="form-select" required>
                    @foreach ($colaboradores as $c)
                        <option value="{{ $c->ID_Colaborador }}">{{ $c->Nombre_Colaborador }} ({{ $c->grupo->Nombre_Grupo ?? 'Sin grupo' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">✂️ Labor</label>
                <select name="Tipo_Labor" class="form-select">
                    <option>DESHOJE</option>
                    <option>CORTE LIMONIUM</option>
                    <option>CORTE STATICE</option>
                    <option>OTRA</option>
                </select>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-3">
                <label class="form-label">🟢 Hora Inicio</label>
                <input type="time" name="Hora_Inicio" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">🔴 Hora Fin</label>
                <input type="time" name="Hora_Fin" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">📦 Cantidad</label>
                <input type="number" name="Cantidad" min="0" step="0.01" class="form-control" required>
            </div>
        </div>
        <button class="btn btn-success mt-3">💾 GUARDAR REGISTRO</button>
    </form>
</div>
@endsection
