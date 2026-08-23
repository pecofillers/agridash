<?php

namespace App\Support;

use App\Models\PermisoRol;
use Illuminate\Support\Facades\Cache;

class Rbac
{
    public static function obtenerModulosConfig(): array
    {
        return config('rbac.modulos', []);
    }

    public static function submodulosVisibles(?int $idRol, string $modulo): array
    {
        if (!$idRol) {
            return [];
        }
        
        // Consulta directa y limpia por ID del rol
        return PermisoRol::where('ID_Rol', $idRol)
            ->where('Modulo', $modulo)
            ->where('Permiso_Ver', true)
            ->whereNotNull('Submodulo')
            ->pluck('Submodulo')
            ->toArray();
    }

    public static function tienePermisoSubmodulo(?int $idRol, string $modulo, string $submodulo): bool
    {
        return in_array($submodulo, self::submodulosVisibles($idRol, $modulo), true);
    }

    // 🟢 Agregamos este método que el middleware VerificarPermiso está buscando
    public static function tienePermiso(?int $idRol, string $modulo, string $accion = 'ver'): bool
    {
        return count(self::submodulosVisibles($idRol, $modulo)) > 0;
    }

    public static function menuPorRol(?int $idRol): array
    {
        $menu = [];
        foreach (self::obtenerModulosConfig() as $clave => $datos) {
            $subs = self::submodulosVisibles($idRol, $clave);
            if (count($subs) > 0) {
                $menu[] = ['clave' => $clave, 'etiqueta' => $datos['etiqueta']];
            }
        }
        return $menu;
    }
    
    public static function limpiarCache(): void
    {
        Cache::forget('rbac_submodulos');
    }
}