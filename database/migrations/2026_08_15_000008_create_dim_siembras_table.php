<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_siembras: historial de siembras por cama (referencia dim_ubicaciones,
     * no columnas Bloque/Nave/Cama sueltas). Fecha_Fin NULL = siembra ACTIVA.
     * Densidad_Plantacion es una columna calculada por MySQL
     * (Cantidad_Plantas / Metros_Lineales), no se escribe manualmente.
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('dim_siembras', function (Blueprint $table) {
            $table->increments('ID_Siembra');
            $table->unsignedInteger('ID_Ubicacion')->nullable();
            $table->unsignedInteger('ID_Variedad')->nullable();
            $table->string('Estado_Siembra', 50)->default('SEMBRADA');
            $table->integer('Ciclo_Actual')->default(1)
                  ->comment('Para identificar si es primer corte o rebrote');
            $table->date('Fecha_Siembra');
            $table->date('Fecha_Pinch')->nullable();
            $table->date('Fecha_Hormona')->nullable();
            $table->date('Fecha_Fin')->nullable();
            $table->date('Fecha_Erradicacion')->nullable();
            $table->integer('Cantidad_Plantas');
            $table->decimal('Metros_Lineales', 10, 2);
            $table->decimal('Densidad_Plantacion', 10, 2)
                  ->storedAs('Cantidad_Plantas / Metros_Lineales');
            $table->softDeletes();

            $table->foreign('ID_Ubicacion', 'fk_siembras_ubicacion')
                  ->references('ID_Ubicacion')->on('dim_ubicaciones')->onDelete('cascade');
            $table->foreign('ID_Variedad', 'fk_siembras_variedad')
                  ->references('ID_Variedad')->on('dim_variedades');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_siembras');
    }
};
