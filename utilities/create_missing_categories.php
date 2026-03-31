<?php
/**
 * Crea categorías faltantes desde los valores únicos de discipline
 * 
 * Uso:
 *   php utilities/create_missing_categories.php                # dry-run (muestra lo que crearía)
 *   php utilities/create_missing_categories.php --apply        # crea las categorías
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

$root = dirname(__DIR__);
if (!defined('ROOT')) {
    define('ROOT', $root);
}

try {
    require_once ROOT . '/config/config.php';
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base de datos.\n");
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
echo "=== Crear categorías faltantes ({$mode}) ===\n\n";

// Cargar categorías existentes
$existing = [];
$catRes = @$conn->query("SELECT clave, description FROM equipment_categories");
if ($catRes) {
    while ($cat = $catRes->fetch_assoc()) {
        $existing[strtoupper(trim($cat['description']))] = $cat['clave'];
    }
}

echo "Categorías existentes: " . count($existing) . "\n";
foreach ($existing as $desc => $clave) {
    echo "  [{$clave}] {$desc}\n";
}
echo "\n";

// Cargar disciplines únicos
$disciplines = [];
$res = $conn->query("
    SELECT DISTINCT TRIM(discipline) as discipline 
    FROM equipments 
    WHERE discipline IS NOT NULL AND TRIM(discipline) != ''
    ORDER BY discipline ASC
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $disciplines[] = trim($r['discipline']);
    }
}

echo "Disciplines encontrados: " . count($disciplines) . "\n\n";

// Generar claves para los faltantes
function generate_clave($description, $existing_claves) {
    $desc = strtoupper(trim($description));
    
    // Casos especiales / basura
    if (preg_match('/^\d+$/', $desc)) {
        return null; // Ignorar valores numéricos como "1234"
    }
    
    // Extraer primeras letras de cada palabra
    $words = preg_split('/\s+/', $desc);
    $clave = '';
    
    if (count($words) >= 2) {
        // Usar iniciales de las primeras palabras
        foreach (array_slice($words, 0, 3) as $word) {
            if (!empty($word)) {
                $clave .= substr($word, 0, 1);
            }
        }
    } else {
        // Una sola palabra: tomar primeras 3 letras
        $clave = substr($desc, 0, 3);
    }
    
    // Normalizar
    $clave = strtoupper(preg_replace('/[^A-Z0-9]/', '', $clave));
    if (strlen($clave) < 2) {
        $clave = substr($desc, 0, 3);
    }
    
    // Evitar duplicados
    $original = $clave;
    $suffix = 1;
    while (in_array($clave, $existing_claves)) {
        $clave = substr($original, 0, 2) . $suffix;
        $suffix++;
    }
    
    return substr($clave, 0, 3);
}

$to_create = [];
$existing_claves = array_values($existing);

foreach ($disciplines as $disc) {
    $disc_upper = strtoupper(trim($disc));
    
    // Ya existe?
    if (isset($existing[$disc_upper])) {
        continue;
    }
    
    $clave = generate_clave($disc, $existing_claves);
    
    if ($clave) {
        $to_create[] = [
            'clave' => $clave,
            'description' => $disc
        ];
        $existing_claves[] = $clave;
    }
}

echo "Categorías a crear: " . count($to_create) . "\n\n";

if (!empty($to_create)) {
    echo "Nuevas categorías:\n";
    foreach ($to_create as $cat) {
        echo "  [{$cat['clave']}] {$cat['description']}\n";
    }
    echo "\n";
}

// Aplicar si corresponde
if ($apply && !empty($to_create)) {
    echo "Creando categorías...\n";
    $stmt = $conn->prepare('INSERT INTO equipment_categories (clave, description, active) VALUES (?, ?, 1)');
    
    $created = 0;
    foreach ($to_create as $cat) {
        $stmt->bind_param('ss', $cat['clave'], $cat['description']);
        if ($stmt->execute()) {
            $created++;
        }
    }
    
    echo "Creadas: {$created} categorías\n";
} elseif (!$apply) {
    echo "[DRY-RUN] Usa --apply para crear las categorías.\n";
}

echo "\n=== Fin ===\n";
