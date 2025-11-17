<?php
// check_db.php - Healthcheck seguro de BD
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/config.php';

// Requerir token para evitar exposición
$expected = getenv('HEALTHCHECK_TOKEN');
if (!$expected) {
    http_response_code(403);
    echo "HEALTHCHECK deshabilitado (defina HEALTHCHECK_TOKEN en config/.env)\n";
    exit(1);
}

$provided = $_GET['token'] ?? ($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '');
if (!hash_equals($expected, $provided)) {
    http_response_code(403);
    echo "No autorizado\n";
    exit(1);
}

// Comprobaciones mínimas
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo "DB: NOK (no inicializada)\n";
    exit(1);
}
if ($conn->connect_error) {
    http_response_code(500);
    echo "DB: NOK ({$conn->connect_error})\n";
    exit(1);
}

$ok = $conn->query('SELECT 1');
if (!$ok) {
    http_response_code(500);
    echo "DB: NOK ({$conn->error})\n";
    exit(1);
}

echo "OK\n";

// Detalle opcional
if (isset($_GET['verbose']) && $_GET['verbose'] == '1') {
    $meta = $conn->query('SELECT DATABASE() db, VERSION() ver');
    $info = $meta ? $meta->fetch_assoc() : [];
    echo "ENVIRONMENT: " . ENVIRONMENT . "\n";
    echo "DB_HOST: " . DB_CONFIG['host'] . "\n";
    echo "DB_NAME: " . DB_CONFIG['name'] . "\n";
    echo "MySQL VERSION: " . ($info['ver'] ?? '(desconocida)') . "\n";
}
?>
