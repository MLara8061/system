<?php
// Activar reporte de errores completo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Diagnóstico de Baja de Equipos</h1>";
echo "<hr>";

// Paso 1: Verificar constante ACCESS
echo "<h2>1. Constante ACCESS</h2>";
define('ACCESS', true);
echo "✓ Constante ACCESS definida<br>";

// Paso 2: Intentar cargar config
echo "<h2>2. Cargar config/config.php</h2>";
try {
    require_once 'config/config.php';
    echo "✓ config.php cargado correctamente<br>";
} catch (Exception $e) {
    echo "✗ ERROR al cargar config.php: " . $e->getMessage() . "<br>";
    die();
}

// Paso 3: Verificar variable $conn
echo "<h2>3. Conexión a base de datos</h2>";
if (!isset($conn)) {
    echo "✗ ERROR: Variable \$conn no está definida<br>";
    die();
} else {
    echo "✓ Variable \$conn existe<br>";
}

if ($conn->connect_error) {
    echo "✗ ERROR de conexión: " . $conn->connect_error . "<br>";
    die();
} else {
    echo "✓ Conexión establecida correctamente<br>";
}

// Paso 4: Verificar tablas
echo "<h2>4. Verificar tablas en base de datos</h2>";
$tables = ['equipment_unsubscribe', 'equipments', 'equipment_withdrawal_reason'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Tabla '$table' existe<br>";
    } else {
        echo "✗ Tabla '$table' NO existe<br>";
    }
}

// Paso 5: Obtener registros de bajas
echo "<h2>5. Registros de bajas disponibles</h2>";
$sql = "SELECT id, folio, equipment_id, date, time FROM equipment_unsubscribe ORDER BY id DESC LIMIT 5";
$result = $conn->query($sql);

if ($result === false) {
    echo "✗ ERROR en consulta: " . $conn->error . "<br>";
} else if ($result->num_rows === 0) {
    echo "⚠ No hay registros de bajas en la base de datos<br>";
} else {
    echo "✓ Se encontraron " . $result->num_rows . " registros:<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Folio</th><th>Equipment ID</th><th>Fecha</th><th>Hora</th><th>Probar PDF</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . ($row['folio'] ?? 'Sin folio') . "</td>";
        echo "<td>{$row['equipment_id']}</td>";
        echo "<td>" . ($row['date'] ?? 'N/A') . "</td>";
        echo "<td>" . ($row['time'] ?? 'N/A') . "</td>";
        echo "<td><a href='equipment_unsubscribe_pdf.php?id={$row['id']}' target='_blank'>Ver PDF</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Paso 6: Probar una consulta completa como la del PDF
echo "<h2>6. Probar consulta completa del PDF</h2>";
$testId = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 1;
echo "Probando con ID: $testId<br><br>";

$unsubscribeSql = "SELECT eu.*, e.name AS equipment_name, e.brand, e.model, e.number_inventory, e.serie, e.date_created, e.image, e.amount, e.discipline, e.location_id, e.id AS equipment_ref
                    FROM equipment_unsubscribe eu
                    INNER JOIN equipments e ON e.id = eu.equipment_id
                    WHERE eu.id = {$testId} LIMIT 1";

echo "<strong>SQL:</strong><br><pre>" . htmlspecialchars($unsubscribeSql) . "</pre>";

$testResult = $conn->query($unsubscribeSql);

if ($testResult === false) {
    echo "✗ ERROR en consulta: " . $conn->error . "<br>";
} else if ($testResult->num_rows === 0) {
    echo "⚠ No se encontró el registro con ID $testId<br>";
} else {
    echo "✓ Consulta exitosa. Datos obtenidos:<br>";
    $data = $testResult->fetch_assoc();
    echo "<pre>" . print_r($data, true) . "</pre>";
}

// Paso 7: Información del servidor
echo "<h2>7. Información del servidor</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible') . "<br>";
echo "Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'No definido') . "<br>";
echo "Base URL: " . (defined('BASE_URL') ? BASE_URL : 'No definido') . "<br>";

echo "<hr>";
echo "<p><strong>Si todo está en verde, el problema puede ser con el navegador o caché.</strong></p>";
?>
