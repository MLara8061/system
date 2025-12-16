<?php
require_once __DIR__ . '/../config/config.php';

echo "=== Estado de categorías en equipos ===\n\n";

$total = $conn->query('SELECT COUNT(*) as n FROM equipments')->fetch_assoc()['n'];
echo "Total equipos: {$total}\n";

$con_cat = $conn->query('SELECT COUNT(*) as n FROM equipments WHERE equipment_category_id IS NOT NULL AND equipment_category_id > 0')->fetch_assoc()['n'];
echo "Con categoría asignada: {$con_cat}\n";

$sin_cat = $total - $con_cat;
echo "Sin categoría: {$sin_cat}\n\n";

if ($con_cat > 0) {
    echo "Distribución por categoría:\n";
    $dist = $conn->query('SELECT equipment_category_id, COUNT(*) as cant FROM equipments WHERE equipment_category_id IS NOT NULL AND equipment_category_id > 0 GROUP BY equipment_category_id ORDER BY cant DESC');
    while ($r = $dist->fetch_assoc()) {
        $cat_id = $r['equipment_category_id'];
        $cant = $r['cant'];
        $cat_info = $conn->query("SELECT clave, description FROM equipment_categories WHERE id = {$cat_id}")->fetch_assoc();
        $cat_name = $cat_info ? "{$cat_info['clave']} - {$cat_info['description']}" : "ID {$cat_id}";
        echo "  {$cat_name}: {$cant} equipos\n";
    }
}
