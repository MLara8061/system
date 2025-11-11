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

// URL que se codificará dentro del QR
// Cambia esta URL según tu entorno (localhost, dominio, etc.)
$base_url = "http://localhost/system/view_equipment.php?id=";
$url = $base_url . $id;

// Nombre del archivo QR
$filename = $dir . 'equipment_' . $id . '.png';

// Generamos el QR si no existe o si se quiere regenerar
if (!file_exists($filename)) {
    QRcode::png($url, $filename, QR_ECLEVEL_L, 5);
}

// Mostramos la imagen directamente en el navegador
header('Content-Type: image/png');
readfile($filename);
exit;
?>
