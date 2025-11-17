<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular POST data para crear usuario
$_POST = [
    'id' => 0,
    'firstname' => 'TestUser',
    'middlename' => '',
    'lastname' => 'Testing',
    'username' => 'testuser_' . time(),
    'password' => 'TestPassword123',
    'role' => 1
];

// Simular sesión de admin
$_SESSION['login_id'] = 2;
$_SESSION['login_type'] = 1;

echo "=== TEST SAVE USER VIA WEB ===\n";
echo "POST: " . json_encode($_POST) . "\n";
echo "SESSION: " . json_encode($_SESSION) . "\n\n";

require_once 'admin_class.php';
$crud = new Action();
$result = $crud->save_user();

echo "Result: $result\n";

if ($result == 1) {
    echo "✓ SUCCESS - Usuario guardado\n";
} elseif ($result == 2) {
    echo "✗ ERROR 2 - Usuario ya existe\n";
} elseif ($result == 3) {
    echo "✗ ERROR 3 - Campos vacíos\n";
} elseif ($result == 4) {
    echo "✗ ERROR 4 - Contraseña requerida\n";
} else {
    echo "✗ ERROR - Code: $result\n";
}
?>
