@extends('layouts.app')

@section('title', 'Vision Gerencial')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">📊 VISION GERENCIAL</h2>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon stat-green mx-auto">👨‍🌾</div>
            <div class="stat-value">{{ $colaboradoresActivos }}</div>
            <div class="stat-label">Colaboradores Activos</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon stat-blue mx-auto">👥</div>
            <div class="stat-value">{{ $usuarios }}</div>
            <div class="stat-label">Usuarios del Sistema</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon stat-purple mx-auto">🏢</div>
            <div class="stat-value">{{ $grupos }}</div>
            <div class="stat-label">Grupos de Trabajo</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon stat-orange mx-auto">📍</div>
            <div class="stat-value">{{ $ubicaciones }}</div>
            <div class="stat-label">Ubicaciones Registradas</div>
        </div>
    </div>
</div>

<div class="card card-dashboard mt-4 p-4">
    <div class="d-flex align-items-center gap-3">
        <div style="font-size:2.5rem;">🌱</div>
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--primary);">👋 Bienvenido, {{ auth()->user()->Nombre }}</h4>
            <p class="text-muted mb-0">Selecciona un modulo en el menu lateral izquierdo para comenzar a gestionar tu finca.</p>
        </div>
    </div>
</div>
@endsection
