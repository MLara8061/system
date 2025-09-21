<?php
include 'config/db_connect.php';

$result = $mysqli->query("SELECT 1");

if ($result) {
    echo "✅ Conexión exitosa a la base de datos.";
} else {
    echo "❌ Error en la consulta: " . $mysqli->error;
}
?>
