-- Migration 016: Tabla de adjuntos fotográficos para reportes de mantenimiento
-- Ejecutar en la base de datos del sistema

CREATE TABLE IF NOT EXISTS `report_attachments` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id`  INT UNSIGNED NOT NULL DEFAULT 0,
    `file_name`  VARCHAR(255)  NOT NULL,
    `file_path`  VARCHAR(500)  NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_report` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Evidencia fotográfica adjunta a reportes de mantenimiento';
