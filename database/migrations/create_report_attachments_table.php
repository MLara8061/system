<?php
/**
 * Migration: Crear tabla report_attachments
 * Ejecutar: php -r "require 'database/migrations/create_report_attachments_table.php';"
 */

define('ROOT', dirname(dirname(dirname(__FILE__))));
require_once ROOT . '/config/db.php';

try {
    $pdo = get_pdo();
    
    $sql = "CREATE TABLE IF NOT EXISTS report_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id INT DEFAULT 0 COMMENT 'ID del reporte o 0 si es temporal',
        file_name VARCHAR(255) NOT NULL COMMENT 'Nombre del archivo',
        file_path VARCHAR(500) NOT NULL COMMENT 'Ruta relativa al archivo',
        sort_order INT DEFAULT 0 COMMENT 'Orden de visualización',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_report_id (report_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "✅ Tabla 'report_attachments' creada correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error al crear tabla: " . $e->getMessage() . "\n";
    exit(1);
}
?>
