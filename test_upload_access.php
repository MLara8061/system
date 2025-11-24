<?php
/**
 * Script de prueba para verificar el acceso a la página de carga masiva
 * Este archivo puede ser eliminado después de verificar que todo funciona
 */

session_start();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['login_id'])) {
    echo "<h2>❌ Error: No hay sesión activa</h2>";
    echo "<p>Por favor inicia sesión primero</p>";
    echo '<a href="login.php">Ir a Login</a>';
    exit;
}

echo "<h1>✅ Verificación de Acceso - Carga Masiva</h1>";

echo "<h3>Información de Sesión:</h3>";
echo "<ul>";
echo "<li><strong>Usuario ID:</strong> " . ($_SESSION['login_id'] ?? 'No definido') . "</li>";
echo "<li><strong>Nombre:</strong> " . ($_SESSION['login_firstname'] ?? '') . " " . ($_SESSION['login_lastname'] ?? '') . "</li>";
echo "<li><strong>Username:</strong> " . ($_SESSION['login_username'] ?? 'No definido') . "</li>";
echo "<li><strong>Role:</strong> " . ($_SESSION['login_role'] ?? 'No definido') . "</li>";
echo "</ul>";

// Verificar si es administrador
if (isset($_SESSION['login_role']) && $_SESSION['login_role'] == 1) {
    echo "<h3 style='color: green;'>✅ ACCESO CONCEDIDO</h3>";
    echo "<p>Eres administrador. Tienes acceso a la funcionalidad de Carga Masiva.</p>";
    echo '<p><a href="index.php?page=upload_equipment" class="btn btn-primary">Ir a Carga Masiva de Equipos</a></p>';
} else {
    echo "<h3 style='color: red;'>❌ ACCESO DENEGADO</h3>";
    echo "<p>No eres administrador. No tienes acceso a esta funcionalidad.</p>";
    echo "<p><strong>Role actual:</strong> " . ($_SESSION['login_role'] ?? 'No definido') . " (se requiere role = 1)</p>";
}

echo "<hr>";
echo "<h3>Verificación de Archivos:</h3>";
echo "<ul>";

$archivos_requeridos = [
    'ajax.php' => 'Controlador AJAX',
    'admin_class.php' => 'Clase de administración',
    'upload_equipment.php' => 'Página de carga masiva',
    'lib/simplexlsx-master/src/SimpleXLSX.php' => 'Librería SimpleXLSX',
    'assets/templates/generar_plantilla.html' => 'Generador de plantilla'
];

foreach ($archivos_requeridos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "<li style='color: green;'>✅ <strong>$descripcion:</strong> $archivo</li>";
    } else {
        echo "<li style='color: red;'>❌ <strong>$descripcion:</strong> $archivo (NO ENCONTRADO)</li>";
    }
}

echo "</ul>";

echo "<hr>";
echo "<h3>Enlaces Útiles:</h3>";
echo "<ul>";
echo '<li><a href="index.php">Ir al Dashboard</a></li>';
echo '<li><a href="index.php?page=upload_equipment">Carga Masiva de Equipos</a></li>';
echo '<li><a href="assets/templates/generar_plantilla.html" target="_blank">Generar Plantilla Excel</a></li>';
echo '<li><a href="LEEME_CARGA_MASIVA.md" target="_blank">Ver Documentación</a></li>';
echo "</ul>";

echo "<hr>";
echo "<p><small>Puedes eliminar este archivo (test_upload_access.php) después de verificar que todo funciona correctamente.</small></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 20px; }
ul { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; border-radius: 5px; margin: 10px 0; }
.btn:hover { background: #0056b3; text-decoration: none; }
</style>
