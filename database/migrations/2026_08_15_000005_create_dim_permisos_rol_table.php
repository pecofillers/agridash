<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_permisos_rol: matriz de permisos RBAC por rol.
     * El control de acceso es SOLO por pestaña (Permiso_Ver sobre Submodulo).
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('dim_permisos_rol', function (Blueprint $table) {
            $table->increments('ID_Permiso');
            $table->unsignedInteger('ID_Rol')->nullable();
            $table->string('Modulo', 100)->nullable();
            $table->string('Submodulo', 100)->nullable();
            $table->boolean('Permiso_Ver')->default(false);

            $table->foreign('ID_Rol', 'fk_permisos_rol')
                  ->references('ID_Rol')->on('dim_roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_permisos_rol');
    }
};
