<?php
define('ROOT', __DIR__);
require_once ROOT . '/config/config.php';

echo "=== REPAIR BRANCHES TABLE ===\n";

// Verificar estado de la tabla
echo "\n1. CHECK TABLE branches:\n";
$result = $conn->query("CHECK TABLE branches");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
}

// Reparar tabla
echo "\n2. REPAIR TABLE branches:\n";
$result = $conn->query("REPAIR TABLE branches");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
}

// Probar SELECT nuevamente
echo "\n3. Test SELECT after repair:\n";
$result = $conn->query("SELECT id, name FROM branches WHERE active = 1 LIMIT 3");
if ($result) {
    echo "SUCCESS! Rows: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - " . json_encode($row) . "\n";
    }
} else {
    echo "FAILED: " . $conn->error . "\n";
}
