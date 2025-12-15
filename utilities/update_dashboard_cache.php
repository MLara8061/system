<?php
/**
 * Script para actualizar caché del dashboard
 * Se ejecuta vía cron cada hora: 0 * * * * /usr/bin/php /path/update_dashboard_cache.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutos - cron tiene más tiempo

define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/db_connect.php';

$log_file = ROOT . '/cache_update.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

log_message("=== INICIO ACTUALIZACIÓN CACHE ===");

// Crear tabla de caché si no existe
$conn->query("CREATE TABLE IF NOT EXISTS dashboard_cache (
    cache_key VARCHAR(100) PRIMARY KEY,
    cache_data JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// 1. Top Proveedores (con timeout extendido)
log_message("1. Actualizando top proveedores...");
try {
    $conn->query("SET SESSION max_execution_time=30000");
    $result = $conn->query("
        SELECT COALESCE(s.name, 'Sin Proveedor') as supplier, 
               COUNT(*) as cnt,
               (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM equipments)) as pct
        FROM equipments e 
        LEFT JOIN suppliers s ON e.supplier_id = s.id 
        GROUP BY s.id, s.name 
        ORDER BY cnt DESC LIMIT 5
    ");
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $json = json_encode($data);
        $conn->query("REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES ('top_suppliers', '$json')");
        log_message("   ✓ Top proveedores: " . count($data) . " registros");
    } else {
        log_message("   ✗ Error: " . $conn->error);
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 2. Equipos Recientes
log_message("2. Actualizando equipos recientes...");
try {
    $result = $conn->query("
        SELECT e.id, e.number_inventory, e.name, 
               COALESCE(s.name, 'Sin proveedor') as supplier, 
               e.amount, e.revision 
        FROM equipments e 
        LEFT JOIN suppliers s ON e.supplier_id = s.id 
        ORDER BY e.id DESC LIMIT 5
    ");
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $json = json_encode($data);
        $conn->query("REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES ('recent_equipments', '$json')");
        log_message("   ✓ Equipos recientes: " . count($data) . " registros");
    } else {
        log_message("   ✗ Error: " . $conn->error);
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 3. Distribución de Proveedores (para pie chart)
log_message("3. Actualizando distribución proveedores...");
try {
    $result = $conn->query("
        SELECT COALESCE(s.name, 'Sin Proveedor') as supplier, COUNT(*) as cnt
        FROM equipments e 
        LEFT JOIN suppliers s ON e.supplier_id = s.id 
        GROUP BY s.id, s.name 
        ORDER BY cnt DESC LIMIT 5
    ");
    
    if ($result) {
        $labels = [];
        $values = [];
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['supplier'];
            $values[] = (int)$row['cnt'];
        }
        $data = ['labels' => $labels, 'values' => $values];
        $json = json_encode($data);
        $conn->query("REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES ('pie_suppliers', '$json')");
        log_message("   ✓ Pie proveedores: " . count($labels) . " categorías");
    } else {
        log_message("   ✗ Error: " . $conn->error);
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 4. Mantenimientos por Tipo
log_message("4. Actualizando mantenimientos...");
try {
    $result = $conn->query("SELECT type, COUNT(*) as total FROM maintenance_reports GROUP BY type");
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['type']] = (int)$row['total'];
        }
        $json = json_encode($data);
        $conn->query("REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES ('maintenance_counts', '$json')");
        log_message("   ✓ Mantenimientos: MP=" . ($data['MP'] ?? 0) . ", MC=" . ($data['MC'] ?? 0));
    } else {
        log_message("   ✗ Error: " . $conn->error);
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 5. Ejecución Mensual (últimos 6 meses)
log_message("5. Actualizando ejecución mensual...");
try {
    $start_date = date('Y-m-01', strtotime('-5 months'));
    $result = $conn->query("
        SELECT DATE_FORMAT(date, '%Y-%m') as mes, type, COUNT(*) as total
        FROM maintenance_reports 
        WHERE date >= '$start_date'
        GROUP BY DATE_FORMAT(date, '%Y-%m'), type
        ORDER BY mes
    ");
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $json = json_encode($data);
        $conn->query("REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES ('monthly_maintenance', '$json')");
        log_message("   ✓ Ejecución mensual: " . count($data) . " registros");
    } else {
        log_message("   ✗ Error: " . $conn->error);
    }
} catch (Exception $e) {
    log_message("   ✗ Exception: " . $e->getMessage());
}

// 6. Adquisición de Equipos Mensual
log_message("6. Actualizando adquisición equipos...");
try {
    $result = $conn->query("
        SELECT DATE_FORMAT(purchase_date, '%Y-%m') as mes, 
               COUNT(*) as cantidad, 
               SUM(price) as valor_total
        FROM equipments 
        WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
        ORDER BY mes
    ");
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $json = json_encode($data);
        $conn->query("REPLACE INTO dashboard_cache (cache_key, cache_data) VALUES ('monthly_equipment', '$json')");
        log_message("   ✓ Adquisición equipos: " . count($data) . " meses");
    } else {
        log_message("   ✗ Error: " . $conn->error);
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
