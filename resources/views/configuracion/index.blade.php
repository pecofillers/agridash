@extends('layouts.app')

@section('title', 'Configuracion y Seguridad')

@section('content')
@php
    $rolNombre = session('rol_nombre', 'SIN ROL');
    $esAdmin = in_array($rolNombre, ['ADMIN', 'SUPERADMIN']);
    $submodulos = \App\Support\Rbac::submodulosVisibles(auth()->user()->ID_Rol, 'configuracion');
@endphp

<h2 class="page-title mb-4">⚙️ CONFIGURACION Y SEGURIDAD</h2>

{{-- ================= BLOQUE DE ALERTAS GLOBALES ================= --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>¡Éxito!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>¡Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>¡Atención! Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
{{-- ============================================================== --}}

<div class="card card-dashboard">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            @if (in_array('usuarios', $submodulos))
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-usuarios" role="tab">
                        <i class="bi bi-people"></i> Gestion de Usuarios
                    </a>
                </li>
            @endif
            
            <li class="nav-item">
                <a class="nav-link {{ !in_array('usuarios', $submodulos) ? 'active' : '' }}" data-bs-toggle="tab" href="#tab-credenciales" role="tab">
                    <i class="bi bi-key"></i> Cambio de Contraseñas
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            
            {{-- ================= SUB-TAB: GESTION DE USUARIOS ================= --}}
            @if (in_array('usuarios', $submodulos))
            <div class="tab-pane fade show active" id="tab-usuarios" role="tabpanel">
                @if (!$esAdmin)
                    <div class="alert alert-warning">🔒 Solo los administradores pueden gestionar usuarios del sistema.</div>
                @else
                    {{-- Crear nuevo usuario --}}
                    <h5 class="mb-3">➕ Crear Nuevo Usuario</h5>
                    <form method="POST" action="{{ route('configuracion.crear_usuario') }}" class="mb-4">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="Username" value="{{ old('Username') }}" class="form-control @error('Username') is-invalid @enderror" required>
                                @error('Username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nombres</label>
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

                    <hr>

                    {{-- Listado y control de usuarios --}}
                    <h5 class="mb-3">📋 Listado y Control de Usuarios</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Intentos</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($usuarios as $u)
                                    <tr>
                                        <td>{{ $u->Username }}</td>
                                        <td>{{ $u->Nombre }} {{ $u->Apellidos }}</td>
                                        <td>{{ $u->Correo ?? '—' }}</td>
                                        <td>{{ $u->rol->Nombre_Rol ?? 'Sin rol' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $u->Estado == 'ACTIVO' ? 'success' : 'secondary' }}">{{ $u->Estado }}</span>
                                        </td>
                                        <td>
                                            {{ $u->Intentos_Fallidos }}
                                            @if ($u->Bloqueado_Hasta)
                                                <span class="badge bg-danger ms-1">Bloqueado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                {{-- Cambiar estado --}}
                                                <form method="POST" action="{{ route('configuracion.cambiar_estado') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="ID_Usuario" value="{{ $u->ID_Usuario }}">
                                                    <input type="hidden" name="Estado" value="{{ $u->Estado == 'ACTIVO' ? 'INACTIVO' : 'ACTIVO' }}">
                                                    <button class="btn btn-sm btn-outline-{{ $u->Estado == 'ACTIVO' ? 'danger' : 'success' }}" {{ $u->ID_Usuario == auth()->id() ? 'disabled' : '' }}>
                                                        {{ $u->Estado == 'ACTIVO' ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                                {{-- Desbloquear --}}
                                                <form method="POST" action="{{ route('configuracion.desbloquear') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="ID_Usuario" value="{{ $u->ID_Usuario }}">
                                                    <button class="btn btn-sm btn-outline-warning" {{ $u->Bloqueado_Hasta ? '' : 'disabled' }}>🔓 Desbloquear</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center">No hay usuarios registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @endif

            {{-- ================= SUB-TAB: CAMBIO DE CONTRASEÑAS ================= --}}
            <div class="tab-pane fade {{ !in_array('usuarios', $submodulos) ? 'show active' : '' }}" id="tab-credenciales" role="tabpanel">
                
                {{-- Cambiar mi propia contrasena --}}
                <h5 class="mb-3">🔑 Cambiar mi Contraseña</h5>
                <form method="POST" action="{{ route('configuracion.cambiar_contrasena') }}" class="mb-4">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" name="password_actual" class="form-control @error('password_actual') is-invalid @enderror" required>
                            @error('password_actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="password_nueva" class="form-control @error('password_nueva') is-invalid @enderror" required>
                            <div class="form-text small">Mínimo 8 caracteres, con mayúscula, número y símbolo.</div>
                            @error('password_nueva')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" name="password_nueva_confirmation" class="form-control @error('password_nueva') is-invalid @enderror" required>
                        </div>
                    </div>
                    <button class="btn btn-success mt-3">🔒 Cambiar Contraseña</button>
                </form>

                {{-- Solo Administradores ven la opción de cambiar claves de otros --}}
                @if ($esAdmin)
                    <hr>
                    <h5 class="mb-3">🔑 Restablecer Contraseña de Usuario (Admin)</h5>
                    <form method="POST" action="{{ route('configuracion.restablecer_contrasena') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Usuario</label>
                                <select name="ID_Usuario" class="form-select" required>
                                    @foreach ($usuarios as $u)
                                        <option value="{{ $u->ID_Usuario }}">{{ $u->Username }} ({{ $u->Nombre }} {{ $u->Apellidos }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="Password" class="form-control @error('Password') is-invalid @enderror" required>
                                <div class="form-text small">Mínimo 8 caracteres, con mayúscula, número y símbolo.</div>
                                @error('Password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" name="Password_confirmation" class="form-control" required>
                            </div>
                        </div>
                        <button class="btn btn-success mt-3">🔒 Actualizar Contraseña</button>
                    </form>
                @endif
            </div>
        </div>  
    </div>
</div>
@endsection