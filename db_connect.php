<?php
// db_connect.php - CONEXIÓN SEGURA Y AUTOMÁTICA

$host = $_SERVER['HTTP_HOST'];

// === DETECCIÓN AUTOMÁTICA DE ENTORNO ===
if ($host === 'localhost' || $host === '127.0.0.1') {
    // === LOCAL (XAMPP) ===
    $servername = 'localhost';
    $username   = 'root';
    $password   = '';
    $dbname     = 'system'; // Asegúrate de que esta base de datos exista
} else {
    // === PRODUCCIÓN (Hostinger u otro) ===
    $servername = 'localhost';
    $username   = 'u228864460_Arla';
    $password   = 'Mlara806*';
    $dbname     = 'u228864460_assets_dragon';
}

// === INTENTAR CONEXIÓN ===
$conn = new mysqli($servername, $username, $password, $dbname);

// === VERIFICAR CONEXIÓN ===
if ($conn->connect_error) {
    // Mostrar error solo en desarrollo
    if ($host === 'localhost') {
        die("Error de conexión a la base de datos: " . $conn->connect_error . " (MySQL: " . mysqli_connect_error() . ")");
    } else {
        // En producción: mensaje genérico por seguridad
        die("Error interno del servidor. Contacte al administrador.");
    }
}

// === CONFIGURACIÓN ADICIONAL ===
$conn->set_charset("utf8mb4");
mysqli_query($conn, "SET SESSION sql_mode = ''") or die("Error en sql_mode: " . $conn->error);

?>