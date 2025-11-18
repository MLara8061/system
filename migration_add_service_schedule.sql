-- Agregar columnas de horario de servicio a la tabla maintenance_reports
-- Ejecutar en phpMyAdmin o línea de comandos

ALTER TABLE `maintenance_reports` 
ADD COLUMN `service_date` DATE NULL DEFAULT NULL AFTER `execution_type`,
ADD COLUMN `service_start_time` TIME NULL DEFAULT NULL AFTER `service_date`,
ADD COLUMN `service_end_time` TIME NULL DEFAULT NULL AFTER `service_start_time`;

-- Verificar que se agregaron correctamente
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'maintenance_reports' 
AND COLUMN_NAME IN ('service_date', 'service_start_time', 'service_end_time');
