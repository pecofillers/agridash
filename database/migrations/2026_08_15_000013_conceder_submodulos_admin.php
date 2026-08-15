<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Concede a los roles ADMIN y SUPERADMIN el acceso (Permiso_Ver = 1) a todas
 * las pestañas del sistema, ya que el acceso se controla SOLO por pestaña
 * (filas con Submodulo NOT NULL).
 *
 * Nota: los nombres de submódulo se ajustaron para coincidir con los que
 * realmente existen en la BD ('gestion_labores' en vez de 'labores',
 * 'editar' en vez de 'roles' para administracion_roles, y se agregó
 * 'consolidado_bloque' en agronomia). Revisa esta lista si tu taxonomía
 * de permisos ha cambiado desde entonces.
 */
return new class extends Migration
{
    public function up(): void
    {
        $submodulos = [
            'vista_gerencial' => ['ver'],
            'rendimiento_colaboradores' => ['registro_labor', 'reporte_graficas', 'reporte_semanal', 'gestion_grupos', 'gestion_labores'],
            'registro_produccion' => ['registro', 'editar'],
            'agronomia' => ['historial', 'siembra', 'variedades', 'consolidado_bloque'],
            'configuracion' => ['usuarios', 'credenciales'],
            'administracion_ubicaciones' => ['crear', 'listado'],
            'gestion_usuarios' => ['directorio'],
            'administracion_roles' => ['editar'],
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
