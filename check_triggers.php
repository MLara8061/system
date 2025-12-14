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

echo "<h1>Diagnóstico de Recreación Automática de Mantenimientos</h1>";
echo "<hr>";

// 1. Verificar triggers
echo "<h2>1. Triggers de Base de Datos</h2>";
$triggers = $conn->query("SHOW TRIGGERS");
if ($triggers && $triggers->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Trigger</th><th>Evento</th><th>Tabla</th><th>Timing</th></tr>";
    while ($row = $triggers->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Trigger']}</td>";
        echo "<td>{$row['Event']}</td>";
        echo "<td>{$row['Table']}</td>";
        echo "<td>{$row['Timing']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✓ No hay triggers configurados</p>";
}

// 2. Verificar eventos programados (scheduled events)
echo "<h2>2. Eventos Programados (MySQL Events)</h2>";
$events = $conn->query("SHOW EVENTS");
if ($events && $events->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Nombre</th><th>Estado</th><th>Intervalo</th><th>Última Ejecución</th></tr>";
    while ($row = $events->fetch_assoc()) {
        echo "<tr style='background: #fff3cd;'>";
        echo "<td><strong>{$row['Name']}</strong></td>";
        echo "<td>{$row['Status']}</td>";
        echo "<td>{$row['Interval_value']} {$row['Interval_field']}</td>";
        echo "<td>" . ($row['Last_executed'] ?? 'Nunca') . "</td>";
        echo "</tr>";
        
        // Obtener definición del evento
        $def = $conn->query("SHOW CREATE EVENT `{$row['Name']}`");
        if ($def && $def_row = $def->fetch_assoc()) {
            echo "<tr><td colspan='4'><pre>" . htmlspecialchars($def_row['Create Event']) . "</pre></td></tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✓ No hay eventos programados en MySQL</p>";
}

// 3. Verificar si el event_scheduler está activo
echo "<h2>3. Estado del Event Scheduler</h2>";
$scheduler = $conn->query("SHOW VARIABLES LIKE 'event_scheduler'");
if ($scheduler && $row = $scheduler->fetch_assoc()) {
    $status = $row['Value'];
    $color = $status === 'ON' ? 'orange' : 'green';
    echo "<p style='color: $color;'><strong>Event Scheduler:</strong> $status</p>";
    if ($status === 'ON') {
        echo "<p style='background: #fff3cd; padding: 10px;'>⚠ El scheduler está activo. Si hay eventos programados, se ejecutarán automáticamente.</p>";
    }
}

// 4. Buscar archivos cron o tareas programadas
echo "<h2>4. Archivos de Tareas Programadas</h2>";
$cron_files = ['cron.php', 'cronjob.php', 'scheduled.php', 'scheduler.php', 'auto_maintenance.php'];
$found_files = [];
foreach ($cron_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $found_files[] = $file;
    }
}

if (!empty($found_files)) {
    echo "<p style='color: orange;'>⚠ Archivos encontrados:</p><ul>";
    foreach ($found_files as $file) {
        echo "<li><a href='$file' target='_blank'>$file</a></li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✓ No se encontraron archivos de cron estándar</p>";
}

// 5. Buscar llamadas a generate_maintenance o similar
echo "<h2>5. Funciones de Generación Automática</h2>";
echo "<p>Buscando funciones que generan mantenimientos automáticamente...</p>";

$generate_functions = [
    'generate_preventive_maintenances',
    'auto_generate_maintenances', 
    'create_automatic_maintenances',
    'schedule_maintenances'
];

echo "<ul>";
foreach ($generate_functions as $func) {
    $found = shell_exec("cd " . escapeshellarg(__DIR__) . " && grep -r \"function $func\" --include=\"*.php\" 2>nul");
    if ($found) {
        echo "<li style='color: orange;'>⚠ Encontrada: <strong>$func</strong><pre>" . htmlspecialchars($found) . "</pre></li>";
    }
}
echo "</ul>";

// 6. Verificar cuándo fueron creados los registros problemáticos
echo "<h2>6. Análisis de Registros Recreados</h2>";
$recent = $conn->query("
    SELECT 
        DATE(created_at) as fecha_creacion,
        COUNT(*) as total,
        MIN(created_at) as primera,
        MAX(created_at) as ultima
    FROM mantenimientos
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY created_at DESC
");

if ($recent && $recent->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Fecha Creación</th><th>Cantidad</th><th>Primera</th><th>Última</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['fecha_creacion']}</td>";
        echo "<td><strong>{$row['total']}</strong></td>";
        echo "<td>{$row['primera']}</td>";
        echo "<td>{$row['ultima']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='background: #e7f3ff; padding: 10px;'>💡 <strong>Pista:</strong> Si ves muchos registros creados en la misma fecha/hora, probablemente hay un proceso automático ejecutándose.</p>";
}

// 7. Verificar si hay algún proceso que llame a generate_preventive_maintenances en login/home
echo "<h2>7. Verificar Llamadas en Archivos Principales</h2>";
$main_files = ['index.php', 'home.php', 'login.php', 'ajax.php'];
foreach ($main_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (strpos($content, 'generate_preventive_maintenances') !== false) {
            echo "<p style='color: red;'>❌ <strong>$file</strong> llama a generate_preventive_maintenances()</p>";
        }
    }
}

$conn->close();

echo "<hr>";
echo "<h2>Recomendaciones</h2>";
echo "<ul>";
echo "<li>Si encuentras eventos MySQL activos, desactívalos con: <code>DROP EVENT nombre_evento;</code></li>";
echo "<li>Si hay archivos cron, revisa su contenido y desactívalos si generan mantenimientos</li>";
echo "<li>Revisa el código de <code>legacy/admin_class.php</code> para ver dónde se llama a la generación automática</li>";
echo "</ul>";
?>
