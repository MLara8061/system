<?php
// config/db_connect.php
if (!defined('ACCESS')) exit('Acceso no permitido.');
$conn = new mysqli(DB_CONFIG['host'], DB_CONFIG['user'], DB_CONFIG['pass'], DB_CONFIG['name']);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

$mysqli = $conn;