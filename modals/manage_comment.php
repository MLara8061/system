<?php
// Endpoint para cargar el formulario de comentario en un modal (AJAX)

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

require_once ROOT . '/config/session.php';

if (function_exists('validate_session') && !validate_session()) {
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo '<div class="alert alert-warning mb-0">Sesión expirada. Recarga la página e inicia sesión nuevamente.</div>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');

require ROOT . '/legacy/manage_comment.php';
