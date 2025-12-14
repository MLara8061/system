-- Migration: Add clave field to services_category
-- Date: 2025-12-13
-- Description: Add unique immutable clave field to services_category table

USE system_db;

-- First, assign unique claves to existing rows
SET @row_number = 0;
UPDATE services_category
SET clave = CONCAT('CAT_', LPAD((@row_number:=@row_number + 1), 3, '0'))
WHERE clave IS NULL OR clave = '';

-- Now add the column with unique constraint
ALTER TABLE services_category
ADD COLUMN clave VARCHAR(50) UNIQUE NOT NULL AFTER category;

-- Add index for performance
CREATE INDEX idx_services_category_clave ON services_category(clave);