<?php
require_once 'config/config.php';

echo "<h3>Estructura de maintenance_reports:</h3>";
$result = $conn->query('DESCRIBE maintenance_reports');
echo "<ul>";
while($row = $result->fetch_assoc()){ 
    echo "<li>{$row['Field']} ({$row['Type']})</li>";
}
echo "</ul>";
?>
