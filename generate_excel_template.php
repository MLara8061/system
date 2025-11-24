<?php
/**
 * Generador de plantilla Excel con validaciones usando PHPSpreadsheet
 */

// Mostrar errores para debugging (comentar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión
session_start();
if (!isset($_SESSION['login_id'])) {
    http_response_code(403);
    die('Acceso no autorizado');
}

// Incluir conexión a base de datos
if (file_exists(__DIR__ . '/config/db_connect.php')) {
    require_once __DIR__ . '/config/db_connect.php';
} elseif (file_exists(__DIR__ . '/db_connect.php')) {
    require_once __DIR__ . '/db_connect.php';
} else {
    die('Error: No se encuentra el archivo de conexión a la base de datos');
}

// Verificar que PHPSpreadsheet existe
$autoloader_path = __DIR__ . '/lib/PhpSpreadsheet-1.29.0/src/PhpSpreadsheet/Autoloader.php';
if (!file_exists($autoloader_path)) {
    die('Error: No se encuentra la librería PHPSpreadsheet en: ' . $autoloader_path);
}

// Cargar PHPSpreadsheet
require $autoloader_path;
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
        'A1' => 'Serie *',
        'B1' => 'Nombre *',
        'C1' => 'Marca',
        'D1' => 'Modelo *',
        'E1' => 'Valor *',
        'F1' => 'Tipo de Adquisición *',
        'G1' => 'Disciplina *',
        'H1' => 'Proveedor *',
        'I1' => 'Cantidad',
        'J1' => 'Características',
        'K1' => 'Voltaje (V)',
        'L1' => 'Amperaje (A)',
        'M1' => 'Frecuencia (Hz)',
        'N1' => 'Departamento *',
        'O1' => 'Ubicación *',
        'P1' => 'Responsable *',
        'Q1' => 'Cargo Responsable',
        'R1' => 'Fecha Capacitación',
        'S1' => 'Factura Nro',
        'T1' => 'Garantía (Años)',
        'U1' => 'Fecha Adquisición'
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
    $sheet->getStyle('A1:U1')->applyFromArray($headerStyle);

    // Ajustar ancho de columnas
    $sheet->getColumnDimension('A')->setWidth(15);  // Serie
    $sheet->getColumnDimension('B')->setWidth(25);  // Nombre
    $sheet->getColumnDimension('C')->setWidth(15);  // Marca
    $sheet->getColumnDimension('D')->setWidth(20);  // Modelo
    $sheet->getColumnDimension('E')->setWidth(12);  // Valor
    $sheet->getColumnDimension('F')->setWidth(20);  // Tipo Adquisición
    $sheet->getColumnDimension('G')->setWidth(15);  // Disciplina
    $sheet->getColumnDimension('H')->setWidth(25);  // Proveedor
    $sheet->getColumnDimension('I')->setWidth(10);  // Cantidad
    $sheet->getColumnDimension('J')->setWidth(35);  // Características
    $sheet->getColumnDimension('K')->setWidth(12);  // Voltaje
    $sheet->getColumnDimension('L')->setWidth(12);  // Amperaje
    $sheet->getColumnDimension('M')->setWidth(12);  // Frecuencia
    $sheet->getColumnDimension('N')->setWidth(20);  // Departamento
    $sheet->getColumnDimension('O')->setWidth(20);  // Ubicación
    $sheet->getColumnDimension('P')->setWidth(25);  // Responsable
    $sheet->getColumnDimension('Q')->setWidth(20);  // Cargo Responsable
    $sheet->getColumnDimension('R')->setWidth(18);  // Fecha Capacitación
    $sheet->getColumnDimension('S')->setWidth(15);  // Factura
    $sheet->getColumnDimension('T')->setWidth(15);  // Garantía
    $sheet->getColumnDimension('U')->setWidth(18);  // Fecha Adquisición

    // ========== OBTENER DATOS DE LA BD ==========
    
    // Obtener proveedores
    $proveedores = [];
    $query_proveedores = $conn->query("SELECT empresa FROM suppliers WHERE estado=1 ORDER BY empresa ASC");
    if ($query_proveedores) {
        while ($row = $query_proveedores->fetch_assoc()) {
            $proveedores[] = $row['empresa'];
        }
    }

    // Obtener departamentos
    $departamentos = [];
    $query_departamentos = $conn->query("SELECT name FROM departments ORDER BY name ASC");
    if ($query_departamentos) {
        while ($row = $query_departamentos->fetch_assoc()) {
            $departamentos[] = $row['name'];
        }
    }

    // Obtener ubicaciones
    $ubicaciones = [];
    $query_ubicaciones = $conn->query("SELECT name FROM locations ORDER BY name ASC");
    if ($query_ubicaciones) {
        while ($row = $query_ubicaciones->fetch_assoc()) {
            $ubicaciones[] = $row['name'];
        }
    }

    // Obtener cargos de responsables
    $cargos = [];
    $query_cargos = $conn->query("SELECT name FROM responsible_positions ORDER BY name ASC");
    if ($query_cargos) {
        while ($row = $query_cargos->fetch_assoc()) {
            $cargos[] = $row['name'];
        }
    }

    // Tipos de adquisición
    $tipos_adquisicion = [];
    $query_tipos = $conn->query("SELECT name FROM acquisition_type ORDER BY name ASC");
    if ($query_tipos) {
        while ($row = $query_tipos->fetch_assoc()) {
            $tipos_adquisicion[] = $row['name'];
        }
    }
    // Si no hay tipos en BD, usar valores por defecto
    if (empty($tipos_adquisicion)) {
        $tipos_adquisicion = ['Compra', 'Donación', 'Comodato', 'Arrendamiento', 'Préstamo'];
    }

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

    // Columna D: Departamentos
    $dataSheet->setCellValue('D1', 'Departamentos');
    if (!empty($departamentos)) {
        foreach ($departamentos as $index => $departamento) {
            $dataSheet->setCellValue('D' . ($index + 2), $departamento);
        }
    }

    // Columna E: Ubicaciones
    $dataSheet->setCellValue('E1', 'Ubicaciones');
    if (!empty($ubicaciones)) {
        foreach ($ubicaciones as $index => $ubicacion) {
            $dataSheet->setCellValue('E' . ($index + 2), $ubicacion);
        }
    }

    // Columna F: Cargos
    $dataSheet->setCellValue('F1', 'Cargos');
    if (!empty($cargos)) {
        foreach ($cargos as $index => $cargo) {
            $dataSheet->setCellValue('F' . ($index + 2), $cargo);
        }
    }

    // ========== FILAS DE EJEMPLO ==========
    $ejemplos = [
        [
            'EQ-001-2024', // Serie
            'Laptop Dell Latitude', // Nombre
            'Dell', // Marca
            'Latitude 5520', // Modelo
            '15000', // Valor
            '', // Tipo Adquisición (dropdown)
            '', // Disciplina (dropdown)
            '', // Proveedor (dropdown)
            '1', // Cantidad
            'Intel i5 11va Gen, 16GB RAM, 512GB SSD, Windows 11 Pro', // Características
            '110', // Voltaje
            '3.5', // Amperaje
            '60', // Frecuencia
            '', // Departamento (dropdown)
            '', // Ubicación (dropdown)
            'Juan Pérez', // Responsable
            '', // Cargo (dropdown)
            date('Y-m-d'), // Fecha Capacitación
            'FAC-001-2024', // Factura
            '2', // Garantía
            date('Y-m-d') // Fecha Adquisición
        ],
        [
            'EQ-002-2024',
            'Proyector Epson',
            'Epson',
            'PowerLite X49',
            '8500',
            '',
            '',
            '',
            '1',
            '3LCD, 3600 lúmenes, HDMI, VGA, resolución XGA',
            '110',
            '2.8',
            '60',
            '',
            '',
            'María García',
            '',
            date('Y-m-d'),
            'FAC-002-2024',
            '1',
            date('Y-m-d')
        ],
        [
            'EQ-003-2024',
            'Impresora HP LaserJet',
            'HP',
            'LaserJet Pro M404dn',
            '5200',
            '',
            '',
            '',
            '2',
            'Impresión B/N, 38 ppm, dúplex automático, red ethernet',
            '110',
            '1.2',
            '60',
            '',
            '',
            'Carlos López',
            '',
            date('Y-m-d'),
            '',
            '1',
            date('Y-m-d')
        ]
    ];

    $row = 2;
    foreach ($ejemplos as $ejemplo) {
        $col = 'A';
        foreach ($ejemplo as $valor) {
            $sheet->setCellValue($col . $row, $valor);
            $col++;
        }
        
        // Estilo de filas de ejemplo (fondo gris claro)
        $sheet->getStyle('A' . $row . ':U' . $row)->applyFromArray([
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

    // Validación para Tipo de Adquisición (Columna F)
    $tiposCount = count($tipos_adquisicion);
    $tiposRange = 'Datos!$A$2:$A$' . ($tiposCount + 1);
    for ($i = $startRow; $i <= $endRow; $i++) {
        $validation = $sheet->getCell('F' . $i)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
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
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Error');
        $validation->setError('Seleccione una disciplina válida');
        $validation->setPromptTitle('Disciplina');
        $validation->setPrompt('Seleccione una opción de la lista');
        $validation->setFormula1($disciplinasRange);
    }

    // Validación para Proveedor (Columna H)
    if (!empty($proveedores)) {
        $proveedoresCount = count($proveedores);
        $proveedoresRange = 'Datos!$C$2:$C$' . ($proveedoresCount + 1);
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell('H' . $i)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Información');
            $validation->setError('Seleccione un proveedor de la lista');
            $validation->setPromptTitle('Proveedor');
            $validation->setPrompt('Seleccione un proveedor registrado');
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

    // Validación para Departamento (Columna N)
    if (!empty($departamentos)) {
        $departamentosCount = count($departamentos);
        $departamentosRange = 'Datos!$D$2:$D$' . ($departamentosCount + 1);
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell('N' . $i)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Error');
            $validation->setError('Seleccione un departamento válido');
            $validation->setPromptTitle('Departamento');
            $validation->setPrompt('Seleccione un departamento de la lista');
            $validation->setFormula1($departamentosRange);
        }
    }

    // Validación para Ubicación (Columna O)
    if (!empty($ubicaciones)) {
        $ubicacionesCount = count($ubicaciones);
        $ubicacionesRange = 'Datos!$E$2:$E$' . ($ubicacionesCount + 1);
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell('O' . $i)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Error');
            $validation->setError('Seleccione una ubicación válida');
            $validation->setPromptTitle('Ubicación');
            $validation->setPrompt('Seleccione una ubicación de la lista');
            $validation->setFormula1($ubicacionesRange);
        }
    }

    // Validación para Cargo Responsable (Columna Q)
    if (!empty($cargos)) {
        $cargosCount = count($cargos);
        $cargosRange = 'Datos!$F$2:$F$' . ($cargosCount + 1);
        for ($i = $startRow; $i <= $endRow; $i++) {
            $validation = $sheet->getCell('Q' . $i)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Información');
            $validation->setError('Seleccione un cargo de la lista o deje vacío');
            $validation->setPromptTitle('Cargo Responsable');
            $validation->setPrompt('Seleccione el cargo del responsable (opcional)');
            $validation->setFormula1($cargosRange);
        }
    }

    // ========== OCULTAR HOJA DE DATOS ==========
    $dataSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // ========== AGREGAR NOTA EN LA PRIMERA HOJA ==========
    $sheet->setCellValue('A' . ($endRow + 2), 'NOTA: Los campos marcados con * son obligatorios. Las columnas F, G, H, N, O y Q tienen listas desplegables.');
    $sheet->getStyle('A' . ($endRow + 2))->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('FF0000');
    $sheet->mergeCells('A' . ($endRow + 2) . ':U' . ($endRow + 2));

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
    error_log('Error en generate_excel_template.php: ' . $e->getMessage());
    http_response_code(500);
    die('Error al generar plantilla: ' . $e->getMessage() . '<br>Archivo: ' . $e->getFile() . '<br>Línea: ' . $e->getLine());
}
?>
