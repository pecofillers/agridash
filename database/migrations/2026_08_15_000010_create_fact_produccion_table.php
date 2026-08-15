<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fact_produccion: producción semanal por cama/siembra.
     * Referencia dim_ubicaciones y dim_siembras directamente (no Bloque/Nave/Cama).
     * No tiene columnas por día (Lunes..Domingo); solo Bajas y Total.
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('fact_produccion', function (Blueprint $table) {
            $table->increments('ID_Produccion');
            $table->unsignedInteger('ID_Ubicacion')->nullable();
            $table->unsignedInteger('ID_Siembra')->nullable();
            $table->integer('Semana')->nullable();
            $table->integer('Anio')->nullable();
            $table->integer('Bajas')->nullable()->default(0);
            $table->integer('Total')->nullable()->default(0);
            $table->softDeletes();

            $table->foreign('ID_Ubicacion', 'fk_produccion_ubicacion')
                  ->references('ID_Ubicacion')->on('dim_ubicaciones')->onDelete('cascade');
            $table->foreign('ID_Siembra', 'fk_produccion_siembra')
                  ->references('ID_Siembra')->on('dim_siembras')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_produccion');
    }
};
