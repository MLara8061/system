<?php
// Debug del autoloader
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = __DIR__;

echo "<h2>Debug Autoloader</h2>";

// El archivo autoload.php está en $root/vendor/autoload.php
$autoloadPath = $root . '/vendor/autoload.php';
echo "<p>Autoload path: $autoloadPath</p>";
echo "<p>Autoload exists: " . (file_exists($autoloadPath) ? 'YES' : 'NO') . "</p>";

if (file_exists($autoloadPath)) {
    echo "<p>Loading autoload...</p>";
    require_once $autoloadPath;
    echo "<p>Autoload loaded</p>";
}

// Calcular baseDir como lo hace el autoloader
$baseDir = dirname(dirname($autoloadPath));
echo "<p>BaseDir: $baseDir</p>";

// Verificar que existe PhpSpreadsheet
$phpSpreadsheetPath = $baseDir . '/lib/PhpSpreadsheet-1.29.0/src';
echo "<p>PhpSpreadsheet path: $phpSpreadsheetPath</p>";
echo "<p>PhpSpreadsheet exists: " . (is_dir($phpSpreadsheetPath) ? 'YES' : 'NO') . "</p>";

// Verificar archivo específico
$spreadsheetFile = $phpSpreadsheetPath . '/PhpSpreadsheet/Spreadsheet.php';
echo "<p>Spreadsheet.php path: $spreadsheetFile</p>";
echo "<p>Spreadsheet.php exists: " . (file_exists($spreadsheetFile) ? 'YES' : 'NO') . "</p>";

// Intentar cargar manualmente
echo "<p><strong>Intentando cargar clase...</strong></p>";
if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    echo "<p style='color:green'>✓ Clase EXISTE</p>";
} else {
    echo "<p style='color:red'>✗ Clase NO existe</p>";
    
    // Intentar cargar manualmente el archivo
    if (file_exists($spreadsheetFile)) {
        echo "<p>Cargando manualmente...</p>";
        require_once $spreadsheetFile;
        
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            echo "<p style='color:green'>✓ Clase cargada manualmente</p>";
        }
    }
}

// Intentar crear instancia
try {
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "<p style='color:green'>✓ Spreadsheet creado!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error al crear Spreadsheet: " . $e->getMessage() . "</p>";
}
?>
