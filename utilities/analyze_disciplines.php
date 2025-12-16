<?php
require_once __DIR__ . '/../config/config.php';

echo "=== Análisis de disciplines en equipos ===\n\n";

$disciplines = [];
$res = $conn->query("
    SELECT discipline, COUNT(*) as cant 
    FROM equipments 
    WHERE discipline IS NOT NULL AND TRIM(discipline) != ''
    GROUP BY discipline 
    ORDER BY cant DESC, discipline ASC
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $disciplines[] = $r;
    }
}

echo "Total de disciplines únicos: " . count($disciplines) . "\n\n";
echo "Distribución:\n";
foreach ($disciplines as $d) {
    echo sprintf("  %-30s : %3d equipos\n", $d['discipline'], $d['cant']);
}
