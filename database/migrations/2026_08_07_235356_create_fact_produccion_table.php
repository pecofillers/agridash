<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fact_produccion: producción semanal por cama.
     * Total = Lunes+Martes+...+Domingo+Bajas.
     */
    public function up(): void
    {
        Schema::create('fact_produccion', function (Blueprint $table) {
            $table->id('ID_Produccion');
            $table->string('Bloque', 50);
            $table->string('Nave', 50);
            $table->string('Cama', 50);
            $table->integer('Semana');
            $table->integer('Anio');
            $table->integer('Lunes')->default(0);
            $table->integer('Martes')->default(0);
            $table->integer('Miercoles')->default(0);
            $table->integer('Jueves')->default(0);
            $table->integer('Viernes')->default(0);
            $table->integer('Sabado')->default(0);
            $table->integer('Domingo')->default(0);
            $table->integer('Bajas')->default(0);
            $table->integer('Total')->default(0);
            $table->timestamps();

            $table->unique(['Bloque', 'Nave', 'Cama', 'Semana', 'Anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_produccion');
    }
};
