-- ===================================
-- MIGRACIÓN: Relacionar Departamentos, Ubicaciones y Puestos
-- ===================================
-- Fecha: 2025-11-29
-- Descripción: Agregar relaciones entre departments, locations y job_positions

-- PASO 1: Agregar department_id a locations
-- Una ubicación pertenece a un departamento
ALTER TABLE locations 
ADD COLUMN department_id INT NULL AFTER name,
ADD CONSTRAINT fk_locations_department 
FOREIGN KEY (department_id) REFERENCES departments(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- PASO 2: Agregar location_id a job_positions (si no existe)
-- Un puesto está en una ubicación específica
ALTER TABLE job_positions 
ADD COLUMN location_id INT NULL AFTER name,
ADD CONSTRAINT fk_job_positions_location 
FOREIGN KEY (location_id) REFERENCES locations(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- PASO 3: Agregar department_id a job_positions (redundante pero útil para filtrado)
-- Un puesto pertenece a un departamento
ALTER TABLE job_positions 
ADD COLUMN department_id INT NULL AFTER location_id,
ADD CONSTRAINT fk_job_positions_department 
FOREIGN KEY (department_id) REFERENCES departments(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- PASO 4: Migrar datos existentes de location_positions a job_positions.location_id
UPDATE job_positions jp
INNER JOIN location_positions lp ON lp.job_position_id = jp.id
SET jp.location_id = lp.location_id
WHERE jp.location_id IS NULL;

-- PASO 5: Actualizar department_id en job_positions basado en location
UPDATE job_positions jp
INNER JOIN locations l ON l.id = jp.location_id
SET jp.department_id = l.department_id
WHERE jp.department_id IS NULL AND l.department_id IS NOT NULL;

-- NOTA: Después de confirmar que todo funciona correctamente,
-- puedes eliminar la tabla location_positions si ya no se usa:
-- DROP TABLE location_positions;

-- ===================================
-- VERIFICACIÓN
-- ===================================
-- Ver estructura actualizada
DESCRIBE departments;
DESCRIBE locations;
DESCRIBE job_positions;

-- Ver relaciones
SELECT 
    d.name as Departamento,
    l.name as Ubicacion,
    jp.name as Puesto
FROM departments d
LEFT JOIN locations l ON l.department_id = d.id
LEFT JOIN job_positions jp ON jp.location_id = l.id
ORDER BY d.name, l.name, jp.name;
