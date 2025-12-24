<?php
// Test directo para Excel
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== EXCEL TEST ===";
echo "<br>PHP Version: " . phpversion();
echo "<br>Current DIR: " . __DIR__;

// Verificar autoloader
$autoloader_path = __DIR__ . '/vendor/autoload.php';
echo "<br>Autoloader path: " . $autoloader_path;
echo "<br>Autoloader exists: " . (file_exists($autoloader_path) ? 'YES' : 'NO');

if (file_exists($autoloader_path)) {
    echo "<br>Requiring autoloader...";
    require $autoloader_path;
    echo "<br>Autoloader required OK";
}

// Verificar clase
echo "<br>Class exists: " . (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet') ? 'YES' : 'NO');

if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
    echo "<br>Creating spreadsheet...";
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "<br>Spreadsheet created: " . get_class($ss);
}
?>
