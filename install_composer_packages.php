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

// 1. Crear composer.json
echo "<p>1. Creando composer.json...</p>";
$config = [
    "require" => [
        "phpoffice/phpspreadsheet" => "^1.29"
    ],
    "config" => [
        "vendor-dir" => "vendor"
    ]
];

file_put_contents($composer_json, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "<p style='color: green;'>✓ composer.json creado</p>";

// 2. Descargar Composer
if (!file_exists($composer_phar)) {
    echo "<p>2. Descargando Composer...</p>";
    $installer = file_get_contents('https://getcomposer.org/installer');
    if ($installer) {
        file_put_contents('composer-setup.php', $installer);
        
        // Ejecutar instalador
        ob_start();
        include 'composer-setup.php';
        $output = ob_get_clean();
        
        unlink('composer-setup.php');
        
        if (file_exists($composer_phar)) {
            echo "<p style='color: green;'>✓ Composer descargado</p>";
        } else {
            die("<p style='color: red;'>✗ Error al descargar Composer</p>");
        }
    } else {
        die("<p style='color: red;'>✗ No se pudo descargar el instalador de Composer</p>");
    }
} else {
    echo "<p style='color: green;'>✓ Composer ya existe</p>";
}

// 3. Ejecutar Composer install
echo "<p>3. Instalando PHPSpreadsheet (esto puede tardar varios minutos)...</p>";
echo "<pre>";

// Usar shell_exec para ejecutar composer
$command = "php " . escapeshellarg($composer_phar) . " install --no-dev --optimize-autoloader 2>&1";
$result = shell_exec($command);

echo htmlspecialchars($result);
echo "</pre>";

// 4. Verificar instalación
echo "<p>4. Verificando instalación...</p>";
if (file_exists($root . '/vendor/autoload.php')) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ ¡PHPSpreadsheet instalado correctamente!</p>";
    echo "<p>Puedes eliminar estos archivos de forma segura:</p>";
    echo "<ul>";
    echo "<li>composer.phar</li>";
    echo "<li>install_composer_packages.php (este archivo)</li>";
    echo "</ul>";
    echo "<p><strong>Ahora la descarga de plantillas Excel funcionará correctamente.</strong></p>";
} else {
    echo "<p style='color: red;'>✗ Error: No se completó la instalación</p>";
    echo "<p>Puede que necesites instalar Composer manualmente vía SSH.</p>";
}
?>
