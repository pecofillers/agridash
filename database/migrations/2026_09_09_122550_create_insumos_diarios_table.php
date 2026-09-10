<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos_diarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();

            $table->decimal('amortiguador_medida_visual', 10, 2)->nullable();
            $table->decimal('amortiguador_medida_registro', 10, 2)->nullable();
            $table->decimal('amortiguador_consumo', 10, 2)->nullable();

            $table->decimal('agrofeed_medida_visual', 10, 2)->nullable();
            $table->decimal('agrofeed_medida_registro', 10, 2)->nullable();
            $table->decimal('agrofeed_consumo', 10, 2)->nullable();

            $table->decimal('agua_lectura', 12, 2)->nullable();
            $table->decimal('agua_consumo', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos_diarios');
    }
};