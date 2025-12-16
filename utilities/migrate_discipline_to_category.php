<?php
/**
 * Migra el campo discipline a equipment_category_id
 * 
 * Lee equipments.discipline y busca la categoría correspondiente en equipment_categories.description,
 * luego asigna el equipment_category_id.
 * 
 * Uso:
 *   php utilities/migrate_discipline_to_category.php                # dry-run
 *   php utilities/migrate_discipline_to_category.php --apply        # aplica cambios
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
if (!defined('ROOT')) {
    define('ROOT', $root);
}

try {
    require_once ROOT . '/config/config.php';
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base de datos.\n");
    fwrite(STDERR, "Detalle: " . $e->getMessage() . "\n");
    exit(2);
}

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "No hay conexión a la base de datos.\n");
    exit(2);
}

$apply = false;
foreach ($argv as $arg) {
    if ($arg === '--apply') $apply = true;
}

$mode = $apply ? 'APPLY' : 'DRY-RUN';
echo "=== Migración discipline → equipment_category_id ({$mode}) ===\n\n";

// Cargar categorías
$categories = [];
$catRes = @$conn->query("SELECT id, clave, description FROM equipment_categories ORDER BY id ASC");
if ($catRes) {
    while ($cat = $catRes->fetch_assoc()) {
        $categories[] = $cat;
    }
}

if (empty($categories)) {
    echo "No hay categorías definidas en equipment_categories.\n";
    exit(0);
}

echo "Categorías disponibles:\n";
foreach ($categories as $cat) {
    echo "  [{$cat['id']}] {$cat['clave']} - {$cat['description']}\n";
}
echo "\n";

// Cargar equipos sin categoría pero con discipline
$equipos = [];
$eqRes = @$conn->query("
    SELECT id, discipline, equipment_category_id 
    FROM equipments 
    WHERE (equipment_category_id IS NULL OR equipment_category_id = 0)
      AND discipline IS NOT NULL 
      AND TRIM(discipline) != ''
    ORDER BY id ASC
");

if ($eqRes) {
    while ($eq = $eqRes->fetch_assoc()) {
        $equipos[] = $eq;
    }
}

if (empty($equipos)) {
    echo "No hay equipos con discipline sin categoría asignada.\n";
    exit(0);
}

echo "Equipos a migrar: " . count($equipos) . "\n\n";

// Mapear discipline → category_id
$mappings = [];
$no_match = [];

foreach ($equipos as $eq) {
    $id = (int)$eq['id'];
    $discipline = trim((string)$eq['discipline']);
    
    // Buscar categoría que coincida (exacta o parcial)
    $matched_cat_id = null;
    
    // 1. Buscar coincidencia exacta por description
    foreach ($categories as $cat) {
        if (strcasecmp(trim($cat['description']), $discipline) === 0) {
            $matched_cat_id = (int)$cat['id'];
            break;
        }
    }
    
    // 2. Si no hay match exacto, buscar coincidencia parcial
    if (!$matched_cat_id) {
        foreach ($categories as $cat) {
            $desc = trim($cat['description']);
            if (stripos($discipline, $desc) !== false || stripos($desc, $discipline) !== false) {
                $matched_cat_id = (int)$cat['id'];
                break;
            }
        }
    }
    
    if ($matched_cat_id) {
        $mappings[] = [
            'equipment_id' => $id,
            'discipline' => $discipline,
            'category_id' => $matched_cat_id
        ];
    } else {
        $no_match[] = [
            'equipment_id' => $id,
            'discipline' => $discipline
        ];
    }
}

echo "Mapeos encontrados: " . count($mappings) . "\n";
echo "Sin coincidencia: " . count($no_match) . "\n\n";

if (!empty($mappings)) {
    echo "Primeros 10 mapeos:\n";
    foreach (array_slice($mappings, 0, 10) as $m) {
        $cat_info = null;
        foreach ($categories as $cat) {
            if ($cat['id'] == $m['category_id']) {
                $cat_info = $cat;
                break;
            }
        }
        $cat_label = $cat_info ? "{$cat_info['clave']} - {$cat_info['description']}" : "ID {$m['category_id']}";
        echo "  Equipo #{$m['equipment_id']}: '{$m['discipline']}' → {$cat_label}\n";
    }
    echo "\n";
}

if (!empty($no_match)) {
    echo "Equipos sin coincidencia (primeros 10):\n";
    foreach (array_slice($no_match, 0, 10) as $nm) {
        echo "  Equipo #{$nm['equipment_id']}: '{$nm['discipline']}'\n";
    }
    echo "\n";
}

// Aplicar si corresponde
if ($apply && !empty($mappings)) {
    echo "Aplicando cambios...\n";
    $conn->query('START TRANSACTION');
    
    $updated = 0;
    $stmt = $conn->prepare('UPDATE equipments SET equipment_category_id = ? WHERE id = ?');
    
    foreach ($mappings as $m) {
        $stmt->bind_param('ii', $m['category_id'], $m['equipment_id']);
        if ($stmt->execute()) {
            $updated++;
        }
    }
    
    $conn->query('COMMIT');
    echo "Actualizados: {$updated} equipos\n";
} elseif (!$apply) {
    echo "[DRY-RUN] Usa --apply para aplicar los cambios.\n";
}

echo "\n=== Fin ===\n";
