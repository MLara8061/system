<?php
/**
 * Instalación simplificada de PHPSpreadsheet con Composer
 */

set_time_limit(600);
ini_set('memory_limit', '512M');
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 'off');
ini_set('implicit_flush', 'on');
ob_implicit_flush(true);

// Deshabilitar output buffering completamente
while (ob_get_level()) {
    ob_end_flush();
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>body{font-family:Arial;padding:20px;} .box{border:1px solid #ccc;padding:10px;background:#f9f9f9;margin:10px 0;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";

echo "<h2>Instalación de PHPSpreadsheet con Composer</h2>";

$root = __DIR__;
$composer_json = $root . '/composer.json';
$composer_phar = $root . '/composer.phar';
$vendor_autoload = $root . '/vendor/autoload.php';

// Establecer variables de entorno
putenv('HOME=' . $root);
putenv('COMPOSER_HOME=' . $root . '/.composer');

function print_step($num, $msg) {
    echo "<p><strong>$num. $msg</strong></p>";
    flush();
}

function print_success($msg) {
    echo "<p class='success'>✓ $msg</p>";
    flush();
}

function print_error($msg) {
    echo "<p class='error'>✗ $msg</p>";
    flush();
}

function print_info($msg) {
    echo "<p class='info'>→ $msg</p>";
    flush();
}

// 1. Crear composer.json
print_step(1, "Creando composer.json");
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
print_success("composer.json creado");

// 2. Verificar/Descargar Composer
print_step(2, "Verificando Composer");
if (!file_exists($composer_phar)) {
    print_info("Descargando Composer...");
    $composer_url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    $composer_content = @file_get_contents($composer_url);
    
    if ($composer_content) {
        file_put_contents($composer_phar, $composer_content);
        chmod($composer_phar, 0755);
        print_success("Composer descargado (" . number_format(filesize($composer_phar) / 1024 / 1024, 2) . " MB)");
    } else {
        print_error("No se pudo descargar Composer");
        die("</body></html>");
    }
} else {
    print_success("Composer ya existe");
}

// 3. Verificar si ya está instalado
if (file_exists($vendor_autoload)) {
    print_step(3, "Verificando instalación existente");
    print_success("PHPSpreadsheet ya está instalado");
    
    require $vendor_autoload;
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        print_success("Clase Spreadsheet verificada y funcional");
    }
    
    echo "<hr><p style='font-size:18px;'><strong>✓ Todo está listo para usar</strong></p>";
    echo "<p><a href='generate_excel_template.php' style='padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;'>Descargar Plantilla Excel</a></p>";
    die("</body></html>");
}

// 4. Instalar PHPSpreadsheet
print_step(3, "Instalando PHPSpreadsheet");
print_info("Esto tomará 2-5 minutos. Por favor espera...");

chdir($root);

$command = sprintf(
    'cd %s && HOME=%s COMPOSER_HOME=%s php %s install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1',
    escapeshellarg($root),
    escapeshellarg($root),
    escapeshellarg($root . '/.composer'),
    escapeshellarg($composer_phar)
);

print_info("Ejecutando Composer (modo silencioso para evitar timeout)...");
flush();

$start_time = time();
exec($command, $output, $return_code);
$duration = time() - $start_time;

echo "<div class='box'>";
if (!empty($output)) {
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
}
echo "</div>";

print_info("Completado en $duration segundos (Código: $return_code)");

// 5. Verificar instalación
print_step(4, "Verificando instalación");

if (file_exists($vendor_autoload)) {
    print_success("vendor/autoload.php creado correctamente");
    
    require $vendor_autoload;
    if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        print_success("Clase Spreadsheet cargada correctamente");
        
        echo "<hr>";
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;padding:15px;border-radius:5px;'>";
        echo "<h3 style='color:#155724;margin:0 0 10px 0;'>✓ ¡Instalación exitosa!</h3>";
        echo "<p>PHPSpreadsheet está instalado y funcional.</p>";
        echo "<p><a href='generate_excel_template.php' style='padding:10px 20px;background:#4CAF50;color:white;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Descargar Plantilla Excel</a></p>";
        echo "</div>";
        
        echo "<hr>";
        echo "<p style='color:#666;font-size:12px;'>Archivos que puedes eliminar después:</p>";
        echo "<ul style='color:#666;font-size:12px;'>";
        echo "<li>composer.phar</li>";
        echo "<li>composer.json</li>";
        echo "<li>composer.lock</li>";
        echo "<li>install_composer_packages.php</li>";
        echo "<li>install_composer_simple.php</li>";
        echo "<li>Todos los archivos: check_*.php, fix_*.php, debug_*.php</li>";
        echo "</ul>";
    } else {
        print_error("No se pudo cargar la clase Spreadsheet");
    }
} else {
    print_error("La instalación no se completó correctamente");
    echo "<div class='box'>";
    echo "<p><strong>Salida del comando:</strong></p>";
    if (!empty($output)) {
        foreach ($output as $line) {
            echo htmlspecialchars($line) . "<br>";
        }
    } else {
        echo "<p>Sin salida (posible timeout o error de permisos)</p>";
    }
    echo "<p><strong>Código de retorno:</strong> $return_code</p>";
    echo "</div>";
    
    echo "<hr>";
    echo "<p><strong>Posibles soluciones:</strong></p>";
    echo "<ol>";
    echo "<li>Contacta a soporte de Hostinger para activar permisos de Composer</li>";
    echo "<li>Instala PHPSpreadsheet manualmente vía SSH con: <code>composer require phpoffice/phpspreadsheet</code></li>";
    echo "<li>Solicita acceso SSH a tu hosting</li>";
    echo "</ol>";
}

echo "</body></html>";
?>
