<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_colaboradores: personas asignadas a grupos de trabajo.
     */
    public function up(): void
    {
        Schema::create('dim_colaboradores', function (Blueprint $table) {
            $table->id('ID_Colaborador');
            $table->string('Nombre_Colaborador', 150);
            $table->unsignedBigInteger('ID_Grupo')->nullable();
            $table->enum('Estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->timestamps();

            $table->foreign('ID_Grupo')->references('ID_Grupo')->on('dim_grupos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_colaboradores');
    }
};
