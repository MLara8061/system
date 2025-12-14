<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar variables de entorno
$env_file = __DIR__ . '/config/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (trim($line) === '' || $line[0] === '#') continue;
        list($key, $value) = array_map('trim', explode('=', $line, 2));
        if (!empty($key)) putenv("$key=$value");
    }
}

require_once __DIR__ . '/config/config.php';

echo "<h1>Estructura de Base de Datos</h1>";

// Listar todas las tablas
$tables = $conn->query("SHOW TABLES");
echo "<h2>Tablas en la base de datos:</h2>";
echo "<ul>";
while ($table = $tables->fetch_array()) {
    echo "<li><strong>{$table[0]}</strong></li>";
}
echo "</ul>";

// Estructura de equipments
echo "<h2>Estructura de tabla 'equipments':</h2>";
$cols = $conn->query("SHOW COLUMNS FROM equipments");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($col = $cols->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Estructura de categories
echo "<h2>Estructura de tabla 'categories':</h2>";
$cols = $conn->query("SHOW COLUMNS FROM categories");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($col = $cols->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Verificar si existe tabla locations
$location_check = $conn->query("SHOW TABLES LIKE 'locations'");
if ($location_check->num_rows > 0) {
    echo "<h2>Estructura de tabla 'locations':</h2>";
    $cols = $conn->query("SHOW COLUMNS FROM locations");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($col = $cols->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Verificar si existe tabla accessories
$acc_check = $conn->query("SHOW TABLES LIKE 'accessories'");
if ($acc_check->num_rows > 0) {
    echo "<h2>Estructura de tabla 'accessories':</h2>";
    $cols = $conn->query("SHOW COLUMNS FROM accessories");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($col = $cols->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Verificar si existe tabla acquisition_types
$acq_check = $conn->query("SHOW TABLES LIKE 'acquisition_types'");
if ($acq_check->num_rows > 0) {
    echo "<h2>Datos en tabla 'acquisition_types':</h2>";
    $data = $conn->query("SELECT * FROM acquisition_types");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
    while($row = $data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>" . ($row['description'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Mostrar datos de categories
echo "<h2>Datos en tabla 'categories':</h2>";
$cats = $conn->query("SELECT * FROM categories LIMIT 20");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr>";
while($cat = $cats->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$cat['id']}</td>";
    echo "<td>{$cat['name']}</td>";
    echo "<td>" . ($cat['description'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";
