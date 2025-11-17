<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular POST data para login
$_POST = [
    'username' => 'eduardo',
    'password' => '123456',
    'type' => 1
];

echo "=== TEST DIRECT LOGIN ===\n";
echo "POST: " . json_encode($_POST) . "\n\n";

require_once 'admin_class.php';
$crud = new Action();
$result = $crud->login();

echo "Result: $result\n";

if ($result == 1) {
    echo "✓ LOGIN SUCCESS\n";
    echo "SESSION login_id: " . ($_SESSION['login_id'] ?? 'NOT SET') . "\n";
    echo "SESSION login_type: " . ($_SESSION['login_type'] ?? 'NOT SET') . "\n";
} elseif ($result == 2) {
    echo "✗ LOGIN FAILED - Credenciales incorrectas\n";
} else {
    echo "✗ LOGIN ERROR - Code: $result\n";
}

echo "\nSESSION completa:\n";
echo json_encode($_SESSION, JSON_PRETTY_PRINT) . "\n";
?>
