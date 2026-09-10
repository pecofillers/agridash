@extends('layouts.app')

@section('title', 'Configuración de Planillas OneDrive')

@section('content')
<h2 class="page-title mb-4">⚙️ CONFIGURACIÓN DE ENLACES ONEDRIVE</h2>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Formulario para Guardar -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🔗 Registrar o Actualizar Enlace</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Selecciona el bloque y pega el enlace de OneDrive (asegúrate de que permita descargar).</p>
                <form action="{{ route('configuracion.planillas.guardar') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Bloque</label>
                        <select name="bloque" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach($bloques as $b)
                                <option value="{{ $b }}">Bloque {{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Enlace OneDrive</label>
                        <input type="url" name="url_onedrive" class="form-control form-control-sm" placeholder="https://1drv.ms/x/c/..." required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">💾 Guardar Enlace</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabla de Enlaces Guardados -->
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📋 Enlaces Configurados</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Bloque</th>
                                <th>Enlace Registrado</th>
                                <th>Última Actualización</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planillas as $p)
                                <tr>
                                    <td class="fw-bold fs-6">Bloque {{ $p->bloque }}</td>
                                    <td>
                                        <a href="{{ $p->url_onedrive }}" target="_blank" class="text-truncate d-inline-block text-primary" style="max-width: 200px;" title="{{ $p->url_onedrive }}">
                                            Abrir Archivo ↗️
                                        </a>
                                    </td>
                                    <td class="text-muted small">{{ $p->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted py-4">No hay enlaces configurados aún. Rellena el formulario de la izquierda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection