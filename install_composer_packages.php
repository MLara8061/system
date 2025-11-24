<?php
/**
 * Instalar PHPSpreadsheet usando Composer
 */

set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');

echo "<h2>Instalación de PHPSpreadsheet con Composer</h2>";

$root = __DIR__;
$composer_json = $root . '/composer.json';
$composer_phar = $root . '/composer.phar';

// Establecer variables de entorno
putenv('HOME=' . $root);
putenv('COMPOSER_HOME=' . $root . '/.composer');

// 1. Crear composer.json
echo "<p>1. Creando composer.json...</p>";
$config = [
    "require" => [
        "phpoffice/phpspreadsheet" => "^1.29"
    ],
    "config" => [
        "vendor-dir" => "vendor",
        "preferred-install" => "dist",
        "optimize-autoloader" => true
    ]
];

file_put_contents($composer_json, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "<p style='color: green;'>✓ composer.json creado</p>";

// 2. Descargar Composer
if (!file_exists($composer_phar)) {
    echo "<p>2. Descargando Composer...</p>";
    $composer_url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    $composer_content = @file_get_contents($composer_url);
    
    if ($composer_content) {
        file_put_contents($composer_phar, $composer_content);
        chmod($composer_phar, 0755);
        echo "<p style='color: green;'>✓ Composer descargado (" . number_format(filesize($composer_phar) / 1024 / 1024, 2) . " MB)</p>";
    } else {
        die("<p style='color: red;'>✗ No se pudo descargar Composer. Verifica la conexión.</p>");
    }
} else {
    echo "<p style='color: green;'>✓ Composer ya existe</p>";
}

// 3. Ejecutar Composer install
echo "<p>3. Instalando PHPSpreadsheet (esto puede tardar varios minutos)...</p>";
echo "<p style='color: orange;'>Por favor espera, no cierres esta página...</p>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f5f5f5; max-height: 400px; overflow-y: auto;'>";
flush();
ob_flush();

// Cambiar al directorio de trabajo
chdir($root);

// Ejecutar composer con variables de entorno configuradas
$command = sprintf(
    'HOME=%s COMPOSER_HOME=%s php %s install --no-dev --optimize-autoloader --no-interaction 2>&1',
    escapeshellarg($root),
    escapeshellarg($root . '/.composer'),
    escapeshellarg($composer_phar)
);

$output = '';
$handle = popen($command, 'r');
if ($handle) {
    while (!feof($handle)) {
        $line = fgets($handle);
        echo htmlspecialchars($line) . "<br>";
        $output .= $line;
        flush();
        ob_flush();
    }
    pclose($handle);
} else {
    // Fallback si popen no funciona
    $output = shell_exec($command);
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

echo "</div>";

// 4. Verificar instalación
echo "<p>4. Verificando instalación...</p>";
if (file_exists($root . '/vendor/autoload.php')) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ ¡PHPSpreadsheet instalado correctamente!</p>";
    
    // Verificar que PhpOffice\PhpSpreadsheet está disponible
    require $root . '/vendor/autoload.php';
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        echo "<p style='color: green;'>✓ Clase Spreadsheet cargada correctamente</p>";
    }
    
    echo "<p><strong>Archivos instalados:</strong></p>";
    echo "<ul>";
    echo "<li>vendor/autoload.php ✓</li>";
    echo "<li>vendor/phpoffice/phpspreadsheet/ ✓</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><strong>Ahora la descarga de plantillas Excel funcionará correctamente.</strong></p>";
    echo "<p style='color: gray; font-size: 12px;'>Nota: Puedes eliminar estos archivos después:</p>";
    echo "<ul style='color: gray; font-size: 12px;'>";
    echo "<li>composer.phar</li>";
    echo "<li>composer.json</li>";
    echo "<li>composer.lock</li>";
    echo "<li>install_composer_packages.php</li>";
    echo "<li>Todos los archivos install_*.php, check_*.php, fix_*.php, debug_*.php</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Error: No se completó la instalación</p>";
    echo "<p>Output del comando:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}
?>
