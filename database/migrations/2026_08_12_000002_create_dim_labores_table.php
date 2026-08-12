<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla dim_labores ya existe en la base de datos
        // Esta migración solo registra que ha sido aplicada
    }

    public function down(): void
    {
        // No eliminar la tabla en down
    }
};
