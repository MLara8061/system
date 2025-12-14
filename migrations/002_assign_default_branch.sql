-- 002_assign_default_branch.sql
-- Asigna la sucursal por defecto (HAC) a registros existentes y agrega active_branch_id a users

-- Obtener id de HAC
SET @branch_id = (SELECT id FROM branches WHERE code = 'HAC' LIMIT 1);

-- Actualizar tablas que ahora tienen branch_id
UPDATE equipments SET branch_id = @branch_id WHERE branch_id IS NULL;
UPDATE accessories SET branch_id = @branch_id WHERE branch_id IS NULL;
UPDATE inventory SET branch_id = @branch_id WHERE branch_id IS NULL;
UPDATE mantenimientos SET branch_id = @branch_id WHERE branch_id IS NULL;

-- Agregar columna active_branch_id a users si no existe
ALTER TABLE users ADD COLUMN IF NOT EXISTS active_branch_id INT UNSIGNED NULL;

-- Establecer active_branch_id por defecto para los usuarios (si está vacío)
UPDATE users SET active_branch_id = @branch_id WHERE active_branch_id IS NULL;
