<?php
/**
 * Script de diagnóstico para verificar el usuario Eduardo
 */

// Definir ROOT
if (!defined('ROOT')) {
    define('ROOT', __DIR__);
}

// Conectar a BD
require_once ROOT . '/config/config.php';
require_once ROOT . '/config/db.php';

echo "<h2>Diagnóstico del Usuario Eduardo</h2>";
echo "<hr>";

// Buscar usuario Eduardo
$username = 'Eduardo';

echo "<h3>1. Búsqueda en Base de Datos</h3>";

// Buscar con mysqli
$qry = $conn->query("SELECT id, username, firstname, lastname, role, password, avatar, date_created FROM users WHERE username LIKE '%Eduardo%' OR firstname LIKE '%Eduardo%' OR lastname LIKE '%Eduardo%'");

if ($qry && $qry->num_rows > 0) {
    echo "<p style='color: green;'>✓ Usuario encontrado en la base de datos</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Nombre</th><th>Apellido</th><th>Role</th><th>Password Hash</th><th>Avatar</th><th>Fecha Creación</th></tr>";
    
    while ($user = $qry->fetch_array()) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['firstname']}</td>";
        echo "<td>{$user['lastname']}</td>";
        echo "<td>" . ($user['role'] == 1 ? 'Admin' : 'Usuario') . "</td>";
        
        // Verificar tipo de password
        $passType = 'Desconocido';
        if (strpos($user['password'], '$2y$') === 0) {
            $passType = 'bcrypt (moderno)';
        } elseif (strlen($user['password']) == 32) {
            $passType = 'MD5 (legacy)';
        }
        echo "<td>{$passType}<br><small style='color: #666;'>" . substr($user['password'], 0, 20) . "...</small></td>";
        
        echo "<td>" . ($user['avatar'] ?: 'Sin avatar') . "</td>";
        echo "<td>{$user['date_created']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ No se encontró ningún usuario con el nombre 'Eduardo'</p>";
}

echo "<hr>";
echo "<h3>2. Verificación de Contraseña</h3>";

// Intentar login con diferentes contraseñas comunes
$test_passwords = ['Eduardo', 'eduardo', 'Eduardo123', 'eduardo123', '123456', 'admin'];

$qry = $conn->query("SELECT id, username, password FROM users WHERE username LIKE '%Eduardo%' OR firstname LIKE '%Eduardo%' LIMIT 1");

if ($qry && $qry->num_rows > 0) {
    $user = $qry->fetch_array();
    echo "<p><strong>Usuario a probar:</strong> {$user['username']} (ID: {$user['id']})</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Contraseña Probada</th><th>Resultado</th></tr>";
    
    foreach ($test_passwords as $test_pass) {
        $result = '';
        
        if (strpos($user['password'], '$2y$') === 0) {
            // bcrypt
            if (password_verify($test_pass, $user['password'])) {
                $result = "<span style='color: green;'>✓ CORRECTA</span>";
            } else {
                $result = "<span style='color: red;'>✗ Incorrecta</span>";
            }
        } else {
            // MD5
            if ($user['password'] === md5($test_pass)) {
                $result = "<span style='color: green;'>✓ CORRECTA</span>";
            } else {
                $result = "<span style='color: red;'>✗ Incorrecta</span>";
            }
        }
        
        echo "<tr><td>{$test_pass}</td><td>{$result}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No se puede probar contraseñas - usuario no encontrado</p>";
}

echo "<hr>";
echo "<h3>3. Verificación de Sesión y Cookies</h3>";

echo "<p><strong>Configuración de sesión PHP:</strong></p>";
echo "<ul>";
echo "<li>session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "</li>";
echo "<li>session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "</li>";
echo "<li>session.cookie_secure: " . (ini_get('session.cookie_secure') ? 'Sí' : 'No') . "</li>";
echo "<li>session.cookie_httponly: " . (ini_get('session.cookie_httponly') ? 'Sí' : 'No') . "</li>";
echo "<li>session.cookie_samesite: " . ini_get('session.cookie_samesite') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>4. Verificación de Archivos</h3>";

$files_to_check = [
    'public/ajax/login.php',
    'config/session.php',
    'config/config.php',
    'legacy/admin_class.php'
];

echo "<ul>";
foreach ($files_to_check as $file) {
    $path = ROOT . '/' . $file;
    if (file_exists($path)) {
        echo "<li style='color: green;'>✓ {$file} - Existe</li>";
    } else {
        echo "<li style='color: red;'>✗ {$file} - NO EXISTE</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<h3>5. Recomendaciones</h3>";

$qry = $conn->query("SELECT id, username, password FROM users WHERE username LIKE '%Eduardo%' OR firstname LIKE '%Eduardo%' LIMIT 1");

if ($qry && $qry->num_rows > 0) {
    $user = $qry->fetch_array();
    
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<h4>Para el usuario: {$user['username']}</h4>";
    
    if (strpos($user['password'], '$2y$') !== 0 && strlen($user['password']) == 32) {
        echo "<p><strong>⚠ Advertencia:</strong> Este usuario usa encriptación MD5 (legacy). Se recomienda actualizar la contraseña para usar bcrypt.</p>";
        echo "<p><strong>Solución:</strong> Resetear la contraseña del usuario desde el panel de administración.</p>";
    }
    
    echo "<p><strong>Para probar el login:</strong></p>";
    echo "<ol>";
    echo "<li>Abre la página de login en modo incógnito</li>";
    echo "<li>Usa el username: <code>{$user['username']}</code></li>";
    echo "<li>Si no conoces la contraseña, resetéala desde el panel de admin</li>";
    echo "<li>Revisa los logs del navegador (F12 → Console) para ver errores</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<h4>Usuario no encontrado</h4>";
    echo "<p>El usuario 'Eduardo' no existe en la base de datos.</p>";
    echo "<p><strong>Solución:</strong> Crear el usuario desde el panel de administración.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Script ejecutado el: " . date('Y-m-d H:i:s') . "</small></p>";
?>
