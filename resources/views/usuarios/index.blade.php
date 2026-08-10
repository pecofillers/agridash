@extends('layouts.app')

@section('title', 'Gestion de Usuarios')

@section('content')
<h2 class="page-title mb-4">👥 GESTION DE USUARIOS</h2>

<div class="card card-dashboard p-4 mb-4">
    <h5>➕ Registrar Nuevo Usuario</h5>
    <form method="POST" action="{{ route('usuarios.crear') }}">
        @csrf
<div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Username</label>
                <input type="text" name="Username" value="{{ old('Username') }}" class="form-control @error('Username') is-invalid @enderror" required>
                @error('Username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="Nombre" value="{{ old('Nombre') }}" class="form-control @error('Nombre') is-invalid @enderror" required>
                @error('Nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Apellidos</label>
                <input type="text" name="Apellidos" value="{{ old('Apellidos') }}" class="form-control @error('Apellidos') is-invalid @enderror">
                @error('Apellidos')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Telefono</label>
                <input type="text" name="Telefono" value="{{ old('Telefono') }}" class="form-control @error('Telefono') is-invalid @enderror">
                @error('Telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" name="Correo" value="{{ old('Correo') }}" class="form-control @error('Correo') is-invalid @enderror">
                @error('Correo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-lock"></i> Contrasena</label>
                <input type="password" name="Password" class="form-control @error('Password') is-invalid @enderror" required>
                <div class="form-text small">Minimo 8 caracteres, con mayuscula, numero y simbolo.</div>
                @error('Password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Rol</label>
                <select name="ID_Rol" class="form-select @error('ID_Rol') is-invalid @enderror" required>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->ID_Rol }}" {{ old('ID_Rol') == $rol->ID_Rol ? 'selected' : '' }}>{{ $rol->Nombre_Rol }}</option>
                    @endforeach
                </select>
                @error('ID_Rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <button class="btn btn-success mt-3" type="submit">💾 Registrar Usuario</button>
    </form>
</div>

<div class="card card-dashboard p-4">
    <h5>📋 Directorio de Usuarios</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Username</th><th>Nombre</th><th>Apellidos</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $u)
                    <tr>
                        <td>{{ $u->Username }}</td>
                        <td>{{ $u->Nombre }}</td>
                        <td>{{ $u->Apellidos }}</td>
                        <td>{{ $u->Correo }}</td>
                        <td>{{ $u->rol->Nombre_Rol ?? 'Sin rol' }}</td>
                        <td>
                            <span class="badge bg-{{ $u->Estado == 'ACTIVO' ? 'success' : 'secondary' }}">{{ $u->Estado }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('usuarios.estado') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="ID_Usuario" value="{{ $u->ID_Usuario }}">
                                <input type="hidden" name="Estado" value="{{ $u->Estado == 'ACTIVO' ? 'INACTIVO' : 'ACTIVO' }}">
                                <button class="btn btn-sm btn-outline-{{ $u->Estado == 'ACTIVO' ? 'danger' : 'success' }}">
                                    {{ $u->Estado == 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
