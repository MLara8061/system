<?php
define('ROOT', __DIR__);
require_once ROOT . '/config/config.php';

echo "=== KILL LOCKS ===\n";

// Ver procesos que están bloqueando
$result = $conn->query("SHOW PROCESSLIST");
if ($result) {
    echo "Active processes:\n";
    while ($row = $result->fetch_assoc()) {
        if ($row['Time'] > 10 || strpos($row['Info'] ?? '', 'branches') !== false) {
            echo "  ID: {$row['Id']}, Time: {$row['Time']}s, State: {$row['State']}, Info: " . substr($row['Info'] ?? '', 0, 80) . "\n";
            
            // Matar procesos que llevan más de 30 segundos
            if ($row['Time'] > 30) {
                echo "    -> Killing process {$row['Id']}\n";
                $conn->query("KILL {$row['Id']}");
            }
        }
    }
}

// Intentar unlock
echo "\n=== UNLOCK TABLES ===\n";
$conn->query("UNLOCK TABLES");
echo "Done\n";

// Verificar índices corruptos
echo "\n=== ANALYZE TABLE ===\n";
$result = $conn->query("ANALYZE TABLE branches");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
}

// Flush query cache
echo "\n=== FLUSH QUERY CACHE ===\n";
$conn->query("FLUSH QUERY CACHE");
echo "Done\n";
