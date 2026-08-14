@extends('layouts.app')

@section('title', 'Catálogo de labores')

@section('content')
<h2 class="page-title mb-4">🧰 CATÁLOGO DE LABORES</h2>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-dashboard p-4 mb-4 shadow-sm">
    <h5 class="mb-3">
        {{ isset($laborSeleccionada) ? '✏️ Editar Labor: ' . $laborSeleccionada->Nombre_Labor : '➕ Agregar nueva labor al catálogo' }}
    </h5>
    
    <form method="POST" action="{{ route('rendimiento.guardarLaborCatalogo') }}" class="row g-3 align-items-end">
        @csrf
        
        @if(isset($laborSeleccionada))
            <input type="hidden" name="ID_Labor" value="{{ $laborSeleccionada->ID_Labor }}">
        @endif

        <div class="col-md-3">
            <label class="form-label fw-bold">Nombre de la Labor</label>
            <input type="text" name="Nombre_Labor" class="form-control" placeholder="Ej: DESHOJE" value="{{ old('Nombre_Labor', $laborSeleccionada->Nombre_Labor ?? '') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold">Unidad de Medida</label>
            <input type="text" name="Unidad_Medida" class="form-control" placeholder="Ej: CUADROS" value="{{ old('Unidad_Medida', $laborSeleccionada->Unidad_Medida ?? '') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold text-success">Meta Verde (Óptimo)</label>
            <input type="number" step="0.01" min="0" name="Umbral_Verde" class="form-control border-success" value="{{ old('Umbral_Verde', $laborSeleccionada->Umbral_Verde ?? '') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold text-warning">Meta Naranja (Mínimo)</label>
            <input type="number" step="0.01" min="0" name="Umbral_Naranja" class="form-control border-warning" value="{{ old('Umbral_Naranja', $laborSeleccionada->Umbral_Naranja ?? '') }}" required>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100 fw-bold">
                💾 {{ isset($laborSeleccionada) ? 'Actualizar' : 'Guardar' }}
            </button>
            @if(isset($laborSeleccionada))
                <a href="{{ route('rendimiento.labores') }}" class="btn btn-secondary" title="Cancelar Edición">❌</a>
            @endif
        </div>

        <div class="col-12 mt-4">
            <label class="form-label fw-bold text-info">🌸 Variantes de esta labor (Opcional)</label>
            <p class="small text-muted mb-2">Agrega aquí si la labor tiene diferentes tipos (Ej: Blanco, Azul, Rojo). Si es una labor sencilla (Ej: Deshoje), déjalo vacío.</p>
            
            <div id="variantes-container" class="d-flex flex-wrap gap-2 mb-2">
                </div>
            
            <button type="button" class="btn btn-sm btn-outline-info fw-bold" onclick="agregarVariante()">
                ➕ Agregar variante
            </button>

            <input type="hidden" name="Variantes" id="hiddenVariantes" value="{{ old('Variantes', $laborSeleccionada->Variantes ?? '') }}">
        </div>
    </form>
</div>

<div class="card card-dashboard p-4 shadow-sm">
    <h5 class="mb-3">📋 Labores registradas actualmente</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle table-striped">
            <thead>
                <tr>
                    <th>Labor</th>
                    <th>Unidad</th>
                    <th>Meta Verde</th>
                    <th>Meta Naranja</th>
                    <th>Variantes</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($labores as $l)
                    <tr>
                        <td><strong>{{ $l->Nombre_Labor }}</strong></td>
                        <td><span class="badge bg-light text-dark border">{{ $l->Unidad_Medida }}</span></td>
                        <td class="text-success fw-bold">≥ {{ $l->Umbral_Verde }}</td>
                        <td class="text-warning fw-bold">≥ {{ $l->Umbral_Naranja }}</td>
                        <td>
                            @if(!empty($l->Variantes))
                                @foreach(explode(',', $l->Variantes) as $v)
                                    <span class="badge bg-info text-dark border me-1">{{ trim($v) }}</span>
                                @endforeach
                            @else
                                <span class="text-muted small">Sin variantes</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-success">Activo</span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('rendimiento.labores', ['editar' => $l->ID_Labor]) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    ✏️
                                </a>
                                <form action="{{ route('rendimiento.eliminarLaborCatalogo', $l->ID_Labor) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta labor del catálogo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay labores registradas en el sistema.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    // Función para crear visualmente un input de variante
    function agregarVariante(valor = '') {
        const container = document.getElementById('variantes-container');
        
        const div = document.createElement('div');
        div.className = 'input-group input-group-sm w-auto';
        div.innerHTML = `
            <input type="text" class="form-control variante-input" placeholder="Nombre variante" value="${valor}" oninput="syncVariantes()">
            <button class="btn btn-outline-danger" type="button" onclick="eliminarVariante(this)">❌</button>
        `;
        
        container.appendChild(div);
        syncVariantes();
    }

    // Función para eliminar un input y sincronizar
    function eliminarVariante(btn) {
        btn.parentElement.remove();
        syncVariantes();
    }

    // Función para actualizar el campo oculto (que se envía a Laravel)
    function syncVariantes() {
        const inputs = document.querySelectorAll('.variante-input');
        // Extraemos los valores, los limpiamos de espacios y quitamos los vacíos
        const values = Array.from(inputs)
            .map(i => i.value.trim())
            .filter(v => v !== '');
        
        // Guardamos todo separado por comas en el input hidden
        document.getElementById('hiddenVariantes').value = values.join(',');
    }

    // Al cargar la página, verificamos si ya hay variantes guardadas (en modo edición) y las dibujamos
    document.addEventListener('DOMContentLoaded', () => {
        const initialVariantes = document.getElementById('hiddenVariantes').value;
        if (initialVariantes) {
            const variantesArray = initialVariantes.split(',');
            variantesArray.forEach(v => {
                if (v.trim() !== '') {
                    agregarVariante(v.trim());
                }
            });
        }
    });
</script>
@endsection