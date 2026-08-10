@extends('layouts.app')

@section('title', 'Gestion de Roles y Permisos')

@section('content')
<h2 class="page-title mb-4">🛡️ GESTION DE ROLES Y PERMISOS</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>➕ Crear Nuevo Rol</h5>
    <form method="POST" action="{{ route('roles.guardar') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del Rol</label>
                <input type="text" name="Nombre_Rol" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Descripcion</label>
                <input type="text" name="Descripcion" class="form-control">
            </div>
        </div>

        <h6 class="mt-4 mb-2">Pestanas a las que el rol tendra acceso</h6>
        <p class="text-muted small">Marca las pestañas (sub-menus) que podra ver y usar este rol. El acceso a cada módulo se concede automáticamente al marcar al menos una de sus pestañas.</p>
        <div class="row g-3">
            @foreach ($modulos as $claveMod => $etiqueta)
                @if (isset($submodulos[$claveMod]))
                    <div class="col-md-4">
                        <div class="card p-3 h-100">
                            <strong>{{ $etiqueta }}</strong>
                            <hr class="my-2">
                            @foreach ($submodulos[$claveMod] as $claveSub => $labelSub)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="submodulos[{{ $claveMod }}][]" value="{{ $claveSub }}" id="sub_{{ $claveMod }}_{{ $claveSub }}">
                                    <label class="form-check-label small" for="sub_{{ $claveMod }}_{{ $claveSub }}">{{ $labelSub }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <button class="btn btn-success mt-4" type="submit">💾 Guardar Rol y Permisos</button>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 Roles Existentes</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead><tr><th>ID</th><th>Nombre</th><th>Descripcion</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach ($roles as $r)
                    <tr>
                        <td>{{ $r->ID_Rol }}</td>
                        <td>{{ $r->Nombre_Rol }}</td>
                        <td>{{ $r->Descripcion }}</td>
                        <td>
                            <a href="{{ route('roles.index', ['rol' => $r->ID_Rol]) }}" class="btn btn-sm btn-outline-primary">Ver Permisos</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($rolSeleccionado)
        <hr>
        <h5>Pestañas concedidas a: {{ $rolSeleccionado->Nombre_Rol }}</h5>
        @php
            $agrupados = $permisos->where('Permiso_Ver', true)->groupBy('Modulo');
            $mapa = $submodulos;
        @endphp
        @if ($agrupados->isEmpty())
            <p class="text-muted">Este rol no tiene pestañas concedidas aún.</p>
        @else
            <div class="row g-3">
                @foreach ($agrupados as $modulo => $items)
                    <div class="col-md-4">
                        <div class="card p-3 h-100">
                            <strong>{{ $modulos[$modulo] ?? $modulo }}</strong>
                            <hr class="my-2">
                            @foreach ($items as $p)
                                <div class="small">
                                    <i class="bi bi-check-circle text-success"></i>
                                    {{ $mapa[$modulo][$p->Submodulo] ?? $p->Submodulo }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection
