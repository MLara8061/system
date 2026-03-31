-- =====================================================
-- Migración 012: Índice UNIQUE en equipments.serie
-- Garantiza que no haya números de serie duplicados
-- =====================================================

-- PASO 1: Revisar duplicados actuales (ejecutar como consulta informativa)
-- SELECT serie, COUNT(*) c, GROUP_CONCAT(id) ids FROM equipments WHERE serie IS NOT NULL AND serie != '' GROUP BY serie HAVING c > 1;

-- PASO 2: Renombrar duplicados agregando sufijo -DUPn (conserva el registro mas antiguo intacto)
UPDATE equipments e
INNER JOIN (
    SELECT id, serie,
           ROW_NUMBER() OVER (PARTITION BY serie ORDER BY id ASC) AS rn
    FROM equipments
    WHERE serie IS NOT NULL AND serie != ''
) ranked ON e.id = ranked.id
SET e.serie = CONCAT(e.serie, '-DUP', ranked.rn - 1)
WHERE ranked.rn > 1;

-- PASO 3: Ahora sí crear el índice único
ALTER TABLE `equipments` ADD UNIQUE INDEX `idx_serie_unique` (`serie`);
