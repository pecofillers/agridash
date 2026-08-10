<?php
$f = __DIR__ . '/.env';
$c = file_get_contents($f);
$lines = explode("\n", $c);
foreach ($lines as $line) {
    if (preg_match('/^DB_/', $line) || preg_match('/^APP_/', $line) || preg_match('/^SESSION_/', $line)) {
        echo $line . "\n";
    }
}
