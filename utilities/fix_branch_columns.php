<?php
// Agrega branch_id faltante en tablas para multi-sucursal.
// Uso:
//   php utilities/fix_branch_columns.php
//   php utilities/fix_branch_columns.php --fix

require_once __DIR__ . '/../config/config.php';

function column_exists(mysqli $conn, string $table, string $column): bool {
    // MariaDB puede fallar con placeholders en SHOW COLUMNS ... LIKE ?
    $columnEscaped = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$columnEscaped}'");
    return $result && $result->num_rows > 0;
}

function index_exists(mysqli $conn, string $table, string $indexName): bool {
    // Evitar placeholders en SHOW INDEX para máxima compatibilidad
    $indexEscaped = $conn->real_escape_string($indexName);
    $result = $conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexEscaped}'");
    return $result && $result->num_rows > 0;
}

function run_sql(mysqli $conn, string $sql): bool {
    echo "SQL: {$sql}\n";
    $ok = $conn->query($sql);
    if ($ok) {
        echo "  ✓ OK\n";
        return true;
    }
    echo "  ✗ ERROR: {$conn->error}\n";
    return false;
}

$doFix = isset($argv[1]) && $argv[1] === '--fix';

$targets = [
    'tools' => [
        'needs_branch_id' => true,
        'branch_id_nullable' => true,
        'default_branch' => 1,
    ],
    'maintenance_reports' => [
        'needs_branch_id' => true,
        'branch_id_nullable' => true,
        'default_branch' => 1,
    ],
];

echo "=== FIX branch_id (multi-sucursal) ===\n\n";

echo "Modo: " . ($doFix ? 'APLICAR' : 'DIAGNÓSTICO') . "\n\n";

foreach ($targets as $table => $cfg) {
    echo "Tabla: {$table}\n";

    $hasBranch = column_exists($conn, $table, 'branch_id');
    echo $hasBranch ? "  ✓ Tiene branch_id\n" : "  ✗ Falta branch_id\n";

    $idxName = 'idx_branch_id';
    $hasIdx = $hasBranch && index_exists($conn, $table, $idxName);
    if ($hasBranch) {
        echo $hasIdx ? "  ✓ Tiene índice {$idxName}\n" : "  ✗ Falta índice {$idxName}\n";
    }

    if (!$doFix) {
        echo "\n";
        continue;
    }

    if (!$hasBranch) {
        // NULL por seguridad; asignamos por UPDATE luego.
        $sql = "ALTER TABLE `{$table}` ADD COLUMN `branch_id` INT(11) NULL";
        run_sql($conn, $sql);
        $hasBranch = column_exists($conn, $table, 'branch_id');
    }

    if ($hasBranch && !index_exists($conn, $table, $idxName)) {
        run_sql($conn, "ALTER TABLE `{$table}` ADD INDEX {$idxName} (`branch_id`)");
    }

    // Asignar branch por defecto a registros existentes
    if ($hasBranch) {
        $defaultBranch = (int)($cfg['default_branch'] ?? 1);
        run_sql($conn, "UPDATE `{$table}` SET `branch_id` = {$defaultBranch} WHERE `branch_id` IS NULL");
    }

    echo "\n";
}

echo "=== FIN ===\n";
