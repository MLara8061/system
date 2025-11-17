<?php
// check_db.php - Verificación simple de conexión a BD usando config/.env
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/config.php';

echo "ENVIRONMENT: " . ENVIRONMENT . "\n";
echo "DB_HOST: " . DB_CONFIG['host'] . "\n";
echo "DB_NAME: " . DB_CONFIG['name'] . "\n";

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo "ERROR: Conexión no inicializada.\n";
    exit(1);
}

if ($conn->connect_error) {
    http_response_code(500);
    echo "ERROR de conexión: " . $conn->connect_error . "\n";
    exit(1);
}

$meta = $conn->query('SELECT DATABASE() db, USER() usr, VERSION() ver');
$info = $meta ? $meta->fetch_assoc() : [];
echo "Conectado a: " . ($info['db'] ?? '(desconocido)') . "\n";
echo "MySQL VERSION: " . ($info['ver'] ?? '(desconocida)') . "\n\n";

$result = $conn->query('SELECT id, username FROM users ORDER BY id DESC LIMIT 5');
if ($result) {
    echo "Usuarios (últimos 5):\n";
    while ($row = $result->fetch_assoc()) {
        echo "- [{$row['id']}] {$row['username']}\n";
    }
} else {
    echo "Consulta falló: " . $conn->error . "\n";
}
?>
