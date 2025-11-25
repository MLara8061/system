<?php
require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

// Aseguramos que recibimos el parámetro "id"
if (!isset($_GET['id'])) {
    die("Falta el parámetro 'id'");
}

$id = intval($_GET['id']);
if ($id <= 0) {
    die("ID inválido");
}

// Ruta donde se guardarán los códigos QR
$dir = __DIR__ . '/uploads/qrcodes/';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// URL que se codificará dentro del QR - usar URL base de configuración
require_once __DIR__ . '/config/config.php';

// DEBUG: Forzar detección correcta en localhost
if (isset($_SERVER['HTTP_HOST']) && 
    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    // Construir URL local manualmente
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_local = $protocol . $host . '/system';
    $url = $base_local . '/equipment_public.php?id=' . $id;
} else {
    $url = BASE_URL . '/equipment_public.php?id=' . $id;
}

// Nombre del archivo QR
$filename = $dir . 'equipment_' . $id . '.png';

// Regenerar si se solicita
$force = isset($_GET['force']) && $_GET['force'] == '1';
if ($force && file_exists($filename)) {
    unlink($filename);
}

// Generamos el QR si no existe o si se regeneró
if (!file_exists($filename)) {
    QRcode::png($url, $filename, QR_ECLEVEL_L, 5);
}

// Mostramos la imagen directamente en el navegador
header('Content-Type: image/png');
readfile($filename);
exit;
?>
