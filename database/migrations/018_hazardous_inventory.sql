-- ============================================================
-- Migración 018: Sustancias peligrosas en inventario
-- Sprint 4 - E5.1
-- ============================================================

-- 1. Agregar columnas de riesgo a la tabla inventory
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory'
          AND COLUMN_NAME = 'is_hazardous'
    ),
    'SELECT 1',
    "ALTER TABLE inventory ADD COLUMN is_hazardous TINYINT(1) DEFAULT 0 COMMENT '1=sustancia peligrosa'"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory'
          AND COLUMN_NAME = 'hazard_class'
    ),
    'SELECT 1',
    "ALTER TABLE inventory ADD COLUMN hazard_class VARCHAR(100) DEFAULT NULL COMMENT 'inflamable, corrosivo, toxico, oxidante, explosivo, irritante, otro'"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory'
          AND COLUMN_NAME = 'safety_data_sheet'
    ),
    'SELECT 1',
    "ALTER TABLE inventory ADD COLUMN safety_data_sheet VARCHAR(500) DEFAULT NULL COMMENT 'path relativo a uploads/ del archivo de hoja de seguridad'"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory'
          AND INDEX_NAME = 'idx_hazardous'
    ),
    'SELECT 1',
    'ALTER TABLE inventory ADD INDEX idx_hazardous (is_hazardous)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Tabla de documentos adicionales por ítem de inventario
CREATE TABLE IF NOT EXISTS inventory_documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_id  INT UNSIGNED NOT NULL,
    document_type ENUM('safety_data_sheet','certificate','photo','other') NOT NULL DEFAULT 'other',
    file_name     VARCHAR(255) NOT NULL,
    file_path     VARCHAR(500) NOT NULL,
    file_type     VARCHAR(50)  NOT NULL,
    uploaded_by   INT          NOT NULL DEFAULT 0,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory_documents'
          AND INDEX_NAME = 'idx_inventory'
    ),
    'SELECT 1',
    'ALTER TABLE inventory_documents ADD INDEX idx_inventory (inventory_id)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory_documents'
    )
    AND EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory'
    )
    AND EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory_documents'
          AND COLUMN_NAME = 'inventory_id'
          AND COLUMN_TYPE = 'int(10) unsigned'
    )
    AND EXISTS(
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'inventory'
          AND COLUMN_NAME = 'id'
          AND COLUMN_TYPE = 'int(10) unsigned'
    )
    AND NOT EXISTS(
        SELECT 1
        FROM information_schema.REFERENTIAL_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND CONSTRAINT_NAME = 'fk_invdoc_inventory'
    ),
    'ALTER TABLE inventory_documents ADD CONSTRAINT fk_invdoc_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Registrar módulo sustancias peligrosas (si existe la tabla system_modules)
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'system_modules'
    ),
    "INSERT IGNORE INTO system_modules (code, name, description, icon, `order`, active)
     VALUES ('hazardous_materials', 'Sustancias Peligrosas',
             'Visualización de insumos clasificados como sustancia peligrosa',
             'fas fa-exclamation-triangle', 85, 1)",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Permisos por rol para el nuevo módulo
-- Admin (role_id=1): acceso total
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'role_permissions'
    )
    AND EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'roles'
    ),
    "INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_delete, can_export)
     SELECT r.id, 'hazardous_materials', 1, 1, 1, 1, 1
     FROM roles r
     WHERE r.id = 1
     ON DUPLICATE KEY UPDATE
         can_view = VALUES(can_view),
         can_create = VALUES(can_create),
         can_edit = VALUES(can_edit),
         can_delete = VALUES(can_delete),
         can_export = VALUES(can_export)",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Supervisor/gestor (role_id=2): solo visualización y exportación
SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'role_permissions'
    )
    AND EXISTS(
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'roles'
    ),
    "INSERT INTO role_permissions (role_id, module_code, can_view, can_create, can_edit, can_delete, can_export)
     SELECT r.id, 'hazardous_materials', 1, 0, 0, 0, 1
     FROM roles r
     WHERE r.id = 2
     ON DUPLICATE KEY UPDATE
         can_view = VALUES(can_view),
         can_create = VALUES(can_create),
         can_edit = VALUES(can_edit),
         can_delete = VALUES(can_delete),
         can_export = VALUES(can_export)",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
