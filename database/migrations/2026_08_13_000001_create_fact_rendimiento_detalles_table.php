<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fact_rendimiento_detalles', function (Blueprint $table) {
            $table->id('ID_Detalle');
            $table->unsignedBigInteger('ID_Rendimiento');
            $table->string('Nombre_Variante', 50); // Ejemplo: 'Blanco', 'Azul', 'General'
            $table->decimal('Cantidad', 10, 2); // Cantidad específica de esta variante

            // Llave foránea que se conecta con la cabecera de rendimiento
            $table->foreign('ID_Rendimiento')
                  ->references('ID_Rendimiento')
                  ->on('fact_rendimientos_labor')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fact_rendimiento_detalles');
    }
};