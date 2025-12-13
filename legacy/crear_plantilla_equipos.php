<?php
/**
 * Script para crear la plantilla de Excel para carga masiva de equipos
 * Ejecutar una sola vez para generar el archivo plantilla_equipos.xlsx
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Establecer nombre de la hoja
$sheet->setTitle('Equipos');

// Encabezados
$headers = [
    'A1' => 'Serie',
    'B1' => 'Nombre',
    'C1' => 'Marca',
    'D1' => 'Modelo',
    'E1' => 'Tipo de Adquisición',
    'F1' => 'Características',
    'G1' => 'Disciplina',
    'H1' => 'Proveedor',
    'I1' => 'Cantidad'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Estilo para los encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];

$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(25);
$sheet->getColumnDimension('F')->setWidth(40);
$sheet->getColumnDimension('G')->setWidth(20);
$sheet->getColumnDimension('H')->setWidth(25);
$sheet->getColumnDimension('I')->setWidth(12);

// Datos de ejemplo
$ejemplos = [
    ['EQ-2024-001', 'Microscopio Óptico', 'Olympus', 'CX23', 'Compra', 'Microscopio binocular con 4 objetivos', 'Laboratorio', 'MediEquip SA', '2'],
    ['EQ-2024-002', 'Centrifuga', 'Eppendorf', '5424R', 'Donación', 'Centrifuga refrigerada hasta 15000 rpm', 'Laboratorio', 'BioTech Supplies', '1'],
    ['EQ-2024-003', 'Espectrofotómetro', 'Thermo', 'NanoDrop', 'Comodato', 'Espectrofotómetro UV-Vis', 'Investigación', '', '1']
];

$row = 2;
foreach ($ejemplos as $ejemplo) {
    $col = 'A';
    foreach ($ejemplo as $valor) {
        $sheet->setCellValue($col . $row, $valor);
        $col++;
    }
    $row++;
}

// Estilo para datos de ejemplo (color más claro)
$exampleStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E7E6E6']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'CCCCCC']
        ]
    ]
];

$sheet->getStyle('A2:I4')->applyFromArray($exampleStyle);

// Agregar nota
$sheet->setCellValue('A6', 'INSTRUCCIONES:');
$sheet->setCellValue('A7', '1. Elimina las filas de ejemplo (2-4) antes de cargar tus datos');
$sheet->setCellValue('A8', '2. La columna "Serie" es obligatoria y debe ser única');
$sheet->setCellValue('A9', '3. El proveedor debe existir previamente en el sistema');
$sheet->setCellValue('A10', '4. La cantidad debe ser un número entero positivo');
$sheet->setCellValue('A11', '5. No modifiques los encabezados de la primera fila');

$sheet->getStyle('A6')->getFont()->setBold(true)->setSize(12);
$sheet->mergeCells('A7:I7');
$sheet->mergeCells('A8:I8');
$sheet->mergeCells('A9:I9');
$sheet->mergeCells('A10:I10');
$sheet->mergeCells('A11:I11');

// Guardar archivo
$writer = new Xlsx($spreadsheet);
$filename = 'assets/templates/plantilla_equipos.xlsx';

try {
    $writer->save($filename);
    echo "✓ Plantilla creada exitosamente en: $filename\n";
    echo "Tamaño del archivo: " . filesize($filename) . " bytes\n";
} catch (Exception $e) {
    echo "✗ Error al crear la plantilla: " . $e->getMessage() . "\n";
}
?>
