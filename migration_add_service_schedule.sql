-- Agregar columnas de horario de servicio a la tabla maintenance_reports
-- Ejecutar en phpMyAdmin o línea de comandos

-- Verificar si las columnas NO existen antes de agregarlas
SET @dbname = DATABASE();
SET @tablename = 'maintenance_reports';

-- service_date
SET @columnexists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'service_date'
);

SET @sqlstmt = IF(@columnexists = 0,
    'ALTER TABLE maintenance_reports ADD COLUMN service_date DATE NULL DEFAULT NULL AFTER execution_type',
    'SELECT ''Column service_date already exists'' AS msg'
);

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- service_start_time
SET @columnexists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'service_start_time'
);

SET @sqlstmt = IF(@columnexists = 0,
    'ALTER TABLE maintenance_reports ADD COLUMN service_start_time TIME NULL DEFAULT NULL AFTER service_date',
    'SELECT ''Column service_start_time already exists'' AS msg'
);

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- service_end_time
SET @columnexists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'service_end_time'
);

SET @sqlstmt = IF(@columnexists = 0,
    'ALTER TABLE maintenance_reports ADD COLUMN service_end_time TIME NULL DEFAULT NULL AFTER service_start_time',
    'SELECT ''Column service_end_time already exists'' AS msg'
);

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar que se agregaron correctamente
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'maintenance_reports' 
AND COLUMN_NAME IN ('service_date', 'service_start_time', 'service_end_time');
