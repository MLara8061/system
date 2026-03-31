-- ============================================================
-- Migración 019: Sprint 5 (Reportes + Branding)
-- E6.1, E6.2, E7.1
-- ============================================================

-- 1) E6.1 - Horas de uso diario para cálculo de kWh
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'equipment_power_specs'
          AND COLUMN_NAME = 'daily_usage_hours'
    ),
    'SELECT 1',
    "ALTER TABLE equipment_power_specs ADD COLUMN daily_usage_hours DECIMAL(4,1) DEFAULT 8.0 COMMENT 'horas estimadas de uso diario'"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) E6.2 - Relación directa accesorio -> equipo
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accessories'
          AND COLUMN_NAME = 'equipment_id'
    ),
    'SELECT 1',
    "ALTER TABLE accessories ADD COLUMN equipment_id INT UNSIGNED DEFAULT NULL"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'accessories'
          AND INDEX_NAME = 'idx_equipment'
    ),
    'SELECT 1',
    'ALTER TABLE accessories ADD INDEX idx_equipment (equipment_id)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3) E7.1 - Logo por sucursal en company_config
SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'company_config'
    ),
    'SELECT 1',
    "CREATE TABLE company_config (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        branch_id INT UNSIGNED NOT NULL,
        company_name VARCHAR(255) NOT NULL DEFAULT '',
        address_line_1 VARCHAR(255) NOT NULL DEFAULT '',
        address_line_2 VARCHAR(255) NOT NULL DEFAULT '',
        city_state_zip VARCHAR(255) NOT NULL DEFAULT '',
        phone_number VARCHAR(255) NOT NULL DEFAULT '',
        company_description VARCHAR(500) NOT NULL DEFAULT '',
        logo_path VARCHAR(500) NOT NULL DEFAULT '',
        report_prefix VARCHAR(20) NOT NULL DEFAULT 'O.T',
        unsubscribe_prefix VARCHAR(20) NOT NULL DEFAULT 'BAJA',
        report_current_number INT UNSIGNED NOT NULL DEFAULT 0,
        report_current_year SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        report_current_month TINYINT UNSIGNED NOT NULL DEFAULT 0,
        unsubscribe_current_number INT UNSIGNED NOT NULL DEFAULT 0,
        unsubscribe_current_year SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        unsubscribe_current_month TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_branch (branch_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'company_config'
          AND COLUMN_NAME = 'logo_path'
    ),
    'SELECT 1',
    "ALTER TABLE company_config ADD COLUMN logo_path VARCHAR(500) NOT NULL DEFAULT '' AFTER company_description"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
