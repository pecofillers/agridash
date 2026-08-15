<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_labores: catálogo de labores agrícolas y sus umbrales de rendimiento.
     * La migración anterior (2026_08_12_000002) estaba vacía y asumía que la
     * tabla ya existía; en una instalación desde cero nunca se creaba.
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('dim_labores', function (Blueprint $table) {
            $table->increments('ID_Labor');
            $table->string('Nombre_Labor', 100);
            $table->string('Unidad_Medida', 50);
            $table->decimal('Umbral_Verde', 10, 2);
            $table->decimal('Umbral_Naranja', 10, 2);
            $table->string('Variantes', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_labores');
    }
};
