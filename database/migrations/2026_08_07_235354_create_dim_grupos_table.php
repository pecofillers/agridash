<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_grupos: grupos/equipos de trabajo con un supervisor asignado.
     */
    public function up(): void
    {
        Schema::create('dim_grupos', function (Blueprint $table) {
            $table->id('ID_Grupo');
            $table->string('Nombre_Grupo', 100)->unique();
            $table->string('Supervisor_Asignado', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_grupos');
    }
};
