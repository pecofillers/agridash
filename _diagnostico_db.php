<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DRIVERS ===\n";
echo "SESSION=".config('session.driver')."\n";
echo "CACHE=".config('cache.default')."\n";
echo "QUEUE=".config('queue.default')."\n";
echo "DB=".config('database.default')."\n";

$pdo = DB::connection()->getPdo();

echo "\n=== CONTEO TABLAS LARAVEL ===\n";
foreach (['users','password_reset_tokens','jobs','job_batches','failed_jobs','sessions','cache','cache_locks'] as $t) {
    try {
        $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "- $t: $n filas\n";
    } catch (Exception $e) {
        echo "- $t: NO EXISTE o error\n";
    }
}
