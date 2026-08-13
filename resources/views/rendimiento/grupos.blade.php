@extends('layouts.app')

@section('title', 'Gestion de Grupos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">👥 GESTION DE GRUPOS Y EQUIPO</h2>
    <a href="{{ route('rendimiento.labores') }}" class="btn btn-outline-secondary">
        <i class="bi bi-wrench"></i> Catalogo de Labores
    </a>
</div>

<div class="card card-dashboard p-4 mb-4">
    <h5>➕ Crear Nuevo Grupo</h5>
    <form method="POST" action="{{ route('rendimiento.crearGrupo') }}" class="row g-2">
        @csrf
        <div class="col-md-5">
            <input type="text" name="Nombre_Grupo" class="form-control" placeholder="Nombre del grupo (ej: GRUPO 4)" required>
        </div>
        <div class="col-md-5">
            <select name="Supervisor_Asignado" class="form-select" required>
                <option value="">-- Seleccione un Supervisor --</option>
                @foreach ($supervisores as $s)
                    <option value="{{ $s->Username }}">
                        {{ trim(($s->Nombre ?? '') . ' ' . ($s->Apellidos ?? '')) ?: $s->Username }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Crear Grupo</button>
        </div>
    </form>
</div>

<div class="card card-dashboard p-4 mb-4">
    <h5>👤 Asignar Usuario a un Grupo</h5>
    <form method="POST" action="{{ route('rendimiento.agregar') }}" class="row g-2">
        @csrf
        <div class="col-md-5">
            <select name="ID_Usuario" class="form-select" required>
                <option value="">-- Seleccione Usuario sin grupo --</option>
                @foreach ($usuariosSinGrupo as $colab)
                    <option value="{{ $colab->ID_Usuario }}">{{ trim(($colab->Nombre ?? '') . ' ' . ($colab->Apellidos ?? '')) ?: $colab->Username }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <select name="ID_Grupo" class="form-select" required>
                <option value="">-- Seleccione el Grupo --</option>
                @foreach ($grupos as $g)
                    <option value="{{ $g->ID_Grupo }}">{{ $g->Nombre_Grupo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100">➕ Agregar</button>
        </div>
    </form>
</div>

<div class="card card-dashboard p-4 mb-4">
    <h5>🏢 Grupos y Supervisores</h5>
    @if (in_array($rolNombre, ['ADMIN', 'SUPERADMIN']))
        <div class="alert alert-info small">💡 Puedes reasignar el supervisor de cada grupo usando el selector.</div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>Grupo</th><th>Supervisor Asignado</th><th>Accion</th></tr></thead>
            <tbody>
                @forelse ($grupos as $g)
                    <tr>
                        <td>{{ $g->Nombre_Grupo }}</td>
                        <td>
                            @if (in_array($rolNombre, ['ADMIN', 'SUPERADMIN']))
                                <form method="POST" action="{{ route('rendimiento.actualizarSupervisor') }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="ID_Grupo" value="{{ $g->ID_Grupo }}">
                                    <select name="Supervisor_Asignado" class="form-select form-select-sm">
                                        @foreach ($supervisores as $s)
                                            <option value="{{ $s->Username }}" {{ $g->Supervisor_Asignado == $s->Username ? 'selected' : '' }}>
                                                {{ trim(($s->Nombre ?? '') . ' ' . ($s->Apellidos ?? '')) ?: $s->Username }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">💾 Reasignar</button>
                                </form>
                            @else
                                {{ $g->Supervisor_Asignado ?? 'N/A' }}
                            @endif
                        </td>
                        <td>{{ $usuarios->where('ID_Grupo', $g->ID_Grupo)->count() }} integrantes</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">No hay grupos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card card-dashboard p-4">
    <h5>👥 Usuarios por Grupo</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>Usuario</th><th>Grupo</th><th>Supervisor</th><th>Accion</th></tr></thead>
            <tbody>
                @foreach ($usuarios as $c)
                    <tr>
                        <td>{{ trim(($c->Nombre ?? '') . ' ' . ($c->Apellidos ?? '')) ?: $c->Username }}</td>
                        <td>{{ $c->grupo->Nombre_Grupo ?? 'Sin asignar' }}</td>
                        <td>{{ $c->grupo->Supervisor_Asignado ?? 'N/A' }}</td>
                        <td>
                            @if ($c->ID_Grupo)
                                <form method="POST" action="{{ route('rendimiento.quitar') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="ID_Usuario" value="{{ $c->ID_Usuario }}">
                                    <button class="btn btn-sm btn-outline-danger">❌ Quitar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection