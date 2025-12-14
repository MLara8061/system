<?php
if (!defined('ACCESS')) define('ACCESS', true);
require_once __DIR__ . '/../../config/config.php';

echo "<h2>Configurando sistema de prefijos de inventario</h2>";

$errors = [];
$success = [];

// Crear tabla inventory_config
$sql1 = "CREATE TABLE IF NOT EXISTS inventory_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    prefix VARCHAR(10) NOT NULL,
    current_number INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
)";
if ($conn->query($sql1)) {
    echo "<p style='color:green'>Tabla inventory_config creada o ya existente.</p>";
} else {
    echo "<p style='color:red'>Error creando inventory_config: " . $conn->error . "</p>";
    $errors[] = $conn->error;
}

// Insertar prefijo para HAC
$sql2 = "INSERT IGNORE INTO inventory_config (branch_id, prefix) VALUES (1, 'HAC')";
if ($conn->query($sql2)) {
    echo "<p style='color:green'>Prefijo HAC insertado o ya existente.</p>";
} else {
    echo "<p style='color:red'>Error insertando prefijo HAC: " . $conn->error . "</p>";
    $errors[] = $conn->error;
}

// Añadir inventario_anterior a equipments
$sql3 = "ALTER TABLE equipments ADD COLUMN IF NOT EXISTS inventario_anterior VARCHAR(255)";
if ($conn->query($sql3)) {
    echo "<p style='color:green'>Campo inventario_anterior añadido a equipments.</p>";
} else {
    echo "<p style='color:red'>Error añadiendo inventario_anterior: " . $conn->error . "</p>";
    $errors[] = $conn->error;
}

// Añadir numero_parte a accessories
$sql4 = "ALTER TABLE accessories ADD COLUMN IF NOT EXISTS numero_parte VARCHAR(255)";
if ($conn->query($sql4)) {
    echo "<p style='color:green'>Campo numero_parte añadido a accessories.</p>";
} else {
    echo "<p style='color:red'>Error añadiendo numero_parte: " . $conn->error . "</p>";
    $errors[] = $conn->error;
}

if (empty($errors)) {
    echo "<p style='color:green'>Configuración completada exitosamente.</p>";
} else {
    echo "<p style='color:red'>Hubo errores: " . implode(', ', $errors) . "</p>";
}

echo "<hr><p><a href='/index.php'>Volver</a></p>";

?>