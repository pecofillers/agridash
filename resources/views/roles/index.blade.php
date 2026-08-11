@extends('layouts.app')

@section('title', 'Gestion de Roles y Permisos')

@section('content')
<h2 class="page-title mb-4">🛡️ GESTION DE ROLES Y PERMISOS</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>{{ $rolSeleccionado ? '✏️ Editar Rol: ' . $rolSeleccionado->Nombre_Rol : '➕ Crear Nuevo Rol' }}</h5>
    
    <form method="POST" action="{{ route('roles.guardar') }}">
        @csrf
        
        @if($rolSeleccionado)
            <input type="hidden" name="ID_Rol" value="{{ $rolSeleccionado->ID_Rol }}">
        @endif
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del Rol</label>
                <input type="text" name="Nombre_Rol" class="form-control" value="{{ $rolSeleccionado->Nombre_Rol ?? '' }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Descripcion</label>
                <input type="text" name="Descripcion" class="form-control" value="{{ $rolSeleccionado->Descripcion ?? '' }}">
            </div>
        </div>

        <h6 class="mt-4 mb-2">Pestañas a las que el rol tendrá acceso</h6>
        <p class="text-muted small">Marca las pestañas (sub-menus) que podra ver y usar este rol. El acceso a cada módulo se concede automáticamente al marcar al menos una de sus pestañas.</p>
        
        <div class="row g-3">
            @foreach ($modulos as $claveMod => $etiqueta)
                @if (isset($submodulos[$claveMod]))
                    <div class="col-md-4">
                        <div class="card p-3 h-100">
                            <strong>{{ $etiqueta }}</strong>
                            <hr class="my-2">
                            @foreach ($submodulos[$claveMod] as $claveSub => $labelSub)
                                @php
                                    // Logica para determinar si este checkbox debe estar marcado
                                    $isChecked = false;
                                    if ($rolSeleccionado && $permisos) {
                                        $isChecked = $permisos->where('Modulo', $claveMod)
                                                              ->where('Submodulo', $claveSub)
                                                              ->where('Permiso_Ver', true)
                                                              ->isNotEmpty();
                                    }
                                @endphp
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="submodulos[{{ $claveMod }}][]" value="{{ $claveSub }}" id="sub_{{ $claveMod }}_{{ $claveSub }}" {{ $isChecked ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="sub_{{ $claveMod }}_{{ $claveSub }}">{{ $labelSub }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-success" type="submit">💾 Guardar Rol y Permisos</button>
            
            @if($rolSeleccionado)
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">❌ Cancelar Edición</a>
            @endif
        </div>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 Roles Existentes</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach ($roles as $r)
                    <tr>
                        <td>{{ $r->ID_Rol }}</td>
                        <td>{{ $r->Nombre_Rol }}</td>
                        <td>{{ $r->Descripcion }}</td>
                        <td>
                            <a href="{{ route('roles.index', ['rol' => $r->ID_Rol]) }}" class="btn btn-sm btn-outline-primary">✏️ Editar / Ver Permisos</a>
                            
                            <form method="POST" action="{{ route('roles.borrar') }}" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este rol?');">
                                @csrf
                                <input type="hidden" name="ID_Rol" value="{{ $r->ID_Rol }}">
                                <button class="btn btn-sm btn-outline-danger">🗑️ Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection