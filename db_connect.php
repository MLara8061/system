<?php
$host = $_SERVER['HTTP_HOST'];

// Configuración para local (XAMPP)
if (strpos($host, 'localhost') !== false) {
    $conn = new mysqli('localhost', 'root', '', 'system')
        or die("Could not connect to mysql: " . mysqli_error($conn));
    mysqli_query($conn, "SET SESSION sql_mode = ''");

// Configuración para Hostinger (PRODUCCIÓN)
} else {
    $conn = new mysqli('localhost', 'u228864460_Arla', 'Mlara806*', 'u228864460_assets_dragon')
        or die("Could not connect to mysql: " . mysqli_error($conn));
    mysqli_query($conn, "SET SESSION sql_mode = ''");
}
?>
