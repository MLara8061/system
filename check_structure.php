<?php
define('ACCESS', true);
require_once 'config/config.php';

echo "<h2>🔍 Diagnóstico Completo de Estructura</h2>";

// 1. Verificar estructura de job_positions
echo "<h3>1. Estructura de la tabla job_positions</h3>";
$result = $conn->query("DESCRIBE job_positions");
echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Verificar si existen las nuevas columnas
echo "<h3>2. ¿Existen las nuevas columnas?</h3>";
$has_location_id = false;
$has_department_id = false;
$result = $conn->query("DESCRIBE job_positions");
while($row = $result->fetch_assoc()) {
    if($row['Field'] == 'location_id') $has_location_id = true;
    if($row['Field'] == 'department_id') $has_department_id = true;
}

if($has_location_id) {
    echo "<p style='color:green'>✅ Columna location_id existe</p>";
} else {
    echo "<p style='color:red'>❌ Columna location_id NO existe - NECESITAS EJECUTAR LA MIGRACIÓN</p>";
}

if($has_department_id) {
    echo "<p style='color:green'>✅ Columna department_id existe</p>";
} else {
    echo "<p style='color:red'>❌ Columna department_id NO existe - NECESITAS EJECUTAR LA MIGRACIÓN</p>";
}

// 3. Verificar estructura de locations
echo "<h3>3. Estructura de la tabla locations</h3>";
$result = $conn->query("DESCRIBE locations");
echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Verificar si location tiene department_id
echo "<h3>4. ¿Locations tiene department_id?</h3>";
$has_dept_in_locations = false;
$result = $conn->query("DESCRIBE locations");
while($row = $result->fetch_assoc()) {
    if($row['Field'] == 'department_id') $has_dept_in_locations = true;
}

if($has_dept_in_locations) {
    echo "<p style='color:green'>✅ Columna department_id existe en locations</p>";
} else {
    echo "<p style='color:red'>❌ Columna department_id NO existe en locations - NECESITAS EJECUTAR LA MIGRACIÓN</p>";
}

// 5. Datos actuales
echo "<h3>5. Datos Actuales</h3>";

echo "<h4>Departamentos:</h4>";
$depts = $conn->query("SELECT * FROM departments ORDER BY name");
echo "<table border='1'><tr><th>ID</th><th>Nombre</th></tr>";
while($row = $depts->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td></tr>";
}
echo "</table>";

echo "<h4>Ubicaciones:</h4>";
$locs = $conn->query("SELECT * FROM locations ORDER BY name");
echo "<table border='1'><tr><th>ID</th><th>Nombre</th>";
if($has_dept_in_locations) echo "<th>Department ID</th>";
echo "</tr>";
while($row = $locs->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td>";
    if($has_dept_in_locations) echo "<td>" . ($row['department_id'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h4>Puestos de Trabajo:</h4>";
$jobs = $conn->query("SELECT * FROM job_positions ORDER BY name");
echo "<table border='1'><tr><th>ID</th><th>Nombre</th>";
if($has_location_id) echo "<th>Location ID</th>";
if($has_department_id) echo "<th>Department ID</th>";
echo "</tr>";
while($row = $jobs->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td>";
    if($has_location_id) echo "<td>" . ($row['location_id'] ?? 'NULL') . "</td>";
    if($has_department_id) echo "<td>" . ($row['department_id'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h4>Relaciones (location_positions):</h4>";
$rels = $conn->query("SELECT lp.*, l.name as loc_name, j.name as job_name 
                      FROM location_positions lp 
                      LEFT JOIN locations l ON l.id = lp.location_id 
                      LEFT JOIN job_positions j ON j.id = lp.job_position_id");
if($rels->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Location ID</th><th>Ubicación</th><th>Job Position ID</th><th>Puesto</th></tr>";
    while($row = $rels->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['location_id']}</td><td>{$row['loc_name']}</td><td>{$row['job_position_id']}</td><td>{$row['job_name']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠️ Tabla location_positions está vacía</p>";
}

// 6. Diagnóstico final
echo "<h3>6. 🎯 DIAGNÓSTICO Y SOLUCIÓN</h3>";
if(!$has_location_id || !$has_department_id || !$has_dept_in_locations) {
    echo "<div style='background:#ffcccc; padding:20px; border:2px solid red;'>";
    echo "<h4>❌ PROBLEMA ENCONTRADO: Migración SQL no ejecutada</h4>";
    echo "<p><strong>Las columnas necesarias NO existen en la base de datos.</strong></p>";
    echo "<p>Debes ejecutar el archivo <code>migration_department_relations.sql</code></p>";
    echo "<h5>Cómo ejecutarlo:</h5>";
    echo "<ol>";
    echo "<li>Abre phpMyAdmin o MySQL Workbench</li>";
    echo "<li>Selecciona tu base de datos</li>";
    echo "<li>Ve a la pestaña SQL</li>";
    echo "<li>Copia y pega el contenido de <code>migration_department_relations.sql</code></li>";
    echo "<li>Ejecuta el script</li>";
    echo "</ol>";
    echo "<p>O desde terminal: <code>mysql -u usuario -p nombre_base < migration_department_relations.sql</code></p>";
    echo "</div>";
} else {
    echo "<div style='background:#ccffcc; padding:20px; border:2px solid green;'>";
    echo "<h4>✅ Estructura correcta: Columnas existen</h4>";
    echo "<p>Ahora necesitas asignar los datos:</p>";
    echo "<ol>";
    echo "<li>Ir a <strong>Configuración → Departamentos</strong></li>";
    echo "<li>Editar cada departamento y seleccionar sus ubicaciones y puestos</li>";
    echo "<li>Ir a <strong>Configuración → Puestos</strong></li>";
    echo "<li>Editar cada puesto y asignar su departamento y ubicación</li>";
    echo "</ol>";
    echo "</div>";
}
?>
