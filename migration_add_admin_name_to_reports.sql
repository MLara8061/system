-- Agregar campo para nombre del usuario administrativo en reportes de mantenimiento
-- Este campo guardará el nombre completo del usuario logueado que genera el reporte

ALTER TABLE maintenance_reports 
ADD COLUMN admin_name VARCHAR(255) NULL AFTER recibe_nombre;

-- Comentario de la columna
ALTER TABLE maintenance_reports 
MODIFY COLUMN admin_name VARCHAR(255) NULL COMMENT 'Nombre completo del usuario administrativo que generó el reporte';
