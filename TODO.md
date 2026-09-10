CREATE TABLE `planillas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bloque` VARCHAR(15) NOT NULL UNIQUE,
  `url_onedrive` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `insumos_diarios` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fecha` DATE NOT NULL,
    `amortiguador_medida_visual` DECIMAL(10,2) NULL,
    `amortiguador_medida_registro` DECIMAL(10,2) NULL,
    `amortiguador_diferencia_registro` DECIMAL(10,2) NULL,
    `amortiguador_diferencia_visual` DECIMAL(10,2) NULL,
    `agrofeed_medida_visual` DECIMAL(10,2) NULL,
    `agrofeed_medida_registro` DECIMAL(10,2) NULL,
    `agrofeed_diferencia_registro` DECIMAL(10,2) NULL,
    `agrofeed_diferencia_visual` DECIMAL(10,2) NULL,
    `agua_lectura` DECIMAL(12,2) NULL,
    `agua_diferencia_registro` DECIMAL(12,2) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `insumos_diarios_fecha_unique` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;