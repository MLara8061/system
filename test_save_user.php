<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular una sesión de admin
if (empty($_SESSION['login_id'])) {
    $_SESSION['login_id'] = 2;  // Usar usuario existente (Arla)
    $_SESSION['login_type'] = 1;
}

require_once 'admin_class.php';
$crud = new Action();

// Simular POST data
$_POST = [
    'id' => 0,
    'firstname' => 'Juan',
    'middlename' => '',
    'lastname' => 'Pérez',
    'username' => 'jperez_test_' . time(),
    'password' => 'TestPassword123',
    'role' => 1
];

echo "=== TEST SAVE USER ===\n";
echo "POST data: " . json_encode($_POST) . "\n";
echo "SESSION: " . json_encode($_SESSION) . "\n\n";

$result = $crud->save_user();
echo "Result: " . $result . "\n";

if ($result == 1) {
    echo "\n✓ Usuario guardado exitosamente\n";
} else {
    echo "\n✗ Error al guardar usuario\n";
}
?>
