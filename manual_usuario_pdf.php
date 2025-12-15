<?php
// Entrypoint público (whitelist .htaccess) para el Manual de Usuario.
// La lógica/HTML del manual vive en app/helpers/manual_usuario_pdf.php.

require_once __DIR__ . '/config/session.php';

if (function_exists('validate_session') && !validate_session()) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/app/helpers/manual_usuario_pdf.php';
