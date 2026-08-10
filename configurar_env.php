<?php
// Script temporal para configurar .env con MySQL (agrega claves si faltan)
$f = __DIR__ . '/.env';
if (!file_exists($f)) {
    copy(__DIR__ . '/.env.example', $f);
}
$c = file_get_contents($f);

$reemplazos = [
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_DATABASE' => 'agridash_db',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
];

foreach ($reemplazos as $clave => $valor) {
    $patron = '/^' . preg_quote($clave, '/') . '=.*$/m';
    if (preg_match($patron, $c)) {
        // Reemplaza si existe
        $c = preg_replace($patron, $clave . '=' . $valor, $c);
    } else {
        // Si no existe, la agrega después de la línea DB_CONNECTION
        $c .= "\n" . $clave . '=' . $valor;
    }
}

file_put_contents($f, $c);
echo "OK: .env configurado con MySQL\n";
