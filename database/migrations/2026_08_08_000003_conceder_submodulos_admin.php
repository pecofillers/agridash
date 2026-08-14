<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restaura el acceso total de los roles ADMIN y SUPERADMIN.
 * Como ahora el acceso se controla SOLO por pestaña (filas con Submodulo NOT NULL),
 * insertamos todas las pestañas del sistema concedidas (Permiso_Ver = 1) para
 * que ambos roles recuperen la matriz de navegación completa.
 */
return new class extends Migration
{
    public function up(): void
    {
        $submodulos = [
            'vista_gerencial' => ['ver'],
            'rendimiento_colaboradores' => ['registro_labor', 'reporte_graficas', 'reporte_semanal', 'gestion_grupos', 'labores'],
            'registro_produccion' => ['registro', 'editar'],
            'agronomia' => ['historial', 'siembra', 'variedades'],
            'configuracion' => ['usuarios', 'credenciales'],
            'administracion_ubicaciones' => ['crear', 'listado'],
            'gestion_usuarios' => ['directorio'],
            'administracion_roles' => ['roles'],
        ];

        $roles = DB::table('dim_roles')->whereIn('Nombre_Rol', ['ADMIN', 'SUPERADMIN'])->get();

        foreach ($roles as $rol) {
            foreach ($submodulos as $modulo => $subs) {
                foreach ($subs as $sub) {
                    $existe = DB::table('dim_permisos_rol')
                        ->where('ID_Rol', $rol->ID_Rol)
                        ->where('Modulo', $modulo)
                        ->where('Submodulo', $sub)
                        ->exists();

                    if (!$existe) {
                        DB::table('dim_permisos_rol')->insert([
                            'ID_Rol' => $rol->ID_Rol,
                            'Modulo' => $modulo,
                            'Submodulo' => $sub,
                            'Permiso_Ver' => true,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // No se revierte automáticamente.
    }
};

