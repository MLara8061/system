<?php
require_once 'config/config.php';

$log = __DIR__ . '/list_orphan_images.log';
file_put_contents($log, "\n=== List orphan images run: " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

echo "Buscando referencias en BD (tabla equipments.image)...\n\n";
file_put_contents($log, "Searching DB references (equipments.image)\n", FILE_APPEND);

$db = $conn;
// 1) Referencias en BD que no existen en disco
$q = $db->query("SELECT id, image FROM equipments WHERE image IS NOT NULL AND TRIM(image) <> ''");
$missing_refs = [];
while ($r = $q->fetch_assoc()) {
    $img = $r['image'];
    $path = __DIR__ . DIRECTORY_SEPARATOR . ltrim($img, '/\\');
    if (!file_exists($path)) {
        $missing_refs[] = ['id' => $r['id'], 'image' => $img, 'checked_path' => $path];
        file_put_contents($log, "MISSING REF: id={$r['id']}, image={$img}, checked={$path}\n", FILE_APPEND);
    }
}

if (count($missing_refs) === 0) {
    echo "No hay referencias rotas en equipments.image.\n\n";
    file_put_contents($log, "No broken references found in equipments.image\n", FILE_APPEND);
} else {
    echo "Referencias rotas encontradas en equipments.image:\n";
    foreach ($missing_refs as $m) {
        echo " - equipment id={$m['id']}, image={$m['image']}, checked={$m['checked_path']}\n";
    }
    echo "\n";
}

// 2) Archivos en uploads/equipment no referenciados por la BD (reverse)
$uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'equipment';
$unreferenced = [];
if (is_dir($uploadsDir)) {
    $files = array_values(array_filter(scandir($uploadsDir), function($f){ return !in_array($f,['.','..']); }));
    echo "Buscando archivos en $uploadsDir que no estén referenciados...\n";
    file_put_contents($log, "Scanning directory: $uploadsDir\n", FILE_APPEND);
    foreach ($files as $f) {
        $full = $uploadsDir . DIRECTORY_SEPARATOR . $f;
        if (!is_file($full)) continue;
        // buscar si existe referencia en equipments.image (contiene filename)
        $escaped = $db->real_escape_string($f);
        $res = $db->query("SELECT id, image FROM equipments WHERE image LIKE '%" . $escaped . "%' LIMIT 1");
        if ($res->num_rows == 0) {
            $unreferenced[] = $full;
            file_put_contents($log, "UNREFERENCED FILE: $full\n", FILE_APPEND);
        }
    }
    if (count($unreferenced) === 0) {
        echo "No hay archivos huérfanos en uploads/equipment.\n";
    } else {
        echo "Archivos en disco no referenciados por la BD (uploads/equipment):\n";
        foreach ($unreferenced as $u) echo " - $u\n";
    }
} else {
    echo "Directorio $uploadsDir no existe.\n";
}

file_put_contents($log, "\nSummary: missing_refs=" . count($missing_refs) . ", unreferenced_files=" . count($unreferenced) . "\n", FILE_APPEND);

echo "\nList saved to: $log\n";

?>