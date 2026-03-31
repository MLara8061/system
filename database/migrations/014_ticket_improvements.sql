-- =====================================================
-- Migracion 014: Mejoras del modulo de Tickets
-- 1. Campo priority en tickets
-- 2. Campo tracking_token en tickets
-- 3. Campo assigned_to en tickets
-- 4. Tabla quick_replies (respuestas rapidas)
-- 5. Generar tracking_token para tickets publicos existentes
-- MariaDB 10.0+ soporta ADD COLUMN IF NOT EXISTS / ADD INDEX IF NOT EXISTS
-- =====================================================

-- === 1. Prioridad del ticket ===
ALTER TABLE `tickets`
    ADD COLUMN IF NOT EXISTS `priority` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium'
    COMMENT 'Prioridad del ticket' AFTER `status`;

-- === 2. Token de seguimiento publico ===
ALTER TABLE `tickets`
    ADD COLUMN IF NOT EXISTS `tracking_token` VARCHAR(64) DEFAULT NULL
    COMMENT 'Token para seguimiento publico' AFTER `is_public`;

-- Indice unico para tracking_token (solo si no existe)
ALTER TABLE `tickets`
    ADD UNIQUE INDEX IF NOT EXISTS `idx_tracking_token` (`tracking_token`);

-- === 3. Tecnico asignado ===
ALTER TABLE `tickets`
    ADD COLUMN IF NOT EXISTS `assigned_to` INT DEFAULT NULL
    COMMENT 'ID del tecnico asignado (users.id)' AFTER `staff_id`;

-- === 4. Tabla de respuestas rapidas ===
CREATE TABLE IF NOT EXISTS `quick_replies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL COMMENT 'Titulo corto de la respuesta',
    `content` TEXT NOT NULL COMMENT 'Contenido HTML de la respuesta',
    `category` VARCHAR(50) DEFAULT 'general' COMMENT 'Categoria de la respuesta',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar respuestas rapidas predeterminadas (idempotente via INSERT IGNORE)
INSERT IGNORE INTO `quick_replies` (`id`, `title`, `content`, `category`, `sort_order`) VALUES
(1, 'Recibido - En revision', 'Hemos recibido su reporte y se encuentra en revision. Un tecnico sera asignado en breve.', 'estado', 1),
(2, 'Tecnico asignado', 'Se ha asignado un tecnico para atender su solicitud. Sera contactado proximamente para coordinar la visita.', 'estado', 2),
(3, 'En proceso de reparacion', 'El equipo se encuentra actualmente en proceso de reparacion. Le mantendremos informado del avance.', 'estado', 3),
(4, 'Solicitar informacion', 'Para poder continuar con la atencion de su ticket, necesitamos la siguiente informacion adicional:', 'consulta', 4),
(5, 'Equipo reparado', 'El equipo ha sido reparado exitosamente y se encuentra operativo. Por favor confirme que todo funciona correctamente.', 'resolucion', 5),
(6, 'Requiere refaccion', 'El equipo requiere una refaccion que se encuentra en proceso de adquisicion. Le notificaremos cuando este disponible.', 'estado', 6),
(7, 'Cierre de ticket', 'Se procede a cerrar el presente ticket. Si requiere asistencia adicional, no dude en generar un nuevo reporte.', 'resolucion', 7);

-- === 5. Generar tracking_token para tickets publicos existentes sin token ===
-- UUID() en MariaDB devuelve 36 chars; REPLACE quita guiones -> 32 chars hex, compatible con bin2hex(random_bytes(16))
UPDATE `tickets`
SET `tracking_token` = REPLACE(UUID(), '-', '')
WHERE `is_public` = 1
  AND (`tracking_token` IS NULL OR `tracking_token` = '');
