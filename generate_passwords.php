<?php
// Generador de hashes bcrypt
header('Content-Type: text/plain; charset=utf-8');

$passwords = [
    'Mlara806' => 'Arla',
    '12345' => 'eduardo/Master'
];

echo "=== HASHES BCRYPT ===\n\n";

foreach ($passwords as $pass => $user) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    echo "Password: $pass (para $user)\n";
    echo "Hash: $hash\n";
    echo "Verificación: " . (password_verify($pass, $hash) ? 'OK' : 'FAIL') . "\n\n";
    
    echo "SQL:\n";
    echo "UPDATE users SET password = '$hash' WHERE username = '$user';\n\n";
    echo "---\n\n";
}
?>
