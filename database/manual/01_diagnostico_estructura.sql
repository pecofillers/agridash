-- Agridash: inventario de SOLO LECTURA.
-- Seleccionar primero la base correcta en phpMyAdmin.
-- Ejecutar en desarrollo y producción y conservar ambos resultados.
-- No contiene credenciales, escrituras ni consultas de datos personales.

SELECT DATABASE() AS base_seleccionada,
       VERSION() AS version_servidor,
       @@sql_mode AS modo_sql,
       @@character_set_database AS charset_base,
       @@collation_database AS collation_base;

SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

SELECT TABLE_NAME, ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE,
       IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX,
       COLUMN_NAME, SUB_PART, INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME, k.ORDINAL_POSITION,
       k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME,
       r.UPDATE_RULE, r.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE k
JOIN information_schema.REFERENTIAL_CONSTRAINTS r
  ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
 AND r.TABLE_NAME = k.TABLE_NAME
 AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
WHERE k.CONSTRAINT_SCHEMA = DATABASE()
  AND k.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION;

-- Ejecutar las consultas siguientes solo si existe fact_produccion.
-- Las filas borradas lógicamente también importan para la clave UNIQUE.
SELECT ID_Ubicacion, Anio, Semana, COUNT(*) AS registros
FROM fact_produccion
GROUP BY ID_Ubicacion, Anio, Semana
HAVING COUNT(*) > 1;

SELECT COUNT(*) AS registros,
       SUM(CASE WHEN Total = 0 THEN 1 ELSE 0 END) AS registros_cero,
       SUM(CASE WHEN Total IS NULL THEN 1 ELSE 0 END) AS total_nulo,
       SUM(CASE WHEN ID_Siembra IS NULL THEN 1 ELSE 0 END) AS sin_siembra,
       SUM(CASE WHEN ID_Ubicacion IS NULL OR Anio IS NULL OR Semana IS NULL
                THEN 1 ELSE 0 END) AS clave_incompleta,
       SUM(CASE WHEN Semana < 1 OR Semana > 53 THEN 1 ELSE 0 END) AS semana_fuera_rango
FROM fact_produccion;

-- No deduplicar, borrar ceros ni asociar siembras automáticamente a partir
-- de estos resultados. Una semana 53 también requiere validar su año ISO.
-- Complementar con exportación SOLO DE ESTRUCTURA desde phpMyAdmin:
-- así se conservan las definiciones completas de tablas, vistas y triggers.
