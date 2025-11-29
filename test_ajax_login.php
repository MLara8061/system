<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test AJAX Login</h2>";

// Simular llamada AJAX
$_POST['username'] = 'Arla';
$_POST['password'] = 'tu_contraseña_aqui'; // CAMBIA ESTO
$_REQUEST['action'] = 'login';

echo "<h3>Simulando llamada a ajax.php?action=login</h3>";
echo "Username: " . $_POST['username'] . "<br>";
echo "Action: " . $_REQUEST['action'] . "<br><br>";

echo "<h3>Paso 1: Verificar sesión</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "✓ Sesión iniciada<br>";
} else {
    echo "✓ Sesión ya activa<br>";
}

echo "<h3>Paso 2: Incluir admin_class.php</h3>";
try {
    ob_start();
    include 'admin_class.php';
    $output = ob_get_clean();
    if ($output) {
        echo "<strong style='color:orange;'>⚠ Output capturado durante include:</strong><br>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    echo "✓ admin_class.php incluido<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    die();
}

echo "<h3>Paso 3: Crear instancia de Action</h3>";
try {
    $crud = new Action();
    echo "✓ Instancia de Action creada<br>";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    die();
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    die();
}

echo "<h3>Paso 4: Llamar método login()</h3>";
try {
    $result = $crud->login();
    echo "✓ Método login() ejecutado<br>";
    echo "<strong>Resultado: $result</strong><br>";
    
    if ($result == 1) {
        echo "<span style='color:green;'>✓ LOGIN EXITOSO</span><br>";
        echo "<h4>Variables de sesión:</h4>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } elseif ($result == 2) {
        echo "<span style='color:red;'>✗ Usuario no encontrado</span><br>";
    } elseif ($result == 3) {
        echo "<span style='color:red;'>✗ Contraseña incorrecta</span><br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>IMPORTANTE:</strong> Cambia la contraseña en la línea 8 de este archivo antes de probar.</p>";
?>
