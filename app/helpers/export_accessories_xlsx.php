<?php
date_default_timezone_set('America/Cancun');

if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/../..'));
}

require_once ROOT . '/config/config.php';

// Verificar sesión
if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    die('Unauthorized'); 
}

// Verificar permisos
if ($_SESSION['login_type'] == 3) {
    http_response_code(403);
    die('No permission');
}

require_once ROOT . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Style;

try {

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Accesorios');

    // Get accessories data
    $branch_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id') : '';
    $query = $conn->query("
        SELECT 
            id,
            name,
            category,
            unit_price,
            quantity,
            supplier_id,
            stock_min,
            stock_max,
            created_at
        FROM accessories
        {$branch_where}
        ORDER BY name ASC
    ");

    if (!$query) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Headers
    $headers = [
        'Accesorio',
        'Categoría',
        'Precio Unitario',
        'Cantidad',
        'Proveedor',
        'Stock Mínimo',
        'Stock Máximo',
        'Fecha Creación'
    ];

    // Set header row
    $sheet->fromArray([$headers], null, 'A1');

    // Style header row
    $headerFill = new Fill();
    $headerFill->setFillType(Fill::FILL_SOLID);
    $headerFill->getStartColor()->setRGB('1565C0');

    $headerFont = new Font();
    $headerFont->getColor()->setRGB('FFFFFF');
    $headerFont->setBold(true);

    $headerAlignment = new Alignment();
    $headerAlignment->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $headerAlignment->setVertical(Alignment::VERTICAL_CENTER);

    for ($col = 1; $col <= count($headers); $col++) {
        $cellAddress = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
        $sheet->getStyle($cellAddress)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '1565C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
    }

    // Add data rows
    $row = 2;
    while ($accessory = $query->fetch_assoc()) {
        // Get supplier name
        $supplier_name = 'N/A';
        if ($accessory['supplier_id']) {
            $supplier_query = $conn->query("SELECT empresa FROM suppliers WHERE id = {$accessory['supplier_id']}");
            if ($supplier_query && $supplier_query->num_rows > 0) {
                $supplier_name = $supplier_query->fetch_assoc()['empresa'];
            }
        }

        $sheet->setCellValue('A' . $row, $accessory['name'] ?? '');
        $sheet->setCellValue('B' . $row, $accessory['category'] ?? '');
        $sheet->setCellValue('C' . $row, $accessory['unit_price'] ?? 0);
        $sheet->setCellValue('D' . $row, $accessory['quantity'] ?? 0);
        $sheet->setCellValue('E' . $row, $supplier_name);
        $sheet->setCellValue('F' . $row, $accessory['stock_min'] ?? 0);
        $sheet->setCellValue('G' . $row, $accessory['stock_max'] ?? 0);
        $sheet->setCellValue('H' . $row, $accessory['created_at'] ?? '');
        $row++;
    }

    // Set column widths
    $columnWidths = [25, 18, 15, 12, 12, 12, 12, 15];
    for ($col = 1; $col <= count($columnWidths); $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($colLetter)->setWidth($columnWidths[$col - 1]);
    }

    // Add borders to all cells
    $dataRange = 'A1:H' . ($row - 1);
    $sheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ]
        ]
    ]);

    // Freeze header row
    $sheet->freezePane('A2');

    // Generate file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="accesorios_' . date('Y-m-d_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    error_log('Error exporting accessories: ' . $e->getMessage());
    http_response_code(500);
    die('Error generating export: ' . $e->getMessage());
}
?>
