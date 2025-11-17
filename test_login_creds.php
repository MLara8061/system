<?php
$conn = new mysqli('localhost', 'root', '', 'system');

// Intentar con varias contraseñas comunes
$passwords = ['admin', 'Arla', 'arla', '123456', 'password', 'test', '12345678'];

$users = ['Arla', 'eduardo', 'JLawrence'];

echo "=== INTENTANDO LOGUEAR ===\n\n";

foreach ($users as $username) {
    foreach ($passwords as $password) {
        $hash = md5($password);
        $result = $conn->query("SELECT id, username, password FROM users WHERE username = '$username' AND password = '$hash'");
        
        if ($result->num_rows > 0) {
            echo "✓ ÉXITO: Usuario=$username, Contraseña=$password\n";
            $row = $result->fetch_assoc();
            echo "  ID: {$row['id']}, Hash: {$row['password']}\n\n";
        }
    }
}

// Mostrar las contraseñas actuales en hash
echo "\n=== HASHES ACTUALES EN BD ===\n";
$result = $conn->query("SELECT username, password FROM users");
while ($row = $result->fetch_assoc()) {
    echo "Username: {$row['username']}, Hash: {$row['password']}\n";
}
?>
