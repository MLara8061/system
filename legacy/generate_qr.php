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

// DEPURACIÓN: Mostrar valores si se pasa debug=1
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== DEBUG GENERATE_QR.PHP ===\n\n";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NO DEFINIDO') . "\n";
    echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NO DEFINIDO') . "\n";
    echo "ENVIRONMENT: " . ENVIRONMENT . "\n";
    echo "BASE_URL: " . BASE_URL . "\n\n";
    
    // Construir URL local
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_local = $protocol . $host . '/system';
    echo "URL Local construida: " . $base_local . "/equipment_public.php?id=$id\n\n";
    
    // Verificar detección localhost
    $is_localhost = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                     strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
    echo "¿Es localhost? " . ($is_localhost ? 'SÍ' : 'NO') . "\n";
    exit;
}

// Usar siempre BASE_URL que ya detecta automáticamente el dominio correcto
$url = BASE_URL . '/equipment_public.php?id=' . $id;

// Nombre del archivo QR
$filename = $dir . 'equipment_' . $id . '.png';

// SIEMPRE REGENERAR para asegurar que usa la URL correcta
// Comentar estas líneas después de confirmar que funciona
if (file_exists($filename)) {
    unlink($filename);
}

// Generamos el QR
QRcode::png($url, $filename, QR_ECLEVEL_L, 5);

// Mostramos la imagen directamente en el navegador con headers anti-cache
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($filename);
exit;
?>
