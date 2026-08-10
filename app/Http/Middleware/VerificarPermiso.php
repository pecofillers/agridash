<?php

namespace App\Http\Middleware;

use App\Support\Rbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Verifica que el usuario autenticado tenga el permiso solicitado.
     * Uso: ->middleware('permiso:registro_produccion,editar')
     *
     * @param  string  $modulo
     * @param  string  $accion
     */
    public function handle(Request $request, Closure $next, string $modulo, string $accion = 'ver'): Response
    {
        /** @var \App\Models\Usuario|null $usuario */
        $usuario = $request->user();
        $idRol = $usuario?->ID_Rol;

        if (!Rbac::tienePermiso($idRol, $modulo, $accion)) {
            abort(403, 'No tienes permiso para acceder a este modulo.');
        }

        return $next($request);
    }
}
