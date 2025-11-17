<?php
// Diagnóstico de usuarios - acceso solo desde localhost
require_once 'config/config.php';

// Solo permitir desde localhost
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']);
if (!$is_local) {
    http_response_code(403);
    die('Acceso denegado');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE USUARIOS ===\n\n";

$qry = $conn->query("SELECT id, username, firstname, lastname, role, password FROM users ORDER BY id");

if ($qry && $qry->num_rows > 0) {
    while ($u = $qry->fetch_assoc()) {
        echo "----------------------------------------\n";
        echo "ID: {$u['id']}\n";
        echo "Username: {$u['username']}\n";
        echo "Nombre: {$u['firstname']} {$u['lastname']}\n";
        echo "Role: {$u['role']} (tipo: " . gettype($u['role']) . ")\n";
        echo "Role interpretado: " . ($u['role']==1 ? 'Admin' : ($u['role']==2 ? 'Staff' : 'Desconocido')) . "\n";
        echo "Password hash: {$u['password']}\n";
        echo "\n";
    }
} else {
    echo "No se pudieron obtener usuarios o no hay usuarios\n";
}

echo "\n=== PRUEBAS DE LOGIN ===\n\n";

// Probar eduardo con contraseña común
$test_users = [
    ['username' => 'eduardo', 'password' => 'admin123', 'type' => 1],
    ['username' => 'eduardo', 'password' => '123456', 'type' => 1],
    ['username' => 'Master', 'password' => 'admin123', 'type' => 1],
    ['username' => 'Arla', 'password' => '123456', 'type' => 2]
];

foreach ($test_users as $test) {
    echo "Probando: {$test['username']} con password '{$test['password']}' como tipo {$test['type']}\n";
    
    $qry = $conn->query("SELECT * FROM users WHERE username = '{$test['username']}'");
    if ($qry && $qry->num_rows > 0) {
        $user = $qry->fetch_assoc();
        $hash = md5($test['password']);
        
        echo "  - Usuario existe: SÍ\n";
        echo "  - Role en BD: {$user['role']}\n";
        echo "  - Password coincide: " . ($user['password'] === $hash ? 'SÍ' : 'NO') . "\n";
        echo "  - Role coincide con type: " . ($user['role'] == $test['type'] ? 'SÍ' : 'NO') . "\n";
        echo "  - Login permitido: " . (($user['password'] === $hash && $user['role'] == $test['type']) ? 'SÍ' : 'NO') . "\n";
    } else {
        echo "  - Usuario NO existe\n";
    }
    echo "\n";
}
?>
