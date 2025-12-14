<?php
if (!defined('ACCESS')) define('ACCESS', true);
require_once __DIR__ . '/../../config/config.php';

echo "<h2>Ejecutando asignación: asignar sucursal por defecto y agregar active_branch_id a users</h2>";

$errors = [];
$success = [];

// Obtener id de HAC
$res = $conn->query("SELECT id FROM branches WHERE code = 'HAC' LIMIT 1");
if (!$res || $res->num_rows == 0) {
    echo "<p style='color:red'>No se encontró la sucursal HAC. Crea la sucursal primero.</p>";
    exit;
}
$branch_id = $res->fetch_assoc()['id'];

echo "<p>Branch HAC id = <strong>{$branch_id}</strong></p>";

$queries = [
    "UPDATE equipments SET branch_id = {$branch_id} WHERE branch_id IS NULL",
    "UPDATE accessories SET branch_id = {$branch_id} WHERE branch_id IS NULL",
    "UPDATE inventory SET branch_id = {$branch_id} WHERE branch_id IS NULL",
    "UPDATE mantenimientos SET branch_id = {$branch_id} WHERE branch_id IS NULL",
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "<p style='color:green'>Ejecutado: " . htmlspecialchars($q) . " (afectados: {$conn->affected_rows})</p>";
    } else {
        echo "<p style='color:orange'>Error en: " . htmlspecialchars($q) . " - " . htmlspecialchars($conn->error) . "</p>";
        $errors[] = $conn->error;
    }
}

// Agregar columna active_branch_id a users si no existe
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'active_branch_id'");
if ($check && $check->num_rows > 0) {
    echo "<p style='color:green'>Columna users.active_branch_id ya existe.</p>";
} else {
    $sql = "ALTER TABLE users ADD COLUMN active_branch_id INT UNSIGNED NULL";
    if ($conn->query($sql)) echo "<p style='color:green'>Columna users.active_branch_id agregada.</p>";
    else echo "<p style='color:orange'>Error agregando columna users.active_branch_id: " . htmlspecialchars($conn->error) . "</p>";
}

// Establecer active_branch_id para usuarios sin valor
$sql = "UPDATE users SET active_branch_id = {$branch_id} WHERE active_branch_id IS NULL";
if ($conn->query($sql)) {
    echo "<p style='color:green'>Usuarios actualizados: {$conn->affected_rows}</p>";
} else {
    echo "<p style='color:orange'>Error actualizando users: " . htmlspecialchars($conn->error) . "</p>";
}

echo "<hr><p><a href='/index.php'>Volver</a></p>";

?>
