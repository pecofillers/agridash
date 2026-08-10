<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_ubicaciones: estructura física de bloques > naves > camas.
     */
    public function up(): void
    {
        Schema::create('dim_ubicaciones', function (Blueprint $table) {
            $table->id('ID_Ubicacion');
            $table->string('Bloque', 50);
            $table->string('Nave', 50);
            $table->string('Cama', 50);
            $table->enum('Estado', ['ACTIVA', 'INACTIVA'])->default('ACTIVA');
            $table->timestamps();

            $table->unique(['Bloque', 'Nave', 'Cama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_ubicaciones');
    }
};
