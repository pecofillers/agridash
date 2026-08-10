<?php
/**
 * _registrar_migraciones.php
 *
 * PROPOSITO:
 *   Registrar en la tabla `migrations` las migraciones de las tablas
 *   dim_* / fact_* como YA APLICADAS, SIN volver a crear las tablas.
 *
 *   Esto es necesario porque tus tablas YA EXISTEN en la base de datos
 *   (creadas por el proyecto Python / SQL original). Laravel intenta
 *   recrearlas y falla con "Table already exists".
 *
 * COMO USAR (desde la raiz de agridash-web):
 *   php _registrar_migraciones.php
 *
 *   Luego verifica:
 *   php artisan migrate
 *   => Deberia decir "Nothing to migrate".
 *
 * IMPORTANTE:
 *   Este script NO borra ni modifica tus tablas. Solo inserta registros
 *   en la tabla `migrations` para "marcar" las migraciones como hechas.
 *   Es seguro ejecutarlo.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Filas a registrar (deben coincidir EXACTAMENTE con los nombres de archivo
// que aparecen en "php artisan migrate:status" como Pending).
$migraciones = [
    '2026_08_07_235348_create_dim_roles_table',
    '2026_08_07_235349_create_dim_permisos_rol_table',
    '2026_08_07_235350_create_dim_usuarios_table',
    '2026_08_07_235351_create_dim_ubicaciones_table',
    '2026_08_07_235353_create_dim_siembras_table',
    '2026_08_07_235353_create_dim_variedades_table',
    '2026_08_07_235354_create_dim_grupos_table',
    '2026_08_07_235355_create_dim_colaboradores_table',
    '2026_08_07_235356_create_fact_produccion_table',
    '2026_08_07_235357_create_fact_rendimientos_labor_table',
];

echo "Registrando migraciones como aplicadas...\n";

foreach ($migraciones as $m) {
    $existe = DB::table('migrations')->where('migration', $m)->exists();
    if ($existe) {
        echo "  [SKIP] {$m} (ya registrada)\n";
        continue;
    }
    DB::table('migrations')->insert([
        'migration' => $m,
        'batch'     => 1,
    ]);
    echo "  [OK] {$m}\n";
}

echo "\nListo. Ejecuta ahora: php artisan migrate\n";
