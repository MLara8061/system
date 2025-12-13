<?php
// Script de depuración para verificar la generación de QR
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DEBUG - Configuración de URL</h2>";
echo "<pre>";

require_once __DIR__ . '/config/config.php';

echo "=== INFORMACIÓN DEL SERVIDOR ===\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NO DEFINIDO') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NO DEFINIDO') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NO DEFINIDO') . "\n";
echo "\n";

echo "=== DETECCIÓN DE ENTORNO ===\n";
$host = $_SERVER['HTTP_HOST'] ?? php_uname('n');
echo "Host detectado: " . $host . "\n";

$is_cli = php_sapi_name() === 'cli';
echo "Es CLI: " . ($is_cli ? 'SI' : 'NO') . "\n";

$is_local = $is_cli
    || in_array($host, ['localhost', '127.0.0.1', '::1'], true)
    || str_starts_with($host, '192.168.')
    || str_ends_with($host, '.local')
    || str_ends_with($host, '.test')
    || str_contains($host, '.dev');

echo "Es Local: " . ($is_local ? 'SI' : 'NO') . "\n";
echo "\n";

echo "=== CONFIGURACIÓN ACTUAL ===\n";
echo "ENVIRONMENT: " . ENVIRONMENT . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "\n";

echo "=== URL DEL QR ===\n";
$test_id = 1;
$qr_url = BASE_URL . '/equipment_public.php?id=' . $test_id;
echo "URL para equipo ID " . $test_id . ":\n";
echo $qr_url . "\n";
echo "\n";

echo "=== VERIFICAR .env ===\n";
echo "BASE_URL desde getenv(): " . (getenv('BASE_URL') ?: 'NO DEFINIDO') . "\n";

echo "</pre>";

echo "<h3>Prueba de generación de QR</h3>";
echo "<img src='generate_qr.php?id=1&force=1' alt='QR Code'>";
?>
