@extends('layouts.app')

@section('title', 'Catalogo de labores')

@section('content')
<h2 class="page-title mb-4">🧰 CATÁLOGO DE LABORES</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>{{ isset($laborSeleccionada) ? '✏️ Editar Labor: ' . $laborSeleccionada->Nombre_Labor : '➕ Agregar o actualizar labor' }}</h5>
    
    <form method="POST" action="{{ route('rendimiento.guardarLaborCatalogo') }}" class="row g-3 align-items-end">
        @csrf
        
        @if(isset($laborSeleccionada))
            <input type="hidden" name="ID_Labor" value="{{ $laborSeleccionada->ID_Labor }}">
        @endif

        <div class="col-md-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="Nombre_Labor" class="form-control" placeholder="DESHOJE" value="{{ old('Nombre_Labor', $laborSeleccionada->Nombre_Labor ?? '') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Unidad</label>
            <input type="text" name="Unidad_Medida" class="form-control" placeholder="CUADROS" value="{{ old('Unidad_Medida', $laborSeleccionada->Unidad_Medida ?? '') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Verde</label>
            <input type="number" step="0.01" min="0" name="Umbral_Verde" class="form-control" value="{{ old('Umbral_Verde', $laborSeleccionada->Umbral_Verde ?? '') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Naranja</label>
            <input type="number" step="0.01" min="0" name="Umbral_Naranja" class="form-control" value="{{ old('Umbral_Naranja', $laborSeleccionada->Umbral_Naranja ?? '') }}" required>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100">💾 {{ isset($laborSeleccionada) ? 'Actualizar' : 'Guardar labor' }}</button>
            @if(isset($laborSeleccionada))
                <a href="{{ route('rendimiento.labores') }}" class="btn btn-secondary">❌ Cancelar</a>
            @endif
        </div>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 Labores actuales</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th>Labor</th>
                    <th>Unidad</th>
                    <th>Verde</th>
                    <th>Naranja</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($labores as $l)
                    <tr>
                        <td><strong>{{ $l->Nombre_Labor }}</strong></td>
                        <td>{{ $l->Unidad_Medida }}</td>
                        <td>{{ $l->Umbral_Verde }}</td>
                        <td>{{ $l->Umbral_Naranja }}</td>
                        <td>
                            <span class="badge bg-success">Activo</span>
                        </td>
                        <td>
                            <a href="{{ route('rendimiento.labores', ['editar' => $l->ID_Labor]) }}" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay labores registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection