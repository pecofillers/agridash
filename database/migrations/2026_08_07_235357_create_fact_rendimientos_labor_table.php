<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fact_rendimientos_labor: registros de rendimiento de colaboradores.
     * Rendimiento_Hora = Cantidad / Horas_Trabajadas.
     */
    public function up(): void
    {
        Schema::create('fact_rendimientos_labor', function (Blueprint $table) {
            $table->id('ID_Rendimiento');
            $table->date('Fecha');
            $table->unsignedBigInteger('ID_Colaborador')->nullable();
            $table->string('Nombre_Colaborador', 150);
            $table->unsignedBigInteger('ID_Grupo')->nullable();
            $table->string('Supervisor', 50)->nullable();
            $table->string('Tipo_Labor', 50);
            $table->string('Unidad_Medida', 20)->nullable();
            $table->time('Hora_Inicio')->nullable();
            $table->time('Hora_Fin')->nullable();
            $table->decimal('Horas_Trabajadas', 6, 2)->default(0);
            $table->decimal('Cantidad', 12, 2)->default(0);
            $table->decimal('Rendimiento_Hora', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('ID_Colaborador')->references('ID_Colaborador')->on('dim_colaboradores')->onDelete('set null');
            $table->foreign('ID_Grupo')->references('ID_Grupo')->on('dim_grupos')->onDelete('set null');
            $table->index(['Fecha', 'ID_Grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_rendimientos_labor');
    }
};
