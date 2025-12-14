<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Mover Archivos de uploads/equipment/ a uploads/</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";

$source_dir = __DIR__ . '/uploads/equipment/';
$dest_dir = __DIR__ . '/uploads/';

// Verificar que exista el directorio fuente
if (!is_dir($source_dir)) {
    echo "<p style='color: red;'>✗ El directorio uploads/equipment/ no existe.</p>";
    exit;
}

echo "<h2>1. Verificando directorio fuente</h2>";
echo "<p>Directorio: <code>$source_dir</code></p>";

// Obtener lista de archivos
$files = glob($source_dir . '*');
$total_files = count($files);

echo "<p>Total de archivos encontrados: <strong>$total_files</strong></p>";

if ($total_files == 0) {
    echo "<p style='color: orange;'>⚠ No hay archivos para mover.</p>";
    exit;
}

// Mostrar archivos a mover
echo "<h2>2. Archivos a Mover</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>#</th><th>Archivo</th><th>Tamaño</th><th>Estado</th></tr>";

$moved = 0;
$errors = 0;
$skipped = 0;

foreach ($files as $index => $file) {
    $num = $index + 1;
    $filename = basename($file);
    $size = filesize($file);
    $size_kb = round($size / 1024, 2);
    $dest_file = $dest_dir . $filename;
    
    echo "<tr>";
    echo "<td>$num</td>";
    echo "<td>$filename</td>";
    echo "<td>{$size_kb} KB</td>";
    
    // Verificar si ya existe en destino
    if (file_exists($dest_file)) {
        echo "<td style='color: orange;'>⚠ Ya existe en destino (omitido)</td>";
        $skipped++;
    } else {
        // Mover archivo
        if (rename($file, $dest_file)) {
            echo "<td style='color: green;'>✓ Movido exitosamente</td>";
            $moved++;
        } else {
            echo "<td style='color: red;'>✗ Error al mover</td>";
            $errors++;
        }
    }
    
    echo "</tr>";
}

echo "</table>";

// Resumen
echo "<h2>3. Resumen</h2>";
echo "<ul>";
echo "<li>Total de archivos: <strong>$total_files</strong></li>";
echo "<li style='color: green;'>Movidos exitosamente: <strong>$moved</strong></li>";
echo "<li style='color: orange;'>Omitidos (ya existían): <strong>$skipped</strong></li>";
echo "<li style='color: red;'>Errores: <strong>$errors</strong></li>";
echo "</ul>";

// Verificar si quedó vacío el directorio
$remaining = glob($source_dir . '*');
$remaining_count = count($remaining);

echo "<h2>4. Directorio uploads/equipment/</h2>";
if ($remaining_count == 0) {
    echo "<p style='color: green;'>✓ El directorio está vacío. Puedes eliminarlo si lo deseas.</p>";
    
    // Intentar eliminar el directorio vacío
    if (rmdir($source_dir)) {
        echo "<p style='color: green;'>✓ Directorio <code>uploads/equipment/</code> eliminado.</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No se pudo eliminar el directorio (puede tener archivos ocultos).</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Aún quedan $remaining_count archivos en uploads/equipment/</p>";
}

// Verificación final
echo "<h2>5. Verificación Final</h2>";
$uploads_files = glob($dest_dir . '*');
$uploads_count = count($uploads_files);
echo "<p>Total de archivos en uploads/: <strong>$uploads_count</strong></p>";

if ($moved > 0) {
    echo "<p style='color: green; font-size: 18px;'><strong>✓ Operación completada</strong></p>";
    echo "<p>Los archivos han sido movidos correctamente a uploads/</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=equipment_list'>← Volver a lista de equipos</a></p>";
echo "<p><a href='diagnose_views.php'>Ver diagnóstico completo</a></p>";
?>
