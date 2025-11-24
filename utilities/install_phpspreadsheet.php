<?php
/**
 * Script de instalación de PHPSpreadsheet para Hostinger
 * Ejecutar una sola vez: https://tu-dominio.com/install_phpspreadsheet.php
 */

set_time_limit(300); // 5 minutos

$lib_dir = __DIR__ . '/lib';
$target_dir = $lib_dir . '/PhpSpreadsheet-1.29.0';
$zip_file = $lib_dir . '/phpspreadsheet.zip';
$download_url = 'https://github.com/PHPOffice/PhpSpreadsheet/archive/refs/tags/1.29.0.zip';

echo "<h2>Instalación de PHPSpreadsheet</h2>";

// Verificar si ya existe
if (file_exists($target_dir . '/src/PhpSpreadsheet/Autoloader.php')) {
    echo "<p style='color: green;'>✓ PHPSpreadsheet ya está instalado correctamente.</p>";
    echo "<p>Ubicación: " . realpath($target_dir) . "</p>";
    exit;
}

echo "<p>1. Creando directorio lib/...</p>";
if (!is_dir($lib_dir)) {
    mkdir($lib_dir, 0755, true);
}

echo "<p>2. Descargando PHPSpreadsheet desde GitHub...</p>";
$content = @file_get_contents($download_url);
if ($content === false) {
    die("<p style='color: red;'>✗ Error: No se pudo descargar PHPSpreadsheet. Verifica tu conexión.</p>");
}

echo "<p>3. Guardando archivo ZIP (" . number_format(strlen($content) / 1024 / 1024, 2) . " MB)...</p>";
file_put_contents($zip_file, $content);

echo "<p>4. Extrayendo archivos...</p>";
$zip = new ZipArchive();
if ($zip->open($zip_file) === true) {
    $zip->extractTo($lib_dir);
    $zip->close();
    echo "<p style='color: green;'>✓ Archivos extraídos correctamente.</p>";
    
    // Verificar y reorganizar estructura si es necesario
    $extracted_folder = $lib_dir . '/Phpspreadsheet-1.29.0'; // GitHub usa primera letra mayúscula
    if (is_dir($extracted_folder) && !is_dir($target_dir)) {
        echo "<p>4.1. Renombrando carpeta...</p>";
        rename($extracted_folder, $target_dir);
    }
    
} else {
    die("<p style='color: red;'>✗ Error: No se pudo extraer el archivo ZIP.</p>");
}

echo "<p>5. Limpiando archivo temporal...</p>";
@unlink($zip_file);

echo "<p>6. Verificando instalación...</p>";
if (file_exists($target_dir . '/src/PhpSpreadsheet/Autoloader.php')) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ ¡PHPSpreadsheet instalado correctamente!</p>";
    echo "<p>Ahora puedes usar la función de descarga de plantillas Excel.</p>";
    echo "<p><strong>IMPORTANTE:</strong> Por seguridad, elimina este archivo (install_phpspreadsheet.php) después de la instalación.</p>";
} else {
    echo "<p style='color: red;'>✗ Error: La instalación no se completó correctamente.</p>";
}
?>
