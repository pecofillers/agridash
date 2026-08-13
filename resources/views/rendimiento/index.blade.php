@extends('layouts.app')

@section('title', 'Rendimiento')

@section('content')
<h2 class="page-title mb-4">⏱️ RENDIMIENTO Y MANO DE OBRA</h2>

@if ($rolNombre != 'ADMIN' && $rolNombre != 'SUPERADMIN')
    @php
        $userAuth = auth()->user();
        $nombreCompletoAuth = trim(($userAuth->Nombre ?? '') . ' ' . ($userAuth->Apellidos ?? ''));
    @endphp
    <div class="alert alert-info">👋 Hola {{ $nombreCompletoAuth }}. Panel de gestión de rendimiento para tu grupo asignado.</div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card card-dashboard p-4 mb-4">
    <h5>📝 Ingreso de Tiempos y Producción</h5>
    
    <form method="POST" action="{{ route('rendimiento.registrar') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">📅 Fecha</label>
                <input type="date" name="Fecha" class="form-control" required value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">👤 Usuario</label>
                <select name="ID_Usuario" class="form-select" required>
                    <option value="">-- Seleccione Usuario --</option>
                    @foreach ($usuarios as $c)
                        <option value="{{ $c->ID_Usuario }}">{{ trim(($c->Nombre ?? '') . ' ' . ($c->Apellidos ?? '')) ?: $c->Username }} ({{ $c->grupo->Nombre_Grupo ?? 'Sin grupo' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">✂️ Labor</label>
                <select name="ID_Labor" class="form-select" required>
                    <option value="">-- Seleccione --</option>
                    @foreach ($labores as $labor)
                        <option value="{{ $labor->ID_Labor }}">{{ $labor->Nombre_Labor }}</option>
                    @endforeach
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
                <input type="number" name="Cantidad" min="0" max="9999999999" step="0.01" class="form-control" maxlength="10" oninput="if(this.value.length > 10) this.value = this.value.slice(0, 10);" required>
            </div>
        </div>

        <button class="btn btn-success mt-3">💾 GUARDAR REGISTRO</button>

        <div class="mt-3" style="background-color: var(--secondary-background-color); padding: 12px; border-radius: 8px; border-left: 5px solid #2e7d32;">
            @if(session('ultimo_registro'))
                <b>⏱️ Hora Inicio:</b> {{ session('ultimo_registro')->Hora_Inicio }} &nbsp;|&nbsp;
                <b>⏱️ Hora Fin:</b> {{ session('ultimo_registro')->Hora_Fin }} &nbsp;|&nbsp;
                <b>Total:</b> {{ session('ultimo_registro')->Horas_Trabajadas }} hrs &nbsp;|&nbsp;
                <b>⚡ Rendimiento (Último guardado):</b> 
                <span style="font-size: 1.2rem; font-weight: bold; color: #2e7d32;">
                    {{ number_format(session('ultimo_registro')->Rendimiento_Hora, 2) }} /hora
                </span>
            @else
                <b>⏱️ Hora Inicio:</b> --:-- &nbsp;|&nbsp;
                <b>⏱️ Hora Fin:</b> --:-- &nbsp;|&nbsp;
                <b>Total:</b> -- hrs &nbsp;|&nbsp;
                <b>⚡ Rendimiento:</b> 
                <span style="font-size: 1.2rem; font-weight: bold; color: #2e7d32;">
                    -- /hora
                </span>
            @endif
        </div>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5 class="mb-3">📊 Registros de Rendimiento Recientes</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Labor</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Cantidad</th>
                    <th>Rendimiento/H</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rendimientos ?? [] as $item)
                    <tr>
                        <td>{{ $item->Fecha ? \Carbon\Carbon::parse($item->Fecha)->format('Y-m-d') : '' }}</td>
                        <td>{{ $item->usuario ? trim(($item->usuario->Nombre ?? '') . ' ' . ($item->usuario->Apellidos ?? '')) ?: $item->usuario->Username : 'N/D' }}</td>
                        <td><span class="badge bg-secondary">{{ $item->labor->Nombre_Labor ?? 'N/D' }}</span></td>
                        <td>{{ $item->Hora_Inicio }}</td>
                        <td>{{ $item->Hora_Fin }}</td>
                        <td>{{ number_format($item->Cantidad, 2) }}</td>
                        <td><strong>{{ number_format($item->Rendimiento_Hora, 2) }}</strong></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $item->ID_Rendimiento }}">
                                ✏️ Editar
                            </button>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditar{{ $item->ID_Rendimiento }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('rendimiento.actualizar', $item->ID_Rendimiento) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar Registro de Rendimiento</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label class="form-label">📅 Fecha</label>
                                            <input type="date" name="Fecha" class="form-control" value="{{ $item->Fecha }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">👤 Usuario</label>
                                            <select name="ID_Usuario" class="form-select" required>
                                                @foreach ($usuarios as $c)
                                                    <option value="{{ $c->ID_Usuario }}" {{ $item->ID_Usuario == $c->ID_Usuario ? 'selected' : '' }}>
                                                        {{ trim(($c->Nombre ?? '') . ' ' . ($c->Apellidos ?? '')) ?: $c->Username }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">✂️ Labor</label>
                                            <select name="ID_Labor" class="form-select" required>
                                                @foreach ($labores as $labor)
                                                    <option value="{{ $labor->ID_Labor }}" {{ $item->ID_Labor == $labor->ID_Labor ? 'selected' : '' }}>
                                                        {{ $labor->Nombre_Labor }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">🟢 Hora Inicio</label>
                                                <input type="time" name="Hora_Inicio" class="form-control" value="{{ $item->Hora_Inicio }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">🔴 Hora Fin</label>
                                                <input type="time" name="Hora_Fin" class="form-control" value="{{ $item->Hora_Fin }}" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">📦 Cantidad</label>
                                            <input type="number" name="Cantidad" min="0" max="9999999999" step="0.01" class="form-control" value="{{ $item->Cantidad }}" maxlength="10" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-success">Actualizar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay registros de rendimiento guardados todavía para tu grupo hoy.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection