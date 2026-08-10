<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_siembras: registro de siembras por cama (historial de cama).
     * Fecha_Fin NULL = siembra ACTIVA actualmente.
     */
    public function up(): void
    {
        Schema::create('dim_siembras', function (Blueprint $table) {
            $table->id('ID_Siembra');
            $table->string('Bloque', 50);
            $table->string('Nave', 50);
            $table->string('Cama', 50);
            $table->unsignedBigInteger('ID_Variedad')->nullable();
            $table->date('Fecha_Siembra');
            $table->date('Fecha_Fin')->nullable();
            $table->integer('Cantidad_Plantas')->default(0);
            $table->decimal('Metros_Lineales', 8, 2)->default(0);
            $table->decimal('Densidad_Plantacion', 8, 2)->nullable();
            $table->timestamps();

            $table->foreign('ID_Variedad')->references('ID_Variedad')->on('dim_variedades')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_siembras');
    }
};
