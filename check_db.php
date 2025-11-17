<?php
$conn = new mysqli('localhost', 'root', '', 'system');
$result = $conn->query('SELECT id, username FROM users LIMIT 5');
echo "Usuarios en BD:\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Username: {$row['username']}\n";
}
?>
