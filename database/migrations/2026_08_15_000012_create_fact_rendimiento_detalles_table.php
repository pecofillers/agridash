<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fact_rendimiento_detalles: variantes de cantidad dentro de un
     * registro de rendimiento (ej. 'Blanco', 'Azul', 'General').
     */
    public function up(): void
    {
        Schema::create('fact_rendimiento_detalles', function (Blueprint $table) {
            $table->id('ID_Detalle');
            $table->unsignedBigInteger('ID_Rendimiento');
            $table->string('Nombre_Variante', 50);
            $table->decimal('Cantidad', 10, 2);

            $table->foreign('ID_Rendimiento')
                  ->references('ID_Rendimiento')->on('fact_rendimientos_labor')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_rendimiento_detalles');
    }
};
