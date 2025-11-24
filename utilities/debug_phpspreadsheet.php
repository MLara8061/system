<?php
/**
 * Diagnóstico detallado de PhpSpreadsheet-1.29.0
 */

echo "<h2>Diagnóstico Detallado</h2>";

$target = __DIR__ . '/lib/PhpSpreadsheet-1.29.0';

echo "<h3>1. Verificando carpeta PhpSpreadsheet-1.29.0:</h3>";
if (!is_dir($target)) {
    die("<p style='color: red;'>✗ La carpeta no existe: $target</p>");
}

echo "<p style='color: green;'>✓ La carpeta existe</p>";

echo "<h3>2. Contenido de PhpSpreadsheet-1.29.0:</h3>";
$items = scandir($target);
if (count($items) <= 2) {
    echo "<p style='color: red;'>✗ La carpeta está VACÍA</p>";
    echo "<p><strong>Solución:</strong> Necesitas eliminar la carpeta vacía y reinstalar.</p>";
    echo "<p><a href='?action=delete'>Click aquí para eliminar y reinstalar</a></p>";
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        function deleteDirectory($dir) {
            if (!is_dir($dir)) return false;
            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . '/' . $item;
                is_dir($path) ? deleteDirectory($path) : unlink($path);
            }
            return rmdir($dir);
        }
        
        if (deleteDirectory($target)) {
            echo "<p style='color: green;'>✓ Carpeta eliminada. <a href='install_phpspreadsheet.php'>Reinstalar ahora</a></p>";
        } else {
            echo "<p style='color: red;'>✗ Error al eliminar. Hazlo manualmente vía FTP.</p>";
        }
    }
} else {
    echo "<ul>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $target . '/' . $item;
        $type = is_dir($path) ? '[DIR]' : '[FILE]';
        echo "<li>$type $item";
        
        // Si es la carpeta src, mostrar su contenido
        if ($item === 'src' && is_dir($path)) {
            echo "<ul>";
            $srcItems = scandir($path);
            foreach ($srcItems as $srcItem) {
                if ($srcItem === '.' || $srcItem === '..') continue;
                $srcPath = $path . '/' . $srcItem;
                $srcType = is_dir($srcPath) ? '[DIR]' : '[FILE]';
                echo "<li>$srcType $srcItem</li>";
            }
            echo "</ul>";
        }
        echo "</li>";
    }
    echo "</ul>";
    
    echo "<h3>3. Verificando Autoloader.php:</h3>";
    $autoloader = $target . '/src/PhpSpreadsheet/Autoloader.php';
    if (file_exists($autoloader)) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✓ ¡ENCONTRADO! PHPSpreadsheet está correctamente instalado.</p>";
        echo "<p>Ubicación: $autoloader</p>";
        echo "<p><strong>Ya puedes usar la descarga de plantillas Excel.</strong></p>";
    } else {
        echo "<p style='color: red;'>✗ No se encuentra Autoloader.php en la ubicación esperada</p>";
        echo "<p>Ruta buscada: $autoloader</p>";
    }
}
?>
