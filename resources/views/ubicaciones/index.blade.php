@extends('layouts.app')

@section('title', 'Administracion de Ubicaciones')

@section('content')
<h2 class="page-title mb-4">📍 ADMINISTRACION DE UBICACIONES</h2>

{{-- Mensajes de Éxito --}}
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row mb-4">
    {{-- Formulario de Filtros --}}
    <div class="col-md-7">
        <div class="card card-dashboard p-3 h-100">
            <h5>🔍 Filtrar Búsqueda</h5>
            <form method="GET" action="{{ route('ubicaciones.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Bloque</label>
                        <select name="Bloque" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach ($bloques as $b)
                                <option value="{{ $b }}" {{ request('Bloque') == $b ? 'selected' : '' }}>
                                    Bloque {{ $b }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nave</label>
                        <select name="Nave" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            @foreach ($naves as $n)
                                <option value="{{ $n }}" {{ request('Nave') == $n ? 'selected' : '' }}>
                                    Nave {{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('ubicaciones.index') }}" class="btn btn-outline-secondary w-100">Limpiar Filtros</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Formulario de Creación --}}
    <div class="col-md-5">
        <div class="card card-dashboard p-3 h-100">
            <h5>➕ Crear Rápida (Cama Vacía)</h5>
            <form method="POST" action="{{ route('ubicaciones.crear') }}">
                @csrf
                <div class="input-group mb-2">
                    <input type="text" name="Bloque" class="form-control" placeholder="Bloque" required>
                    <input type="text" name="Nave" class="form-control" placeholder="Nave" required>
                    <input type="text" name="Cama" class="form-control" placeholder="Cama" required>
                </div>
                <div class="input-group">
                    <input type="number" step="0.01" name="Metros_Lineales" class="form-control" placeholder="Medida (m)" required>
                    <input type="number" name="Cuadros" class="form-control" placeholder="Cuadros" required>
                    <button class="btn btn-success" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 Estructura de la Finca</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th>Bloque</th>
                    <th>Nave</th>
                    <th>Cama</th>
                    <th>Medida (m)</th>
                    <th>Cuadros</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ubicaciones as $u)
                    <tr>
                        <td>{{ $u->Bloque }}</td>
                        <td>{{ $u->Nave }}</td>
                        <td>{{ $u->Cama }}</td>
                        <td>{{ $u->Metros_Lineales }}</td>
                        <td>{{ $u->Cuadros }}</td>
                        <td>
                            @php
                                $color = 'secondary'; // Color gris por defecto
                                if($u->Estado == 'EN PRODUCCION') $color = 'success'; // Verde
                                elseif($u->Estado == 'SEMBRADA') $color = 'primary'; // Azul
                                elseif($u->Estado == 'ERRADICADA') $color = 'danger'; // Rojo
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ $u->Estado }}</span>
                        </td>
                        <td>
                            {{-- Botón para abrir modal de edición --}}
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $u->ID_Ubicacion }}">
                                ✏️ Editar
                            </button>
                        </td>
                    </tr>

                    {{-- Modal de Edición para cada fila --}}
                    <div class="modal fade" id="editModal{{ $u->ID_Ubicacion }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('ubicaciones.actualizar', $u->ID_Ubicacion) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar Ubicación</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Bloque</label>
                                                <input type="text" name="Bloque" class="form-control" value="{{ $u->Bloque }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Nave</label>
                                                <input type="text" name="Nave" class="form-control" value="{{ $u->Nave }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Cama</label>
                                                <input type="text" name="Cama" class="form-control" value="{{ $u->Cama }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Metros Lineales</label>
                                                <input type="number" step="0.01" name="Metros_Lineales" class="form-control" value="{{ $u->Metros_Lineales }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Cuadros</label>
                                                <input type="number" name="Cuadros" class="form-control" value="{{ $u->Cuadros }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Estado</label>
                                                <select name="Estado" class="form-select">
                                                    <option value="ERRADICADA" {{ $u->Estado == 'ERRADICADA' ? 'selected' : '' }}>ERRADICADA</option>
                                                    <option value="SEMBRADA" {{ $u->Estado == 'SEMBRADA' ? 'selected' : '' }}>SEMBRADA</option>
                                                    <option value="EN PRODUCCION" {{ $u->Estado == 'EN PRODUCCION' ? 'selected' : '' }}>EN PRODUCCION</option>
                                                </select>
                                            </div>
                                                                                    </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- Controles de Paginación --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $ubicaciones->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection