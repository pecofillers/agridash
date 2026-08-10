<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las columnas de acciones (crear/editar/eliminar) de dim_permisos_rol.
 * El control de acceso ahora es SOLO por pestaña (Permiso_Ver sobre Submodulo).
 * Se conserva únicamente Permiso_Ver.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dim_permisos_rol')) {
            Schema::table('dim_permisos_rol', function (Blueprint $table) {
                if (Schema::hasColumn('dim_permisos_rol', 'Permiso_Crear')) {
                    $table->dropColumn('Permiso_Crear');
                }
                if (Schema::hasColumn('dim_permisos_rol', 'Permiso_Editar')) {
                    $table->dropColumn('Permiso_Editar');
                }
                if (Schema::hasColumn('dim_permisos_rol', 'Permiso_Eliminar')) {
                    $table->dropColumn('Permiso_Eliminar');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dim_permisos_rol')) {
            Schema::table('dim_permisos_rol', function (Blueprint $table) {
                $table->boolean('Permiso_Crear')->default(false);
                $table->boolean('Permiso_Editar')->default(false);
                $table->boolean('Permiso_Eliminar')->default(false);
            });
        }
    }
};
