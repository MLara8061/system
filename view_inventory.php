<?php
// Endpoint para modal (whitelist .htaccess). Requiere sesión.
require_once __DIR__ . '/config/session.php';
if (!validate_session()) {
	header('location: app/views/auth/login.php');
	exit();
}

require_once __DIR__ . '/app/views/pages/view_inventory.php';
