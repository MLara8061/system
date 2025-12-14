<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cfgPath = __DIR__ . '/../config/maintenance_config.php';
if (!file_exists($cfgPath)) {
    http_response_code(500);
    echo "Configuración de mantenimiento no encontrada.";
    exit;
}

$config = include $cfgPath;
$token = $_GET['token'] ?? '';

if (empty($config['admin_token']) || $token !== $config['admin_token']) {
    http_response_code(403);
    echo "Token inválido.";
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (empty($ip)) {
    http_response_code(500);
    echo "No se pudo detectar la IP.";
    exit;
}

// Añadir IP si no existe
if (!in_array($ip, $config['allowed_ips'])) {
    $config['allowed_ips'][] = $ip;
    // Reescribir el archivo de configuración
    $export = "<?php\n/**\n * Configuración de Modo Mantenimiento\n * \n * Para ACTIVAR mantenimiento: cambiar \$maintenance_enabled = true\n * Para DESACTIVAR: cambiar \$maintenance_enabled = false\n */\n\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents($cfgPath, $export) === false) {
        http_response_code(500);
        echo "No se pudo actualizar la configuración.";
        exit;
    }
    echo "IP añadida correctamente: $ip";
} else {
    echo "La IP ya está registrada: $ip";
}

// Mostrar link para volver al sistema
echo "\n<br><a href='/' style='color: #0f0;'>Volver al sitio</a>";
