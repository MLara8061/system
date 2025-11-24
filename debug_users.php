<?php
require_once 'config/config.php';

echo "=== USUARIOS EN LA BASE DE DATOS ===\n\n";

$qry = $conn->query("SELECT id, username, firstname, lastname, role, password FROM users ORDER BY id");

if ($qry->num_rows > 0) {
    while ($user = $qry->fetch_assoc()) {
        echo "ID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Nombre: {$user['firstname']} {$user['lastname']}\n";
        echo "Role: {$user['role']} (" . ($user['role'] == 1 ? 'Admin' : 'Usuario') . ")\n";
        echo "Password Hash: {$user['password']}\n";
        echo "---\n\n";
    }
} else {
    echo "No hay usuarios\n";
}

// Test específico de login para eduardo
echo "\n=== TEST LOGIN EDUARDO ===\n";
$test_user = 'eduardo';
$test_pass = 'admin123'; // Cambia esto si la contraseña es otra

$qry = $conn->query("SELECT * FROM users WHERE username = '$test_user'");
if ($qry->num_rows > 0) {
    $user = $qry->fetch_assoc();
    echo "Usuario encontrado: {$user['username']}\n";
    echo "Role en BD: {$user['role']}\n";
    echo "Password hash en BD: {$user['password']}\n";
    echo "MD5 de '$test_pass': " . md5($test_pass) . "\n";
    echo "¿Coinciden? " . ($user['password'] === md5($test_pass) ? 'SÍ' : 'NO') . "\n";
} else {
    echo "Usuario 'eduardo' NO encontrado en BD\n";
}
?>
