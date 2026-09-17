<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        if (
            env('APP_MAINTENANCE', false)
            && !$request->user()?->hasRole('Administrador')
        ) {
            return response()
                ->view('errors.mantenimiento');
        }

        return $next($request);
    }
}