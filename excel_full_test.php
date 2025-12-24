<?php
// Test de Excel completo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// El archivo está en /public_html/test/excel_full_test.php
// Necesitamos ir a /public_html/test (que es __DIR__)
$root = __DIR__;

// Cargar autoloader
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

echo "<h1>Test de Excel Completo</h1>";
echo "<p>ZIP Extension disponible: " . (extension_loaded('zip') ? 'SÍ' : 'NO') . "</p>";
echo "<p>Temp directory: " . sys_get_temp_dir() . "</p>";

try {
    // Cargar PHPSpreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Test');
    $sheet->setCellValue('A2', 'Data');
    
    echo "<p>✓ Spreadsheet creado correctamente</p>";
    
    // Intentar escribir a un buffer
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    
    echo "<p>✓ Writer Xlsx creado correctamente</p>";
    
    // Guardar en archivo temporal
    $temp = tempnam(sys_get_temp_dir(), 'xlsx_');
    $temp .= '.xlsx';
    
    echo "<p>Guardando a: " . $temp . "</p>";
    
    $writer->save($temp);
    
    if (file_exists($temp)) {
        $size = filesize($temp);
        echo "<p style='color:green'>✓ Archivo generado exitosamente - Tamaño: " . $size . " bytes</p>";
        @unlink($temp);
    } else {
        echo "<p style='color:red'>✗ El archivo no se creó</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong></p>";
    echo "<pre>" . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString() . "</pre>";
}
?>
