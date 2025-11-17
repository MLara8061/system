-- ============================================
-- SCRIPT DE MIGRACIÓN INCREMENTAL
-- Fecha: 16 de noviembre de 2025
-- Descripción: Actualizar BD de producción con cambios locales
-- ============================================

-- INSTRUCCIONES:
-- 1. Haz backup de la BD de producción antes de ejecutar
-- 2. Sube este archivo al cPanel
-- 3. Importa desde phpMyAdmin de producción

-- ============================================
-- AGREGAR NUEVAS TABLAS (si existen)
-- ============================================

-- Ejemplo (ajusta según tus tablas):
-- CREATE TABLE IF NOT EXISTS `nueva_tabla` (
--   `id` int(11) NOT NULL AUTO_INCREMENT,
--   `nombre` varchar(255) DEFAULT NULL,
--   PRIMARY KEY (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- AGREGAR NUEVAS COLUMNAS (si existen)
-- ============================================

-- Ejemplo (ajusta según tus cambios):
-- ALTER TABLE `equipments` 
-- ADD COLUMN IF NOT EXISTS `nueva_columna` VARCHAR(100) NULL AFTER `columna_existente`;


-- ============================================
-- ACTUALIZAR DATOS (si necesitas)
-- ============================================

-- Ejemplo:
-- INSERT IGNORE INTO `categories` (`name`) VALUES ('Nueva Categoría');


-- ============================================
-- NOTA: Elimina estos comentarios y agrega tus 
-- cambios reales. Revisa tu BD local para ver 
-- qué tablas o columnas agregaste.
-- ============================================
