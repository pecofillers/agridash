<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_roles: catálogo de roles del sistema (RBAC).
     * Nota: en producción esta tabla NO tiene timestamps ni unique en
     * Nombre_Rol, por eso no se declaran aquí.
     */
    public function up(): void
    {
        Schema::create('dim_roles', function (Blueprint $table) {
            $table->increments('ID_Rol');
            $table->string('Nombre_Rol', 50);
            $table->string('Descripcion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_roles');
    }
};
