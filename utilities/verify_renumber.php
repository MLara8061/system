<?php
require_once __DIR__ . '/../config/config.php';

echo "=== Verificación de renumeración ===\n\n";

$res = $conn->query('SELECT id, name, number_inventory, inventario_anterior FROM equipments WHERE equipment_category_id IS NOT NULL ORDER BY id LIMIT 15');

echo sprintf("%-5s %-35s %-20s %-15s\n", "ID", "Equipo", "Nuevo Inventario", "Anterior");
echo str_repeat("-", 80) . "\n";

while($r = $res->fetch_assoc()) {
    echo sprintf("%-5d %-35s %-20s %-15s\n", 
        $r['id'], 
        substr($r['name'], 0, 33), 
        $r['number_inventory'], 
        $r['inventario_anterior'] ?: 'N/A'
    );
}
