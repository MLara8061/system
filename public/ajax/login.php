<?php
// public/ajax/login.php - Endpoint de login
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir ROOT
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

// Iniciar sesión hardened
if (session_status() == PHP_SESSION_NONE) {
    require_once ROOT . '/config/session.php';
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('2');
}

// Obtener credenciales
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    die('2');
}

// Conectar a BD
try {
    require_once ROOT . '/config/config.php';
} catch (Exception $e) {
    error_log("CONFIG ERROR: " . $e->getMessage());
    die('2');
}

// Escapar entrada
$username = $conn->real_escape_string($username);

// Buscar usuario
$qry = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) as name FROM users WHERE username = '$username'");

if (!$qry || $qry->num_rows == 0) {
    die('2'); // Usuario no encontrado
}

$user = $qry->fetch_array();

// Verificar contraseña
$password_valid = false;
if (strpos($user['password'], '$2y$') === 0) {
    // bcrypt
    $password_valid = password_verify($password, $user['password']);
} else {
    // MD5 legacy
    $password_valid = ($user['password'] === md5($password));
}

if (!$password_valid) {
    die('3'); // Contraseña incorrecta
}

// Establecer sesión
foreach ($user as $key => $value) {
    if ($key != 'password' && !is_numeric($key)) {
        if ($key === 'role') {
            $_SESSION['login_type'] = $value;
        } else {
            $_SESSION['login_' . $key] = $value;
        }
    }
}

$_SESSION['login_avatar'] = $user['avatar'] ?? 'default-avatar.png';

// Log activity
try {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity, table_name, created_at) VALUES (?, ?, ?, NOW())");
    $activity = "Inició sesión";
    $table = "users";
    $stmt->bind_param("iss", $_SESSION['login_id'], $activity, $table);
    $stmt->execute();
} catch (Exception $e) {
    // Ignorar errores de log
}

die('1'); // Login exitoso
?>
