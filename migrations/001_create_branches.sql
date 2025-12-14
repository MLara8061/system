-- 001_create_branches.sql
-- Crea tabla branches y agrega branch_id a tablas principales

CREATE TABLE IF NOT EXISTS `branches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` CHAR(6) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_branch_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar columna branch_id a tablas que manejarán sucursal
ALTER TABLE `equipments` ADD COLUMN IF NOT EXISTS `branch_id` INT UNSIGNED NULL;
ALTER TABLE `accessories` ADD COLUMN IF NOT EXISTS `branch_id` INT UNSIGNED NULL;
ALTER TABLE `inventory` ADD COLUMN IF NOT EXISTS `branch_id` INT UNSIGNED NULL;
ALTER TABLE `mantenimientos` ADD COLUMN IF NOT EXISTS `branch_id` INT UNSIGNED NULL;

-- Agregar FK (si la columna ya existe y la FK no)
ALTER TABLE `equipments` ADD CONSTRAINT IF NOT EXISTS `fk_equipments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL;
ALTER TABLE `accessories` ADD CONSTRAINT IF NOT EXISTS `fk_accessories_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL;
ALTER TABLE `inventory` ADD CONSTRAINT IF NOT EXISTS `fk_inventory_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL;
ALTER TABLE `mantenimientos` ADD CONSTRAINT IF NOT EXISTS `fk_mantenimientos_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL;

-- Insertar sucursal por defecto si no existe
INSERT INTO `branches` (`code`, `name`, `description`)
SELECT 'HAC', 'Sede Principal', 'Sucursal principal' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `code` = 'HAC');
