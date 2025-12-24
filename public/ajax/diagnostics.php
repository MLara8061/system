<?php
/**
 * Script de diagnóstico del sistema
 */

// Suprimir errores de salida normal
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$diagnostics = [];

// 1. Verificar archivos críticos
$diagnostics['files'] = [];
$critical_files = [
    '/config/config.php' => 'config.php',
    '/config/session.php' => 'session.php',
    '/app/routing.php' => 'routing.php',
    '/vendor/autoload.php' => 'vendor/autoload.php',
    '/lib/PhpSpreadsheet-1.29.0/src/PhpSpreadsheet/Spreadsheet.php' => 'PhpSpreadsheet'
];

foreach ($critical_files as $path => $name) {
    $full_path = __DIR__ . $path;
    $diagnostics['files'][$name] = file_exists($full_path) ? 'OK' : 'MISSING';
}

// 2. Verificar sesión
try {
    session_start();
    $diagnostics['session'] = isset($_SESSION['login_id']) ? 'ACTIVE' : 'NO_LOGIN';
} catch (Exception $e) {
    $diagnostics['session'] = 'ERROR: ' . $e->getMessage();
}

// 3. Intentar cargar PHPSpreadsheet
try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        $diagnostics['phpspreadsheet'] = 'LOADED';
        // Intentar crear instancia
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $diagnostics['spreadsheet_instance'] = 'OK';
    } else {
        $diagnostics['phpspreadsheet'] = 'CLASS_NOT_FOUND';
    }
} catch (Exception $e) {
    $diagnostics['phpspreadsheet'] = 'ERROR: ' . $e->getMessage();
}

// 4. Verificar rutas de routing
try {
    require_once __DIR__ . '/app/routing.php';
    $test_route = resolve_route('home');
    $diagnostics['routing'] = $test_route ? 'OK' : 'FAILED';
    $diagnostics['home_route'] = $test_route;
} catch (Exception $e) {
    $diagnostics['routing'] = 'ERROR: ' . $e->getMessage();
}

// 5. Verificar permisos
$diagnostics['permissions'] = [
    'uploads_writable' => is_writable(__DIR__ . '/uploads'),
    'logs_writable' => is_writable(__DIR__ . '/logs'),
    'config_readable' => is_readable(__DIR__ . '/config/config.php')
];

// 6. Información de PHP
$diagnostics['php'] = [
    'version' => PHP_VERSION,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'extensions_loaded' => [
        'json' => extension_loaded('json'),
        'pdo' => extension_loaded('pdo'),
        'zip' => extension_loaded('zip'),
        'zlib' => extension_loaded('zlib')
    ]
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
