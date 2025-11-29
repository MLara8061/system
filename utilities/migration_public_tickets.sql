-- Migración para soportar tickets públicos desde QR
-- Agregar campos para tickets reportados sin autenticación

ALTER TABLE tickets 
ADD COLUMN IF NOT EXISTS equipment_id INT(11) NULL COMMENT 'ID del equipo relacionado',
ADD COLUMN IF NOT EXISTS reporter_name VARCHAR(255) NULL COMMENT 'Nombre de quien reporta (público)',
ADD COLUMN IF NOT EXISTS reporter_email VARCHAR(255) NULL COMMENT 'Email de quien reporta (público)',
ADD COLUMN IF NOT EXISTS reporter_phone VARCHAR(50) NULL COMMENT 'Teléfono de quien reporta (público)',
ADD COLUMN IF NOT EXISTS issue_type VARCHAR(100) NULL COMMENT 'Tipo de falla reportada',
ADD COLUMN IF NOT EXISTS ticket_number VARCHAR(50) NULL COMMENT 'Número único de ticket',
ADD COLUMN IF NOT EXISTS is_public TINYINT(1) DEFAULT 0 COMMENT '1=Ticket público (QR), 0=Ticket normal';

-- Crear índice para búsqueda rápida de tickets públicos
CREATE INDEX IF NOT EXISTS idx_is_public ON tickets(is_public);
CREATE INDEX IF NOT EXISTS idx_equipment_id ON tickets(equipment_id);
CREATE INDEX IF NOT EXISTS idx_ticket_number ON tickets(ticket_number);
