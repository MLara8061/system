-- =====================================================
-- Migración 013: Sprint 2 - Tickets & Comunicación
-- E2.1 ticket_attachments
-- E2.2 notifications
-- E2.3 ticket_status_history
-- E2.4 alter ticket_comment (is_internal)
-- =====================================================

-- === E2.1: Adjuntos de tickets ===
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL COMMENT 'image/jpeg, image/png, application/pdf',
    `file_size` INT UNSIGNED NOT NULL COMMENT 'bytes',
    `uploaded_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === E2.2: Notificaciones in-app ===
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'ticket_status, ticket_comment, maintenance_due, etc.',
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === E2.3: Historial de estados de ticket ===
CREATE TABLE IF NOT EXISTS `ticket_status_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `old_status` TINYINT DEFAULT NULL,
    `new_status` TINYINT NOT NULL,
    `changed_by` INT NOT NULL,
    `comment` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === E2.4: Agregar is_internal a comments para notas privadas ===
-- Solo si no existe la columna
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comments' AND COLUMN_NAME = 'is_internal');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `comments` ADD COLUMN `is_internal` TINYINT(1) DEFAULT 0 COMMENT ''1=nota privada tecnico'' AFTER `comment`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
