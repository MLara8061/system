<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar variables de entorno
$env_file = __DIR__ . '/config/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (trim($line) === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'equipment_db';

$conn = new mysqli($host, $user, $pass, $name);

echo "<h1>Verificación de Archivos vs Base de Datos</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Listar archivos físicos en uploads/
echo "<h2>1. Archivos Físicos en uploads/</h2>";
$uploads_dir = __DIR__ . '/uploads/';
$physical_files = glob($uploads_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
echo "<p>Total: <strong>" . count($physical_files) . "</strong> archivos</p>";

if (count($physical_files) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>#</th><th>Archivo</th><th>Ruta Completa</th><th>Tamaño</th></tr>";
    foreach ($physical_files as $index => $file) {
        $filename = basename($file);
        $size = filesize($file);
        $size_kb = round($size / 1024, 2);
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>$filename</td>";
        echo "<td>uploads/$filename</td>";
        echo "<td>{$size_kb} KB</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Archivos en uploads/equipment/
echo "<h2>2. Archivos en uploads/equipment/ (si existe)</h2>";
$equipment_dir = __DIR__ . '/uploads/equipment/';
if (is_dir($equipment_dir)) {
    $equipment_files = glob($equipment_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    echo "<p>Total: <strong>" . count($equipment_files) . "</strong> archivos</p>";
    
    if (count($equipment_files) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>#</th><th>Archivo</th><th>Acción Sugerida</th></tr>";
        foreach ($equipment_files as $index => $file) {
            $filename = basename($file);
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>$filename</td>";
            echo "<td style='color: orange;'>⚠ Debe moverse a uploads/</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><a href='move_equipment_images.php' style='color: red; font-weight: bold;'>→ Ejecutar mover archivos nuevamente</a></p>";
    } else {
        echo "<p style='color: green;'>✓ No hay archivos (directorio vacío o no existe)</p>";
    }
} else {
    echo "<p style='color: green;'>✓ El directorio uploads/equipment/ no existe</p>";
}

// 3. Rutas en BD
echo "<h2>3. Rutas en Base de Datos</h2>";
$db_images = $conn->query("SELECT id, name, image FROM equipments WHERE image IS NOT NULL AND image != '' ORDER BY id DESC");
$total_db = $db_images->num_rows;
echo "<p>Total equipos con imagen en BD: <strong>$total_db</strong></p>";

if ($total_db > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Ruta en BD</th><th>Archivo Físico</th><th>Estado</th></tr>";
    
    while ($row = $db_images->fetch_assoc()) {
        $db_path = $row['image'];
        $full_path = __DIR__ . '/' . $db_path;
        $exists = file_exists($full_path);
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($row['name'], 0, 30)) . "</td>";
        echo "<td>$db_path</td>";
        echo "<td>" . basename($db_path) . "</td>";
        
        if ($exists) {
            $size = filesize($full_path);
            $size_kb = round($size / 1024, 2);
            echo "<td style='color: green;'>✓ Existe ({$size_kb} KB)</td>";
        } else {
            echo "<td style='color: red;'>✗ NO EXISTE</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Archivos huérfanos (en disco pero no en BD)
echo "<h2>4. Archivos Huérfanos (en disco pero no en BD)</h2>";
$db_filenames = [];
$db_images->data_seek(0);
while ($row = $db_images->fetch_assoc()) {
    $db_filenames[] = basename($row['image']);
}

$orphaned = [];
foreach ($physical_files as $file) {
    $filename = basename($file);
    if (!in_array($filename, $db_filenames)) {
        $orphaned[] = $filename;
    }
}

if (count($orphaned) > 0) {
    echo "<p style='color: orange;'>Encontrados: <strong>" . count($orphaned) . "</strong> archivos huérfanos</p>";
    echo "<ul>";
    foreach ($orphaned as $orphan) {
        echo "<li>$orphan</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✓ No hay archivos huérfanos</p>";
}

// 5. Resumen y acciones
echo "<h2>5. Resumen</h2>";
echo "<ul>";
echo "<li>Archivos físicos en uploads/: <strong>" . count($physical_files) . "</strong></li>";
echo "<li>Registros en BD con imagen: <strong>$total_db</strong></li>";
echo "<li>Archivos huérfanos: <strong>" . count($orphaned) . "</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php?page=equipment_list'>← Volver a lista de equipos</a></p>";
echo "<p><a href='migrate_image_paths.php'>Migrar rutas en BD</a> | ";
echo "<a href='move_equipment_images.php'>Mover archivos físicos</a></p>";
?>
