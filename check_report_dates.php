<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración
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
    die("Error de conexión: " . $conn->connect_error);
}

echo "<h1>Análisis de Fechas en maintenance_reports</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #007bff; color: white; font-weight: bold; }
tr:hover { background: #f5f5f5; }
.info-box { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #007bff; }
.warning-box { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
.success-box { background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745; }
.danger-box { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc3545; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>
<div class='container'>";

// 1. Rango de fechas que busca la gráfica
$start_service = date('Y-m-01', strtotime('-11 months'));
$end_date = date('Y-m-d');

echo "<div class='info-box'>";
echo "<h2>📅 Rango de Fechas que Busca la Gráfica</h2>";
echo "<p><strong>Fecha inicio:</strong> $start_service</p>";
echo "<p><strong>Fecha fin:</strong> $end_date</p>";
echo "<p><strong>Explicación:</strong> La gráfica busca reportes de los últimos 12 meses (desde " . date('M Y', strtotime($start_service)) . " hasta hoy)</p>";
echo "</div>";

// 2. Fechas reales en la tabla
echo "<h2>📊 Fechas Reales en la Tabla maintenance_reports</h2>";

$query = "SELECT 
    report_date,
    STR_TO_DATE(report_date, '%Y-%m-%d') as fecha_convertida,
    service_type,
    execution_type,
    COUNT(*) as cantidad
FROM maintenance_reports 
GROUP BY report_date, service_type, execution_type
ORDER BY report_date DESC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr>
        <th>report_date (original)</th>
        <th>Fecha Convertida</th>
        <th>Service Type</th>
        <th>Execution Type</th>
        <th>Cantidad</th>
        <th>¿En rango?</th>
    </tr>";
    
    $dentro_rango = 0;
    $fuera_rango = 0;
    
    while ($row = $result->fetch_assoc()) {
        $fecha_conv = $row['fecha_convertida'];
        $en_rango = ($fecha_conv >= $start_service && $fecha_conv <= $end_date);
        
        if ($en_rango) {
            $dentro_rango += $row['cantidad'];
        } else {
            $fuera_rango += $row['cantidad'];
        }
        
        $rango_style = $en_rango ? "color: green; font-weight: bold;" : "color: red;";
        $rango_text = $en_rango ? "✓ SÍ" : "✗ NO";
        
        echo "<tr>";
        echo "<td><code>{$row['report_date']}</code></td>";
        echo "<td>{$fecha_conv}</td>";
        echo "<td><strong>{$row['service_type']}</strong></td>";
        echo "<td>{$row['execution_type']}</td>";
        echo "<td>{$row['cantidad']}</td>";
        echo "<td style='$rango_style'>$rango_text</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Resumen
    if ($dentro_rango > 0) {
        echo "<div class='success-box'>";
        echo "<h3>✓ Hay $dentro_rango registros dentro del rango</h3>";
        echo "<p>Las gráficas DEBERÍAN mostrar datos.</p>";
        echo "</div>";
    } else {
        echo "<div class='danger-box'>";
        echo "<h3>✗ NO hay registros dentro del rango de fechas</h3>";
        echo "<p>Por eso las gráficas están vacías.</p>";
        echo "</div>";
    }
    
    if ($fuera_rango > 0) {
        echo "<div class='warning-box'>";
        echo "<h3>⚠ Hay $fuera_rango registros fuera del rango</h3>";
        echo "<p>Estos registros no aparecen en las gráficas porque están fuera del período de últimos 12 meses.</p>";
        echo "</div>";
    }
    
} else {
    echo "<div class='danger-box'><p>No se encontraron registros o hubo un error en la consulta.</p></div>";
}

// 3. Distribución por mes
echo "<h2>📈 Distribución por Mes (Todos los registros)</h2>";

$monthly = $conn->query("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(report_date, '%Y-%m-%d'), '%Y-%m') as mes,
        service_type,
        COUNT(*) as total
    FROM maintenance_reports
    GROUP BY mes, service_type
    ORDER BY mes DESC, service_type
");

if ($monthly && $monthly->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Mes</th><th>Service Type</th><th>Cantidad</th></tr>";
    
    while ($row = $monthly->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$row['mes']}</strong></td>";
        echo "<td>{$row['service_type']}</td>";
        echo "<td>{$row['total']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay datos para mostrar.</p>";
}

// 4. Consulta SQL exacta que usa la gráfica
echo "<h2>🔍 Consulta SQL que Usa la Gráfica</h2>";

echo "<div class='info-box'>";
echo "<h3>Gráfica de Tipo de Servicio (Donut):</h3>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
$sql1 = "SELECT service_type, COUNT(*) as total 
FROM maintenance_reports 
WHERE STR_TO_DATE(report_date, '%Y-%m-%d') >= '$start_service'
GROUP BY service_type 
ORDER BY total DESC";
echo htmlspecialchars($sql1);
echo "</pre>";

// Ejecutar la consulta
$test_result = $conn->query($sql1);
if ($test_result && $test_result->num_rows > 0) {
    echo "<h4>Resultado de esta consulta:</h4>";
    echo "<ul>";
    while ($row = $test_result->fetch_assoc()) {
        echo "<li><strong>{$row['service_type']}:</strong> {$row['total']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>Esta consulta NO devuelve resultados</strong> - Por eso la gráfica está vacía.</p>";
}
echo "</div>";

echo "<div class='info-box'>";
echo "<h3>Gráfica Mensual (Líneas):</h3>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
$sql2 = "SELECT 
    DATE_FORMAT(STR_TO_DATE(report_date, '%Y-%m-%d'), '%Y-%m') as month,
    service_type,
    COUNT(*) as total
FROM maintenance_reports
WHERE STR_TO_DATE(report_date, '%Y-%m-%d') >= '$start_service'
GROUP BY month, service_type
ORDER BY month ASC";
echo htmlspecialchars($sql2);
echo "</pre>";

// Ejecutar la consulta
$test_result2 = $conn->query($sql2);
if ($test_result2 && $test_result2->num_rows > 0) {
    echo "<h4>Resultado de esta consulta:</h4>";
    echo "<ul>";
    while ($row = $test_result2->fetch_assoc()) {
        echo "<li><strong>{$row['month']} - {$row['service_type']}:</strong> {$row['total']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>Esta consulta NO devuelve resultados</strong> - Por eso la gráfica está vacía.</p>";
}
echo "</div>";

// 5. Recomendaciones
echo "<hr>";
echo "<h2>💡 Qué Hacer Ahora</h2>";
echo "<ul>";
echo "<li>Si los registros están fuera del rango de 12 meses, considera ampliar el rango en el código del dashboard</li>";
echo "<li>Si necesitas que las gráficas muestren datos históricos, modifica la fecha de inicio en <code>home.php</code></li>";
echo "<li>Si los registros tienen fechas incorrectas, corrígelas en la tabla <code>maintenance_reports</code></li>";
echo "<li>Para ver TODOS los registros sin importar fecha, puedes quitar la condición WHERE temporalmente</li>";
echo "</ul>";

echo "</div>";
$conn->close();
?>
