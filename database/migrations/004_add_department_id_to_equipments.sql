-- Migración: Agregar columna department_id a equipments para compatibilidad futura
-- Esta columna es opcional ya que equipment_delivery.department_id es la fuente de verdad

ALTER TABLE IF EXISTS `equipments` 
ADD COLUMN IF NOT EXISTS `department_id` INT NULL 
COMMENT 'Referencia a departamento (uso futuro - la fuente de verdad es equipment_delivery.department_id)' 
AFTER `branch_id`;

-- Crear índice para consultas rápidas
ALTER TABLE IF EXISTS `equipments` 
ADD INDEX IF NOT EXISTS `idx_equipment_department_id` (`department_id`);

-- Agregar constraint si la columna se usa
ALTER TABLE IF EXISTS `equipments` 
ADD CONSTRAINT IF NOT EXISTS `equipment_department_fk` 
FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) 
ON UPDATE CASCADE 
ON DELETE SET NULL;
