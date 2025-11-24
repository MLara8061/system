<?php
/**
 * Script para corregir estructura de PHPSpreadsheet
 */

echo "<h2>Corrección de Estructura PHPSpreadsheet</h2>";

$base = __DIR__ . '/lib/PhpSpreadsheet-1.29.0';
$wrong_phpspreadsheet = $base . '/PhpSpreadsheet';
$correct_src = $base . '/src';
$correct_phpspreadsheet = $correct_src . '/PhpSpreadsheet';

echo "<p>1. Analizando estructura...</p>";

// Verificar si PhpSpreadsheet está en el lugar incorrecto
if (is_dir($wrong_phpspreadsheet)) {
    echo "<p style='color: orange;'>⚠ Encontrada carpeta PhpSpreadsheet en ubicación incorrecta</p>";
    
    // Verificar si src existe
    if (!is_dir($correct_src)) {
        echo "<p>2. Creando carpeta src/...</p>";
        mkdir($correct_src, 0755, true);
    }
    
    // Mover PhpSpreadsheet a src/
    echo "<p>3. Moviendo PhpSpreadsheet a src/...</p>";
    if (rename($wrong_phpspreadsheet, $correct_phpspreadsheet)) {
        echo "<p style='color: green;'>✓ Carpeta PhpSpreadsheet movida correctamente</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al mover la carpeta</p>";
    }
}

// Verificar que src/PhpSpreadsheet existe y tiene contenido
if (is_dir($correct_phpspreadsheet)) {
    $items = scandir($correct_phpspreadsheet);
    $fileCount = count($items) - 2; // Menos . y ..
    echo "<p>4. Contenido de src/PhpSpreadsheet: $fileCount archivos/carpetas</p>";
    
    // Verificar Autoloader
    $autoloader = $correct_phpspreadsheet . '/Autoloader.php';
    if (file_exists($autoloader)) {
        echo "<p style='color: green; font-size: 20px; font-weight: bold;'>✓ ¡ÉXITO! Estructura corregida</p>";
        echo "<p>Autoloader encontrado en: $autoloader</p>";
        echo "<p><strong>Ahora la descarga de plantillas Excel debería funcionar.</strong></p>";
        
        // Mostrar algunos archivos para confirmar
        echo "<p>Algunos archivos en src/PhpSpreadsheet:</p><ul>";
        $count = 0;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $count >= 10) continue;
            echo "<li>$item</li>";
            $count++;
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Aún no se encuentra el Autoloader.php</p>";
        echo "<p>Listando contenido de src/PhpSpreadsheet:</p><ul>";
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $type = is_dir($correct_phpspreadsheet . '/' . $item) ? '[DIR]' : '[FILE]';
            echo "<li>$type $item</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>✗ No se encuentra la carpeta src/PhpSpreadsheet</p>";
    echo "<p>Estructura actual de PhpSpreadsheet-1.29.0:</p><ul>";
    foreach (scandir($base) as $item) {
        if ($item === '.' || $item === '..') continue;
        $type = is_dir($base . '/' . $item) ? '[DIR]' : '[FILE]';
        echo "<li>$type $item</li>";
    }
    echo "</ul>";
}
?>
