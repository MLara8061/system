-- Migration: Add clave field to services_category
-- Date: 2025-12-13
-- Description: Add unique immutable clave field to services_category table

USE system_db;

-- Add clave column as unique varchar
ALTER TABLE services_category
ADD COLUMN clave VARCHAR(50) UNIQUE NOT NULL AFTER category;

-- Add index for performance
CREATE INDEX idx_services_category_clave ON services_category(clave);