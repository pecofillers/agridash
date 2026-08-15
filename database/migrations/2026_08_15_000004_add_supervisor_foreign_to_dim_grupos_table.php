<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la FK de dim_grupos.ID_Supervisor -> dim_usuarios.
     * No se pudo declarar en la creación de dim_grupos porque
     * dim_usuarios aún no existía (dependencia circular).
     */
    public function up(): void
    {
        Schema::table('dim_grupos', function (Blueprint $table) {
            $table->foreign('ID_Supervisor', 'fk_grupos_supervisor')
                  ->references('ID_Usuario')->on('dim_usuarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('dim_grupos', function (Blueprint $table) {
            $table->dropForeign('fk_grupos_supervisor');
        });
    }
};
