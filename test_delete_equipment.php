<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular una sesión de admin
$_SESSION['login_id'] = 3;
$_SESSION['login_type'] = 1;

// Simular POST data para eliminar un equipo
// Primero voy a obtener un ID de equipo
$conn = new mysqli('localhost', 'root', '', 'system');
$result = $conn->query("SELECT id FROM equipments LIMIT 1");
$equipment = $result->fetch_assoc();

if (!$equipment) {
    echo "No hay equipos en la base de datos\n";
    exit;
}

$_POST = [
    'id' => $equipment['id']
];

echo "=== TEST DELETE EQUIPMENT ===\n";
echo "Equipment ID: " . $_POST['id'] . "\n";
echo "SESSION: " . json_encode($_SESSION) . "\n\n";

require_once 'admin_class.php';
$crud = new Action();
$result = $crud->delete_equipment();

echo "Result: $result\n";

if ($result == 1) {
    echo "✓ Equipment deleted successfully\n";
} elseif ($result == 2) {
    echo "✗ ERROR - Deletion failed\n";
} else {
    echo "✗ ERROR - Code: $result\n";
}

// Verificar que el equipo fue eliminado
$check = $conn->query("SELECT id FROM equipments WHERE id = " . $_POST['id']);
if ($check->num_rows == 0) {
    echo "✓ Verificado: El equipo fue eliminado de la BD\n";
} else {
    echo "✗ Error: El equipo aún está en la BD\n";
}
?>
