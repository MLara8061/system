<?php
// Este archivo replica exactamente lo que hace ajax.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNÓSTICO COMPLETO AJAX.PHP ===<br><br>";

echo "<strong>1. Headers recibidos:</strong><br>";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "<br><br>";

echo "<strong>2. Iniciando sesión...</strong><br>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "✓ Sesión iniciada correctamente<br>";
    } else {
        echo "✓ Sesión ya activa<br>";
    }
} catch (Exception $e) {
    echo "✗ ERROR en session_start: " . $e->getMessage() . "<br>";
    die();
}

echo "<br><strong>3. Buffer output...</strong><br>";
ob_start();
echo "✓ ob_start() ejecutado<br>";

echo "<br><strong>4. Incluyendo admin_class.php...</strong><br>";
try {
    include 'admin_class.php';
    echo "✓ admin_class.php incluido sin errores<br>";
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . " Línea: " . $e->getLine() . "<br>";
    die();
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . " Línea: " . $e->getLine() . "<br>";
    die();
}

echo "<br><strong>5. Creando instancia Action...</strong><br>";
try {
    $crud = new Action();
    echo "✓ Action creada correctamente<br>";
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "<br>";
    die();
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    die();
}

echo "<br><strong>6. Simulando POST de login...</strong><br>";
$_POST['username'] = 'Arla';
$_POST['password'] = 'test123'; // Cambia esto
$_REQUEST['action'] = 'login';

echo "Username: " . $_POST['username'] . "<br>";
echo "Action: " . $_REQUEST['action'] . "<br>";

echo "<br><strong>7. Llamando login()...</strong><br>";
try {
    $result = $crud->login();
    echo "✓ login() ejecutado<br>";
    echo "Resultado: <strong>$result</strong><br>";
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

echo "<br><strong>8. Contenido del buffer:</strong><br>";
$buffer_content = ob_get_contents();
echo "Longitud: " . strlen($buffer_content) . " bytes<br>";
if ($buffer_content) {
    echo "<pre style='background:#f0f0f0;padding:10px;'>" . htmlspecialchars($buffer_content) . "</pre>";
}

echo "<br><strong>9. Limpiando buffer...</strong><br>";
ob_end_clean();
echo "✓ Buffer limpiado<br>";

echo "<br><strong>=== RESULTADO FINAL ===</strong><br>";
echo "Si esto fuera una llamada real, devolvería: <strong style='font-size:20px;color:green;'>$result</strong><br>";
echo "<br>Este es el valor que JavaScript debería recibir.";
?>
