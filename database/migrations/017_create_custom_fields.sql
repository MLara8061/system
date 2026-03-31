-- Migration 017: Campos personalizados para Equipos, Herramientas, Accesorios e Insumos
-- Ejecutar en la base de datos del sistema

CREATE TABLE IF NOT EXISTS `custom_field_definitions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('equipment','tool','accessory','inventory') NOT NULL,
    `field_name`  VARCHAR(100) NOT NULL,
    `field_label` VARCHAR(150) NOT NULL,
    `field_type`  ENUM('text','number','date','select','textarea','checkbox') NOT NULL DEFAULT 'text',
    `options`     JSON DEFAULT NULL COMMENT 'Opciones para tipo select: ["Op1","Op2"]',
    `is_required` TINYINT(1) DEFAULT 0,
    `sort_order`  INT DEFAULT 0,
    `active`      TINYINT(1) DEFAULT 1,
    `branch_id`   INT UNSIGNED DEFAULT NULL COMMENT 'NULL = global, con valor = solo esa sucursal',
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_entity_field` (`entity_type`, `field_name`, `branch_id`),
    INDEX `idx_entity` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Definiciones de campos personalizados por tipo de entidad';

CREATE TABLE IF NOT EXISTS `custom_field_values` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `definition_id` INT UNSIGNED NOT NULL,
    `entity_type`   ENUM('equipment','tool','accessory','inventory') NOT NULL,
    `entity_id`     INT UNSIGNED NOT NULL,
    `field_value`   TEXT DEFAULT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_field_entity` (`definition_id`, `entity_type`, `entity_id`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_cfv_definition`
        FOREIGN KEY (`definition_id`) REFERENCES `custom_field_definitions`(`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Valores de campos personalizados por entidad';
