<?php
// Vista modal para editar tickets (cargada via uni_modal)

$root = realpath(__DIR__ . '/../../../..');
if (!defined('ROOT')) define('ROOT', $root);

require_once ROOT . '/config/config.php';
require_once ROOT . '/config/session.php';
require_once ROOT . '/config/db_connect.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    echo "<div class=\"alert alert-warning mb-0\">Sesión expirada. Recarga la página.</div>";
    exit;
}

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ticketId <= 0) {
    http_response_code(400);
    echo "<div class=\"alert alert-danger mb-0\">ID de ticket inválido.</div>";
    exit;
}

$qryRes = $conn->query("SELECT * FROM tickets WHERE id = {$ticketId} LIMIT 1");
if (!$qryRes || $qryRes->num_rows === 0) {
    http_response_code(404);
    echo "<div class=\"alert alert-warning mb-0\">Ticket no encontrado.</div>";
    exit;
}

$qry = $qryRes->fetch_assoc();
foreach ($qry as $k => $v) {
    $$k = $v;
}

$in_modal = true;

// Renderizar el mismo formulario reutilizando new.php (en modo modal)
include __DIR__ . '/new.php';
