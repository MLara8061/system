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

// Conexión directa
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'equipment_db';

$conn = new mysqli($host, $user, $pass, $name);

if ($conn->connect_error) {
    die("<h1>Error de conexión:</h1><p>" . $conn->connect_error . "</p>");
}

echo "<h1>Migración de Rutas de Imágenes</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Ver estado actual
echo "<h2>1. Estado Actual</h2>";
$check = $conn->query("SELECT COUNT(*) as total FROM equipments WHERE image IS NOT NULL AND image != ''");
$total_con_imagen = $check->fetch_assoc()['total'];
echo "<p>Total equipos con imagen: <strong>$total_con_imagen</strong></p>";

$check_wrong = $conn->query("SELECT COUNT(*) as total FROM equipments WHERE image LIKE '%/%' AND image NOT LIKE 'uploads/%'");
$total_rutas_incorrectas = $check_wrong->fetch_assoc()['total'];
echo "<p>Rutas con subdirectorios incorrectos: <strong>$total_rutas_incorrectas</strong></p>";

$check_equipment = $conn->query("SELECT COUNT(*) as total FROM equipments WHERE image LIKE 'uploads/equipment/%'");
$total_equipment = $check_equipment->fetch_assoc()['total'];
echo "<p>Rutas con 'uploads/equipment/': <strong>$total_equipment</strong></p>";

// 2. Mostrar ejemplos antes
echo "<h2>2. Ejemplos de Rutas Antes de Migración</h2>";
$examples = $conn->query("SELECT id, name, image FROM equipments WHERE image IS NOT NULL AND image != '' ORDER BY id DESC LIMIT 10");
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Ruta Actual</th></tr>";
while($row = $examples->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['image']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Ejecutar migración
echo "<h2>3. Ejecutando Migración...</h2>";

// Migrar rutas con subdirectorio equipment
$sql1 = "UPDATE equipments 
         SET image = CONCAT('uploads/', SUBSTRING_INDEX(image, '/', -1))
         WHERE image LIKE '%/equipment/%' OR image LIKE 'uploads/equipment/%'";

$result1 = $conn->query($sql1);
if ($result1) {
    $affected1 = $conn->affected_rows;
    echo "<p>✓ Rutas 'equipment' actualizadas: <strong>$affected1</strong></p>";
} else {
    echo "<p>✗ Error al actualizar rutas equipment: " . $conn->error . "</p>";
}

// Migrar cualquier otra ruta con subdirectorios
$sql2 = "UPDATE equipments 
         SET image = CONCAT('uploads/', SUBSTRING_INDEX(image, '/', -1))
         WHERE image LIKE '%/%' 
         AND image NOT LIKE 'uploads/%'
         AND image IS NOT NULL 
         AND image != ''";

$result2 = $conn->query($sql2);
if ($result2) {
    $affected2 = $conn->affected_rows;
    echo "<p>✓ Otras rutas con subdirectorios actualizadas: <strong>$affected2</strong></p>";
} else {
    echo "<p>✗ Error al actualizar otras rutas: " . $conn->error . "</p>";
}

// Normalizar rutas que no empiecen con uploads/
$sql3 = "UPDATE equipments 
         SET image = CONCAT('uploads/', image)
         WHERE image NOT LIKE 'uploads/%'
         AND image IS NOT NULL 
         AND image != ''
         AND image NOT LIKE '%/%'";

$result3 = $conn->query($sql3);
if ($result3) {
    $affected3 = $conn->affected_rows;
    echo "<p>✓ Rutas sin prefijo 'uploads/' actualizadas: <strong>$affected3</strong></p>";
} else {
    echo "<p>✗ Error al normalizar rutas: " . $conn->error . "</p>";
}

// 4. Mostrar ejemplos después
echo "<h2>4. Ejemplos de Rutas Después de Migración</h2>";
$examples_after = $conn->query("SELECT id, name, image FROM equipments WHERE image IS NOT NULL AND image != '' ORDER BY id DESC LIMIT 10");
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Ruta Actualizada</th><th>Archivo Existe</th></tr>";
while($row = $examples_after->fetch_assoc()) {
    $file_path = __DIR__ . '/' . $row['image'];
    $exists = file_exists($file_path) ? '✓ Sí' : '✗ No';
    $color = file_exists($file_path) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['image']) . "</td>";
    echo "<td style='color: $color;'><strong>$exists</strong></td>";
    echo "</tr>";
}
echo "</table>";

// 5. Verificación final
echo "<h2>5. Verificación Final</h2>";
$final_check = $conn->query("SELECT COUNT(*) as total FROM equipments WHERE image LIKE 'uploads/equipment/%'");
$remaining = $final_check->fetch_assoc()['total'];

if ($remaining == 0) {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ Migración completada exitosamente</strong></p>";
    echo "<p>Todas las rutas han sido actualizadas correctamente.</p>";
} else {
    echo "<p style='color: orange;'><strong>⚠ Aún quedan $remaining rutas por actualizar</strong></p>";
}

$total_after = $conn->query("SELECT COUNT(*) as total FROM equipments WHERE image IS NOT NULL AND image != ''")->fetch_assoc()['total'];
echo "<p>Total equipos con imagen después: <strong>$total_after</strong></p>";

// Resumen
echo "<h2>6. Resumen</h2>";
echo "<ul>";
echo "<li>Registros actualizados (equipment): <strong>" . ($affected1 ?? 0) . "</strong></li>";
echo "<li>Registros actualizados (otros subdirs): <strong>" . ($affected2 ?? 0) . "</strong></li>";
echo "<li>Registros normalizados: <strong>" . ($affected3 ?? 0) . "</strong></li>";
echo "<li>Total de cambios: <strong>" . (($affected1 ?? 0) + ($affected2 ?? 0) + ($affected3 ?? 0)) . "</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php?page=equipment_list'>← Volver a lista de equipos</a></p>";

$conn->close();
?>
