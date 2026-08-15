<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Variedad;
use App\Models\Ubicacion;
use App\Models\Siembra;
use App\Models\Produccion;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CREAR VARIEDADES REALES
        $variedadesData = [
            ['Nombre_Variedad' => 'LIMONIUM MISTY BLUE', 'Color' => 'Azul', 'Ciclo_Dias' => 120],
            ['Nombre_Variedad' => 'LIMONIUM SUPREME WHITE', 'Color' => 'Blanco', 'Ciclo_Dias' => 120],
            ['Nombre_Variedad' => 'STATICE PURPLE', 'Color' => 'Morado', 'Ciclo_Dias' => 90],
            ['Nombre_Variedad' => 'STATICE PIPA WINGS', 'Color' => 'Blanco/Morado', 'Ciclo_Dias' => 90],
        ];

        $variedades = [];
        foreach ($variedadesData as $v) {
            $variedades[] = Variedad::firstOrCreate(['Nombre_Variedad' => $v['Nombre_Variedad']], $v);
        }

        $this->command->info('✅ Variedades creadas.');

        // 2. CREAR UBICACIONES (2 Bloques, 3 Naves por bloque, 5 Camas por nave)
        $ubicaciones = [];
        for ($bloque = 1; $bloque <= 2; $bloque++) {
            for ($nave = 1; $nave <= 3; $nave++) {
                for ($cama = 1; $cama <= 5; $cama++) {
                    $ubicaciones[] = Ubicacion::firstOrCreate([
                        'Bloque' => "BLOQUE $bloque",
                        'Nave' => $nave,
                        'Cama' => $cama
                    ]);
                }
            }
        }
        
        $this->command->info('✅ Ubicaciones creadas (Bloques, Naves y Camas).');

        // 3. CREAR SIEMBRAS Y PRODUCCIÓN PARA CADA CAMA
        $anioActual = date('Y');
        $fechaBase = Carbon::now()->subWeeks(15); // Siembras de hace 15 semanas

        foreach ($ubicaciones as $ubi) {
            // Seleccionar una variedad al azar
            $variedadRandom = $variedades[array_rand($variedades)];

            // Crear la siembra
            $siembra = Siembra::firstOrCreate([
                'ID_Ubicacion' => $ubi->ID_Ubicacion,
                'ID_Variedad' => $variedadRandom->ID_Variedad,
                'Fecha_Siembra' => $fechaBase->copy()->addDays(rand(1, 10))->format('Y-m-d'),
            ], [
                'Cantidad_Plantas' => rand(130, 160),
                'Metros_Lineales' => 34,
                // La densidad la calcula la base de datos automáticamente
                'Estado_Siembra' => 'EN PRODUCCION',
                'Ciclo_Actual' => 1,
                'Fecha_Pinch' => $fechaBase->copy()->addWeeks(4)->format('Y-m-d'),
                'Fecha_Hormona' => $fechaBase->copy()->addWeeks(5)->format('Y-m-d'),
            ]);

            // 4. GENERAR HISTORIAL DE PRODUCCIÓN (Últimas 8 semanas)
            for ($i = 8; $i >= 1; $i--) {
                $semana = Carbon::now()->subWeeks($i)->weekOfYear;
                
                // Simular una curva de producción (empieza bajito, sube y luego baja)
                $multiplicador = ($i == 8 || $i == 1) ? 0.5 : 1.2; 
                $tallos = rand(80, 250) * $multiplicador;
                $bajas = rand(0, 15);

                Produccion::updateOrCreate([
                    'ID_Ubicacion' => $ubi->ID_Ubicacion,
                    'Semana' => $semana,
                    'Anio' => $anioActual,
                ], [
                    'Bajas' => $bajas,
                    'Total' => (int) $tallos,
                ]);
            }
        }

        $this->command->info('✅ Siembras y Producción semanal generadas con éxito.');
        $this->command->info('🚀 ¡Base de datos poblada y lista para pruebas!');
    }
}