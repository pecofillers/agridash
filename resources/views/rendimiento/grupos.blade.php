@extends('layouts.app')

@section('title', 'Gestion de Grupos')

@section('content')
<h2 class="page-title mb-4">👥 GESTION DE GRUPOS Y EQUIPO</h2>

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
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Crear Grupo</button>
        </div>
    </form>
</div>

<div class="card card-dashboard p-4 mb-4">
    <h5>👤 Asignar Persona a un Grupo</h5>
    <form method="POST" action="{{ route('rendimiento.agregar') }}" class="row g-2">
        @csrf
        <div class="col-md-5">
            <select name="ID_Colaborador" class="form-select" required>
                <option value="">-- Seleccione persona sin grupo --</option>
                @foreach ($colaboradoresSinGrupo as $colab)
                    <option value="{{ $colab->ID_Colaborador }}">{{ $colab->Nombre_Colaborador }}</option>
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
                                            <option value="{{ $s }}" {{ $g->Supervisor_Asignado == $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">💾 Reasignar</button>
                                </form>
                            @else
                                {{ $g->Supervisor_Asignado ?? 'N/A' }}
                            @endif
                        </td>
                        <td>{{ $colaboradores->where('ID_Grupo', $g->ID_Grupo)->count() }} integrantes</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">No hay grupos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card card-dashboard p-4">
    <h5>👥 Colaboradores por Grupo</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>Colaborador</th><th>Grupo</th><th>Supervisor</th><th>Accion</th></tr></thead>
            <tbody>
                @foreach ($colaboradores as $c)
                    <tr>
                        <td>{{ $c->Nombre_Colaborador }}</td>
                        <td>{{ $c->grupo->Nombre_Grupo ?? 'Sin asignar' }}</td>
                        <td>{{ $c->grupo->Supervisor_Asignado ?? 'N/A' }}</td>
                        <td>
                            @if ($c->ID_Grupo)
                                <form method="POST" action="{{ route('rendimiento.quitar') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="ID_Colaborador" value="{{ $c->ID_Colaborador }}">
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