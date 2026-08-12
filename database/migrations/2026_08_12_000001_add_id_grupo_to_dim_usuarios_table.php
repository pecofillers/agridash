<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la relación real entre usuarios y grupos.
     */
    public function up(): void
    {
        Schema::table('dim_usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('dim_usuarios', 'ID_Grupo')) {
                $table->unsignedInteger('ID_Grupo')->nullable()->after('ID_Rol');
                $table->foreign('ID_Grupo')->references('ID_Grupo')->on('dim_grupos')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dim_usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('dim_usuarios', 'ID_Grupo')) {
                $table->dropForeign(['ID_Grupo']);
                $table->dropColumn('ID_Grupo');
            }
        });
    }
};
