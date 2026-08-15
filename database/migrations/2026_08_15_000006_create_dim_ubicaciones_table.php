<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_ubicaciones: estructura física de bloques > naves > camas.
     * Estado es varchar (no enum) y tiene soft deletes.
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('dim_ubicaciones', function (Blueprint $table) {
            $table->increments('ID_Ubicacion');
            $table->string('Bloque', 50);
            $table->string('Nave', 50);
            $table->string('Cama', 50);
            $table->string('Estado', 20)->default('ACTIVA');
            $table->dateTime('Fecha_Creacion')->useCurrent();
            $table->softDeletes();

            $table->unique(['Bloque', 'Nave', 'Cama'], 'uq_ubicacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_ubicaciones');
    }
};
