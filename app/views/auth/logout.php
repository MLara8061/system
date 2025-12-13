<?php
// Definir ROOT si no está definido
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT . '/config/session.php';

// Destruir sesión de forma segura
destroy_session();

// Redirigir al login
header("location: /app/views/auth/login.php");
exit;

