<?php
/**
 * Script para reorganizar la estructura de PHPSpreadsheet
 */

echo "<h2>Reorganización de PHPSpreadsheet</h2>";

$lib_dir = __DIR__ . '/lib';
$wrong_location = $lib_dir; // Los archivos están sueltos en /lib
$correct_location = $lib_dir . '/PhpSpreadsheet-1.29.0';

echo "<p>1. Verificando estructura actual...</p>";

// Verificar si src está directamente en /lib (ubicación incorrecta)
$src_wrong = $lib_dir . '/src';
if (is_dir($src_wrong)) {
    echo "<p style='color: orange;'>⚠ Los archivos están en ubicación incorrecta (sueltos en /lib)</p>";
    
    echo "<p>2. Creando estructura correcta...</p>";
    if (!is_dir($correct_location)) {
        mkdir($correct_location, 0755, true);
    }
    
    echo "<p>3. Moviendo archivos...</p>";
    $files_to_move = ['src', '.php-cs-fixer.dist.php', '.phpcs.xml.dist', 'CHANGELOG.md', 
                      'CONTRIBUTING.md', 'LICENSE', 'README.md', 'composer.json',
                      'phpstan-baseline.neon', 'phpstan-conditional.php', 'phpstan.neon.dist', 
                      'phpunit10.xml.dist'];
    
    $moved = 0;
    foreach ($files_to_move as $item) {
        $source = $lib_dir . '/' . $item;
        $dest = $correct_location . '/' . $item;
        
        if (file_exists($source)) {
            if (rename($source, $dest)) {
                echo "<p style='color: green;'>✓ Movido: $item</p>";
                $moved++;
            } else {
                echo "<p style='color: red;'>✗ Error al mover: $item</p>";
            }
        }
    }
    
    echo "<p>4. Verificando instalación final...</p>";
    $autoloader = $correct_location . '/src/PhpSpreadsheet/Autoloader.php';
    if (file_exists($autoloader)) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ ¡Estructura corregida! PHPSpreadsheet listo para usar.</p>";
        echo "<p>Archivos movidos: $moved</p>";
        echo "<p>Ubicación final: " . realpath($correct_location) . "</p>";
        echo "<p><strong>Ahora puedes usar la descarga de plantillas Excel.</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ Error: No se encontró el Autoloader después de mover.</p>";
    }
    
} else if (file_exists($correct_location . '/src/PhpSpreadsheet/Autoloader.php')) {
    echo "<p style='color: green;'>✓ La estructura ya es correcta. No se requiere acción.</p>";
} else {
    echo "<p style='color: red;'>✗ No se pudo determinar la ubicación de los archivos.</p>";
    echo "<p>Contenido de /lib:</p><ul>";
    foreach (scandir($lib_dir) as $item) {
        if ($item !== '.' && $item !== '..') {
            echo "<li>$item</li>";
        }
    }
    echo "</ul>";
}
?>
