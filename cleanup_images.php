<?php
require_once 'config/config.php';

// Script para limpiar referencias a imágenes huérfanas en equipments.image
// Uso: php cleanup_images.php

$logFile = __DIR__ . '/cleanup_images.log';
file_put_contents($logFile, "\n=== Cleanup run: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

$db = $conn; // from config
if (!$db) {
    echo "No DB connection\n";
    file_put_contents($logFile, "No DB connection\n", FILE_APPEND);
    exit(1);
}

$q = $db->query("SELECT id, image FROM equipments WHERE image IS NOT NULL AND TRIM(image) <> ''");
if (!$q) {
    echo "Query failed: " . $db->error . "\n";
    file_put_contents($logFile, "Query failed: " . $db->error . "\n", FILE_APPEND);
    exit(1);
}

$removed = 0;
$checked = 0;
$orphaned = [];

while ($row = $q->fetch_assoc()) {
    $checked++;
    $id = (int)$row['id'];
    $img = $row['image'];
    // Normalize path
    $imgPath = ltrim($img, '/\\');
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $imgPath;

    if (!file_exists($fullPath)) {
        // Registrar como huérfana y limpiar la referencia en BD
        $orphaned[] = ['id' => $id, 'image' => $img, 'checked_path' => $fullPath];
        $db->query("UPDATE equipments SET image = '' WHERE id = $id");
        $removed++;
        $msg = "Removed reference for equipment ID=$id, image='{$img}' (checked: $fullPath)\n";
        echo $msg;
        file_put_contents($logFile, $msg, FILE_APPEND);
    }
}

$summary = "Checked: $checked, References cleared: $removed\n";
echo "\n" . $summary;
file_put_contents($logFile, "\n" . $summary, FILE_APPEND);

if ($removed > 0) {
    echo "Detalles en: $logFile\n";
} else {
    echo "No se encontraron referencias huérfanas.\n";
}

?>