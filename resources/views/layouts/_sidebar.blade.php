@php
    $nombre_usuario = auth()->user()->Nombre ?? 'Usuario';
    $partes_nombre = preg_split('/\s+/', trim($nombre_usuario));
    $inicial_1 = strtoupper(substr($partes_nombre[0] ?? 'A', 0, 1));
    $inicial_2 = strtoupper(substr($partes_nombre[1] ?? '', 0, 1));
    $iniciales = $inicial_1 . $inicial_2;
@endphp

<div class="sidebar-inner">
    <div class="brand d-none d-lg-flex">
        <div class="brand-icon"><i class="bi bi-flower1"></i></div>
        AGRIDASH
    </div>

    {{-- User card --}}
    <div class="user-card">
        <div class="avatar">{{ $iniciales }}</div>
        <div style="min-width:0;">
            <div class="user-status">● En Linea</div>
            <div class="user-name">{{ $nombre_usuario }}</div>
            <span class="role-badge">{{ session('rol_nombre', 'SIN ROL') }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="nav-label">Modulos de Navegacion</div>
    <nav class="nav flex-column">
        @php
            $menu = \App\Support\Rbac::menuPorRol(auth()->user()->ID_Rol);
            $rutas = [
                'vista_gerencial' => ['route' => 'dashboard', 'icono' => 'bi-graph-up-arrow'],
                'rendimiento_colaboradores' => ['route' => 'rendimiento.index', 'icono' => 'bi-stopwatch'],
                'gestion_usuarios' => ['route' => 'usuarios.index', 'icono' => 'bi-people'],
                'registro_produccion' => ['route' => 'produccion.index', 'icono' => 'bi-clipboard-data'],
                'agronomia' => ['route' => 'agronomia.index', 'icono' => 'bi-flower3'],
                'administracion_ubicaciones' => ['route' => 'ubicaciones.index', 'icono' => 'bi-geo-alt'],
                'administracion_roles' => ['route' => 'roles.index', 'icono' => 'bi-shield-lock'],
                'configuracion' => ['route' => 'configuracion.index', 'icono' => 'bi-gear'],
            ];
        @endphp
        @foreach ($menu as $item)
            @php
                $cfg = $rutas[$item['clave']] ?? ['route' => 'dashboard', 'icono' => 'bi-circle'];
                $active = request()->routeIs($cfg['route']);
            @endphp
            <a href="{{ route($cfg['route']) }}" class="nav-link {{ $active ? 'active' : '' }}">
                <i class="bi {{ $cfg['icono'] }}"></i>
                {{ $item['etiqueta'] }}
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn-logout" type="submit">
                <i class="bi bi-box-arrow-right"></i> CERRAR SESION
            </button>
        </form>
    </div>
</div>
