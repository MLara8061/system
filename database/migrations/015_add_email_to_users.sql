-- Migracion 015: Agregar columna email a la tabla users
-- Requerido para notificaciones por correo a tecnicos/usuarios del sistema

ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `username`;
