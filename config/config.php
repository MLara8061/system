<?php

// Polyfills para PHP < 8 (Hostinger puede usar 7.x)
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len === 0 ? true : substr($haystack, -$len) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        if ($needle === '') return true;
        return strpos($haystack, $needle) !== false;
    }
}

// === BLOQUEAR ACCESO DIRECTO ===
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado.');
}

if (!defined('ACCESS')) define('ACCESS', true);

// === RUTAS ABSOLUTAS ===
define('CONFIG_PATH', __DIR__ . '/');
define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');
define('PUBLIC_PATH', ROOT_PATH . 'public/');

// === CARGAR .env (CON DEPURACIÓN) ===
$env_file = CONFIG_PATH . '.env';
if (!file_exists($env_file)) {
    // Permitir ejecución en CLI aunque falte .env; en entornos web abortar.
    if (php_sapi_name() !== 'cli') {
        die("ERROR: .env NO encontrado en: $env_file");
    }
    // si estamos en CLI, continuamos esperando que las variables de entorno
    // estén definidas en el entorno del proceso o por otros medios.
}

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        // Quitar comillas envolventes si existen
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        @putenv("$key=$value");
        $_ENV[$key] = $value;
    }
} else {
    // No .env presente: en CLI permitimos continuar, en web se habría detenido arriba.
}

// === DETECTAR ENTORNO (MEJORADO) ===
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? php_uname('n');
$is_cli = php_sapi_name() === 'cli';

// Permite forzar el entorno desde variables de entorno (útil en CLI/Hostinger)
$forced_env = strtolower(trim((string)(getenv('APP_ENV') ?: getenv('ENVIRONMENT'))));
if ($forced_env === 'prod') $forced_env = 'production';
if ($forced_env === 'dev') $forced_env = 'local';

$is_local_by_host = in_array($host, ['localhost', '127.0.0.1', '::1', 'localhost:80', 'localhost:8080'], true)
    || str_starts_with($host, 'localhost')
    || str_starts_with($host, '192.168.')
    || str_starts_with($host, '10.')
    || str_starts_with($host, '172.16.')
    || str_ends_with($host, '.local')
    || str_ends_with($host, '.test')
    || str_contains($host, '.dev');

// En CLI: si existen vars de producción, asumir producción.
// Esto evita que scripts ejecutados por SSH/Cron en Hostinger se queden en "local".
$has_prod_env = (getenv('DB_HOST_PROD') !== false && getenv('DB_HOST_PROD') !== '')
    || (getenv('DB_NAME_PROD') !== false && getenv('DB_NAME_PROD') !== '')
    || (getenv('DB_USER_PROD') !== false && getenv('DB_USER_PROD') !== '');

// Hostinger y otros servicios son producción

// =========================
// Helpers multi-sucursal
// =========================
/**
 * Retorna el branch_id activo para la sesión actual.
 * - Admin (login_type=1) puede usar 0 para "todas".
 * - Usuarios no admin: si falta, retorna 0 (y el código debe tratarlo como "sin filtro" o asignar default en login).
 */
if (!function_exists('active_branch_id')) {
    function active_branch_id(): int {
        $bid = isset($_SESSION['login_active_branch_id']) ? (int)$_SESSION['login_active_branch_id'] : 0;
        return $bid;
    }
}

/**
 * Devuelve SQL para filtrar por sucursal (WHERE/AND).
 * Si el usuario es admin y branch activo es 0 -> retorna '' (sin filtro = todas).
 */
if (!function_exists('branch_sql')) {
    function branch_sql(string $clause = 'AND', string $column = 'branch_id', string $alias = ''): string {
        $login_type = isset($_SESSION['login_type']) ? (int)$_SESSION['login_type'] : 0;
        $bid = active_branch_id();

        if ($login_type === 1 && $bid === 0) {
            return '';
        }
        if ($bid <= 0) {
            return '';
        }

        $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
        $clause = strtoupper(trim($clause));
        if ($clause !== 'WHERE' && $clause !== 'AND') $clause = 'AND';
        return " $clause {$prefix}{$column} = {$bid} ";
    }
}
if (in_array($forced_env, ['local', 'production'], true)) {
    define('ENVIRONMENT', $forced_env);
} else {
    if ($is_cli) {
        define('ENVIRONMENT', $has_prod_env ? 'production' : 'local');
    } else {
        define('ENVIRONMENT', $is_local_by_host ? 'local' : 'production');
    }
}

// === CONFIGURACIÓN BD ===
$db_config = [
    'local' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'name' => getenv('DB_NAME') ?: null,
    ],
    'production' => [
        'host' => getenv('DB_HOST_PROD') ?: null,
        'user' => getenv('DB_USER_PROD') ?: null,
        'pass' => getenv('DB_PASS_PROD') ?: null,
        'name' => getenv('DB_NAME_PROD') ?: null,
    ]
];

$cfg = $db_config[ENVIRONMENT];
// Validar configuración según entorno
if (ENVIRONMENT === 'local') {
    if (empty($cfg['name'])) die('ERROR: Define DB_NAME en .env (local)');
} else {
    if (empty($cfg['host']) || empty($cfg['user']) || empty($cfg['name'])) die('ERROR: Define DB_HOST_PROD, DB_USER_PROD y DB_NAME_PROD en .env');
}

define('DB_CONFIG', $cfg);

// === INICIAR SESIÓN (NECESARIO PARA PDFs Y VISTAS) ===
require_once CONFIG_PATH . 'session.php';

// === CARGAR CONEXIÓN ===
require_once CONFIG_PATH . 'db_connect.php';

// === URL BASE DEL SITIO (DETECCIÓN AUTOMÁTICA) ===
// El sistema detecta automáticamente el dominio y protocolo sin importar dónde esté alojado
$base_url_env = getenv('BASE_URL');

if ($base_url_env && $base_url_env !== '') {
    // Si BASE_URL está definido en .env, usar ese valor
    define('BASE_URL', $base_url_env);
} elseif (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_NAME'])) {
    // Detección automática basada en el servidor actual
    // Funciona en cualquier dominio: Hostinger, otro hosting, o localhost
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // Detectar si está en subcarpeta
    $script_path = $_SERVER['SCRIPT_NAME'];
    $project_dir = '';
    
    // Si el script está en una subcarpeta, extraer la ruta base
    if (str_contains($script_path, '/system/')) {
        $project_dir = '/system';
    } elseif (dirname($script_path) !== '/' && dirname($script_path) !== '.') {
        $project_dir = rtrim(dirname($script_path), '/');
    }
    
    define('BASE_URL', $protocol . $host . $project_dir);
} else {
    // Fallback para CLI o contextos sin $_SERVER
    define('BASE_URL', ENVIRONMENT === 'production' ? 'https://indigo-porcupine-764368.hostingersite.com' : 'http://localhost/system');
}

// === FUNCIÓN db() ===
function db() {
    return $GLOBALS['conn'] ?? null;
}

// === HELPERS ===
function include_path($path) {
    $full = ROOT_PATH . ltrim($path, '/');
    return file_exists($full) ? include $full : trigger_error("Include: $full", E_USER_ERROR);
}

function require_path($path) {
    $full = ROOT_PATH . ltrim($path, '/');
    return file_exists($full) ? require $full : trigger_error("Require: $full", E_USER_ERROR);
}