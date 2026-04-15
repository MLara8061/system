<?php
date_default_timezone_set('America/Cancun');
require_once 'config/config.php';

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

require_once 'vendor/autoload.php';

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
    $sheet->setTitle('Proveedores');

    // Get suppliers data
    $branch_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id') : '';
    $query = $conn->query("
        SELECT 
            id,
            company_name,
            contact_name,
            email,
            phone,
            address,
            city,
            state,
            postal_code,
            country,
            payment_method,
            created_at
        FROM suppliers
        {$branch_where}
        ORDER BY company_name ASC
    ");

    if (!$query) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Headers
    $headers = [
        'Empresa',
        'Contacto',
        'Email',
        'Teléfono',
        'Dirección',
        'Ciudad',
        'Estado',
        'C.P.',
        'País',
        'Método de Pago',
        'Creado'
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
    while ($supplier = $query->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, $supplier['company_name'] ?? '');
        $sheet->setCellValue('B' . $row, $supplier['contact_name'] ?? '');
        $sheet->setCellValue('C' . $row, $supplier['email'] ?? '');
        $sheet->setCellValue('D' . $row, $supplier['phone'] ?? '');
        $sheet->setCellValue('E' . $row, $supplier['address'] ?? '');
        $sheet->setCellValue('F' . $row, $supplier['city'] ?? '');
        $sheet->setCellValue('G' . $row, $supplier['state'] ?? '');
        $sheet->setCellValue('H' . $row, $supplier['postal_code'] ?? '');
        $sheet->setCellValue('I' . $row, $supplier['country'] ?? '');
        $sheet->setCellValue('J' . $row, $supplier['payment_method'] ?? '');
        $sheet->setCellValue('K' . $row, $supplier['created_at'] ?? '');
        $row++;
    }

    // Set column widths
    $columnWidths = [25, 20, 25, 15, 30, 15, 12, 10, 15, 18, 15];
    for ($col = 1; $col <= count($columnWidths); $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($colLetter)->setWidth($columnWidths[$col - 1]);
    }

    // Add borders to all cells
    $dataRange = 'A1:K' . ($row - 1);
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
    header('Content-Disposition: attachment; filename="proveedores_' . date('Y-m-d_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    error_log('Error exporting suppliers: ' . $e->getMessage());
    http_response_code(500);
    die('Error generating export: ' . $e->getMessage());
}
?>
