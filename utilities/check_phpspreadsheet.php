<?php
/**
 * Diagnóstico de instalación de PHPSpreadsheet
 */

echo "<h2>Diagnóstico de PHPSpreadsheet</h2>";

$lib_dir = __DIR__ . '/lib';

echo "<h3>1. Contenido de /lib:</h3>";
if (is_dir($lib_dir)) {
    $items = scandir($lib_dir);
    echo "<ul>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $lib_dir . '/' . $item;
        $type = is_dir($path) ? '[DIR]' : '[FILE]';
        echo "<li>$type $item</li>";
        
        // Si es directorio y contiene "Spreadsheet", mostrar su contenido
        if (is_dir($path) && stripos($item, 'spreadsheet') !== false) {
            echo "<ul>";
            $subitems = scandir($path);
            foreach ($subitems as $subitem) {
                if ($subitem === '.' || $subitem === '..') continue;
                $subpath = $path . '/' . $subitem;
                $subtype = is_dir($subpath) ? '[DIR]' : '[FILE]';
                echo "<li>$subtype $subitem</li>";
            }
            echo "</ul>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>El directorio /lib no existe</p>";
}

echo "<h3>2. Buscando Autoloader.php:</h3>";
$possible_paths = [
    __DIR__ . '/lib/PhpSpreadsheet-1.29.0/src/PhpSpreadsheet/Autoloader.php',
    __DIR__ . '/lib/Phpspreadsheet-1.29.0/src/PhpSpreadsheet/Autoloader.php',
    __DIR__ . '/lib/phpspreadsheet-1.29.0/src/PhpSpreadsheet/Autoloader.php',
];

$found = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        echo "<p style='color: green;'>✓ Encontrado en: $path</p>";
        $found = true;
        break;
    } else {
        echo "<p style='color: gray;'>✗ No existe: $path</p>";
    }
}

if (!$found) {
    echo "<h3>3. Búsqueda recursiva de Autoloader.php:</h3>";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($lib_dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() === 'Autoloader.php') {
            echo "<p style='color: blue;'>Encontrado: " . $file->getPathname() . "</p>";
        }
    }
}

echo "<h3>4. Solución sugerida:</h3>";
echo "<p>Si encontraste el archivo en una ubicación diferente, necesitamos actualizar la ruta en generate_excel_template.php</p>";
?>
