<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_roles: catálogo de roles del sistema (RBAC).
     */
    public function up(): void
    {
        Schema::create('dim_roles', function (Blueprint $table) {
            $table->id('ID_Rol');
            $table->string('Nombre_Rol', 50)->unique();
            $table->string('Descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_roles');
    }
};
