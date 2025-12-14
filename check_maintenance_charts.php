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

echo "<h1>Diagnóstico de Gráficas de Mantenimiento</h1>";
echo "<hr>";

// 1. Verificar si existe la tabla maintenance_reports
echo "<h2>1. Tabla maintenance_reports</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'maintenance_reports'");
if ($check_table && $check_table->num_rows > 0) {
    echo "<p style='color: green;'>✓ La tabla existe</p>";
    
    // Ver estructura
    $structure = $conn->query("DESCRIBE maintenance_reports");
    echo "<h3>Estructura:</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>" . ($row['Default'] ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    // Ver cantidad de registros
    $count = $conn->query("SELECT COUNT(*) as total FROM maintenance_reports");
    $total = $count ? $count->fetch_assoc()['total'] : 0;
    echo "<p><strong>Total de registros:</strong> $total</p>";
    
    if ($total > 0) {
        // Ver distribución por tipo de servicio
        echo "<h3>Distribución por Tipo de Servicio:</h3>";
        $service_types = $conn->query("
            SELECT service_type, COUNT(*) as total 
            FROM maintenance_reports 
            GROUP BY service_type
        ");
        
        if ($service_types && $service_types->num_rows > 0) {
            echo "<ul>";
            while ($row = $service_types->fetch_assoc()) {
                echo "<li><strong>{$row['service_type']}:</strong> {$row['total']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠ No hay datos de service_type</p>";
        }
        
        // Ver distribución por tipo de ejecución
        echo "<h3>Distribución por Tipo de Ejecución:</h3>";
        $execution_types = $conn->query("
            SELECT execution_type, COUNT(*) as total 
            FROM maintenance_reports 
            GROUP BY execution_type
        ");
        
        if ($execution_types && $execution_types->num_rows > 0) {
            echo "<ul>";
            while ($row = $execution_types->fetch_assoc()) {
                echo "<li><strong>{$row['execution_type']}:</strong> {$row['total']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠ No hay datos de execution_type</p>";
        }
        
        // Ver últimos 5 registros
        echo "<h3>Últimos 5 Registros:</h3>";
        $recent = $conn->query("SELECT * FROM maintenance_reports ORDER BY created_at DESC LIMIT 5");
        if ($recent && $recent->num_rows > 0) {
            echo "<table border='1' cellpadding='5' style='font-size: 12px;'>";
            $first = true;
            while ($row = $recent->fetch_assoc()) {
                if ($first) {
                    echo "<tr>";
                    foreach ($row as $key => $val) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars(substr($val ?? 'NULL', 0, 50)) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>La tabla está vacía</strong></p>";
        echo "<p>Las gráficas no funcionan porque no hay datos de mantenimiento reportados.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ La tabla maintenance_reports NO existe</p>";
    echo "<p><strong>PROBLEMA:</strong> Las gráficas intentan leer de esta tabla pero no existe en la base de datos.</p>";
}

// 2. Verificar tabla mantenimientos (que sí existe)
echo "<hr><h2>2. Alternativa: Tabla mantenimientos</h2>";
$check_mant = $conn->query("SHOW TABLES LIKE 'mantenimientos'");
if ($check_mant && $check_mant->num_rows > 0) {
    echo "<p style='color: green;'>✓ La tabla mantenimientos SÍ existe</p>";
    
    $count_mant = $conn->query("SELECT COUNT(*) as total FROM mantenimientos");
    $total_mant = $count_mant ? $count_mant->fetch_assoc()['total'] : 0;
    echo "<p><strong>Total de registros:</strong> $total_mant</p>";
    
    // Ver estructura
    $structure_mant = $conn->query("DESCRIBE mantenimientos");
    echo "<h3>Estructura:</h3>";
    echo "<table border='1' cellpadding='5'><tr><th>Campo</th><th>Tipo</th></tr>";
    while ($row = $structure_mant->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    if ($total_mant > 0) {
        // Ver distribución por tipo
        echo "<h3>Distribución por Tipo de Mantenimiento:</h3>";
        $tipos = $conn->query("
            SELECT tipo_mantenimiento, COUNT(*) as total 
            FROM mantenimientos 
            GROUP BY tipo_mantenimiento
        ");
        
        if ($tipos && $tipos->num_rows > 0) {
            echo "<ul>";
            while ($row = $tipos->fetch_assoc()) {
                echo "<li><strong>{$row['tipo_mantenimiento']}:</strong> {$row['total']}</li>";
            }
            echo "</ul>";
        }
        
        // Ver distribución por estatus
        echo "<h3>Distribución por Estatus:</h3>";
        $estatus = $conn->query("
            SELECT estatus, COUNT(*) as total 
            FROM mantenimientos 
            GROUP BY estatus
        ");
        
        if ($estatus && $estatus->num_rows > 0) {
            echo "<ul>";
            while ($row = $estatus->fetch_assoc()) {
                echo "<li><strong>{$row['estatus']}:</strong> {$row['total']}</li>";
            }
            echo "</ul>";
        }
    }
}

echo "<hr>";
echo "<h2>Recomendaciones</h2>";
echo "<ul>";
echo "<li>Si <code>maintenance_reports</code> no existe, se debe crear o adaptar las gráficas para usar <code>mantenimientos</code></li>";
echo "<li>Si <code>maintenance_reports</code> existe pero está vacía, se deben registrar mantenimientos en esa tabla</li>";
echo "<li>Verifica que el formulario de reportes de mantenimiento esté guardando en la tabla correcta</li>";
echo "</ul>";

$conn->close();
?>
