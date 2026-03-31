<?php
// Script temporal para verificar estado de audit_logs
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$pdo = get_pdo();
if (!$pdo) { echo "ERROR: PDO es null\n"; exit(1); }

// Contar registros
$r = $pdo->query("SELECT COUNT(*) as c FROM audit_logs");
$total = $r->fetch(PDO::FETCH_ASSOC)['c'];
echo "Total registros en audit_logs: $total\n";

if ($total > 0) {
    echo "\nUltimos 5 registros:\n";
    $r2 = $pdo->query("SELECT id, created_at, user_name, module, action, table_name, record_id FROM audit_logs ORDER BY id DESC LIMIT 5");
    foreach ($r2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  [{$row['id']}] {$row['created_at']} | {$row['user_name']} | {$row['module']}.{$row['action']} | {$row['table_name']} #{$row['record_id']}\n";
    }
    
    echo "\nRango de fechas disponible:\n";
    $r3 = $pdo->query("SELECT MIN(created_at) as min_date, MAX(created_at) as max_date FROM audit_logs");
    $range = $r3->fetch(PDO::FETCH_ASSOC);
    echo "  Desde: {$range['min_date']}\n";
    echo "  Hasta: {$range['max_date']}\n";
} else {
    echo "\nLa tabla audit_logs esta VACIA. No hay registros de auditoria aun.\n";
    echo "Los registros se generan automaticamente cuando se crean, editan o eliminan datos.\n";
}
