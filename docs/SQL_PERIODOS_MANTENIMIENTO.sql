-- Script SQL para gestionar periodos de mantenimiento
-- Fecha: 2025-12-22

-- =====================================================
-- CONSULTAR PERIODOS EXISTENTES
-- =====================================================
SELECT 
    id,
    name AS 'Nombre',
    days_interval AS 'Días',
    CONCAT(
        FLOOR(days_interval / 365), ' años ',
        FLOOR((days_interval % 365) / 30), ' meses ',
        (days_interval % 30), ' días'
    ) AS 'Equivalente'
FROM maintenance_periods 
ORDER BY days_interval ASC;


-- =====================================================
-- AGREGAR NUEVOS PERIODOS (ejemplos)
-- =====================================================

-- Periodo cada 2 días
-- INSERT INTO maintenance_periods (name, days_interval) VALUES ('Cada 2 días', 2);

-- Periodo cada 3 meses
-- INSERT INTO maintenance_periods (name, days_interval) VALUES ('Cada 3 meses', 90);

-- Periodo cada 5 años
-- INSERT INTO maintenance_periods (name, days_interval) VALUES ('Quinquenal', 1825);


-- =====================================================
-- VERIFICAR EQUIPOS CON PERIODOS ASIGNADOS
-- =====================================================
SELECT 
    mp.name AS 'Periodo',
    COUNT(e.id) AS 'Cantidad Equipos',
    GROUP_CONCAT(e.number_inventory SEPARATOR ', ') AS 'Inventarios'
FROM maintenance_periods mp
LEFT JOIN equipments e ON e.mandate_period_id = mp.id
GROUP BY mp.id, mp.name
ORDER BY mp.days_interval ASC;


-- =====================================================
-- DETECTAR EQUIPOS CON PERIODOS INVÁLIDOS
-- =====================================================
SELECT 
    e.id,
    e.number_inventory AS 'Inventario',
    e.name AS 'Nombre Equipo',
    e.mandate_period_id AS 'ID Periodo (Inválido)'
FROM equipments e
LEFT JOIN maintenance_periods mp ON e.mandate_period_id = mp.id
WHERE e.mandate_period_id IS NOT NULL 
  AND mp.id IS NULL;


-- =====================================================
-- CORREGIR EQUIPOS CON PERIODOS INVÁLIDOS
-- =====================================================

-- OPCIÓN 1: Asignar periodo Mensual (ID 9) por defecto
-- UPDATE equipments 
-- SET mandate_period_id = 9 
-- WHERE mandate_period_id NOT IN (SELECT id FROM maintenance_periods);

-- OPCIÓN 2: Establecer como NULL (sin periodo automático)
-- UPDATE equipments 
-- SET mandate_period_id = NULL 
-- WHERE mandate_period_id NOT IN (SELECT id FROM maintenance_periods);


-- =====================================================
-- VERIFICAR MANTENIMIENTOS AUTOMÁTICOS GENERADOS
-- =====================================================
SELECT 
    e.number_inventory AS 'Inventario',
    e.name AS 'Equipo',
    mp.name AS 'Periodo',
    COUNT(m.id) AS 'Total Mantenimientos',
    MIN(m.fecha_programada) AS 'Primer Mantenimiento',
    MAX(m.fecha_programada) AS 'Último Mantenimiento'
FROM equipments e
INNER JOIN maintenance_periods mp ON e.mandate_period_id = mp.id
LEFT JOIN mantenimientos m ON m.equipo_id = e.id 
    AND m.descripcion = 'Mantenimiento automático'
GROUP BY e.id, e.number_inventory, e.name, mp.name
ORDER BY e.number_inventory ASC;


-- =====================================================
-- LIMPIAR MANTENIMIENTOS AUTOMÁTICOS DE UN EQUIPO
-- (útil antes de regenerar)
-- =====================================================
-- DELETE FROM mantenimientos 
-- WHERE equipo_id = <ID_EQUIPO> 
--   AND descripcion = 'Mantenimiento automático';


-- =====================================================
-- ESTADÍSTICAS GENERALES
-- =====================================================
SELECT 
    'Total Periodos Disponibles' AS 'Métrica',
    COUNT(*) AS 'Valor'
FROM maintenance_periods

UNION ALL

SELECT 
    'Equipos con Periodo Asignado',
    COUNT(*)
FROM equipments
WHERE mandate_period_id IS NOT NULL

UNION ALL

SELECT 
    'Mantenimientos Automáticos Futuros',
    COUNT(*)
FROM mantenimientos
WHERE descripcion = 'Mantenimiento automático'
  AND fecha_programada >= CURDATE()
  AND estatus != 'cancelado';
