<?php
// Test de verificación de errores en logs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Revisar Logs de Errores</h1>";

$log_path = __DIR__ . '/logs/php_fatal.log';

if (file_exists($log_path)) {
    echo "<p>Log file exists: YES</p>";
    echo "<p>File size: " . filesize($log_path) . " bytes</p>";
    
    // Leer últimas 20 líneas
    $lines = file($log_path, FILE_IGNORE_NEW_LINES);
    $last_lines = array_slice($lines, -20);
    
    echo "<h2>Últimas 20 líneas del log:</h2>";
    echo "<pre>";
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>Log file does not exist</p>";
}
?>
