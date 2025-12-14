<?php
define('ACCESS', true);
require_once __DIR__ . '/../config/config.php';

echo "<h2>Ejecutando migración: Crear tabla branches y agregar branch_id</h2>";

$errors = [];
$success = [];

function column_exists($conn, $table, $column) {
    $sql = "SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($conn->query("SELECT DATABASE()')->fetch_row()[0]) . "' AND TABLE_NAME = '" . $conn->real_escape_string($table) . "' AND COLUMN_NAME = '" . $conn->real_escape_string($column) . "'";
}

// 1. Crear tabla branches
$sql = "CREATE TABLE IF NOT EXISTS `branches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` CHAR(6) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_branch_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($sql)) {
    $success[] = "Tabla `branches` creada o ya existente.";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    $errors[] = "Error creando tabla branches: " . $conn->error;
    echo "<p style='color:red'>{$errors[count($errors)-1]}</p>";
}

// Helper para agregar columna si no existe
function add_column_if_not_exists($conn, $table, $columnDef) {
    $parts = preg_split('/\s+/', trim($columnDef), 2);
    $colName = trim($parts[0], '`');
    $check = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$colName}'");
    if ($check && $check->num_rows > 0) {
        return "exists";
    }
    if ($conn->query("ALTER TABLE `{$table}` ADD COLUMN {$columnDef}")) return true;
    return $conn->error;
}

$tables = [
    'equipments' => '`branch_id` INT UNSIGNED NULL',
    'accessories' => '`branch_id` INT UNSIGNED NULL',
    'inventory' => '`branch_id` INT UNSIGNED NULL',
    'mantenimientos' => '`branch_id` INT UNSIGNED NULL',
];

foreach ($tables as $table => $colDef) {
    echo "<h4>Procesando tabla: {$table}</h4>";
    $res = add_column_if_not_exists($conn, $table, $colDef);
    if ($res === true) {
        echo "<p style='color:green'>Columna añadida: {$table}.branch_id</p>";
        $success[] = "Columna branch_id añadida a {$table}";
    } elseif ($res === 'exists') {
        echo "<p style='color:green'>Columna ya existe: {$table}.branch_id</p>";
    } else {
        echo "<p style='color:orange'>Warning al añadir columna en {$table}: {$res}</p>";
        $errors[] = "Error en {$table}: {$res}";
    }
    // intentar agregar FK
    $fkName = "fk_{$table}_branch";
    $fkSql = "ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL";
    if ($conn->query($fkSql)) {
        echo "<p style='color:green'>FK agregada: {$fkName}</p>";
    } else {
        if (strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'exists') !== false) {
            echo "<p style='color:green'>FK ya existe: {$fkName}</p>";
        } else {
            echo "<p style='color:orange'>Warning FK {$fkName}: {$conn->error}</p>";
        }
    }
}

// Insertar sucursal por defecto HAC
$sql = "INSERT INTO `branches` (`code`,`name`,`description`) SELECT 'HAC','Sede Principal','Sucursal principal' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `branches` WHERE `code` = 'HAC')";
if ($conn->query($sql)) {
    echo "<p style='color:green'>Sucursal por defecto insertada o ya existente.</p>";
}

echo "<hr><h3>Resumen</h3>";
if (!empty($success)) {
    echo "<ul>";
    foreach ($success as $s) echo "<li style='color:green'>".htmlspecialchars($s)."</li>";
    echo "</ul>";
}
if (!empty($errors)) {
    echo "<ul>";
    foreach ($errors as $e) echo "<li style='color:red'>".htmlspecialchars($e)."</li>";
    echo "</ul>";
}

echo "<p><a href='/index.php' class='btn'>Volver</a></p>";

?>
