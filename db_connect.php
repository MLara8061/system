<?php
$host = $_SERVER['HTTP_HOST'];

// Configuración para local (XAMPP)
// if ($host === 'localhost') {
//     $conn = new mysqli('localhost', 'root', '', 'mario1') 
//         or die("Could not connect to mysql: " . mysqli_error($conn));
//     mysqli_query($conn, "SET SESSION sql_mode = ''");
    
// Configuración para Hostinger (PRODUCCIÓN)
// } else {
//     $conn = new mysqli('localhost', 'u228864460_Arla', 'Mlara806*', 'u228864460_assets_dragon') 
//         or die("Could not connect to mysql: " . mysqli_error($conn));
//     mysqli_query($conn, "SET SESSION sql_mode = ''");
// }

$conn = new mysqli('localhost', 'root', '', 'system');

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>