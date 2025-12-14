<?php
/**
 * Verificación de Límites de Mantenimiento
 * Muestra estadísticas sobre la distribución de eventos
 */

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
    die("<h1>Error de conexión:</h1><p>" . $conn->connect_error . "</p>");
}

// Cargar límites
$config_file = __DIR__ . '/config/maintenance_limits.php';
$config = file_exists($config_file) ? include($config_file) : [];
$max_limit = $config['max_events_per_day'] ?? 20;
$warning_threshold = round($max_limit * (($config['warning_threshold_percent'] ?? 80) / 100));

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Verificación de Límites de Mantenimiento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 5px; border-left: 4px solid #007bff; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-value { font-size: 32px; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-danger { color: #dc3545; font-weight: bold; }
        .config-box { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
<div class='container'>
    <h1>📊 Verificación de Límites de Mantenimiento</h1>
    <p><strong>Fecha de verificación:</strong> " . date('Y-m-d H:i:s') . "</p>
";

// Estadísticas generales
$total_query = $conn->query("SELECT COUNT(*) as total FROM mantenimientos");
$total_events = $total_query ? $total_query->fetch_assoc()['total'] : 0;

$unique_dates = $conn->query("SELECT COUNT(DISTINCT fecha_programada) as total FROM mantenimientos");
$total_dates = $unique_dates ? $unique_dates->fetch_assoc()['total'] : 0;

$avg_query = $conn->query("SELECT AVG(eventos) as promedio FROM (SELECT COUNT(*) as eventos FROM mantenimientos GROUP BY fecha_programada) as counts");
$avg_per_day = $avg_query ? round($avg_query->fetch_assoc()['promedio'], 2) : 0;

$exceeding = $conn->query("
    SELECT COUNT(*) as total 
    FROM (
        SELECT fecha_programada, COUNT(*) as eventos 
        FROM mantenimientos 
        GROUP BY fecha_programada
        HAVING eventos > $max_limit
    ) as over_limit
");
$exceeding_count = $exceeding ? $exceeding->fetch_assoc()['total'] : 0;

echo "<div class='stats'>
    <div class='stat-card'>
        <div class='stat-value'>$total_events</div>
        <div class='stat-label'>Total de Mantenimientos</div>
    </div>
    <div class='stat-card'>
        <div class='stat-value'>$total_dates</div>
        <div class='stat-label'>Fechas Únicas</div>
    </div>
    <div class='stat-card'>
        <div class='stat-value'>$avg_per_day</div>
        <div class='stat-label'>Promedio por Día</div>
    </div>
    <div class='stat-card " . ($exceeding_count > 0 ? 'danger' : '') . "'>
        <div class='stat-value'>$exceeding_count</div>
        <div class='stat-label'>Fechas Excediendo Límite</div>
    </div>
</div>";

echo "<div class='config-box'>
    <h3>⚙️ Configuración Actual</h3>
    <p><strong>Límite máximo por día:</strong> $max_limit eventos</p>
    <p><strong>Umbral de advertencia:</strong> $warning_threshold eventos (" . ($config['warning_threshold_percent'] ?? 80) . "%)</p>
    <p><strong>Archivo de configuración:</strong> <code>config/maintenance_limits.php</code></p>
</div>";

// Fechas problemáticas
echo "<h2>🚨 Fechas con Mayor Cantidad de Eventos</h2>";
$problem_dates = $conn->query("
    SELECT 
        DATE(fecha_programada) as fecha,
        COUNT(*) as total,
        DAYNAME(fecha_programada) as dia_semana
    FROM mantenimientos
    GROUP BY DATE(fecha_programada)
    HAVING total >= $warning_threshold
    ORDER BY total DESC
    LIMIT 30
");

if ($problem_dates && $problem_dates->num_rows > 0) {
    echo "<table>
        <tr>
            <th>Fecha</th>
            <th>Día de la Semana</th>
            <th>Cantidad</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>";
    
    while ($row = $problem_dates->fetch_assoc()) {
        $status_class = 'status-ok';
        $status_text = 'Normal';
        
        if ($row['total'] >= $max_limit) {
            $status_class = 'status-danger';
            $status_text = '⚠ EXCEDE LÍMITE';
        } elseif ($row['total'] >= $warning_threshold) {
            $status_class = 'status-warning';
            $status_text = '⚠ Cerca del límite';
        }
        
        echo "<tr>
            <td>{$row['fecha']}</td>
            <td>{$row['dia_semana']}</td>
            <td><strong>{$row['total']}</strong> / $max_limit</td>
            <td class='$status_class'>$status_text</td>
            <td><a href='manage_calendar_events.php?date={$row['fecha']}'>Gestionar</a></td>
        </tr>";
    }
    
    echo "</table>";
} else {
    echo "<p class='status-ok'>✓ No hay fechas problemáticas detectadas</p>";
}

// Distribución por mes
echo "<h2>📅 Distribución por Mes</h2>";
$monthly = $conn->query("
    SELECT 
        DATE_FORMAT(fecha_programada, '%Y-%m') as mes,
        COUNT(*) as total,
        COUNT(DISTINCT fecha_programada) as dias,
        ROUND(COUNT(*) / COUNT(DISTINCT fecha_programada), 2) as promedio_dia
    FROM mantenimientos
    WHERE fecha_programada >= '2025-01-01'
    GROUP BY DATE_FORMAT(fecha_programada, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 24
");

if ($monthly && $monthly->num_rows > 0) {
    echo "<table>
        <tr>
            <th>Mes</th>
            <th>Total Eventos</th>
            <th>Días con Eventos</th>
            <th>Promedio/Día</th>
        </tr>";
    
    while ($row = $monthly->fetch_assoc()) {
        echo "<tr>
            <td>{$row['mes']}</td>
            <td>{$row['total']}</td>
            <td>{$row['dias']}</td>
            <td>{$row['promedio_dia']}</td>
        </tr>";
    }
    
    echo "</table>";
}

echo "<hr style='margin: 40px 0;'>
<p><strong>Acciones Disponibles:</strong></p>
<ul>
    <li><a href='manage_calendar_events.php'>Gestionar Eventos de Calendario</a></li>
    <li><a href='index.php?page=calendar'>Ver Calendario de Mantenimientos</a></li>
    <li>Editar límites: <code>config/maintenance_limits.php</code></li>
</ul>

</div>
</body>
</html>";

$conn->close();
?>
