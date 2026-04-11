-- Migración: Crear tabla company_config para datos de empresa por sucursal
-- Esta tabla permite que cada sucursal configure su información de membrete,
-- prefijos de folio y consecutivos para reportes y bajas.

CREATE TABLE IF NOT EXISTS `company_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` INT UNSIGNED NOT NULL,
  `company_name` VARCHAR(255) NOT NULL DEFAULT '',
  `address_line_1` VARCHAR(255) NOT NULL DEFAULT '',
  `address_line_2` VARCHAR(255) NOT NULL DEFAULT '',
  `city_state_zip` VARCHAR(255) NOT NULL DEFAULT '',
  `phone_number` VARCHAR(255) NOT NULL DEFAULT '',
  `company_description` VARCHAR(500) NOT NULL DEFAULT '',
  `logo_path` VARCHAR(500) NOT NULL DEFAULT '',
  `report_prefix` VARCHAR(20) NOT NULL DEFAULT 'O.T',
  `unsubscribe_prefix` VARCHAR(20) NOT NULL DEFAULT 'BAJA',
  `report_current_number` INT UNSIGNED NOT NULL DEFAULT 0,
  `report_current_year` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `report_current_month` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `unsubscribe_current_number` INT UNSIGNED NOT NULL DEFAULT 0,
  `unsubscribe_current_year` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `unsubscribe_current_month` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
