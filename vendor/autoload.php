<?php
/**
 * Autoloader para PHPSpreadsheet
 */

$baseDir = dirname(dirname(__FILE__));

// Cargar ZipStream stub PRIMERO para evitar errores cuando PHPSpreadsheet lo necesite
$zipStreamFile = $baseDir . '/lib/ZipStream.php';
if (file_exists($zipStreamFile)) {
    require_once $zipStreamFile;
}

spl_autoload_register(function($class) use ($baseDir) {
    // Procesar clases de PhpOffice\PhpSpreadsheet
    if (strpos($class, 'PhpOffice\\') === 0) {
        $path = str_replace('\\', '/', $class);
        $path = str_replace('PhpOffice/', '', $path);
        $file = $baseDir . '/lib/PhpSpreadsheet-1.29.0/src/' . $path . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    // Procesar interfaces PSR - crear stubs simples
    if (strpos($class, 'Psr\\') === 0) {
        // Crear stubs mínimos para PSR
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            // Crear un mock simple que no hace nada
            eval("namespace Psr\\SimpleCache; 
            if (!interface_exists('CacheInterface')) {
                interface CacheInterface {
                    public function get(\$key, \$default = null);
                    public function set(\$key, \$value, \$ttl = null);
                    public function delete(\$key);
                    public function clear();
                    public function getMultiple(\$keys, \$default = null);
                    public function setMultiple(\$values, \$ttl = null);
                    public function deleteMultiple(\$keys);
                    public function has(\$key);
                }
            }");
            
            eval("namespace Psr\\Log;
            if (!interface_exists('LoggerInterface')) {
                interface LoggerInterface {
                    public function log(\$level, \$message, array \$context = []);
                    public function emergency(\$message, array \$context = []);
                    public function alert(\$message, array \$context = []);
                    public function critical(\$message, array \$context = []);
                    public function error(\$message, array \$context = []);
                    public function warning(\$message, array \$context = []);
                    public function notice(\$message, array \$context = []);
                    public function info(\$message, array \$context = []);
                    public function debug(\$message, array \$context = []);
                }
            }");
        }
        return true;
    }
    
    // Procesar ZipStream - cargar desde lib/ZipStream.php
    if (strpos($class, 'ZipStream\\') === 0 || $class === 'ZipStream\\ZipStream') {
        $zipStreamFile = $baseDir . '/lib/ZipStream.php';
        if (file_exists($zipStreamFile)) {
            require_once $zipStreamFile;
            return true;
        }
        return false;
    }
    
    return false;
}, true);




