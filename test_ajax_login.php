<?php
// Configurar para que los errores se escriban en un archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Iniciar sesión
session_start();

// Incluir la clase
require_once 'admin_class.php';

// Crear instancia
$crud = new Action();

// Simular la petición de AJAX
$_REQUEST['action'] = 'login';
$_POST['username'] = 'eduardo';
$_POST['password'] = '123456';
$_POST['type'] = 1;

echo "=== SIMULAR AJAX LOGIN ===\n";
echo "REQUEST action: " . $_REQUEST['action'] . "\n";
echo "POST: " . json_encode($_POST) . "\n\n";

// Llamar al login
$result = $crud->login();
echo "Result: $result\n";

// Mostrar sesión
echo "SESSION: " . json_encode($_SESSION) . "\n\n";

// Leer el log
echo "=== PHP ERROR LOG ===\n";
if (file_exists(__DIR__ . '/php_error.log')) {
    echo file_get_contents(__DIR__ . '/php_error.log');
}
?>
