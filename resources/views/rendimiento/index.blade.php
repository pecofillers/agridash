@extends('layouts.app')

@section('title', 'Rendimiento')

@section('content')
<h2 class="page-title mb-4">⏱️ RENDIMIENTO Y MANO DE OBRA</h2>

@if ($rolNombre != 'ADMIN' && $rolNombre != 'SUPERADMIN')
    @php
        $userAuth = auth()->user();
        $nombreCompletoAuth = trim(($userAuth->Nombre ?? '') . ' ' . ($userAuth->Apellidos ?? '')) ?: $userAuth->Username;
    @endphp
    <div class="alert alert-info">👋 Hola <strong>{{ $nombreCompletoAuth }}</strong>. Panel de gestión de rendimiento para tu grupo asignado.</div>
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
                <select name="ID_Labor" id="selectLabor" class="form-select" required>
                    <option value="">-- Seleccione Labor --</option>
                    @foreach ($labores as $labor)
                        <option value="{{ $labor->ID_Labor }}" data-variantes="{{ $labor->Variantes }}">{{ $labor->Nombre_Labor }}</option>
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

            <div class="col-md-3" id="containerCantidadSimple">
                <label class="form-label">📦 Cantidad Total</label>
                <input type="number" id="inputCantidadSimple" name="Cantidad" min="0" step="0.01" class="form-control" required placeholder="Ej: 150">
            </div>

            <div class="col-md-6" id="containerVariantes" style="display: none;">
                <label class="form-label text-primary font-weight-bold">🌸 Cantidades por Variante</label>
                <div class="row g-2" id="variantesInputsContainer">
                    </div>
            </div>
        </div>

        <button class="btn btn-success mt-3">💾 GUARDAR REGISTRO</button>

        <div class="mt-3" style="background-color: var(--secondary-background-color, #f8f9fa); padding: 12px; border-radius: 8px; border-left: 5px solid #2e7d32;">
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
                <span style="font-size: 1.2rem; font-weight: bold; color: #2e7d32;">-- /hora</span>
            @endif
        </div>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5 class="mb-3">📊 Registros de Rendimiento Recientes (Hoy)</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Labor</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Cantidad / Desglose</th>
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
                        <td>
                            <strong>{{ number_format($item->Cantidad, 2) }}</strong>
                            @if($item->detalles && $item->detalles->count() > 0)
                                <div class="mt-1">
                                    @foreach($item->detalles as $det)
                                        @if($det->Nombre_Variante !== 'General' && $det->Nombre_Variante !== 'General (Editado)')
                                            <span class="badge bg-light text-dark border me-1">
                                                {{ $det->Nombre_Variante }}: {{ number_format($det->Cantidad, 0) }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td><strong>{{ number_format($item->Rendimiento_Hora, 2) }}</strong></td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditar{{ $item->ID_Rendimiento }}" title="Editar">✏️</button>
                                <form action="{{ route('rendimiento.eliminar', $item->ID_Rendimiento) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este registro de forma permanente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditar{{ $item->ID_Rendimiento }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('rendimiento.actualizar', $item->ID_Rendimiento) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Editar Registro #{{ $item->ID_Rendimiento }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <div class="mb-3">
                                            <label class="form-label">📅 Fecha</label>
                                            <input type="date" name="Fecha" class="form-control" value="{{ \Carbon\Carbon::parse($item->Fecha)->format('Y-m-d') }}" required>
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
                                            <select name="ID_Labor" class="form-select" onchange="toggleEditVariantes(this, {{ $item->ID_Rendimiento }})" required>
                                                @foreach ($labores as $labor)
                                                    <option value="{{ $labor->ID_Labor }}" data-variantes="{{ $labor->Variantes }}" {{ $item->ID_Labor == $labor->ID_Labor ? 'selected' : '' }}>
                                                        {{ $labor->Nombre_Labor }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">🟢 Hora Inicio</label>
                                                <input type="time" name="Hora_Inicio" class="form-control" value="{{ $item->Hora_Inicio ? \Carbon\Carbon::parse($item->Hora_Inicio)->format('H:i') : '' }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">🔴 Hora Fin</label>
                                                <input type="time" name="Hora_Fin" class="form-control" value="{{ $item->Hora_Fin ? \Carbon\Carbon::parse($item->Hora_Fin)->format('H:i') : '' }}" required>
                                            </div>
                                        </div>

                                        @php
                                            $laborActual = $item->labor;
                                            $tieneVariantes = !empty($laborActual->Variantes);
                                        @endphp

                                        <div class="mb-3" id="containerSimpleEdit{{ $item->ID_Rendimiento }}" style="display: {{ $tieneVariantes ? 'none' : 'block' }}">
                                            <label class="form-label">📦 Cantidad Total</label>
                                            <input type="number" id="inputSimpleEdit{{ $item->ID_Rendimiento }}" name="Cantidad" min="0" step="0.01" class="form-control" value="{{ $tieneVariantes ? '' : $item->Cantidad }}" {{ $tieneVariantes ? 'disabled' : 'required' }}>
                                        </div>

                                        <div class="mb-3" id="containerVariantesEdit{{ $item->ID_Rendimiento }}" style="display: {{ $tieneVariantes ? 'block' : 'none' }}">
                                            <label class="form-label text-primary font-weight-bold">🌸 Editar Variantes</label>
                                            <div class="row g-2" id="variantesInputsContainerEdit{{ $item->ID_Rendimiento }}">
                                                @if($tieneVariantes)
                                                    @php
                                                        $varsArray = array_map('trim', explode(',', $laborActual->Variantes));
                                                        $detallesMap = $item->detalles->pluck('Cantidad', 'Nombre_Variante')->toArray();
                                                    @endphp
                                                    @foreach($varsArray as $idx => $vName)
                                                        @if($vName !== '')
                                                            @php $cant = $detallesMap[$vName] ?? ''; @endphp
                                                            <div class="col-6">
                                                                <input type="hidden" name="variantes[{{ $idx }}][nombre]" value="{{ $vName }}">
                                                                <input type="number" name="variantes[{{ $idx }}][cantidad]" class="form-control" min="0" step="0.01" placeholder="👉 {{ $vName }}" value="{{ $cant }}">
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </div>
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
                        <td colspan="8" class="text-center text-muted py-4">No hay registros guardados hoy para tu grupo asignado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// Lógica dinámica para el formulario de Creación
document.addEventListener('DOMContentLoaded', function () {
    const selectLabor = document.getElementById('selectLabor');
    const containerSimple = document.getElementById('containerCantidadSimple');
    const inputSimple = document.getElementById('inputCantidadSimple');
    const containerVariantes = document.getElementById('containerVariantes');
    const variantesContainerDiv = document.getElementById('variantesInputsContainer');

    function actualizarFormularioCreacion() {
        const selectedOption = selectLabor.options[selectLabor.selectedIndex];
        // Leemos la propiedad 'data-variantes' directamente desde la DB
        const variantesStr = selectedOption ? (selectedOption.getAttribute('data-variantes') || '') : '';

        // Limpiamos el contenedor
        variantesContainerDiv.innerHTML = '';

        if (variantesStr.trim() !== '') {
            // Ocultar labor simple
            containerSimple.style.display = 'none';
            inputSimple.removeAttribute('required');
            inputSimple.disabled = true; 
            inputSimple.value = '';
            
            // Separar el texto "DETIERRA, DEARENA" en un array y crear un input por cada uno
            const variantesArray = variantesStr.split(',');
            variantesArray.forEach((v, index) => {
                const nombreVar = v.trim();
                if(nombreVar !== '') {
                    variantesContainerDiv.innerHTML += `
                        <div class="col-6">
                            <input type="hidden" name="variantes[${index}][nombre]" value="${nombreVar}">
                            <input type="number" name="variantes[${index}][cantidad]" class="form-control" min="0" step="0.01" placeholder="👉 ${nombreVar}">
                        </div>
                    `;
                }
            });

            containerVariantes.style.display = 'block';
        } else {
            // Activar labor simple
            containerSimple.style.display = 'block';
            inputSimple.setAttribute('required', 'required');
            inputSimple.disabled = false;
            
            containerVariantes.style.display = 'none';
        }
    }

    selectLabor.addEventListener('change', actualizarFormularioCreacion);
    actualizarFormularioCreacion();
});

// Lógica dinámica para los modales de Edición
function toggleEditVariantes(selectElement, idRendimiento) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const variantesStr = selectedOption ? (selectedOption.getAttribute('data-variantes') || '') : '';
    
    const containerSimple = document.getElementById('containerSimpleEdit' + idRendimiento);
    const inputSimple = document.getElementById('inputSimpleEdit' + idRendimiento);
    const containerVariantes = document.getElementById('containerVariantesEdit' + idRendimiento);
    const variantesContainerDiv = document.getElementById('variantesInputsContainerEdit' + idRendimiento);

    variantesContainerDiv.innerHTML = '';

    if (variantesStr.trim() !== '') {
        containerSimple.style.display = 'none';
        inputSimple.removeAttribute('required');
        inputSimple.disabled = true; 
        inputSimple.value = '';
        
        const variantesArray = variantesStr.split(',');
        variantesArray.forEach((v, index) => {
            const nombreVar = v.trim();
            if(nombreVar !== '') {
                variantesContainerDiv.innerHTML += `
                    <div class="col-6">
                        <input type="hidden" name="variantes[${index}][nombre]" value="${nombreVar}">
                        <input type="number" name="variantes[${index}][cantidad]" class="form-control" min="0" step="0.01" placeholder="👉 ${nombreVar}">
                    </div>
                `;
            }
        });

        containerVariantes.style.display = 'block';
    } else {
        containerSimple.style.display = 'block';
        inputSimple.setAttribute('required', 'required');
        inputSimple.disabled = false;
        
        containerVariantes.style.display = 'none';
    }
}
</script>
@endsection