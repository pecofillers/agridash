<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dim_grupos: grupos/equipos de trabajo.
     * ID_Supervisor referencia a dim_usuarios, pero la FK se agrega en
     * la migración 2026_08_15_000004 porque dim_usuarios todavía no
     * existe en este punto (dependencia circular entre ambas tablas).
     * Sin timestamps (coincide con BD real).
     */
    public function up(): void
    {
        Schema::create('dim_grupos', function (Blueprint $table) {
            $table->increments('ID_Grupo');
            $table->string('Nombre_Grupo', 50);
            $table->unsignedInteger('ID_Supervisor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_grupos');
    }
};
