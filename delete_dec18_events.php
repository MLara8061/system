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

echo "<h1>Eliminar Mantenimientos del 18 de Diciembre 2025</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

$target_date = '2025-12-18';

// Verificar antes
echo "<h2>1. Estado Antes de Eliminar</h2>";
$before = $conn->query("SELECT COUNT(*) as total FROM mantenimientos WHERE fecha_programada = '$target_date'");
$total_before = $before->fetch_assoc()['total'];
echo "<p>Mantenimientos en $target_date: <strong style='color: red; font-size: 18px;'>$total_before</strong></p>";

if ($total_before == 0) {
    echo "<p style='color: green;'>✓ No hay eventos para eliminar en esa fecha.</p>";
    echo "<p><a href='index.php?page=calendar'>← Volver al calendario</a></p>";
    exit;
}

// Mostrar algunos ejemplos
echo "<h3>Ejemplos de registros a eliminar:</h3>";
$examples = $conn->query("
    SELECT m.id, m.equipo_id, m.tipo_mantenimiento, m.estatus, e.name as equipo
    FROM mantenimientos m
    LEFT JOIN equipments e ON m.equipo_id = e.id
    WHERE m.fecha_programada = '$target_date'
    LIMIT 10
");

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Equipo ID</th><th>Equipo</th><th>Tipo</th><th>Estatus</th></tr>";
while ($row = $examples->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['equipo_id']}</td>";
    echo "<td>" . htmlspecialchars(substr($row['equipo'] ?? 'N/A', 0, 30)) . "</td>";
    echo "<td>{$row['tipo_mantenimiento']}</td>";
    echo "<td>{$row['estatus']}</td>";
    echo "</tr>";
}
echo "</table>";

if ($total_before > 10) {
    echo "<p><em>... y " . ($total_before - 10) . " más</em></p>";
}

// Ejecutar eliminación
if (!isset($_GET['confirm'])) {
    echo "<h2>2. Confirmación Requerida</h2>";
    echo "<div style='margin: 20px 0; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 5px;'>";
    echo "<p style='font-size: 18px;'><strong>⚠ ATENCIÓN:</strong></p>";
    echo "<p>Se eliminarán <strong style='color: red; font-size: 20px;'>$total_before registros</strong> de la fecha $target_date.</p>";
    echo "<p>Esta acción NO se puede deshacer.</p>";
    echo "</div>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='?confirm=yes' style='padding: 15px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold; margin-right: 15px;'>SÍ, ELIMINAR $total_before REGISTROS</a>";
    echo "<a href='index.php?page=calendar' style='padding: 15px 30px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;'>Cancelar</a>";
    echo "</p>";
    
} else {
    echo "<h2>2. Ejecutando Eliminación...</h2>";
    
    // Eliminar
    $delete_query = "DELETE FROM mantenimientos WHERE fecha_programada = '$target_date'";
    echo "<p><strong>Query:</strong> <code>$delete_query</code></p>";
    
    $result = $conn->query($delete_query);
    
    if ($result) {
        $deleted = $conn->affected_rows;
        
        echo "<div style='margin: 20px 0; padding: 20px; background: #d4edda; border: 2px solid #28a745; border-radius: 5px;'>";
        echo "<p style='color: green; font-size: 20px;'><strong>✓ Eliminación Exitosa</strong></p>";
        echo "<p style='font-size: 16px;'>Registros eliminados: <strong>$deleted</strong></p>";
        echo "</div>";
        
        // Verificar después
        echo "<h2>3. Verificación Final</h2>";
        $after = $conn->query("SELECT COUNT(*) as total FROM mantenimientos WHERE fecha_programada = '$target_date'");
        $total_after = $after->fetch_assoc()['total'];
        
        echo "<p>Mantenimientos restantes en $target_date: <strong>$total_after</strong></p>";
        
        if ($total_after == 0) {
            echo "<p style='color: green; font-size: 18px;'><strong>✓ La fecha está completamente limpia</strong></p>";
        } else {
            echo "<p style='color: red;'>⚠ Aún quedan $total_after registros. Puede ser necesario ejecutar nuevamente.</p>";
        }
        
        // Total general
        $total_all = $conn->query("SELECT COUNT(*) as total FROM mantenimientos")->fetch_assoc()['total'];
        echo "<p>Total de mantenimientos en el sistema: <strong>$total_all</strong></p>";
        
        echo "<hr>";
        echo "<p style='font-size: 16px;'>";
        echo "<a href='index.php?page=calendar' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Ver Calendario Actualizado</a>";
        echo "</p>";
        
    } else {
        echo "<div style='margin: 20px 0; padding: 20px; background: #f8d7da; border: 2px solid #dc3545; border-radius: 5px;'>";
        echo "<p style='color: red; font-size: 18px;'><strong>✗ Error al Eliminar</strong></p>";
        echo "<p><strong>Error MySQL:</strong> " . $conn->error . "</p>";
        echo "<p><strong>Código de error:</strong> " . $conn->errno . "</p>";
        echo "</div>";
    }
}

$conn->close();
?>
