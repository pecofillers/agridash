<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_variedades: catálogo de variedades de plantas.
     */
    public function up(): void
    {
        Schema::create('dim_variedades', function (Blueprint $table) {
            $table->id('ID_Variedad');
            $table->string('Nombre_Variedad', 100)->unique();
            $table->string('Color', 50)->nullable();
            $table->integer('Ciclo_Dias')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_variedades');
    }
};
