<?php

namespace App\Http\Middleware;

use App\Support\Rbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarSubmodulo
{
    /**
     * Verifica que el usuario autenticado tenga permiso sobre una sub-pestaña
     * de un módulo. Uso: ->middleware('check.submodulo:modulo,submodulo')
     *
     * @param  string  $modulo
     * @param  string  $submodulo
     */
    public function handle(Request $request, Closure $next, string $modulo, string $submodulo): Response
    {
        /** @var \App\Models\Usuario|null $usuario */
        $usuario = $request->user();
        $idRol = $usuario?->ID_Rol;

        if (!Rbac::tienePermisoSubmodulo($idRol, $modulo, $submodulo)) {
            abort(403, 'No tienes permiso para acceder a esta sub-opcion.');
        }

        return $next($request);
    }
}
