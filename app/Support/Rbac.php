<?php

namespace App\Support;

use App\Models\PermisoRol;
use App\Models\Rol;
use Illuminate\Support\Facades\Cache;

/**
 * RBAC: Replica la lógica de seguridad/rbac_config.py del proyecto Streamlit.
 * El acceso se controla SOLO por pestaña (submódulo) en dim_permisos_rol.
 * El acceso a un módulo se deriva de tener al menos una pestaña concedida.
 */
class Rbac
{
    public const MODULOS = [
        'vista_gerencial' => 'Vision Gerencial',
        'rendimiento_colaboradores' => 'Rendimiento',
        'gestion_usuarios' => 'Gestion de Usuarios',
        'registro_produccion' => 'Registro de Produccion',
        'agronomia' => 'Agronomia',
        'administracion_ubicaciones' => 'Ubicaciones',
        'administracion_roles' => 'Gestion de Roles',
        'configuracion' => 'Configuracion y Seguridad',
    ];

    public const SUBMODULOS = [
        'vista_gerencial' => [
            'ver' => 'Vision Gerencial / Dashboard',
        ],
        'rendimiento_colaboradores' => [
            'registro_labor' => 'Registro de Labor',
            'reporte_graficas' => 'Reporte y Graficas',
            'reporte_semanal' => 'Reporte Semanal por Colaborador',
            'gestion_grupos' => 'Gestion de Grupos y Equipo',
            'gestion_labores' => 'Catalogo de Labores',
        ],
        'registro_produccion' => [
            'registro' => 'Ingresar Nuevo Registro',
            'editar' => 'Ver y Editar Registros',
        ],
        'agronomia' => [
            'historial' => 'Historial de Cama',
            'siembra' => 'Registrar Nueva Siembra',
            'variedades' => 'Catalogo de Variedades',
        ],
        'configuracion' => [
            'usuarios' => 'Gestion de Usuarios y Estados',
            'credenciales' => 'Cambio de Contrasenas',
        ],
        'administracion_ubicaciones' => [
            'crear' => 'Crear Nuevas Camas / Naves',
            'listado' => 'Ver y Gestionar Estructura',
        ],
        'gestion_usuarios' => [
            'directorio' => 'Directorio de Usuarios',
        ],
        'administracion_roles' => [
            'editar' => 'Permisos de Roles',
        ],
    ];

    public static function matrizSubmodulos(): array
    {
        return Cache::remember('rbac_submodulos', 60, function () {
            $permisos = PermisoRol::whereNotNull('Submodulo')->where('Permiso_Ver', true)->with('rol')->get();
            $result = [];
            foreach ($permisos as $p) {
                $rol = $p->rol;
                if (!$rol) {
                    continue;
                }
                $result[$rol->Nombre_Rol][$p->Modulo][] = $p->Submodulo;
            }
            return $result;
        });
    }

    /**
     * Determina si un rol tiene acceso a un módulo. Se deriva de tener al
     * menos una pestaña (submódulo) concedida. Ya no se usan acciones de módulo.
     */
    public static function tienePermiso(?int $idRol, string $modulo, string $accion = 'ver'): bool
    {
        return count(self::submodulosVisibles($idRol, $modulo)) > 0;
    }

    public static function submodulosVisibles(?int $idRol, string $modulo): array
    {
        if (!$idRol) {
            return [];
        }
        $rol = Rol::find($idRol);
        if (!$rol) {
            return [];
        }
        $matriz = self::matrizSubmodulos();
        // Si no hay filas configuradas para el rol/módulo, no tiene acceso.
        return $matriz[$rol->Nombre_Rol][$modulo] ?? [];
    }

    public static function tienePermisoSubmodulo(?int $idRol, string $modulo, string $submodulo): bool
    {
        return in_array($submodulo, self::submodulosVisibles($idRol, $modulo), true);
    }

    /**
     * Muestra solo los módulos donde el rol tiene al menos una pestaña concedida.
     */
    public static function menuPorRol(?int $idRol): array
    {
        $menu = [];
        foreach (self::MODULOS as $clave => $etiqueta) {
            $subs = self::submodulosVisibles($idRol, $clave);
            $activos = array_filter($subs, fn ($s) => isset(self::SUBMODULOS[$clave][$s]));
            if (count($activos)) {
                $menu[] = ['clave' => $clave, 'etiqueta' => $etiqueta];
            }
        }
        return $menu;
    }

    public static function limpiarCache(): void
    {
        Cache::forget('rbac_submodulos');
    }
}

