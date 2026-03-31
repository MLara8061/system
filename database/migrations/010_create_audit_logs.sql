-- =====================================================
-- Migración 010: Crear tabla audit_logs
-- Registro detallado de auditoría con old/new values
-- =====================================================

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT          NOT NULL,
    `user_name`   VARCHAR(200) DEFAULT NULL COMMENT 'Snapshot del nombre para consulta rápida',
    `module`      VARCHAR(60)  NOT NULL COMMENT 'equipment, ticket, inventory, user, etc.',
    `action`      ENUM('create','update','delete','move','login','logout','export') NOT NULL,
    `table_name`  VARCHAR(100) NOT NULL,
    `record_id`   INT          DEFAULT NULL,
    `old_values`  JSON         DEFAULT NULL,
    `new_values`  JSON         DEFAULT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `user_agent`  VARCHAR(255) DEFAULT NULL,
    `branch_id`   INT UNSIGNED DEFAULT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_module`   (`module`),
    INDEX `idx_audit_action`   (`action`),
    INDEX `idx_audit_user`     (`user_id`),
    INDEX `idx_audit_table`    (`table_name`),
    INDEX `idx_audit_created`  (`created_at`),
    INDEX `idx_audit_branch`   (`branch_id`),
    INDEX `idx_audit_record`   (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrar módulo en el sistema de permisos
INSERT IGNORE INTO `system_modules` (`code`, `name`, `description`, `icon`, `order`, `active`)
VALUES ('audit_logs', 'Registro de Auditoría', 'Visualización y exportación del log de auditoría del sistema', 'fas fa-shield-alt', 95, 1);

-- Dar permiso de vista y exportación al rol Super Admin (role_id = 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `module_code`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_export`)
VALUES (1, 'audit_logs', 1, 0, 0, 0, 1);
