<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * fact_rendimientos_labor: registros de rendimiento de colaboradores.
     * Rendimiento_Hora = Cantidad / Horas_Trabajadas.
     *
     * Esta tabla fue reestructurada en producción fuera de las migraciones:
     * ya no usa ID_Colaborador (FK a dim_colaboradores, tabla que ya no
     * existe) ni las columnas Nombre_Colaborador/Supervisor/Tipo_Labor/
     * Unidad_Medida. Ahora usa ID_Usuario (FK a dim_usuarios, NOT NULL),
     * ID_Grupo (NOT NULL) e incorpora ID_Labor e ID_Ubicacion como FKs.
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('fact_rendimientos_labor', function (Blueprint $table) {
            $table->id('ID_Rendimiento');
            $table->date('Fecha');
            $table->unsignedInteger('ID_Usuario');
            $table->unsignedInteger('ID_Grupo');
            $table->unsignedInteger('ID_Labor')->nullable();
            $table->unsignedInteger('ID_Ubicacion')->nullable();
            $table->time('Hora_Inicio');
            $table->time('Hora_Fin');
            $table->decimal('Horas_Trabajadas', 5, 2);
            $table->decimal('Cantidad', 10, 2);
            $table->decimal('Rendimiento_Hora', 10, 2);

            $table->foreign('ID_Usuario', 'fk_rendimiento_usuario')
                  ->references('ID_Usuario')->on('dim_usuarios')->onDelete('cascade');
            $table->foreign('ID_Grupo', 'fk_rendimiento_grupo')
                  ->references('ID_Grupo')->on('dim_grupos')->onDelete('cascade');
            $table->foreign('ID_Labor', 'fk_rendimiento_labor')
                  ->references('ID_Labor')->on('dim_labores');
            $table->foreign('ID_Ubicacion', 'fk_rendimiento_ubicacion')
                  ->references('ID_Ubicacion')->on('dim_ubicaciones')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_rendimientos_labor');
    }
};
