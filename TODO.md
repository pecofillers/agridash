ALTER TABLE fact_produccion
ADD INDEX idx_produccion_ubicacion_fecha
(
 ID_Ubicacion,
 Anio,
 Semana
);

CREATE TABLE dim_bloques (
    ID_Bloque INT NOT NULL AUTO_INCREMENT,
    Codigo_Bloque VARCHAR(10) NOT NULL,
    Nombre_Bloque VARCHAR(40) NULL,
    Descripcion TEXT NULL,
    Estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Bloque),
    UNIQUE KEY uq_dim_bloques_codigo (Codigo_Bloque)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE dim_ubicaciones
ADD COLUMN ID_Bloque INT NULL AFTER ID_Ubicacion;

# Cargar bloques a bloques
# Cambiar ids
INSERT INTO dim_bloques (
    Codigo_Bloque,
    Nombre_Bloque
)
SELECT DISTINCT
    TRIM(Bloque),
    CONCAT('Bloque ', TRIM(Bloque))
FROM dim_ubicaciones
WHERE Bloque IS NOT NULL
  AND TRIM(Bloque) <> '';

# Bloque a ubicaciones

UPDATE dim_ubicaciones u
INNER JOIN dim_bloques b
    ON TRIM(u.Bloque) COLLATE utf8mb4_unicode_ci
     = TRIM(b.Codigo_Bloque) COLLATE utf8mb4_unicode_ci
SET u.ID_Bloque = b.ID_Bloque;

#### - 

SELECT
    COUNT(*) AS total_ubicaciones,
    COUNT(ID_Bloque) AS con_bloque,
    COUNT(*) - COUNT(ID_Bloque) AS sin_bloque
FROM dim_ubicaciones;

### --
rta 1942

SELECT
    ID_Ubicacion,
    Bloque,
    ID_Bloque
FROM dim_ubicaciones
WHERE ID_Bloque IS NULL;


###
rta 0

# Crear la FK de dim_ubicaciones

ALTER TABLE dim_ubicaciones
ADD CONSTRAINT fk_ubicaciones_bloque
FOREIGN KEY (ID_Bloque)
REFERENCES dim_bloques(ID_Bloque);


# Agregar ID_Bloque a planillas

ALTER TABLE planillas
ADD COLUMN ID_Bloque INT NULL AFTER id;

UPDATE planillas p
INNER JOIN dim_bloques b
    ON TRIM(p.bloque) = TRIM(b.Codigo_Bloque)
SET p.ID_Bloque = b.ID_Bloque;

# Validar
SELECT *
FROM planillas
WHERE bloque IS NOT NULL
  AND TRIM(bloque) <> ''
  AND ID_Bloque IS NULL;

ALTER TABLE planillas
ADD CONSTRAINT fk_planillas_bloque
FOREIGN KEY (ID_Bloque)
REFERENCES dim_bloques(ID_Bloque);

## Crear dim_ciclos_siembras

CREATE TABLE dim_ciclos_siembras (
    ID_Ciclo_Siembra INT NOT NULL AUTO_INCREMENT,
    ID_Bloque INT NOT NULL,
    ID_Variedad INT NOT NULL,
    Fecha_Siembra DATE NOT NULL,
    Estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ID_Ciclo_Siembra),
    KEY idx_ciclos_bloque (ID_Bloque),
    KEY idx_ciclos_variedad (ID_Variedad),
    KEY idx_ciclos_fecha (Fecha_Siembra),
    CONSTRAINT fk_ciclos_bloque
        FOREIGN KEY (ID_Bloque)
        REFERENCES dim_bloques(ID_Bloque),
    CONSTRAINT fk_ciclos_variedad
        FOREIGN KEY (ID_Variedad)
        REFERENCES dim_variedades(ID_Variedad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## Me quede aqui: :()


## Agregar el ciclo a dim_siembras

ALTER TABLE dim_siembras
ADD COLUMN ID_Ciclo_Siembra INT NULL AFTER ID_Siembra;

ALTER TABLE dim_ciclos_siembras
ADD UNIQUE KEY uq_ciclo_bloque_variedad_fecha
(
    ID_Bloque,
    ID_Variedad,
    Fecha_Siembra
);

## Fechas Invalidas
UPDATE dim_siembras
SET Fecha_Pinch = NULL
WHERE Fecha_Pinch = '0000-00-00';

UPDATE dim_siembras
SET Fecha_Hormona = NULL
WHERE Fecha_Hormona = '0000-00-00';

UPDATE dim_siembras
SET Fecha_Erradicacion = NULL
WHERE Fecha_Erradicacion = '0000-00-00';

## Ahora viene la creación de ciclos históricos

Aquí no recomiendo ejecutar todavía un INSERT definitivo.

Primero debemos saber exactamente qué combinaciones tenemos:

SELECT
    u.ID_Bloque,
    s.ID_Variedad,
    s.Fecha_Siembra,
    COUNT(*) AS cantidad_siembras,
    COUNT(DISTINCT s.ID_Ubicacion) AS cantidad_ubicaciones,
    SUM(s.Cantidad_Plantas) AS plantas
FROM dim_siembras s
INNER JOIN dim_ubicaciones u
    ON u.ID_Ubicacion = s.ID_Ubicacion
GROUP BY
    u.ID_Bloque,
    s.ID_Variedad,
    s.Fecha_Siembra
ORDER BY
    s.Fecha_Siembra,
    u.ID_Bloque,
    s.ID_Variedad;

Esta consulta nos va a mostrar exactamente algo como:

Bloque | Variedad | Fecha       | Camas | Plantas
--------------------------------------------------
1      | 1        | 25/07/2023  | 41    | 6.200
1      | 1        | 19/10/2023  | 24    | 3.500
1      | 7        | 02/07/2026  | 41    | 6.100

Esta consulta es fundamental antes de generar los ciclos.

## Crear ciclos

INSERT INTO dim_ciclos_siembras (
    ID_Bloque,
    ID_Variedad,
    Fecha_Siembra
)
SELECT DISTINCT
    u.ID_Bloque,
    s.ID_Variedad,
    s.Fecha_Siembra
FROM dim_siembras s
INNER JOIN dim_ubicaciones u
    ON u.ID_Ubicacion = s.ID_Ubicacion
WHERE s.Fecha_Siembra IS NOT NULL;

## Asociar siembras con ciclo

UPDATE dim_siembras s
INNER JOIN dim_ubicaciones u
    ON u.ID_Ubicacion = s.ID_Ubicacion
INNER JOIN dim_ciclos_siembras c
    ON c.ID_Bloque = u.ID_Bloque
    AND c.ID_Variedad = s.ID_Variedad
    AND c.Fecha_Siembra = s.Fecha_Siembra
SET s.ID_Ciclo_Siembra = c.ID_Ciclo_Siembra;

# Validar
Luego validamos:

SELECT
    COUNT(*) AS siembras_sin_ciclo
FROM dim_siembras
WHERE ID_Ciclo_Siembra IS NULL;

Nuestro objetivo:

siembras_sin_ciclo = 0

## FK de la siembra
ALTER TABLE dim_siembras
ADD CONSTRAINT fk_siembras_ciclo
FOREIGN KEY (ID_Ciclo_Siembra)
REFERENCES dim_ciclos_siembras(ID_Ciclo_Siembra);

ALTER TABLE dim_siembras
MODIFY ID_Ciclo_Siembra INT NOT NULL;

## ################################################################################
## Hasta aca la mornalizacion de la base de datos.



### Agreglo produccion

# Indice nuevo - Acelera consultas

ALTER TABLE fact_produccion
ADD INDEX idx_produccion_siembra (
    ID_Siembra,
    Anio,
    Semana
);

## Eliminacion de tabla planillas y agregar campo a bloques

ALTER TABLE dim_bloques
ADD COLUMN url_onedrive VARCHAR(500) NULL AFTER Descripcion;

DROP TABLE planillas;

## Clave produccion unica.
ALTER TABLE fact_produccion
ADD UNIQUE KEY uq_produccion_ubicacion_semana (
    ID_Ubicacion,
    Anio,
    Semana
);

# Permitir produccion sin siembra.
ALTER TABLE fact_produccion
MODIFY ID_Siembra INT NULL;