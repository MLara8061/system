<?php
// Unificar conexión PDO con la misma configuración que usa mysqli (config/config.php -> DB_CONFIG).
// Esto evita desalineación de IDs cuando la UI carga catálogos con PDO y el backend genera inventario con mysqli.

// Intentar cargar la config principal (define ENVIRONMENT/DB_CONFIG y carga .env).
// require_once es seguro si ya fue incluido en otros puntos.
require_once __DIR__ . '/config.php';

// Fallback por compatibilidad: si por alguna razón DB_CONFIG no existe, usar env.php (comportamiento anterior).
if (!defined('DB_CONFIG')) {
    require_once __DIR__ . '/env.php';
}

$cfg = defined('DB_CONFIG') ? DB_CONFIG : [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'name' => getenv('DB_NAME') ?: '',
];

$DB_HOST = $cfg['host'] ?? 'localhost';
$DB_USER = $cfg['user'] ?? 'root';
$DB_PASS = $cfg['pass'] ?? '';
$DB_NAME = $cfg['name'] ?? '';
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
