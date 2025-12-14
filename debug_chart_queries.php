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

echo "<h1>Debug de Consultas de Gráficas</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
th { background: #007bff; color: white; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
.error { background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0; }
.success { background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0; }
.info { background: #d1ecf1; padding: 15px; border-radius: 5px; color: #0c5460; margin: 10px 0; }
</style>
<div class='container'>";

// Simular diferentes períodos
$periods = [
    '6m' => ['name' => '6 Meses', 'start' => date('Y-m-01', strtotime('-5 months'))],
    '12m' => ['name' => '12 Meses', 'start' => date('Y-m-01', strtotime('-11 months'))],
    'year' => ['name' => 'Este Año', 'start' => date('Y-01-01')],
    'all' => ['name' => 'Todo', 'start' => '2000-01-01']
];

echo "<h2>Verificar Formatos de Fecha en report_date</h2>";
$sample = $conn->query("SELECT report_date FROM maintenance_reports LIMIT 5");
if ($sample && $sample->num_rows > 0) {
    echo "<table><tr><th>report_date Original</th><th>Tipo</th></tr>";
    while ($row = $sample->fetch_assoc()) {
        $type = 'varchar';
        echo "<tr><td><code>{$row['report_date']}</code></td><td>$type</td></tr>";
    }
    echo "</table>";
}

foreach ($periods as $key => $period) {
    echo "<hr><h2>Período: {$period['name']} (period=$key)</h2>";
    echo "<div class='info'><strong>Fecha inicio:</strong> {$period['start']}</div>";
    
    // Query 1: Sin STR_TO_DATE (original)
    echo "<h3>Query 1: Comparación directa (report_date >= ...)</h3>";
    $sql1 = "SELECT service_type, COUNT(*) as total 
             FROM maintenance_reports 
             WHERE report_date >= '{$period['start']}'
             GROUP BY service_type 
             ORDER BY total DESC";
    echo "<pre>$sql1</pre>";
    
    $result1 = $conn->query($sql1);
    if ($result1) {
        if ($result1->num_rows > 0) {
            echo "<div class='success'>✓ Resultados: {$result1->num_rows} tipos encontrados</div>";
            echo "<ul>";
            while ($row = $result1->fetch_assoc()) {
                echo "<li><strong>{$row['service_type']}:</strong> {$row['total']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='error'>✗ No devuelve resultados</div>";
        }
    } else {
        echo "<div class='error'>✗ Error SQL: " . $conn->error . "</div>";
    }
    
    // Query 2: Con STR_TO_DATE
    echo "<h3>Query 2: Con STR_TO_DATE (nueva)</h3>";
    $sql2 = "SELECT service_type, COUNT(*) as total 
             FROM maintenance_reports 
             WHERE STR_TO_DATE(report_date, '%Y-%m-%d') >= '{$period['start']}'
             GROUP BY service_type 
             ORDER BY total DESC";
    echo "<pre>$sql2</pre>";
    
    $result2 = $conn->query($sql2);
    if ($result2) {
        if ($result2->num_rows > 0) {
            echo "<div class='success'>✓ Resultados: {$result2->num_rows} tipos encontrados</div>";
            echo "<ul>";
            while ($row = $result2->fetch_assoc()) {
                echo "<li><strong>{$row['service_type']}:</strong> {$row['total']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='error'>✗ No devuelve resultados</div>";
        }
    } else {
        echo "<div class='error'>✗ Error SQL: " . $conn->error . "</div>";
    }
    
    // Query 3: Probar diferentes formatos
    echo "<h3>Query 3: Detectar formato de fecha</h3>";
    $sql3 = "SELECT 
        report_date,
        STR_TO_DATE(report_date, '%Y-%m-%d') as formato_ymd,
        STR_TO_DATE(report_date, '%d/%m/%Y') as formato_dmy,
        CASE 
            WHEN STR_TO_DATE(report_date, '%Y-%m-%d') IS NOT NULL THEN 'Y-m-d'
            WHEN STR_TO_DATE(report_date, '%d/%m/%Y') IS NOT NULL THEN 'd/m/Y'
            ELSE 'Desconocido'
        END as formato_detectado
    FROM maintenance_reports 
    LIMIT 5";
    
    $result3 = $conn->query($sql3);
    if ($result3 && $result3->num_rows > 0) {
        echo "<table><tr><th>report_date</th><th>Formato Y-m-d</th><th>Formato d/m/Y</th><th>Detectado</th></tr>";
        while ($row = $result3->fetch_assoc()) {
            echo "<tr>";
            echo "<td><code>{$row['report_date']}</code></td>";
            echo "<td>" . ($row['formato_ymd'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['formato_dmy'] ?? 'NULL') . "</td>";
            echo "<td><strong>{$row['formato_detectado']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<hr><h2>Recomendaciones</h2>";
echo "<ul>";
echo "<li>Si Query 1 funciona pero Query 2 no, el problema es STR_TO_DATE()</li>";
echo "<li>Si ambas queries no funcionan, el problema es el rango de fechas</li>";
echo "<li>Verifica el formato de fecha detectado arriba para usar la conversión correcta</li>";
echo "<li>Si report_date ya es formato DATE/DATETIME en MySQL, no necesitas STR_TO_DATE()</li>";
echo "</ul>";

echo "</div>";
$conn->close();
?>
