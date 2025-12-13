<?php
require_once __DIR__ . '/env.php';

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: '';
$DB_CHARSET = getenv('DB_CHARSET') ?: 'utf8mb4';

$pdo = null;

try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('PDO Connection Error: ' . $e->getMessage());
    if (php_sapi_name() === 'cli' || (defined('ENVIRONMENT') && ENVIRONMENT === 'local')) {
        die('Error de conexión PDO: ' . $e->getMessage());
    }
    die('Error de conexión a la base de datos.');
}

/**
 * get_pdo - Obtener conexión PDO global
 */
function get_pdo() {
    global $pdo;
    return $pdo;
}
