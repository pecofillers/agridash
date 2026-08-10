-- =====================================================================
-- migrations_registradas.sql
-- Hola Mundo
-- =====================================================================
-- PROPOSITO:
--   Registrar las migraciones de Laravel como YA APLICADAS sin volver a
--   crear las tablas. Esto es necesario porque las tablas dim_* / fact_*
--   YA EXISTEN en la base de datos (creadas previamente por el proyecto
--   Python / el SQL de migracion original).
--
-- COMO USAR:
--   1. Abre phpMyAdmin (o el cliente MySQL de tu preferencia).
--   2. Selecciona la base de datos: agridash_db
--   3. Importa este archivo (Importar) O ejecuta el SQL manualmente.
--   4. Luego ejecuta en la terminal:  php artisan migrate
--      Deberia decir "Nothing to migrate" (o solo las que falten).
--
-- IMPORTANTE:
--   Este script NO borra ni modifica tus tablas existentes. Solo crea la
--   tabla `migrations` (si no existe) y la marca como ya aplicadas, para
--   que Laravel no intente recrear tablas que ya estan.
-- =====================================================================

-- 1) Crear la tabla `migrations` de Laravel si no existe
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Registrar cada migracion existente como "aplicada" (batch 1)
--    Usamos INSERT IGNORE para no duplicar si ya se ejecutara antes.
INSERT IGNORE INTO migrations (migration, batch) VALUES
('2026_08_07_235348_create_dim_roles_table', 1),
('2026_08_07_235349_create_dim_permisos_rol_table', 1),
('2026_08_07_235350_create_dim_usuarios_table', 1),
('2026_08_07_235351_create_dim_ubicaciones_table', 1),
('2026_08_07_235353_create_dim_variedades_table', 1),
('2026_08_07_235353_create_dim_siembras_table', 1),
('2026_08_07_235354_create_dim_grupos_table', 1),
('2026_08_07_235355_create_dim_colaboradores_table', 1),
('2026_08_07_235356_create_fact_produccion_table', 1),
('2026_08_07_235357_create_fact_rendimientos_labor_table', 1);

-- =====================================================================
-- VERIFICACION:
--   SELECT * FROM migrations;
--   Debe mostrar las 10 migraciones registradas con batch=1.
--
--   Luego corre:  php artisan migrate:status
--   Todas deben aparecer como "Ran" (aplicadas), no "Pending".
-- =====================================================================
