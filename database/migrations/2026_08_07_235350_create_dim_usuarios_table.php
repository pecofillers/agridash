<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_usuarios: catálogo de usuarios del sistema.
     * Password_Hash usa bcrypt.
     * Bloqeuado_Hasta = NULL si no está bloqueado.
     */
    public function up(): void
    {
        Schema::create('dim_usuarios', function (Blueprint $table) {
            $table->id('ID_Usuario');
            $table->string('Username', 50)->unique();
            $table->string('Nombre', 100);
            $table->string('Apellidos', 100)->nullable();
            $table->string('Telefono', 25)->nullable();
            $table->string('Correo', 150)->nullable();
            $table->string('Password_Hash', 255);
            $table->unsignedBigInteger('ID_Rol')->nullable();
            $table->enum('Estado', ['ACTIVO', 'INACTIVO', 'BLOQUEADO'])->default('ACTIVO');
            $table->integer('Intentos_Fallidos')->default(0);
            $table->timestamp('Bloqueado_Hasta')->nullable();
            $table->timestamps();

            $table->foreign('ID_Rol')->references('ID_Rol')->on('dim_roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_usuarios');
    }
};
