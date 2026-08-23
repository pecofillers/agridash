@php
    $usuario = auth()->user();
    $rolId = $usuario?->ID_Rol;
    
    $modulosConfig = \App\Support\Rbac::obtenerModulosConfig();

    $menuSuperior = [];
    foreach ($modulosConfig as $claveMod => $cfg) {
        // Obtenemos únicamente los submódulos que la BD autoriza estrictamente para este rol
        $subsVisibles = \App\Support\Rbac::submodulosVisibles($rolId, $claveMod);
        
        $opciones = [];
        foreach ($cfg['submodulos'] as $claveSub => $infoSub) {
            // Verificamos de forma estricta que la clave del submódulo esté en la lista permitida
            if (in_array($claveSub, $subsVisibles, true)) {
                $opciones[] = [
                    'ruta' => $infoSub['ruta'],
                    'etiqueta' => $infoSub['etiqueta'],
                    'activa' => request()->routeIs($infoSub['ruta'])
                ];
            }
        }

        // Si el módulo tiene submódulos permitidos, lo agregamos incluyendo su 'base'
        if (count($opciones) > 0) {
            $menuSuperior[] = [
                'clave' => $claveMod,
                'etiqueta' => $cfg['etiqueta'],
                'icono' => $cfg['icono'],
                'base' => $cfg['base'], 
                'opciones' => $opciones
            ];
        }
    }

    $nombre_usuario = $usuario?->Nombre ?? 'Usuario';
    $partes = preg_split('/\s+/', trim($nombre_usuario));
    $iniciales = strtoupper(substr($partes[0] ?? 'A', 0, 1) . substr($partes[1] ?? '', 0, 1));
    $rolNombre = session('rol_nombre', 'SIN ROL');

    $modulosAdmin = ['gestion_usuarios', 'administracion_roles', 'configuracion'];
    $menuOperativo = array_filter($menuSuperior, fn ($i) => !in_array($i['clave'], $modulosAdmin, true));
    $menuAdmin = array_filter($menuSuperior, fn ($i) => in_array($i['clave'], $modulosAdmin, true));
@endphp

<nav class="navbar navbar-expand-lg agri-navbar sticky-top">
    <div class="container-fluid px-4">
        {{-- Marca --}}
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <span class="brand-icon"><i class="bi bi-flower1"></i></span>
            <span class="brand-text">AGRIDASH</span>
        </a>

        {{-- Botón colapsar (móvil) --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Menú colapsable --}}
        <div class="collapse navbar-collapse" id="mainNavbar">
            {{-- Módulos operativos --}}
            <ul class="navbar-nav me-auto">
                @foreach ($menuOperativo as $item)
                    @php $active = request()->routeIs($item['base']); @endphp
                    @if (count($item['opciones']) > 1)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ $active ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi {{ $item['icono'] }} me-1"></i> {{ $item['etiqueta'] }}
                            </a>
                            <ul class="dropdown-menu">
                                @foreach ($item['opciones'] as $op)
                                    <li>
                                        <a class="dropdown-item {{ $op['activa'] ? 'active' : '' }}" href="{{ route($op['ruta']) }}">
                                            {{ $op['etiqueta'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($item['opciones'][0]['ruta']) }}">
                                <i class="bi {{ $item['icono'] }} me-1"></i> {{ $item['etiqueta'] }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            {{-- Usuario (avatar/rol) con administración dentro --}}
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar avatar-sm">{{ $iniciales }}</span>
                        <span class="d-none d-md-inline">{{ $nombre_usuario }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <div class="px-3 py-2">
                                <div class="fw-bold">{{ $nombre_usuario }}</div>
                                <small class="text-muted"><span class="role-badge">{{ $rolNombre }}</span></small>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>

                        {{-- Módulos de administración dentro del dropdown del usuario --}}
                        @foreach ($menuAdmin as $itemAdmin)
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="bi {{ $itemAdmin['icono'] }} me-1"></i> {{ $itemAdmin['etiqueta'] }}
                                </h6>
                            </li>
                            @foreach ($itemAdmin['opciones'] as $opAdm)
                                <li>
                                    <a class="dropdown-item {{ $opAdm['activa'] ? 'active' : '' }}" href="{{ route($opAdm['ruta']) }}">
                                        {{ $opAdm['etiqueta'] }}
                                    </a>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                        @endforeach

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>