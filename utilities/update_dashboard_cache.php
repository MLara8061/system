<?php
/**
 * Script para actualizar caché del dashboard
 * Se ejecuta vía cron cada hora: 0 * * * * /usr/bin/php /path/update_dashboard_cache.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos - cron tiene más tiempo

define('ROOT', dirname(__DIR__));

// Conexión directa sin access_guard (cron no tiene sesión)
require_once ROOT . '/config/env.php';
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'u228864460_system';
$DB_PASS = getenv('DB_PASS') ?: 'Mateo2019!';
$DB_NAME = getenv('DB_NAME') ?: 'u228864460_system';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("ERROR: Could not connect. " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$log_file = ROOT . '/cache_update.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

log_message("=== INICIO ACTUALIZACIÓN CACHE ===");

function column_exists(mysqli $conn, $table, $column) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $column = $conn->real_escape_string((string)$column);
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    $exists = $res && $res->num_rows > 0;
    if ($res instanceof mysqli_result) {
        $res->free();
    }
    return $exists;
}

function cache_put(mysqli $conn, $key, $data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = '[]';
    }
    $stmt = $conn->prepare('REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES (?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $key, $json);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function cache_key_for_branch($base, $branch_id = 0) {
    $base = (string)$base;
    $branch_id = (int)$branch_id;
    return $branch_id > 0 ? ($base . ':b' . $branch_id) : $base;
}

// Crear tabla de caché si no existe
$conn->query("CREATE TABLE IF NOT EXISTS dashboard_cache (
    cache_key VARCHAR(100) PRIMARY KEY,
    cache_data JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Lista de sucursales a cachear (derivadas de equipments.branch_id)
$branch_ids = [0]; // 0 => global
if (column_exists($conn, 'equipments', 'branch_id')) {
    $brRes = $conn->query("SELECT DISTINCT branch_id FROM equipments WHERE branch_id IS NOT NULL AND branch_id > 0 ORDER BY branch_id");
    if ($brRes instanceof mysqli_result) {
        while ($r = $brRes->fetch_assoc()) {
            $bid = (int)($r['branch_id'] ?? 0);
            if ($bid > 0) {
                $branch_ids[] = $bid;
            }
        }
        $brRes->free();
    }
}

// 1. Top Proveedores (con timeout extendido)
log_message("1. Actualizando top proveedores...");
try {
    $conn->query("SET SESSION max_execution_time=30000");
    $supplierNameCol = column_exists($conn, 'suppliers', 'empresa') ? 's.empresa' : (column_exists($conn, 'suppliers', 'name') ? 's.name' : 'NULL');
    foreach ($branch_ids as $bid) {
        $branchWhere = ($bid > 0 && column_exists($conn, 'equipments', 'branch_id')) ? ("WHERE e.branch_id = {$bid}") : '';
        $totalSql = "SELECT COUNT(*) AS total FROM equipments e {$branchWhere}";
        $totalRes = $conn->query($totalSql);
        $total = 0;
        if ($totalRes instanceof mysqli_result) {
            $total = (int)($totalRes->fetch_assoc()['total'] ?? 0);
            $totalRes->free();
        }

        $sql = "
            SELECT COALESCE({$supplierNameCol}, 'Sin Proveedor') as supplier,
                   COUNT(*) as cnt
            FROM equipments e
            LEFT JOIN suppliers s ON e.supplier_id = s.id
            {$branchWhere}
            GROUP BY supplier
            ORDER BY cnt DESC LIMIT 5
        ";
        $result = $conn->query($sql);
        $data = [];
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $cnt = (int)($row['cnt'] ?? 0);
                $pct = $total > 0 ? round(($cnt * 100.0) / $total, 1) : 0.0;
                $data[] = ['supplier' => $row['supplier'], 'cnt' => $cnt, 'pct' => $pct];
            }
            $result->free();
        }
        cache_put($conn, cache_key_for_branch('top_suppliers', $bid), $data);
    }
    log_message("   ✓ Top proveedores: global + " . (count($branch_ids) - 1) . " sucursales");
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 2. Equipos Recientes
log_message("2. Actualizando equipos recientes...");
try {
    $supplierNameCol = column_exists($conn, 'suppliers', 'empresa') ? 's.empresa' : (column_exists($conn, 'suppliers', 'name') ? 's.name' : 'NULL');
    $invCol = column_exists($conn, 'equipments', 'number_inventory') ? 'e.number_inventory' : (column_exists($conn, 'equipments', 'inventory_code') ? 'e.inventory_code' : 'NULL');
    $amountCol = column_exists($conn, 'equipments', 'amount') ? 'e.amount' : (column_exists($conn, 'equipments', 'price') ? 'e.price' : 'NULL');
    foreach ($branch_ids as $bid) {
        $branchWhere = ($bid > 0 && column_exists($conn, 'equipments', 'branch_id')) ? ("WHERE e.branch_id = {$bid}") : '';
        $sql = "
            SELECT e.id, {$invCol} AS number_inventory, e.name,
                   COALESCE({$supplierNameCol}, 'Sin proveedor') as supplier,
                   {$amountCol} AS amount, e.revision
            FROM equipments e
            LEFT JOIN suppliers s ON e.supplier_id = s.id
            {$branchWhere}
            ORDER BY e.id DESC LIMIT 5
        ";
        $result = $conn->query($sql);
        $data = [];
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $result->free();
        }
        cache_put($conn, cache_key_for_branch('recent_equipments', $bid), $data);
    }
    log_message("   ✓ Equipos recientes: global + " . (count($branch_ids) - 1) . " sucursales");
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 3. Distribución de Proveedores (para pie chart)
log_message("3. Actualizando distribución proveedores...");
try {
    $supplierNameCol = column_exists($conn, 'suppliers', 'empresa') ? 's.empresa' : (column_exists($conn, 'suppliers', 'name') ? 's.name' : 'NULL');
    foreach ($branch_ids as $bid) {
        $branchWhere = ($bid > 0 && column_exists($conn, 'equipments', 'branch_id')) ? ("WHERE e.branch_id = {$bid}") : '';
        $sql = "
            SELECT COALESCE({$supplierNameCol}, 'Sin Proveedor') as supplier, COUNT(*) as cnt
            FROM equipments e
            LEFT JOIN suppliers s ON e.supplier_id = s.id
            {$branchWhere}
            GROUP BY supplier
            ORDER BY cnt DESC LIMIT 5
        ";
        $result = $conn->query($sql);
        $labels = [];
        $values = [];
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['supplier'];
                $values[] = (int)$row['cnt'];
            }
            $result->free();
        }
        cache_put($conn, cache_key_for_branch('pie_suppliers', $bid), ['labels' => $labels, 'values' => $values]);
    }
    log_message("   ✓ Pie proveedores: global + " . (count($branch_ids) - 1) . " sucursales");
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 4. Mantenimientos por Tipo
log_message("4. Actualizando mantenimientos...");
try {
    $typeCol = column_exists($conn, 'maintenance_reports', 'service_type') ? 'service_type' : (column_exists($conn, 'maintenance_reports', 'type') ? 'type' : null);
    if ($typeCol === null) {
        log_message("   ✗ Sin columna de tipo en maintenance_reports");
    } else {
        foreach ($branch_ids as $bid) {
            $branchWhere = ($bid > 0 && column_exists($conn, 'maintenance_reports', 'branch_id')) ? ("WHERE branch_id = {$bid}") : '';
            $result = $conn->query("SELECT {$typeCol} AS t, COUNT(*) as total FROM maintenance_reports {$branchWhere} GROUP BY {$typeCol}");
            $data = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $t = (string)($row['t'] ?? '');
                    if ($t === '') $t = 'N/A';
                    $data[$t] = (int)($row['total'] ?? 0);
                }
                $result->free();
            }
            cache_put($conn, cache_key_for_branch('maintenance_counts', $bid), $data);
        }
        log_message("   ✓ Mantenimientos: global + " . (count($branch_ids) - 1) . " sucursales");
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 5. Ejecución Mensual (últimos 6 meses)
log_message("5. Actualizando ejecución mensual...");
try {
    $start_date = date('Y-m-01', strtotime('-5 months'));
    $typeCol = column_exists($conn, 'maintenance_reports', 'service_type') ? 'service_type' : (column_exists($conn, 'maintenance_reports', 'type') ? 'type' : null);
    $dateCol = column_exists($conn, 'maintenance_reports', 'service_date') ? 'service_date' : (column_exists($conn, 'maintenance_reports', 'date') ? 'date' : null);
    if ($typeCol === null || $dateCol === null) {
        log_message("   ✗ Sin columnas necesarias en maintenance_reports (tipo/fecha)");
    } else {
        foreach ($branch_ids as $bid) {
            $where = [];
            $where[] = "{$dateCol} >= '{$start_date}'";
            if ($bid > 0 && column_exists($conn, 'maintenance_reports', 'branch_id')) {
                $where[] = "branch_id = {$bid}";
            }
            $whereSql = 'WHERE ' . implode(' AND ', $where);
            $sql = "
                SELECT DATE_FORMAT({$dateCol}, '%Y-%m') as mes, {$typeCol} as type, COUNT(*) as total
                FROM maintenance_reports
                {$whereSql}
                GROUP BY DATE_FORMAT({$dateCol}, '%Y-%m'), {$typeCol}
                ORDER BY mes
            ";
            $result = $conn->query($sql);
            $data = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free();
            }
            cache_put($conn, cache_key_for_branch('monthly_maintenance', $bid), $data);
        }
        log_message("   ✓ Ejecución mensual: global + " . (count($branch_ids) - 1) . " sucursales");
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 6. Adquisición de Equipos Mensual
log_message("6. Actualizando adquisición equipos...");
try {
    $dateCol = column_exists($conn, 'equipments', 'purchase_date') ? 'purchase_date' : (column_exists($conn, 'equipments', 'date_created') ? 'date_created' : null);
    $valueCol = column_exists($conn, 'equipments', 'price') ? 'price' : (column_exists($conn, 'equipments', 'amount') ? 'amount' : null);
    if ($dateCol === null) {
        log_message("   ✗ Sin columna de fecha en equipments");
    } else {
        foreach ($branch_ids as $bid) {
            $where = [];
            $where[] = "{$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
            if ($bid > 0 && column_exists($conn, 'equipments', 'branch_id')) {
                $where[] = "branch_id = {$bid}";
            }
            $whereSql = 'WHERE ' . implode(' AND ', $where);
            $sumExpr = $valueCol ? "SUM({$valueCol})" : '0';
            $sql = "
                SELECT DATE_FORMAT({$dateCol}, '%Y-%m') as mes,
                       COUNT(*) as cantidad,
                       {$sumExpr} as valor_total
                FROM equipments
                {$whereSql}
                GROUP BY DATE_FORMAT({$dateCol}, '%Y-%m')
                ORDER BY mes
            ";
            $result = $conn->query($sql);
            $data = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free();
            }
            cache_put($conn, cache_key_for_branch('monthly_equipment', $bid), $data);
        }
        log_message("   ✓ Adquisición equipos: global + " . (count($branch_ids) - 1) . " sucursales");
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// Verificar estado final
$result = $conn->query("SELECT cache_key, updated_at FROM dashboard_cache ORDER BY updated_at DESC");
log_message("\n=== ESTADO CACHE ===");
while ($row = $result->fetch_assoc()) {
    log_message("  {$row['cache_key']}: {$row['updated_at']}");
}

log_message("\n=== FIN ACTUALIZACIÓN CACHE ===");
$conn->close();
