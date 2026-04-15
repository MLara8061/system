<?php
/**
 * Export Inventory to XLSX format
 * Endpoint: /public/ajax/export_inventory_xlsx.php
 * Method: GET
 */

require_once 'config/config.php';

// Validar sesión
if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión expirada']);
    exit;
}

// Cargar PHPSpreadsheet
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

try {
    // Obtener datos del inventario
    $branch_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id', 'i') : '';
    
    $query = "SELECT 
        id, name, category, price, cost, stock, min_stock, max_stock, 
        (cost * stock) as valor_total, is_hazardous, created_at
    FROM inventory i 
    {$branch_where}
    ORDER BY name ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }
    
    // Crear nuevo Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Insumos');
    
    // Encabezados
    $headers = ['Nombre', 'Categoría', 'Precio', 'Costo', 'Stock', 'Stock Mín.', 'Stock Máx.', 'Valor Total', 'Peligroso', 'Creado'];
    $sheet->fromArray([$headers], null, 'A1');
    
    // Aplicar estilos al encabezado
    $headerFill = new Fill();
    $headerFill->setFillType(Fill::FILL_SOLID);
    $headerFill->getStartColor()->setARGB('FF4472C4');
    
    $headerFont = new Font();
    $headerFont->setBold(true);
    $headerFont->getColor()->setARGB('FFFFFFFF');
    
    $headerAlignment = new Alignment();
    $headerAlignment->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $headerAlignment->setVertical(Alignment::VERTICAL_CENTER);
    $headerAlignment->setWrapText(true);
    
    foreach (range('A', 'J') as $col) {
        $sheet->getStyle("{$col}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => 'FF4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ]
        ]);
    }
    
    // Llenar datos
    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue("A{$rowNum}", $row['name']);
        $sheet->setCellValue("B{$rowNum}", $row['category'] ?? '');
        $sheet->setCellValue("C{$rowNum}", (float)$row['price']);
        $sheet->setCellValue("D{$rowNum}", (float)$row['cost']);
        $sheet->setCellValue("E{$rowNum}", (int)$row['stock']);
        $sheet->setCellValue("F{$rowNum}", (int)$row['min_stock']);
        $sheet->setCellValue("G{$rowNum}", (int)$row['max_stock']);
        $sheet->setCellValue("H{$rowNum}", (float)$row['valor_total']);
        $sheet->setCellValue("I{$rowNum}", $row['is_hazardous'] ? 'Sí' : 'No');
        $sheet->setCellValue("J{$rowNum}", $row['created_at']);
        
        // Aplicar bordes y alineación
        $range = "A{$rowNum}:J{$rowNum}";
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D3D3D3'],
                ]
            ]
        ]);
        
        // Alinear números a la derecha
        $sheet->getStyle("C{$rowNum}:H{$rowNum}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ]
        ]);
        
        // Color peligrosos
        if ($row['is_hazardous']) {
            $sheet->getStyle("I{$rowNum}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFFF0000'],
                ],
                'font' => [
                    'color' => ['argb' => 'FFFFFFFF'],
                    'bold' => true,
                ]
            ]);
        }
        
        $rowNum++;
    }
    
    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(12);
    $sheet->getColumnDimension('D')->setWidth(12);
    $sheet->getColumnDimension('E')->setWidth(10);
    $sheet->getColumnDimension('F')->setWidth(12);
    $sheet->getColumnDimension('G')->setWidth(12);
    $sheet->getColumnDimension('H')->setWidth(14);
    $sheet->getColumnDimension('I')->setWidth(12);
    $sheet->getColumnDimension('J')->setWidth(15);
    
    // Congelar encabezado
    $sheet->freezePane('A2');
    
    // Generar archivo
    $filename = 'inventario_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: no-cache');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
