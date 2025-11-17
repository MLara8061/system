<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log en archivo
$log_file = __DIR__ . '/user_creation_debug.log';

file_put_contents($log_file, "\n=== TEST " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "SESSION: " . json_encode($_SESSION) . "\n", FILE_APPEND);

// Verificar si hay una sesión activa
if (empty($_SESSION['login_id'])) {
    file_put_contents($log_file, "ERROR: No hay sesión activa. login_id no está en SESSION\n", FILE_APPEND);
    echo "ERROR: No hay sesión activa\n";
} else {
    file_put_contents($log_file, "SESSION ACTIVA - login_id: {$_SESSION['login_id']}, login_type: {$_SESSION['login_type']}\n", FILE_APPEND);
    
    // Simular POST data
    $_POST = [
        'id' => 0,
        'firstname' => 'TestCreate',
        'middlename' => '',
        'lastname' => 'User',
        'username' => 'testcreate_' . time(),
        'password' => 'TestPass123',
        'role' => 1
    ];
    
    file_put_contents($log_file, "POST: " . json_encode($_POST) . "\n", FILE_APPEND);
    
    require_once 'admin_class.php';
    $crud = new Action();
    $result = $crud->save_user();
    
    file_put_contents($log_file, "Result: $result\n", FILE_APPEND);
    echo "Result: $result\n";
}

// Mostrar contenido del log
echo "\n--- LOG CONTENT ---\n";
echo file_get_contents($log_file);
?>
