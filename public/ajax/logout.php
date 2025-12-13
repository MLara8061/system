<?php
/**
 * public/ajax/logout.php - Endpoint de logout
 * 
 * USO:
 * GET /public/ajax/logout.php (destruye sesión y redirige)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir ROOT si no existe
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

// Cargar sesión hardened
require_once ROOT . '/config/session.php';

// Log de logout
if (isset($_SESSION['login_id'])) {
    error_log("LOGOUT: User " . $_SESSION['login_id'] . " at " . date('Y-m-d H:i:s'));
}

// Destruir sesión de forma segura
destroy_session();

// Redirigir al login con ruta relativa
header('Location: ../../index.php?page=login', true, 302);
exit;
