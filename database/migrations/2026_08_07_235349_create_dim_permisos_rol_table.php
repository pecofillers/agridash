<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_permisos_rol: matriz de permisos RBAC por rol.
     * Cada fila = permiso de un rol sobre un módulo (o sub-módulo).
     */
    public function up(): void
    {
        Schema::create('dim_permisos_rol', function (Blueprint $table) {
            $table->id('ID_Permiso');
            $table->unsignedBigInteger('ID_Rol');
            $table->string('Modulo', 100);
            $table->string('Submodulo', 100)->nullable();
            $table->boolean('Permiso_Ver')->default(false);
            $table->timestamps();

            $table->foreign('ID_Rol')->references('ID_Rol')->on('dim_roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_permisos_rol');
    }
};
