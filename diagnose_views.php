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

echo "<h1>Diagnóstico de Vistas</h1>";

// 1. Verificar conexión
echo "<h2>1. Conexión a BD</h2>";
if ($conn) {
    echo "✓ Conexión establecida<br>";
} else {
    echo "✗ Error de conexión<br>";
    die();
}

// 2. Verificar tabla mantenimientos
echo "<h2>2. Tabla mantenimientos</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'mantenimientos'");
if ($check_table && $check_table->num_rows > 0) {
    echo "✓ Tabla 'mantenimientos' existe<br>";
    
    // Verificar estructura
    $cols = $conn->query("SHOW COLUMNS FROM mantenimientos");
    echo "<strong>Columnas:</strong> ";
    $col_names = [];
    while($col = $cols->fetch_assoc()) {
        $col_names[] = $col['Field'];
    }
    echo implode(', ', $col_names) . "<br>";
    
    // Contar registros
    $count = $conn->query("SELECT COUNT(*) as total FROM mantenimientos")->fetch_assoc()['total'];
    echo "<strong>Total registros:</strong> $count<br>";
    
    // Mostrar algunos ejemplos
    if ($count > 0) {
        echo "<strong>Ejemplos (primeros 5):</strong><br>";
        $sample = $conn->query("SELECT id, equipo_id, fecha_programada, tipo_mantenimiento, estatus FROM mantenimientos LIMIT 5");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Equipo ID</th><th>Fecha</th><th>Tipo</th><th>Estatus</th></tr>";
        while($row = $sample->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['equipo_id']}</td>";
            echo "<td>{$row['fecha_programada']}</td>";
            echo "<td>{$row['tipo_mantenimiento']}</td>";
            echo "<td>{$row['estatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "✗ Tabla 'mantenimientos' NO existe<br>";
}

// 3. Verificar estructura de tabla equipments
echo "<h2>3. Estructura de tabla equipments</h2>";
$cols = $conn->query("SHOW COLUMNS FROM equipments");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
while($col = $cols->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Verificar query de equipos con estructura real
echo "<h2>4. Query de lista de equipos</h2>";
$start_time = microtime(true);

// Usar date_created que es la única columna de fecha que existe
$date_column = 'date_created';

echo "<strong>Columna de fecha usada:</strong> $date_column<br>";

$sql = "SELECT e.*, 
        IFNULL(s.empresa, 'Sin Proveedor') as supplier_name,
        DATEDIFF(CURDATE(), e.$date_column) AS antiguedad_dias
        FROM equipments e
        LEFT JOIN suppliers s ON e.supplier_id = s.id
        LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id
        WHERE u.id IS NULL
        ORDER BY e.id DESC
        LIMIT 10";

echo "<strong>SQL:</strong><br><pre>" . htmlspecialchars($sql) . "</pre>";

$qry = $conn->query($sql);
$exec_time = microtime(true) - $start_time;

if ($qry) {
    echo "✓ Query ejecutado correctamente<br>";
    echo "<strong>Tiempo de ejecución:</strong> " . number_format($exec_time, 4) . " segundos<br>";
    echo "<strong>Registros:</strong> " . $qry->num_rows . "<br>";
    
    if ($qry->num_rows > 0) {
        echo "<strong>Primer registro:</strong><br>";
        $first = $qry->fetch_assoc();
        echo "<pre>" . print_r($first, true) . "</pre>";
    }
} else {
    echo "✗ Error en query: " . $conn->error . "<br>";
}

// 5. Test endpoint get_mantenimientos
echo "<h2>5. Test get_mantenimientos</h2>";
$start = date('Y-m-01');
$end = date('Y-m-d', strtotime('+12 months'));

$sql_mant = "SELECT m.id, m.equipo_id, m.fecha_programada, m.hora_programada, m.tipo_mantenimiento, 
              m.descripcion, m.estatus, e.name
              FROM mantenimientos m
              JOIN equipments e ON m.equipo_id = e.id
              LEFT JOIN equipment_unsubscribe u ON u.equipment_id = e.id
              WHERE m.fecha_programada BETWEEN '$start' AND '$end'
              AND (u.date IS NULL OR m.fecha_programada < u.date)
              ORDER BY m.fecha_programada
              LIMIT 10";

echo "<strong>SQL mantenimientos:</strong><br><pre>" . htmlspecialchars($sql_mant) . "</pre>";

$start_time = microtime(true);
$qry_mant = $conn->query($sql_mant);
$exec_time = microtime(true) - $start_time;

if ($qry_mant) {
    echo "✓ Query mantenimientos ejecutado<br>";
    echo "<strong>Tiempo de ejecución:</strong> " . number_format($exec_time, 4) . " segundos<br>";
    echo "<strong>Registros:</strong> " . $qry_mant->num_rows . "<br>";
    
    if ($qry_mant->num_rows > 0) {
        echo "<strong>Primeros eventos:</strong><br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Equipo</th><th>Fecha</th><th>Tipo</th><th>Estatus</th></tr>";
        while($row = $qry_mant->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['fecha_programada']}</td>";
            echo "<td>{$row['tipo_mantenimiento']}</td>";
            echo "<td>{$row['estatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<em>No hay mantenimientos programados en el rango de fechas</em><br>";
    }
} else {
    echo "✗ Error en query mantenimientos: " . $conn->error . "<br>";
}

// 6. Verificar llamada AJAX directa
echo "<h2>6. Test llamada directa a get_mantenimientos</h2>";
echo "<a href='ajax.php?action=get_mantenimientos' target='_blank'>Ver respuesta JSON</a><br>";

// 7. Verificar rutas de imágenes
echo "<h2>7. Rutas de imágenes en equipments</h2>";
$img_check = $conn->query("SELECT id, name, image FROM equipments WHERE image IS NOT NULL AND image != '' LIMIT 5");
if ($img_check) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Ruta en BD</th><th>Ruta Completa</th></tr>";
    while($row = $img_check->fetch_assoc()) {
        $full_path = 'uploads/' . basename($row['image']);
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['image']}</td>";
        echo "<td>$full_path</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Diagnóstico completado</h2>";
?>
