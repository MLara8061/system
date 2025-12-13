<?php
/**
 * Session Security Configuration
 * Implementa buenas prácticas para sesiones seguras
 */

// Solo ejecutar una vez
if (defined('SESSION_HARDENED')) {
    return;
}
define('SESSION_HARDENED', true);

// Asegurar que la sesión no estaba iniciada
if (session_status() === PHP_SESSION_NONE) {
    
    // === CONFIGURACIÓN DE COOKIES SEGURAS ===
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
              (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
              php_uname('s') === 'Linux' && env('ENVIRONMENT') === 'production';
    
    session_set_cookie_params([
        'lifetime' => 1800,  // 30 minutos
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => $secure,  // Solo HTTPS en producción
        'httponly' => true,   // No accesible via JavaScript
        'samesite' => 'Strict' // Protección contra CSRF
    ]);
    
    // === INICIAR SESIÓN ===
    session_start();
    
    // === REGENERAR ID EN LOGIN (llamar desde auth después de verificar credenciales) ===
    // Usar: session_regenerate_id(true) después de verificar login
}

/**
 * Regenerar ID de sesión (llamar después de login exitoso)
 * Protege contra Session Fixation attacks
 */
function regenerate_session_id() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Validar sesión activa
 * Protege contra expiración y manipulación
 */
function validate_session() {
    // Verificar si la sesión existe
    if (!isset($_SESSION['login_id']) || !isset($_SESSION['login_type'])) {
        return false;
    }
    
    // Verificar timeout (30 minutos)
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    if (time() - $_SESSION['last_activity'] > 1800) {
        // Sesión expirada
        session_destroy();
        return false;
    }
    
    // Actualizar timestamp
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Destruir sesión de forma segura
 */
function destroy_session() {
    // Log de logout
    if (isset($_SESSION['login_id'])) {
        error_log("User logout: {$_SESSION['login_id']} at " . date('Y-m-d H:i:s'));
    }
    
    // Limpiar todas las variables
    $_SESSION = [];
    
    // Eliminar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destruir sesión
    session_destroy();
}
?>
