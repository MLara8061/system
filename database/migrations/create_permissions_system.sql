-- =====================================================
-- Sistema de Permisos Granulares por Departamento
-- =====================================================

-- 1. Tabla de roles (si no existe)
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_admin` TINYINT(1) DEFAULT 0 COMMENT '1=Admin global, 0=Normal',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar roles por defecto
INSERT INTO `roles` (`id`, `name`, `description`, `is_admin`) VALUES
(1, 'Super Admin', 'Administrador global con acceso total', 1),
(2, 'Admin Departamento', 'Administrador limitado a su departamento', 0),
(3, 'Usuario', 'Usuario normal con permisos limitados', 0),
(4, 'Solo Lectura', 'Usuario con acceso solo de lectura', 0)
ON DUPLICATE KEY UPDATE 
  `description` = VALUES(`description`),
  `is_admin` = VALUES(`is_admin`);

-- 2. Tabla de módulos/recursos del sistema
CREATE TABLE IF NOT EXISTS `system_modules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'Identificador único del módulo',
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'fas fa-cube',
  `order` INT(11) DEFAULT 0,
  `active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar módulos del sistema
INSERT INTO `system_modules` (`code`, `name`, `description`, `icon`, `order`) VALUES
('equipments', 'Equipos', 'Gestión de equipos', 'fas fa-laptop', 10),
('tools', 'Herramientas', 'Gestión de herramientas', 'fas fa-tools', 20),
('accessories', 'Accesorios', 'Gestión de accesorios', 'fas fa-plug', 30),
('calendar', 'Calendario', 'Calendario de mantenimientos', 'fas fa-calendar-alt', 40),
('maintenance', 'Mantenimientos', 'Historial de mantenimientos', 'fas fa-wrench', 50),
('reports', 'Reportes', 'Reportes y estadísticas', 'fas fa-chart-bar', 60),
('users', 'Usuarios', 'Gestión de usuarios', 'fas fa-users', 70),
('departments', 'Departamentos', 'Gestión de departamentos', 'fas fa-building', 80),
('settings', 'Configuración', 'Configuración del sistema', 'fas fa-cog', 90)
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `icon` = VALUES(`icon`),
  `order` = VALUES(`order`);

-- 3. Tabla de permisos (qué puede hacer cada rol en cada módulo)
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_id` INT(11) NOT NULL,
  `module_code` VARCHAR(50) NOT NULL,
  `can_view` TINYINT(1) DEFAULT 0,
  `can_create` TINYINT(1) DEFAULT 0,
  `can_edit` TINYINT(1) DEFAULT 0,
  `can_delete` TINYINT(1) DEFAULT 0,
  `can_export` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_module` (`role_id`, `module_code`),
  KEY `role_id` (`role_id`),
  KEY `module_code` (`module_code`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_module` FOREIGN KEY (`module_code`) REFERENCES `system_modules` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permisos para Super Admin (acceso total)
INSERT INTO `role_permissions` (`role_id`, `module_code`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_export`) 
SELECT 1, code, 1, 1, 1, 1, 1 FROM system_modules
ON DUPLICATE KEY UPDATE 
  can_view=1, can_create=1, can_edit=1, can_delete=1, can_export=1;

-- Permisos para Admin Departamento (acceso completo pero limitado a su departamento)
INSERT INTO `role_permissions` (`role_id`, `module_code`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_export`) VALUES
(2, 'equipments', 1, 1, 1, 1, 1),
(2, 'tools', 1, 1, 1, 1, 1),
(2, 'accessories', 1, 1, 1, 1, 1),
(2, 'calendar', 1, 1, 1, 1, 1),
(2, 'maintenance', 1, 1, 1, 0, 1),
(2, 'reports', 1, 0, 0, 0, 1),
(2, 'users', 1, 0, 0, 0, 0),
(2, 'departments', 1, 0, 0, 0, 0),
(2, 'settings', 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
  can_view=VALUES(can_view), 
  can_create=VALUES(can_create), 
  can_edit=VALUES(can_edit), 
  can_delete=VALUES(can_delete), 
  can_export=VALUES(can_export);

-- Permisos para Usuario (acceso limitado)
INSERT INTO `role_permissions` (`role_id`, `module_code`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_export`) VALUES
(3, 'equipments', 1, 0, 0, 0, 0),
(3, 'tools', 1, 0, 0, 0, 0),
(3, 'accessories', 1, 0, 0, 0, 0),
(3, 'calendar', 1, 1, 0, 0, 0),
(3, 'maintenance', 1, 0, 0, 0, 0),
(3, 'reports', 1, 0, 0, 0, 1),
(3, 'users', 0, 0, 0, 0, 0),
(3, 'departments', 0, 0, 0, 0, 0),
(3, 'settings', 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
  can_view=VALUES(can_view), 
  can_create=VALUES(can_create), 
  can_edit=VALUES(can_edit), 
  can_delete=VALUES(can_delete), 
  can_export=VALUES(can_export);

-- Permisos para Solo Lectura
INSERT INTO `role_permissions` (`role_id`, `module_code`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_export`) VALUES
(4, 'equipments', 1, 0, 0, 0, 0),
(4, 'tools', 1, 0, 0, 0, 0),
(4, 'accessories', 1, 0, 0, 0, 0),
(4, 'calendar', 1, 0, 0, 0, 0),
(4, 'maintenance', 1, 0, 0, 0, 0),
(4, 'reports', 1, 0, 0, 0, 0),
(4, 'users', 0, 0, 0, 0, 0),
(4, 'departments', 0, 0, 0, 0, 0),
(4, 'settings', 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
  can_view=VALUES(can_view), 
  can_create=VALUES(can_create), 
  can_edit=VALUES(can_edit), 
  can_delete=VALUES(can_delete), 
  can_export=VALUES(can_export);

-- 4. Actualizar tabla users (agregar campos necesarios)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `role_id` INT(11) DEFAULT 3 COMMENT 'FK a roles' AFTER `role`,
ADD COLUMN IF NOT EXISTS `department_id` INT(11) DEFAULT NULL COMMENT 'Departamento asignado' AFTER `role_id`,
ADD COLUMN IF NOT EXISTS `can_view_all_departments` TINYINT(1) DEFAULT 0 COMMENT 'Si puede ver todos los deptos' AFTER `department_id`,
ADD KEY `role_id` (`role_id`),
ADD KEY `department_id` (`department_id`);

-- Migrar role antiguo a role_id
UPDATE `users` SET `role_id` = `role` WHERE `role_id` IS NULL OR `role_id` = 0;

-- 5. Vista para consultas simplificadas
CREATE OR REPLACE VIEW `vw_user_permissions` AS
SELECT 
  u.id AS user_id,
  u.username,
  u.firstname,
  u.lastname,
  u.department_id,
  u.can_view_all_departments,
  r.id AS role_id,
  r.name AS role_name,
  r.is_admin,
  rp.module_code,
  rp.can_view,
  rp.can_create,
  rp.can_edit,
  rp.can_delete,
  rp.can_export
FROM users u
INNER JOIN roles r ON u.role_id = r.id
LEFT JOIN role_permissions rp ON r.id = rp.role_id;

-- =====================================================
-- CONSULTAS ÚTILES
-- =====================================================

-- Ver permisos de un usuario específico
-- SELECT * FROM vw_user_permissions WHERE user_id = 1;

-- Ver todos los permisos de un rol
-- SELECT * FROM role_permissions WHERE role_id = 2;

-- Verificar si un usuario puede editar equipos
-- SELECT can_edit FROM vw_user_permissions WHERE user_id = 1 AND module_code = 'equipments';
