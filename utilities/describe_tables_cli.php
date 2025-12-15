<?php
// CLI helper: describe specific tables using the app's DB connection.
// Usage: php utilities/describe_tables_cli.php

require_once __DIR__ . '/../config/config.php';

$tables = ['equipments', 'maintenance_reports', 'tools', 'accessories'];

echo "=== DESCRIBE TABLES ===\n";
foreach ($tables as $table) {
    echo "\n{$table}:\n";
    $result = $conn->query("SHOW COLUMNS FROM {$table}");
    if (!$result) {
        echo "  ERROR: {$conn->error}\n";
        continue;
    }
    while ($row = $result->fetch_assoc()) {
        $key = $row['Key'] ? " [{$row['Key']}]" : '';
        echo "  - {$row['Field']}: {$row['Type']}{$key}\n";
    }
}

echo "\n=== FIN ===\n";
