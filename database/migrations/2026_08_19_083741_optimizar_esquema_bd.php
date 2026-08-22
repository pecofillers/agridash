<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modificar Ubicaciones
        Schema::table('dim_ubicaciones', function (Blueprint $table) {
            $table->decimal('Metros_Lineales', 10, 2)->default(0)->after('Cama');
            $table->integer('Cuadros')->nullable()->after('Metros_Lineales');
            
            $table->string('Bloque', 15)->change();
            $table->string('Nave', 15)->change();
            $table->string('Cama', 15)->change();
        });

        // 2. Modificar Siembras
        Schema::table('dim_siembras', function (Blueprint $table) {
            // Eliminamos la columna calculada y los metros, ya que ahora están en ubicaciones
            $table->dropColumn(['Densidad_Plantacion', 'Metros_Lineales']);
            $table->string('Estado_Siembra', 50)->change();
        });

        // 3. Modificar Detalles de Rendimiento
        Schema::table('fact_rendimiento_detalles', function (Blueprint $table) {
            $table->string('Nombre_Variante', 20)->change();
        });

        // 4. Modificar Roles, Grupos y Labores
        Schema::table('dim_roles', function (Blueprint $table) {
            $table->string('Nombre_Rol', 20)->change();
        });

        Schema::table('dim_grupos', function (Blueprint $table) {
            $table->string('Nombre_Grupo', 20)->change();
        });

        Schema::table('dim_labores', function (Blueprint $table) {
            $table->string('Nombre_Labor', 20)->change();
        });

        // 5. Modificar Usuarios
        Schema::table('dim_usuarios', function (Blueprint $table) {
            $table->string('Username', 30)->change();
            $table->string('Nombre', 30)->change();
            $table->string('Apellidos', 30)->change();
            $table->string('Telefono', 15)->change();
            $table->string('Correo', 50)->change();
        });

        Schema::table('fact_rendimientos_labor', function (Blueprint $table) {
            $table->timestamps(); // Esto agrega 'created_at' y 'updated_at' mágicamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // El método down no es estrictamente necesario a menos que desees
        // revertir y devolver todos los campos a sus longitudes originales
    }
};