<?php
/**
 * Export Bajas de Equipos to Excel
 * Usage: /app/helpers/export_equipment_bajas.php?format=xlsx
 */

if (!defined('ACCESS')) define('ACCESS', true);
if (!defined('DB_CONFIG')) require_once __DIR__ . '/../../config/config.php';

// Validar permisos
if (!isset($_SESSION['login_id'])) {
    http_response_code(403);
    die('Acceso denegado');
}

// Incluir PhpSpreadsheet
require_once ROOT . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bajas de Equipos');

// Headers con formato
$headers = ['Folio', 'Equipo', 'N° Inv.', 'Marca', 'Modelo', 'Fecha', 'Usuario', 'Responsable', 'Destino', 'Dictamen', 'Causas', 'Mantenimientos'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $col++;
}

// Filtros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
$date_filter = '';

if ($fecha_inicio && $fecha_fin) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
        if (strtotime($fecha_inicio) <= strtotime($fecha_fin)) {
            $date_filter = " AND eu.date >= '" . $conn->real_escape_string($fecha_inicio) . "' AND eu.date <= '" . $conn->real_escape_string($fecha_fin) . "'";
        }
    }
}

// Catálogos
$responsibleLabels = [
    1 => 'Jefe de servicio',
    2 => 'Proveedor externo'
];
$destinationLabels = [
    1 => 'Guardar en bodega',
    2 => 'Devolución al proveedor',
    3 => 'Donar',
    4 => 'Venta',
    5 => 'Basura'
];

$reasonCatalog = [];
$reasonRes = $conn->query('SELECT id, name FROM equipment_withdrawal_reason');
while ($reasonRes && $row = $reasonRes->fetch_assoc()) {
    $reasonCatalog[(int)$row['id']] = $row['name'];
}
if ($reasonRes) {
    $reasonRes->free();
}

// Query
$sql = "SELECT eu.*, e.name AS equipment_name, e.number_inventory, e.brand, e.model,
               (SELECT COUNT(1) FROM maintenance_reports mr WHERE mr.equipment_id = eu.equipment_id) AS maintenance_total
        FROM equipment_unsubscribe eu
        INNER JOIN equipments e ON e.id = eu.equipment_id
    " . branch_sql('WHERE', 'e.branch_id') . " {$date_filter}
        ORDER BY eu.date DESC, eu.time DESC, eu.id DESC";

$records = $conn->query($sql);
$row_num = 2;

if ($records) {
    while ($row = $records->fetch_assoc()) {
        $date = !empty($row['date']) ? date('d/m/Y', strtotime($row['date'])) : '';
        $dictamen = isset($row['opinion']) ? (((int)$row['opinion'] === 1) ? 'Funcional' : 'Disfuncional') : 'Sin dictamen';
        $destino = isset($destinationLabels[(int)$row['destination']]) ? $destinationLabels[(int)$row['destination']] : 'No especificado';
        $responsable = isset($responsibleLabels[(int)$row['responsible']]) ? $responsibleLabels[(int)$row['responsible']] : 'No especificado';
        $usuario = !empty($row['processed_by_name']) ? $row['processed_by_name'] : 'No registrado';
        $maintenanceTotal = (int)($row['maintenance_total'] ?? 0);

        // Procesar causas
        $reasonList = [];
        if (!empty($row['withdrawal_reason'])) {
            $decoded = json_decode($row['withdrawal_reason'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $reasonId) {
                    $reasonId = (int)$reasonId;
                    if (isset($reasonCatalog[$reasonId])) {
                        $reasonList[] = $reasonCatalog[$reasonId];
                    }
                }
            }
        }
        $reasonText = !empty($reasonList) ? implode(', ', $reasonList) : 'Sin causas';

        // Folio
        $folio = !empty($row['folio']) ? $row['folio'] : sprintf('BAJ-%s-%04d', date('Y', strtotime($row['date'] ?? 'now')), (int)$row['id']);

        // Llenar fila
        $sheet->setCellValue('A' . $row_num, $folio);
        $sheet->setCellValue('B' . $row_num, $row['equipment_name'] ?? '');
        $sheet->setCellValue('C' . $row_num, $row['number_inventory'] ?? '');
        $sheet->setCellValue('D' . $row_num, $row['brand'] ?? '');
        $sheet->setCellValue('E' . $row_num, $row['model'] ?? '');
        $sheet->setCellValue('F' . $row_num, $date);
        $sheet->setCellValue('G' . $row_num, $usuario);
        $sheet->setCellValue('H' . $row_num, $responsable);
        $sheet->setCellValue('I' . $row_num, $destino);
        $sheet->setCellValue('J' . $row_num, $dictamen);
        $sheet->setCellValue('K' . $row_num, $reasonText);
        $sheet->setCellValue('L' . $row_num, $maintenanceTotal);

        $row_num++;
    }
    $records->free();
}

// Ajustar anchos de columnas
$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(14);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(18);
$sheet->getColumnDimension('I')->setWidth(20);
$sheet->getColumnDimension('J')->setWidth(12);
$sheet->getColumnDimension('K')->setWidth(30);
$sheet->getColumnDimension('L')->setWidth(14);

// Centrar algunos datos
for ($row = 2; $row < $row_num; $row++) {
    $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Descargar
$filename = 'Bajas_Equipos_' . date('Y-m-d_Hi') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
