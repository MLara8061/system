<?php
/**
 * Protección contra acceso directo a vistas
 * Incluir al inicio de cada vista/parcial con: require_once 'config/access_guard.php';
 */

// Verificar que se accede desde index.php
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    die('Acceso denegado. Use la aplicación correctamente.');
}

// Verificar sesión activa (excepto para partials como header/footer)
$public_files = ['header.php', 'footer.php', 'sidebar.php', 'topbar.php'];
$current_file = basename($_SERVER['SCRIPT_FILENAME'] ?? '');

if (!in_array($current_file, $public_files, true)) {
    if (!isset($_SESSION['login_id'])) {
        header('Location: login.php');
        exit();
    }
}
?>