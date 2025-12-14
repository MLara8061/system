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

if ($conn->connect_error) {
    die("<h1>Error de conexión:</h1><p>" . $conn->connect_error . "</p>");
}

echo "<h1>Limpiar Referencias a Imágenes Inexistentes</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Encontrar registros con imágenes que no existen
echo "<h2>1. Buscando Referencias a Archivos Inexistentes</h2>";

$query = $conn->query("SELECT id, name, image FROM equipments WHERE image IS NOT NULL AND image != ''");
$missing_files = [];

while ($row = $query->fetch_assoc()) {
    $file_path = __DIR__ . '/' . $row['image'];
    if (!file_exists($file_path)) {
        $missing_files[] = $row;
    }
}

$total_missing = count($missing_files);
echo "<p>Encontrados: <strong>$total_missing</strong> registros con imágenes faltantes</p>";

if ($total_missing > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Ruta en BD</th><th>Estado</th></tr>";
    
    foreach ($missing_files as $item) {
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>" . htmlspecialchars($item['name']) . "</td>";
        echo "<td>{$item['image']}</td>";
        echo "<td style='color: red;'>✗ Archivo no existe</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Opción de limpieza
    echo "<h2>2. Opciones de Limpieza</h2>";
    
    if (!isset($_GET['confirm'])) {
        echo "<p>Se puede:</p>";
        echo "<ol>";
        echo "<li><strong>Eliminar referencias</strong>: Poner <code>image = NULL</code> en estos registros (recomendado)</li>";
        echo "<li><strong>Mantener referencias</strong>: Dejar las rutas en BD y que el frontend muestre placeholder</li>";
        echo "</ol>";
        
        echo "<div style='margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<p><strong>⚠ ATENCIÓN:</strong></p>";
        echo "<p>Si eliminas las referencias, los equipos ya no tendrán ruta de imagen en la BD.</p>";
        echo "<p>El frontend mostrará el icono de placeholder automáticamente.</p>";
        echo "</div>";
        
        echo "<p>";
        echo "<a href='?confirm=yes' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Eliminar Referencias</a>";
        echo "<a href='verify_images.php' style='padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>Cancelar</a>";
        echo "</p>";
    } else {
        // Ejecutar limpieza
        echo "<h2>3. Ejecutando Limpieza...</h2>";
        
        $cleaned = 0;
        foreach ($missing_files as $item) {
            $id = $item['id'];
            $result = $conn->query("UPDATE equipments SET image = NULL WHERE id = $id");
            if ($result) {
                $cleaned++;
                echo "<p style='color: green;'>✓ Limpiado equipo ID $id: {$item['name']}</p>";
            } else {
                echo "<p style='color: red;'>✗ Error al limpiar equipo ID $id: " . $conn->error . "</p>";
            }
        }
        
        echo "<h2>4. Resumen</h2>";
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Limpieza completada</strong></p>";
        echo "<ul>";
        echo "<li>Registros encontrados: <strong>$total_missing</strong></li>";
        echo "<li>Registros limpiados: <strong>$cleaned</strong></li>";
        echo "</ul>";
        
        echo "<p><a href='verify_images.php'>Ver verificación actualizada</a></p>";
    }
} else {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ No hay referencias a imágenes inexistentes</strong></p>";
    echo "<p>Todas las rutas en la BD apuntan a archivos que existen.</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=equipment_list'>← Volver a lista de equipos</a></p>";
echo "<p><a href='verify_images.php'>Verificar archivos</a></p>";

$conn->close();
?>
