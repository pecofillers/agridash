@extends('layouts.app')

@section('title', 'Gestion de Usuarios')

@section('content')
<h2 class="page-title mb-4">👥 GESTION DE USUARIOS</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>{{ isset($usuarioSeleccionado) ? '✏️ Editar Usuario: ' . $usuarioSeleccionado->Username : '➕ Registrar Nuevo Usuario' }}</h5>
    
    <form method="POST" action="{{ route('usuarios.crear') }}">
        @csrf
        
        @if(isset($usuarioSeleccionado))
            <input type="hidden" name="ID_Usuario" value="{{ $usuarioSeleccionado->ID_Usuario }}">
        @endif

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Username</label>
                <input type="text" name="Username" value="{{ old('Username', $usuarioSeleccionado->Username ?? '') }}" class="form-control @error('Username') is-invalid @enderror" required>
                @error('Username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="Nombre" value="{{ old('Nombre', $usuarioSeleccionado->Nombre ?? '') }}" class="form-control @error('Nombre') is-invalid @enderror" required>
                @error('Nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Apellidos</label>
                <input type="text" name="Apellidos" value="{{ old('Apellidos', $usuarioSeleccionado->Apellidos ?? '') }}" class="form-control @error('Apellidos') is-invalid @enderror">
                @error('Apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Telefono</label>
                <input type="text" name="Telefono" value="{{ old('Telefono', $usuarioSeleccionado->Telefono ?? '') }}" class="form-control @error('Telefono') is-invalid @enderror">
                @error('Telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" name="Correo" value="{{ old('Correo', $usuarioSeleccionado->Correo ?? '') }}" class="form-control @error('Correo') is-invalid @enderror">
                @error('Correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-lock"></i> Contraseña</label>
                <input type="password" name="Password" class="form-control @error('Password') is-invalid @enderror" {{ isset($usuarioSeleccionado) ? '' : 'required' }}>
                <div class="form-text small">
                    {{ isset($usuarioSeleccionado) ? 'Dejar en blanco para mantener la clave actual.' : 'Mínimo 8 caracteres, mayúscula, número y símbolo.' }}
                </div>
                @error('Password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Rol</label>
                <select name="ID_Rol" class="form-select @error('ID_Rol') is-invalid @enderror" required>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->ID_Rol }}" {{ old('ID_Rol', $usuarioSeleccionado->ID_Rol ?? '') == $rol->ID_Rol ? 'selected' : '' }}>
                            {{ $rol->Nombre_Rol }}
                        </option>
                    @endforeach
                </select>
                @error('ID_Rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-success" type="submit">💾 {{ isset($usuarioSeleccionado) ? 'Guardar Cambios' : 'Registrar Usuario' }}</button>
            
            @if(isset($usuarioSeleccionado))
                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">❌ Cancelar Edición</a>
            @endif
        </div>
    </form>
</div>

<div class="card card-dashboard p-4">
    
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-2">
        <h5 class="mb-0">📋 Directorio de Usuarios</h5>
        
        <form method="GET" action="{{ route('usuarios.index') }}" class="d-flex">
            <input type="text" name="buscar" class="form-control form-control-sm me-2" placeholder="Buscar nombre, correo, usuario..." value="{{ $buscar ?? '' }}">
            <button class="btn btn-sm btn-primary text-nowrap" type="submit">🔍 Buscar</button>
            
            @if(isset($buscar) && $buscar != '')
                <a href="{{ route('usuarios.index') }}" class="btn btn-sm btn-outline-secondary ms-1" title="Limpiar búsqueda">✖️</a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle">
            <thead>
                <tr>
                    <th>Username</th><th>Nombre</th><th>Apellidos</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $u)
                    <tr>
                        <td><strong>{{ $u->Username }}</strong></td>
                        <td>{{ $u->Nombre }}</td>
                        <td>{{ $u->Apellidos }}</td>
                        <td>{{ $u->Correo }}</td>
                        <td><span class="badge bg-primary">{{ $u->rol->Nombre_Rol ?? 'Sin rol' }}</span></td>
                        <td>
                            <span class="badge bg-{{ $u->Estado == 'ACTIVO' ? 'success' : 'secondary' }}">{{ $u->Estado }}</span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('usuarios.index', ['usuario' => $u->ID_Usuario]) }}" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                                
                                <form method="POST" action="{{ route('usuarios.estado') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="ID_Usuario" value="{{ $u->ID_Usuario }}">
                                    <input type="hidden" name="Estado" value="{{ $u->Estado == 'ACTIVO' ? 'INACTIVO' : 'ACTIVO' }}">
                                    <button class="btn btn-sm btn-outline-{{ $u->Estado == 'ACTIVO' ? 'danger' : 'success' }}" {{ $u->ID_Usuario == auth()->id() ? 'disabled' : '' }}>
                                        {{ $u->Estado == 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection