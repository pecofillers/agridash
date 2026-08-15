<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_usuarios: catálogo de usuarios del sistema.
     * Password_Hash usa bcrypt. Bloqueado_Hasta = NULL si no está bloqueado.
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('dim_usuarios', function (Blueprint $table) {
            $table->increments('ID_Usuario');
            $table->string('Username', 50)->unique();
            $table->string('Nombre', 100);
            $table->string('Apellidos', 100)->nullable();
            $table->string('Telefono', 20)->nullable();
            $table->string('Correo', 100)->nullable();
            $table->string('Password_Hash', 255);
            $table->unsignedInteger('ID_Rol')->nullable();
            $table->string('Estado', 20)->default('ACTIVO');
            $table->integer('Intentos_Fallidos')->nullable()->default(0);
            $table->dateTime('Bloqueado_Hasta')->nullable();
            $table->unsignedInteger('ID_Grupo')->nullable();

            $table->foreign('ID_Rol', 'fk_usuarios_rol')
                  ->references('ID_Rol')->on('dim_roles')->onDelete('set null');
            $table->foreign('ID_Grupo', 'fk_usuarios_grupo')
                  ->references('ID_Grupo')->on('dim_grupos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_usuarios');
    }
};
