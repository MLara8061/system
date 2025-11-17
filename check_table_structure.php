<?php
$conn = new mysqli('localhost', 'root', '', 'system');

// Obtener estructura de la tabla mantenimientos
$result = $conn->query("DESCRIBE mantenimientos");
echo "=== ESTRUCTURA DE TABLA mantenimientos ===\n";
while ($row = $result->fetch_assoc()) {
    echo "Campo: {$row['Field']}, Tipo: {$row['Type']}\n";
}

// Obtener estructura de otras tablas relacionadas
$tables = [
    'equipment_control_documents',
    'equipment_reception',
    'equipment_delivery',
    'equipment_safeguard',
    'equipment_revision',
    'equipment_unsubscribe',
    'equipment_power_specs'
];

foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "\n=== ESTRUCTURA DE TABLA $table ===\n";
        while ($row = $result->fetch_assoc()) {
            echo "Campo: {$row['Field']}\n";
        }
    }
}
?>
