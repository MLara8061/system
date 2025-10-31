<?php
// generate_qr.php - VERSIÓN 100% FUNCIONAL

// === LIMPIAR TODO BUFFER (ESPACIOS, ERRORES, WARNINGS) ===
ob_clean();

// === VALIDAR ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit;
}
$id = (int)$_GET['id'];

// === RUTA DE LA LIBRERÍA ===
$qrLib = __DIR__ . '/lib/phpqrcode/qrlib.php';
if (!file_exists($qrLib)) {
    http_response_code(500);
    exit;
}

// === CARGAR LIBRERÍA ===
require_once $qrLib;

// === CONSTRUIR URL ===
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$path = $path === '/' ? '' : $path;
$url = $protocol . $host . $path . '/view_equipment.php?id=' . $id;

// === ENVIAR HEADER ===
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// === GENERAR QR ===
QRcode::png($url, null, QR_ECLEVEL_L, 6, 2);
exit;