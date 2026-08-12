<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero, eliminar la clave foránea antigua si existe
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('ALTER TABLE `fact_rendimientos_labor` DROP FOREIGN KEY IF EXISTS `fk_rendimiento_usuario`');
        
        // Asegurar que el tipo de dato es compatible con dim_usuarios
        DB::statement('ALTER TABLE `fact_rendimientos_labor` MODIFY COLUMN `ID_Colaborador` BIGINT UNSIGNED NULL');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // No hacer cambios en down
    }
};
