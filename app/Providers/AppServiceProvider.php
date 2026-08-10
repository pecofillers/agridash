<?php

namespace App\Providers;

use App\Models\Usuario;
use App\Support\Rbac;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Registra Gates de autorización nativos de Laravel que delegan
     * en la clase Rbac (matriz de permisos de dim_permisos_rol).
     * Uso en Blade: @can('modulo:ver', 'registro_produccion')
     * Uso en controladores: Gate::allows('modulo:ver', 'registro_produccion')
     */
public function boot(): void
    {
        // Gate de acceso a módulo: deriva de tener al menos una pestaña concedida.
        Gate::define('modulo:ver', fn (Usuario $user, $modulo) => Rbac::tienePermiso($user->ID_Rol, $modulo, 'ver'));

        // Gate de sub-módulo (pestaña específica)
        Gate::define('submodulo:ver', fn (Usuario $user, $args) => Rbac::tienePermisoSubmodulo($user->ID_Rol, $args[0], $args[1]));
    }
}
