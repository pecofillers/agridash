<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dim_siembras', function (Blueprint $table) {
            // Añadir el estado de la cama
            $table->string('Estado_Siembra', 50)->default('SEMBRADA')->after('ID_Variedad');
            
            // Añadir las fechas agronómicas
            $table->date('Fecha_Pinch')->nullable()->after('Fecha_Siembra');
            $table->date('Fecha_Hormona')->nullable()->after('Fecha_Pinch');
            $table->date('Fecha_Erradicacion')->nullable()->after('Fecha_Fin');
            
            // Opcional pero recomendado para el "Rebrote"
            $table->integer('Ciclo_Actual')->default(1)->after('Estado_Siembra')->comment('Para identificar si es primer corte o rebrote');
        });
    }

    public function down()
    {
        Schema::table('dim_siembras', function (Blueprint $table) {
            $table->dropColumn([
                'Estado_Siembra', 
                'Fecha_Pinch', 
                'Fecha_Hormona', 
                'Fecha_Erradicacion',
                'Ciclo_Actual'
            ]);
        });
    }
};