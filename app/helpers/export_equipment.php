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
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Equipos');

    // Get equipments data with filters
    $branch_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id') : '';
    $query = $conn->query("
        SELECT 
            id,
            number_inventory,
            serie,
            date_created,
            name,
            brand,
            model,
            acquisition_type,
            mandate_period_id,
            amount,
            discipline,
            equipment_category_id,
            characteristics,
            revision,
            supplier_id
        FROM equipments
        {$branch_where}
        ORDER BY name ASC
    ");

    if (!$query) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Headers
    $headers = [
        'ID',
        'No. Inventario',
        'Serie',
        'Fecha Creado',
        'Nombre',
        'Marca',
        'Modelo',
        'Tipo Adquisición',
        'Período Mandato',
        'Valor',
        'Disciplina',
        'Categoría',
        'Características',
        'Revisión',
        'Proveedor'
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
    while ($equipment = $query->fetch_assoc()) {
        // Get supplier name
        $supplier_name = 'N/A';
        if ($equipment['supplier_id']) {
            $supplier_query = $conn->query("SELECT empresa FROM suppliers WHERE id = {$equipment['supplier_id']}");
            if ($supplier_query && $supplier_query->num_rows > 0) {
                $supplier_name = $supplier_query->fetch_assoc()['empresa'];
            }
        }

        // Get category name
        $category_name = 'N/A';
        if ($equipment['equipment_category_id']) {
            $category_query = $conn->query("SELECT name FROM equipment_categories WHERE id = {$equipment['equipment_category_id']}");
            if ($category_query && $category_query->num_rows > 0) {
                $category_name = $category_query->fetch_assoc()['name'];
            }
        }

        $sheet->setCellValue('A' . $row, $equipment['id'] ?? '');
        $sheet->setCellValue('B' . $row, $equipment['number_inventory'] ?? '');
        $sheet->setCellValue('C' . $row, $equipment['serie'] ?? '');
        $sheet->setCellValue('D' . $row, $equipment['date_created'] ? date('d/m/Y', strtotime($equipment['date_created'])) : '');
        $sheet->setCellValue('E' . $row, $equipment['name'] ?? '');
        $sheet->setCellValue('F' . $row, $equipment['brand'] ?? '');
        $sheet->setCellValue('G' . $row, $equipment['model'] ?? '');
        $sheet->setCellValue('H' . $row, $equipment['acquisition_type'] ?? '');
        $sheet->setCellValue('I' . $row, $equipment['mandate_period_id'] ?? '');
        $sheet->setCellValue('J' . $row, $equipment['amount'] ?? 0);
        $sheet->setCellValue('K' . $row, $equipment['discipline'] ?? '');
        $sheet->setCellValue('L' . $row, $category_name);
        $sheet->setCellValue('M' . $row, $equipment['characteristics'] ?? '');
        $sheet->setCellValue('N' . $row, $equipment['revision'] ?? '');
        $sheet->setCellValue('O' . $row, $supplier_name);
        $row++;
    }

    // Set column widths
    $columnWidths = [10, 15, 12, 12, 20, 12, 12, 15, 12, 12, 15, 18, 25, 12, 18];
    for ($col = 1; $col <= count($columnWidths); $col++) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($colLetter)->setWidth($columnWidths[$col - 1]);
    }

    // Add borders to all cells
    $dataRange = 'A1:O' . ($row - 1);
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
    header('Content-Disposition: attachment; filename="equipos_' . date('Y-m-d_His') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
