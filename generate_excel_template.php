<?php
/**
 * Generador de plantilla Excel con validaciones usando PHPSpreadsheet
 */

// Verificar sesión
session_start();
if (!isset($_SESSION['login_id'])) {
    die('Acceso no autorizado');
}

// Incluir conexión a base de datos
include 'db_connect.php';

// Cargar PHPSpreadsheet
require 'lib/PhpSpreadsheet-1.29.0/src/PhpSpreadsheet/Autoloader.php';
\PhpOffice\PhpSpreadsheet\Autoloader::register();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // Crear nuevo documento
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Equipos');

    // ========== ENCABEZADOS ==========
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

    // Estilo de encabezados
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4CAF50']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ];
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(20);
    $sheet->getColumnDimension('C')->setWidth(15);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(35);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(20);
    $sheet->getColumnDimension('I')->setWidth(10);

    // ========== OBTENER DATOS DE LA BD ==========
    
    // Obtener proveedores
    $proveedores = [];
    $query_proveedores = $conn->query("SELECT name FROM suppliers ORDER BY name ASC");
    if ($query_proveedores) {
        while ($row = $query_proveedores->fetch_assoc()) {
            $proveedores[] = $row['name'];
        }
    }

    // Tipos de adquisición comunes
    $tipos_adquisicion = ['Compra', 'Donación', 'Comodato', 'Arrendamiento', 'Préstamo'];

    // Disciplinas comunes
    $disciplinas = ['Informática', 'Audiovisual', 'Oficina', 'Laboratorio', 'Médico', 'Industrial', 'Educativo'];

    // ========== CREAR HOJA DE DATOS DE REFERENCIA ==========
    $dataSheet = $spreadsheet->createSheet(1);
    $dataSheet->setTitle('Datos');

    // Columna A: Tipos de Adquisición
    $dataSheet->setCellValue('A1', 'Tipos de Adquisición');
    foreach ($tipos_adquisicion as $index => $tipo) {
        $dataSheet->setCellValue('A' . ($index + 2), $tipo);
    }

    // Columna B: Disciplinas
    $dataSheet->setCellValue('B1', 'Disciplinas');
    foreach ($disciplinas as $index => $disciplina) {
        $dataSheet->setCellValue('B' . ($index + 2), $disciplina);
    }

    // Columna C: Proveedores
    $dataSheet->setCellValue('C1', 'Proveedores');
    if (!empty($proveedores)) {
        foreach ($proveedores as $index => $proveedor) {
            $dataSheet->setCellValue('C' . ($index + 2), $proveedor);
        }
    }

    // ========== FILAS DE EJEMPLO ==========
    $ejemplos = [
        ['EQ-001-2024', 'Laptop Dell', 'Dell', 'Latitude 5520', 'Compra', 'Intel i5, 16GB RAM, 512GB SSD', 'Informática', '', '1'],
        ['EQ-002-2024', 'Proyector', 'Epson', 'PowerLite X49', 'Donación', '3LCD, 3600 lúmenes, HDMI', 'Audiovisual', '', '1'],
        ['EQ-003-2024', 'Impresora', 'HP', 'LaserJet Pro M404dn', 'Comodato', 'Blanco y negro, 38 ppm, dúplex', 'Oficina', '', '2']
    ];

    $row = 2;
    foreach ($ejemplos as $ejemplo) {
        $col = 'A';
        foreach ($ejemplo as $valor) {
            $sheet->setCellValue($col . $row, $valor);
            $col++;
        }
        
        // Estilo de filas de ejemplo (fondo gris claro)
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0']
            ]
        ]);
        
        $row++;
    }

    // ========== VALIDACIONES DE DATOS ==========
    
    // Rango de filas para aplicar validaciones (de la 2 a la 500)
    $startRow = 2;
    $endRow = 500;

    // Validación para Tipo de Adquisición (Columna E)
    $tiposCount = count($tipos_adquisicion);
    $tiposRange = 'Datos!$A$2:$A$' . ($tiposCount + 1);
    for ($i = $startRow; $i <= $endRow; $i++) {
        $validation = $sheet->getCell('E' . $i)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Error');
        $validation->setError('Seleccione un tipo de adquisición válido');
        $validation->setPromptTitle('Tipo de Adquisición');
        $validation->setPrompt('Seleccione una opción de la lista');
        $validation->setFormula1($tiposRange);
    }

    // Validación para Disciplina (Columna G)
    $disciplinasCount = count($disciplinas);
    $disciplinasRange = 'Datos!$B$2:$B$' . ($disciplinasCount + 1);
    for ($i = $startRow; $i <= $endRow; $i++) {
        $validation = $sheet->getCell('G' . $i)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Error');
        $validation->setError('Seleccione una disciplina válida');
        $validation->setPromptTitle('Disciplina');
        $validation->setPrompt('Seleccione una opción de la lista');
        $validation->setFormula1($disciplinasRange);
    }

    // Validación para Proveedor (Columna H) - solo si hay proveedores
    if (!empty($proveedores)) {
        $proveedoresCount = count($proveedores);
        $proveedoresRange = 'Datos!$C$2:$C$' . ($proveedoresCount + 1);
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell('H' . $i)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Información');
            $validation->setError('Seleccione un proveedor de la lista o deje vacío');
            $validation->setPromptTitle('Proveedor');
            $validation->setPrompt('Seleccione un proveedor registrado (opcional)');
            $validation->setFormula1($proveedoresRange);
        }
    }

    // Validación para Cantidad (Columna I) - solo números positivos
    for ($i = $startRow; $i <= $endRow; $i++) {
        $validation = $sheet->getCell('I' . $i)->getDataValidation();
        $validation->setType(DataValidation::TYPE_WHOLE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $validation->setFormula1('1');
        $validation->setErrorTitle('Error');
        $validation->setError('Ingrese un número entero mayor o igual a 1');
        $validation->setPromptTitle('Cantidad');
        $validation->setPrompt('Ingrese la cantidad de equipos (número entero)');
    }

    // ========== OCULTAR HOJA DE DATOS ==========
    $dataSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // ========== AGREGAR NOTA EN LA PRIMERA HOJA ==========
    $sheet->setCellValue('A' . ($endRow + 2), 'NOTA: Las columnas E (Tipo de Adquisición), G (Disciplina) y H (Proveedor) tienen listas desplegables.');
    $sheet->getStyle('A' . ($endRow + 2))->getFont()->setItalic(true)->setSize(10);
    $sheet->mergeCells('A' . ($endRow + 2) . ':I' . ($endRow + 2));

    // ========== GENERAR Y DESCARGAR ARCHIVO ==========
    $spreadsheet->setActiveSheetIndex(0);

    // Configurar encabezados HTTP
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="plantilla_equipos_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die('Error al generar plantilla: ' . $e->getMessage());
}
?>
