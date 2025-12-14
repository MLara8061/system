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

echo "<h1>Revisión de Mantenimientos - 18 de Diciembre 2025</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

$target_date = '2025-12-18';

// 1. Contar mantenimientos por fecha cercana
echo "<h2>1. Distribución de Mantenimientos (Diciembre 2025)</h2>";
$distribution = $conn->query("
    SELECT DATE(fecha_programada) as fecha, COUNT(*) as total, 
           GROUP_CONCAT(DISTINCT tipo_mantenimiento) as tipos,
           GROUP_CONCAT(DISTINCT estatus) as estados
    FROM mantenimientos 
    WHERE fecha_programada BETWEEN '2025-12-01' AND '2025-12-31'
    GROUP BY DATE(fecha_programada)
    ORDER BY fecha
");

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Fecha</th><th>Total</th><th>Tipos</th><th>Estados</th><th>Alerta</th></tr>";

$max_count = 0;
$problematic_dates = [];

while ($row = $distribution->fetch_assoc()) {
    $is_target = ($row['fecha'] == $target_date);
    $style = $is_target ? "background: #fff3cd; font-weight: bold;" : "";
    $alert = "";
    
    if ($row['total'] > 50) {
        $alert = "⚠ Cantidad anormal";
        $problematic_dates[] = $row['fecha'];
    }
    
    if ($row['total'] > $max_count) {
        $max_count = $row['total'];
    }
    
    echo "<tr style='$style'>";
    echo "<td>{$row['fecha']}</td>";
    echo "<td style='text-align: center;'><strong>{$row['total']}</strong></td>";
    echo "<td>{$row['tipos']}</td>";
    echo "<td>{$row['estados']}</td>";
    echo "<td style='color: orange;'>$alert</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Detalles del 18 de diciembre
echo "<h2>2. Detalles de Mantenimientos - $target_date</h2>";
$details = $conn->query("
    SELECT m.id, m.equipo_id, m.tipo_mantenimiento, m.hora_programada, 
           m.descripcion, m.estatus, e.name as equipo_nombre,
           m.created_at
    FROM mantenimientos m
    LEFT JOIN equipments e ON m.equipo_id = e.id
    WHERE m.fecha_programada = '$target_date'
    ORDER BY m.id
");

$total_target = $details->num_rows;
echo "<p>Total de mantenimientos en esta fecha: <strong style='font-size: 18px; color: " . ($total_target > 50 ? "red" : "green") . ";'>$total_target</strong></p>";

if ($total_target > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Equipo ID</th><th>Equipo</th><th>Tipo</th><th>Hora</th><th>Estatus</th><th>Creado</th></tr>";
    
    $count = 0;
    $limit = 50; // Mostrar solo los primeros 50
    
    while ($row = $details->fetch_assoc()) {
        $count++;
        if ($count <= $limit) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['equipo_id']}</td>";
            echo "<td>" . htmlspecialchars(substr($row['equipo_nombre'] ?? 'N/A', 0, 30)) . "</td>";
            echo "<td>{$row['tipo_mantenimiento']}</td>";
            echo "<td>{$row['hora_programada']}</td>";
            echo "<td>{$row['estatus']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
    }
    
    if ($total_target > $limit) {
        echo "<tr><td colspan='7' style='text-align: center; background: #f0f0f0;'>";
        echo "<em>... y " . ($total_target - $limit) . " registros más</em>";
        echo "</td></tr>";
    }
    
    echo "</table>";
}

// 3. Buscar duplicados
echo "<h2>3. Análisis de Duplicados</h2>";
$duplicates = $conn->query("
    SELECT equipo_id, tipo_mantenimiento, COUNT(*) as repeticiones
    FROM mantenimientos 
    WHERE fecha_programada = '$target_date'
    GROUP BY equipo_id, tipo_mantenimiento
    HAVING COUNT(*) > 1
    ORDER BY repeticiones DESC
");

$dup_count = $duplicates->num_rows;
if ($dup_count > 0) {
    echo "<p style='color: red;'>⚠ Encontrados: <strong>$dup_count</strong> casos de duplicados</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Equipo ID</th><th>Tipo</th><th>Repeticiones</th></tr>";
    
    while ($row = $duplicates->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['equipo_id']}</td>";
        echo "<td>{$row['tipo_mantenimiento']}</td>";
        echo "<td style='color: red; text-align: center;'><strong>{$row['repeticiones']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✓ No se encontraron duplicados obvios</p>";
}

// 4. Opciones de limpieza
if (!isset($_GET['action'])) {
    echo "<h2>4. Opciones de Limpieza</h2>";
    
    echo "<div style='margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;'>";
    echo "<p><strong>⚠ ATENCIÓN:</strong></p>";
    echo "<p>Se eliminarán TODOS los mantenimientos programados para el $target_date.</p>";
    echo "<p>Total a eliminar: <strong style='color: red;'>$total_target registros</strong></p>";
    echo "</div>";
    
    echo "<p>";
    echo "<a href='?action=delete_date&date=$target_date' onclick=\"return confirm('¿Estás seguro de eliminar $total_target mantenimientos del $target_date?');\" style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Eliminar Todos ($target_date)</a>";
    echo "<a href='index.php?page=calendar' style='padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;'>Cancelar</a>";
    echo "</p>";
    
} elseif ($_GET['action'] == 'delete_date') {
    $date_to_delete = $_GET['date'] ?? $target_date;
    
    echo "<h2>4. Ejecutando Eliminación...</h2>";
    
    $result = $conn->query("DELETE FROM mantenimientos WHERE fecha_programada = '$date_to_delete'");
    
    if ($result) {
        $deleted = $conn->affected_rows;
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Eliminación completada</strong></p>";
        echo "<p>Registros eliminados: <strong>$deleted</strong></p>";
        
        // Verificar
        $remaining = $conn->query("SELECT COUNT(*) as total FROM mantenimientos WHERE fecha_programada = '$date_to_delete'")->fetch_assoc()['total'];
        
        if ($remaining == 0) {
            echo "<p style='color: green;'>✓ No quedan mantenimientos para esa fecha</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Aún quedan $remaining registros</p>";
        }
        
        echo "<p><a href='index.php?page=calendar'>Ver calendario actualizado</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Error al eliminar: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php?page=calendar'>← Volver al calendario</a></p>";

$conn->close();
?>
