<?php
$conn = new mysqli('localhost', 'root', '', 'system');
$result = $conn->query('SELECT id, username, role FROM users');
echo "Usuarios en BD:\n";
while ($row = $result->fetch_assoc()) {
    $role = $row['role'] == 1 ? "ADMIN" : "USER";
    echo "ID: {$row['id']}, Username: {$row['username']}, Role: $role\n";
}
?>
