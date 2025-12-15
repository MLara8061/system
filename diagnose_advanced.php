<?php
define('ROOT', __DIR__);
require_once ROOT . '/config/config.php';

echo "=== DIAGNÓSTICO AVANZADO ===\n\n";

// 1. Ver procesos activos
echo "1. SHOW PROCESSLIST:\n";
$result = $conn->query("SHOW PROCESSLIST");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Info'] ?? '', 'branches') !== false || $row['State'] === 'Locked') {
            echo json_encode($row) . "\n";
        }
    }
}

// 2. Ver índices de la tabla
echo "\n2. SHOW INDEX FROM branches:\n";
$result = $conn->query("SHOW INDEX FROM branches");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['Key_name']}: {$row['Column_name']}\n";
    }
}

// 3. Contar filas
echo "\n3. COUNT rows (usando índice):\n";
$result = $conn->query("SELECT COUNT(*) as total FROM branches");
if ($result && $row = $result->fetch_assoc()) {
    echo "Total rows: " . $row['total'] . "\n";
} else {
    echo "FAILED: " . ($conn->error ?? 'timeout') . "\n";
}

// 4. Probar SELECT sin WHERE
echo "\n4. SELECT sin WHERE ni ORDER:\n";
$conn->query("SET SESSION max_execution_time=5");
$result = $conn->query("SELECT id, name FROM branches LIMIT 1");
if ($result) {
    echo "SUCCESS!\n";
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
} else {
    echo "FAILED: " . ($conn->error ?? 'timeout') . "\n";
}
