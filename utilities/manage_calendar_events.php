<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);
ini_set('memory_limit', '256M');

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

echo "<h1>Gestión de Mantenimientos por Fecha</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Obtener fecha del parámetro o usar 25 dic por defecto
$target_date = $_GET['date'] ?? '2025-12-25';
$year_month = substr($target_date, 0, 7); // 2025-12

// 1. Mostrar distribución general
echo "<h2>1. Distribución de Mantenimientos - 2025-2026</h2>";
$distribution = $conn->query("
    SELECT DATE(fecha_programada) as fecha, COUNT(*) as total
    FROM mantenimientos 
    WHERE fecha_programada BETWEEN '2025-01-01' AND '2026-12-31'
    GROUP BY DATE(fecha_programada)
    ORDER BY total DESC
    LIMIT 50
");

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Fecha</th><th>Cantidad</th><th>Día de la Semana</th><th>Acción</th></tr>";

$problematic = [];
while ($row = $distribution->fetch_assoc()) {
    $date_obj = new DateTime($row['fecha']);
    $day_name = $date_obj->format('l'); // Nombre del día
    $day_es = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes', 
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $is_target = ($row['fecha'] == $target_date);
    $style = $is_target ? "background: #fff3cd; font-weight: bold;" : "";
    $alert_style = $row['total'] > 50 ? "color: red;" : "";
    
    if ($row['total'] > 50) {
        $problematic[] = $row['fecha'];
    }
    
    echo "<tr style='$style'>";
    echo "<td>{$row['fecha']}</td>";
    echo "<td style='text-align: center; $alert_style'><strong>{$row['total']}</strong></td>";
    echo "<td>{$day_es[$day_name]}</td>";
    
    if ($row['total'] > 10) {
        echo "<td><a href='?date={$row['fecha']}' style='color: #dc3545;'>Ver detalles</a></td>";
    } else {
        echo "<td style='color: green;'>Normal</td>";
    }
    echo "</tr>";
}
echo "</table>";

if (count($problematic) > 0) {
    echo "<p style='color: red;'><strong>⚠ Fechas con más de 50 eventos:</strong> " . implode(', ', $problematic) . "</p>";
}

// 2. Detalles de la fecha seleccionada
echo "<h2>2. Detalles de la Fecha: $target_date</h2>";
$details = $conn->query("SELECT COUNT(*) as total FROM mantenimientos WHERE fecha_programada = '$target_date'");
$total_target = $details->fetch_assoc()['total'];

$date_obj = new DateTime($target_date);
$day_formatted = $date_obj->format('d/m/Y');

echo "<p>Total de mantenimientos en <strong>$day_formatted</strong>: ";
echo "<strong style='font-size: 20px; color: " . ($total_target > 50 ? "red" : "green") . ";'>$total_target</strong></p>";

if ($total_target > 0) {
    // Mostrar distribución por tipo
    $by_type = $conn->query("
        SELECT tipo_mantenimiento, COUNT(*) as total
        FROM mantenimientos 
        WHERE fecha_programada = '$target_date'
        GROUP BY tipo_mantenimiento
    ");
    
    echo "<h3>Por Tipo de Mantenimiento:</h3>";
    echo "<ul>";
    while ($row = $by_type->fetch_assoc()) {
        echo "<li><strong>{$row['tipo_mantenimiento']}:</strong> {$row['total']}</li>";
    }
    echo "</ul>";
    
    // Mostrar ejemplos
    echo "<h3>Ejemplos de Registros:</h3>";
    $examples = $conn->query("
        SELECT m.id, m.equipo_id, m.tipo_mantenimiento, m.hora_programada, 
               m.estatus, e.name as equipo
        FROM mantenimientos m
        LEFT JOIN equipments e ON m.equipo_id = e.id
        WHERE m.fecha_programada = '$target_date'
        ORDER BY m.id
        LIMIT 10
    ");
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Equipo ID</th><th>Equipo</th><th>Tipo</th><th>Hora</th><th>Estatus</th></tr>";
    
    while ($row = $examples->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['equipo_id']}</td>";
        echo "<td>" . htmlspecialchars(substr($row['equipo'] ?? 'N/A', 0, 30)) . "</td>";
        echo "<td>{$row['tipo_mantenimiento']}</td>";
        echo "<td>{$row['hora_programada']}</td>";
        echo "<td>{$row['estatus']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($total_target > 10) {
        echo "<p><em>... y " . ($total_target - 10) . " registros más</em></p>";
    }
}

// 3. Opción de eliminación
if ($total_target > 0) {
    if (!isset($_GET['confirm'])) {
        echo "<h2>3. Eliminar Mantenimientos de esta Fecha</h2>";
        
        echo "<div style='margin: 20px 0; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 5px;'>";
        echo "<p style='font-size: 18px;'><strong>⚠ ATENCIÓN:</strong></p>";
        echo "<p>Se eliminarán <strong style='color: red; font-size: 20px;'>$total_target registros</strong> de la fecha $day_formatted.</p>";
        echo "<p>Esta acción NO se puede deshacer.</p>";
        echo "</div>";
        
        echo "<p style='margin-top: 30px;'>";
        echo "<a href='?date=$target_date&confirm=yes' style='padding: 15px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold; margin-right: 15px;'>SÍ, ELIMINAR $total_target REGISTROS</a>";
        echo "<a href='?' style='padding: 15px 30px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; font-size: 16px;'>Ver Otra Fecha</a>";
        echo "</p>";
        
    } else {
        echo "<h2>3. Ejecutando Eliminación...</h2>";
        echo "<p>Procesando $total_target registros...</p>";
        
        // Eliminación en lotes para grandes cantidades
        $deleted = 0;
        $batch_size = 1000;
        $max_iterations = ceil($total_target / $batch_size);
        
        for ($i = 0; $i < $max_iterations; $i++) {
            $delete_query = "DELETE FROM mantenimientos WHERE fecha_programada = '$target_date' LIMIT $batch_size";
            $result = $conn->query($delete_query);
            
            if ($result) {
                $deleted += $conn->affected_rows;
                echo "<p>Lote " . ($i + 1) . ": " . $conn->affected_rows . " registros eliminados (Total: $deleted)</p>";
                flush();
            } else {
                echo "<p style='color: red;'>Error en lote " . ($i + 1) . ": " . $conn->error . "</p>";
                break;
            }
            
            if ($conn->affected_rows == 0) {
                break; // No hay más registros
            }
        }
        
        if ($deleted > 0) {
            
            echo "<div style='margin: 20px 0; padding: 20px; background: #d4edda; border: 2px solid #28a745; border-radius: 5px;'>";
            echo "<p style='color: green; font-size: 20px;'><strong>✓ Eliminación Exitosa</strong></p>";
            echo "<p style='font-size: 16px;'>Registros eliminados de $day_formatted: <strong>$deleted</strong></p>";
            echo "</div>";
            
            // Verificar
            $after = $conn->query("SELECT COUNT(*) as total FROM mantenimientos WHERE fecha_programada = '$target_date'");
            $total_after = $after->fetch_assoc()['total'];
            
            if ($total_after == 0) {
                echo "<p style='color: green; font-size: 18px;'><strong>✓ La fecha está completamente limpia</strong></p>";
            } else {
                echo "<p style='color: orange;'>⚠ Aún quedan $total_after registros</p>";
            }
            
            echo "<hr>";
            echo "<p style='font-size: 16px;'>";
            echo "<a href='?' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Ver Distribución Actualizada</a>";
            echo "<a href='index.php?page=calendario' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Ver Calendario</a>";
            echo "</p>";
            
        } else {
            echo "<div style='margin: 20px 0; padding: 20px; background: #f8d7da; border: 2px solid #dc3545; border-radius: 5px;'>";
            echo "<p style='color: red; font-size: 18px;'><strong>✗ Error al Eliminar</strong></p>";
            echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
            echo "</div>";
        }
    }
}

$conn->close();
?>
