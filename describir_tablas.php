<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tablas = ['dim_usuarios', 'dim_roles', 'dim_permisos_rol', 'dim_ubicaciones', 'dim_variedades', 'dim_siembras', 'dim_grupos', 'dim_colaboradores', 'fact_produccion', 'fact_rendimientos_labor'];
foreach ($tablas as $t) {
    echo "=== $t ===\n";
    $cols = DB::select("DESCRIBE $t");
    foreach ($cols as $c) {
        $c = (array)$c;
        echo "  {$c['Field']} | {$c['Type']} | Null={$c['Null']} | Key={$c['Key']} | Default={$c['Default']}\n";
    }
    echo "\n";
}
