<?php

return [
    'modulos' => [
        'vista_gerencial' => [
            'etiqueta' => 'Vision Gerencial',
            'icono' => 'bi-graph-up-arrow',
            'base' => 'dashboard',
            'submodulos' => [
                'ver' => ['etiqueta' => 'Vision Gerencial / Dashboard', 'ruta' => 'dashboard'],
            ]
        ],
        'rendimiento_colaboradores' => [
            'etiqueta' => 'Rendimiento',
            'icono' => 'bi-stopwatch',
            'base' => 'rendimiento.index',
            'submodulos' => [
                'registro_labor' => ['etiqueta' => 'Registro de Labor', 'ruta' => 'rendimiento.index'],
                'reporte_graficas' => ['etiqueta' => 'Reporte y Graficas', 'ruta' => 'rendimiento.reporte'],
                'reporte_semanal' => ['etiqueta' => 'Reporte Semanal por Colaborador', 'ruta' => 'rendimiento.reporteSemanal'],
                'gestion_grupos' => ['etiqueta' => 'Gestion de Grupos', 'ruta' => 'rendimiento.grupos'],
                'gestion_labores' => ['etiqueta' => 'Catalogo de Labores', 'ruta' => 'rendimiento.labores'],
            ]
        ],
        'registro_produccion' => [
            'etiqueta' => 'Registro de Produccion',
            'icono' => 'bi-clipboard-data',
            'base' => 'produccion.index',
            'submodulos' => [
                'registro' => ['etiqueta' => 'Ingresar Registro', 'ruta' => 'produccion.index'],
                'editar' => ['etiqueta' => 'Ver y Editar', 'ruta' => 'produccion.index'],
            ]
        ],
        'agronomia' => [
            'etiqueta' => 'Agronomia',
            'icono' => 'bi-flower3',
            'base' => 'agronomia.index',
            'submodulos' => [
                'siembra' => ['etiqueta' => 'Registrar Siembra', 'ruta' => 'agronomia.index'],
                'consolidado_bloque' => ['etiqueta' => 'Consolidado por Bloque', 'ruta' => 'agronomia.consolidado_bloque'],
                'planos' => ['etiqueta' => 'Plano de Siembras', 'ruta' => 'plano_siembra.index'],
                'insumos' => ['etiqueta' => 'Control Fertilizantes', 'ruta' => 'agronomia.insumos'],
                'historico_siembras' => ['etiqueta' => 'Histórico de Cultivos', 'ruta' => 'agronomia.historico_siembras'],
                'comparador-siembras' => ['etiqueta' => 'Comparador de Siembras','ruta' => 'agronomia.comparador-siembras'],
            ]
        ],
        'administracion_ubicaciones' => [
            'etiqueta' => 'Ubicaciones',
            'icono' => 'bi-geo-alt',
            'base' => 'ubicaciones.index',
            'submodulos' => [
                'listado' => ['etiqueta' => 'Crear Camas / Naves', 'ruta' => 'ubicaciones.index'],
            ]
        ],
        'administracion_roles' => [
            'etiqueta' => 'Gestion de Roles',
            'icono' => 'bi-shield-lock',
            'base' => 'roles.index',
            'submodulos' => [
                'editar' => ['etiqueta' => 'Gestion de Permisos y Roles', 'ruta' => 'roles.index'],
            ]
        ],
        'gestion_usuarios' => [
            'etiqueta' => 'Gestion de Usuarios',
            'icono' => 'bi-people',
            'base' => 'usuarios.index',
            'submodulos' => [
                'directorio' => ['etiqueta' => 'Directorio de Usuarios', 'ruta' => 'usuarios.index'],
            ]
        ],
        'configuracion' => [
            'etiqueta' => 'Configuracion y Seguridad',
            'icono' => 'bi-gear',
            'base' => 'configuracion.index',
            'submodulos' => [
                'usuarios' => ['etiqueta' => 'Gestion de Usuarios y Estados', 'ruta' => 'configuracion.index'],
                'credenciales' => ['etiqueta' => 'Cambio de Contrasena', 'ruta' => 'configuracion.index'],
                'bloques' => ['etiqueta' => 'Configuracion de Enlaces OneDrive', 'ruta' => 'configuracion.bloques.configuracion'],
            ]
        ],
    ]
];