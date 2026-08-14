@php
    $usuario = auth()->user();
    $rolId = $usuario?->ID_Rol;
    $menu = \App\Support\Rbac::menuPorRol($rolId);

    // Mapa submodulo => (ruta, etiqueta) — rutas especificas para control por pestaña
    $submodulos = [
        'rendimiento_colaboradores' => [
            'registro_labor' => ['rendimiento.index', 'Registro de Labor'],
            'reporte_graficas' => ['rendimiento.reporte', 'Reporte y Graficas'],
            'reporte_semanal' => ['rendimiento.reporteSemanal', 'Reporte Semanal'],
            'gestion_grupos' => ['rendimiento.grupos', 'Gestion de Grupos'],
            'gestion_labores' => ['rendimiento.labores', 'Catalogo de Labores'],
        ],
        'registro_produccion' => [
            'registro' => ['produccion.index', 'Ingresar Registro'],
            'editar' => ['produccion.index', 'Ver y Editar'],
        ],
        'agronomia' => [
            'historial' => ['agronomia.index', 'Historial de Cama'],
            'siembra' => ['agronomia.index', 'Registrar Siembra'],
            'variedades' => ['agronomia.index', 'Catalogo de Variedades'],
        ],
        'administracion_ubicaciones' => [
            'crear' => ['ubicaciones.index', 'Crear Camas / Naves'],
            'listado' => ['ubicaciones.index', 'Ver Estructura'],
        ],
        'gestion_usuarios' => [
            'directorio' => ['usuarios.index', 'Directorio'],
        ],
        'administracion_roles' => [
            'editar' => ['roles.index', 'Permisos de roles'],
        ],
    ];

    // Mapa modulo => ruta base + icono
    $modulosConfig = [
        'vista_gerencial' => ['base' => 'dashboard', 'icono' => 'bi-graph-up-arrow'],
        'rendimiento_colaboradores' => ['base' => 'rendimiento.index', 'icono' => 'bi-stopwatch'],
        'registro_produccion' => ['base' => 'produccion.index', 'icono' => 'bi-clipboard-data'],
        'agronomia' => ['base' => 'agronomia.index', 'icono' => 'bi-flower3'],
        'administracion_ubicaciones' => ['base' => 'ubicaciones.index', 'icono' => 'bi-geo-alt'],
        'gestion_usuarios' => ['base' => 'usuarios.index', 'icono' => 'bi-people'],
        'administracion_roles' => ['base' => 'roles.index', 'icono' => 'bi-shield-lock'],
        'configuracion' => ['base' => 'configuracion.index', 'icono' => 'bi-gear'],
    ];

    $nombre_usuario = $usuario?->Nombre ?? 'Usuario';
    $partes = preg_split('/\s+/', trim($nombre_usuario));
    $iniciales = strtoupper(substr($partes[0] ?? 'A', 0, 1) . substr($partes[1] ?? '', 0, 1));
    $rolNombre = session('rol_nombre', 'SIN ROL');

    // Modulos operativos (barra superior) — excluye los administrativos
    $modulosAdmin = ['gestion_usuarios', 'administracion_roles', 'configuracion'];
    $menuSuperior = array_filter($menu, fn ($i) => !in_array($i['clave'], $modulosAdmin, true));
@endphp

<nav class="navbar navbar-expand-lg agri-navbar sticky-top">
    <div class="container-fluid px-4">
        {{-- Marca --}}
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <span class="brand-icon"><i class="bi bi-flower1"></i></span>
            <span class="brand-text">AGRIDASH</span>
        </a>

        {{-- Boton colapsar (movil) --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegacion">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Menu colapsable --}}
        <div class="collapse navbar-collapse" id="mainNavbar">
            {{-- Modulos operativos --}}
            <ul class="navbar-nav me-auto">
                @foreach ($menuSuperior as $item)
                    @php
                        $cfg = $modulosConfig[$item['clave']] ?? null;
                        if (!$cfg) { continue; }
                        $subsDef = $submodulos[$item['clave']] ?? null;
                        $opciones = [];
                        if ($subsDef) {
                            foreach ($subsDef as $claveSub => $sub) {
                                if (\App\Support\Rbac::tienePermisoSubmodulo($rolId, $item['clave'], $claveSub)) {
                                    $opciones[] = ['ruta' => $sub[0], 'etiqueta' => $sub[1], 'activa' => request()->routeIs($sub[0])];
                                }
                            }
                        }
                        $active = request()->routeIs($cfg['base']);
                    @endphp

                    @if (count($opciones) > 0)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ $active ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi {{ $cfg['icono'] }} me-1"></i> {{ $item['etiqueta'] }}
                            </a>
                            <ul class="dropdown-menu">
                                @foreach ($opciones as $op)
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
                            <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($cfg['base']) }}">
                                <i class="bi {{ $cfg['icono'] }} me-1"></i> {{ $item['etiqueta'] }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            {{-- Usuario (avatar/rol) con administracion dentro --}}
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

                        {{-- Modulos de administracion dentro del dropdown del usuario --}}
                        @foreach ($modulosAdmin as $claveAdmin)
                            @php
                                $itemAdmin = collect($menu)->firstWhere('clave', $claveAdmin);
                                if (!$itemAdmin) { continue; }
                                $cfgAdm = $modulosConfig[$claveAdmin] ?? null;
                                if (!$cfgAdm) { continue; }
                                $subsAdm = $submodulos[$claveAdmin] ?? null;
                                $opcionesAdm = [];
                                if ($subsAdm) {
                                    foreach ($subsAdm as $claveSub => $sub) {
                                        if (\App\Support\Rbac::tienePermisoSubmodulo($rolId, $claveAdmin, $claveSub)) {
                                            $opcionesAdm[] = ['ruta' => $sub[0], 'etiqueta' => $sub[1], 'activa' => request()->routeIs($sub[0])];
                                        }
                                    }
                                }
                            @endphp
                            @if ($itemAdmin)
                                <li>
                                    <h6 class="dropdown-header">
                                        <i class="bi {{ $cfgAdm['icono'] }} me-1"></i> {{ $itemAdmin['etiqueta'] }}
                                    </h6>
                                </li>
                                @forelse ($opcionesAdm as $opAdm)
                                    <li>
                                        <a class="dropdown-item {{ $opAdm['activa'] ? 'active' : '' }}" href="{{ route($opAdm['ruta']) }}">
                                            {{ $opAdm['etiqueta'] }}
                                        </a>
                                    </li>
                                @empty
                                    <li>
                                        <a class="dropdown-item" href="{{ route($cfgAdm['base']) }}">
                                            <i class="bi bi-grid me-2"></i> {{ $itemAdmin['etiqueta'] }}
                                        </a>
                                    </li>
                                @endforelse
                                <li><hr class="dropdown-divider"></li>
                            @endif
                        @endforeach

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesion
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
