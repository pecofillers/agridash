<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las tablas de Laravel que NO se usan en AgriDash.
 * - users: la autenticación usa dim_usuarios (Usuario model).
 * - password_reset_tokens: no se usa restablecimiento de contraseña por token.
 * Se conserva 'sessions' (driver session=database), 'cache', 'cache_locks',
 * 'jobs', 'job_batches' y 'failed_jobs' (drivers database).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        // No se recrean para no reintroducir tablas innecesarias.
    }
};
