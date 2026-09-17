@extends('layouts.app')

@section('title', 'Configuración de Bloques')

@section('content')
<h2 class="page-title mb-4">⚙️ CONFIGURACIÓN DE BLOQUES</h2>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🔗 Enlace OneDrive</h5>
            </div>

            <div class="card-body">
                <p class="text-muted small">
                    Selecciona el bloque y registra o actualiza su enlace de OneDrive.
                </p>

                <form action="{{ route('configuracion.bloques.guardar') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Bloque</label>

                        <select name="bloque" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar --</option>

                            @foreach($bloques as $bloque)
                            <option value="{{ $bloque->Codigo_Bloque }}">
                                {{ $bloque->Nombre_Bloque }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">
                            Enlace OneDrive
                        </label>

                        <input
                            type="url"
                            name="url_onedrive"
                            class="form-control form-control-sm"
                            placeholder="https://1drv.ms/x/c/..."
                            required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        💾 Guardar Enlace
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">📋 Bloques Configurados</h5>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Bloque</th>
                                <th>Enlace</th>
                                <th>Estado</th>
                                <th>Actualizado</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($bloques as $bloque)
                            <tr>
                                <td class="fw-bold">
                                    {{ $bloque->Nombre_Bloque }}
                                </td>

                                <td>
                                    @if($bloque->url_onedrive)
                                    <a
                                        href="{{ $bloque->url_onedrive }}"
                                        target="_blank"
                                        class="text-truncate d-inline-block text-primary"
                                        style="max-width: 200px;"
                                        title="{{ $bloque->url_onedrive }}">
                                        Abrir Archivo ↗️
                                    </a>
                                    @else
                                    <span class="text-muted">
                                        Sin configurar
                                    </span>
                                    @endif
                                </td>

                                <td>
                                    @if($bloque->url_onedrive)
                                    <span class="badge bg-success">
                                        Configurado
                                    </span>
                                    @else
                                    <span class="badge bg-secondary">
                                        Pendiente
                                    </span>
                                    @endif
                                </td>

                                <td class="text-muted small">
                                    {{ $bloque->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-muted py-4">
                                    No hay bloques registrados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm h-100 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">➕ Crear Bloque</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('configuracion.bloques.crear') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold small">
                        Código
                    </label>

                    <input
                        type="text"
                        name="Codigo_Bloque"
                        class="form-control form-control-sm"
                        maxlength="10"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">
                        Nombre
                    </label>

                    <input
                        type="text"
                        name="Nombre_Bloque"
                        class="form-control form-control-sm"
                        maxlength="40"
                        placeholder="Ej. Bloque 14">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">
                        Descripción
                    </label>

                    <textarea
                        name="Descripcion"
                        class="form-control form-control-sm"
                        rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-success btn-sm w-100">
                    ➕ Crear Bloque
                </button>
            </form>
        </div>
    </div>
</div>
@endsection