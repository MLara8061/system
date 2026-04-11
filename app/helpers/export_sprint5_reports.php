<?php
if (!defined('ROOT')) define('ROOT', realpath(__DIR__ . '/../..'));
if (!defined('ACCESS')) define('ACCESS', true);

require_once ROOT . '/config/session.php';
require_once ROOT . '/config/config.php';
require_once ROOT . '/app/helpers/permissions.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    die('Sesion expirada');
}

$canExport = function_exists('can') ? can('export', 'reports') : ((int)($_SESSION['login_type'] ?? 0) === 1);
if (!$canExport && (int)($_SESSION['login_type'] ?? 0) !== 1) {
    http_response_code(403);
    die('Sin permisos para exportar');
}

$type = strtolower(trim($_GET['type'] ?? 'energy'));
$departmentFilter = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

$root = ROOT;
$zipStreamStub = $root . '/lib/ZipStream.php';
if (file_exists($zipStreamStub)) require_once $zipStreamStub;
require_once $root . '/vendor/autoload.php';

$branchAndE = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'e') : '';
$branchAndA = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'a') : '';
$branchAndM = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'mr') : '';
$depAnd = $departmentFilter > 0 ? " AND ed.department_id = {$departmentFilter}" : '';

$hasUsageHours = false;
$hasEqInAccessories = false;
$col = @$conn->query("SHOW COLUMNS FROM equipment_power_specs LIKE 'daily_usage_hours'");
if ($col && $col->num_rows > 0) $hasUsageHours = true;
$col2 = @$conn->query("SHOW COLUMNS FROM accessories LIKE 'equipment_id'");
if ($col2 && $col2->num_rows > 0) $hasEqInAccessories = true;
$usageExpr = $hasUsageHours ? 'COALESCE(eps.daily_usage_hours, 8)' : '8';

$title = 'Reporte Sprint5';
$headers = [];
$rows = [];

if ($type === 'energy') {
    $title = 'Consumo Electrico';
    $headers = ['Equipo', 'Inventario', 'Departamento', 'Potencia (W)', 'Horas/Dia', 'kWh/Mes'];
    $sql = "
        SELECT
            e.name AS equipment_name,
            e.number_inventory,
            COALESCE(d.name, 'Sin departamento') AS department_name,
            ROUND(COALESCE(eps.power_w, (eps.voltage * eps.amperage)), 2) AS power_w,
            {$usageExpr} AS daily_usage_hours,
            ROUND((COALESCE(eps.power_w, (eps.voltage * eps.amperage)) * {$usageExpr} * 30) / 1000, 2) AS kwh_monthly
        FROM equipment_power_specs eps
        INNER JOIN equipments e ON e.id = eps.equipment_id
        LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
        LEFT JOIN departments d ON d.id = ed.department_id
        WHERE 1=1 {$branchAndE} {$depAnd}
        ORDER BY kwh_monthly DESC
    ";
    $res = @$conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['equipment_name'] ?? '',
                $r['number_inventory'] ?? '',
                $r['department_name'] ?? '',
                (float)$r['power_w'],
                (float)$r['daily_usage_hours'],
                (float)$r['kwh_monthly'],
            ];
        }
    }
} elseif ($type === 'accessories') {
    $title = 'Top Gasto Accesorios';
    $headers = ['Equipo', 'Inventario', 'Total Piezas', 'Monto Total'];
    if ($hasEqInAccessories) {
        $sql = "
            SELECT
                e.name AS equipment_name,
                e.number_inventory,
                COUNT(a.id) AS total_piezas,
                ROUND(COALESCE(SUM(a.cost), 0), 2) AS total_monto
            FROM accessories a
            INNER JOIN equipments e ON e.id = a.equipment_id
            WHERE 1=1 {$branchAndA}
            GROUP BY e.id, e.name, e.number_inventory
            ORDER BY total_monto DESC, total_piezas DESC
            LIMIT 100
        ";
        $res = @$conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    $r['equipment_name'] ?? '',
                    $r['number_inventory'] ?? '',
                    (int)$r['total_piezas'],
                    (float)$r['total_monto'],
                ];
            }
        }
    }
} elseif ($type === 'tickets') {
    $title = 'Ranking Tickets por Equipo';
    $headers = ['Equipo', 'Inventario', 'Total Tickets'];
    $sql = "
        SELECT
            e.name AS equipment_name,
            e.number_inventory,
            COUNT(t.id) AS total
        FROM tickets t
        INNER JOIN equipments e ON e.id = t.equipment_id
        LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
        WHERE t.equipment_id IS NOT NULL {$branchAndE} {$depAnd}
        GROUP BY e.id, e.name, e.number_inventory
        ORDER BY total DESC
        LIMIT 100
    ";
    $res = @$conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['equipment_name'] ?? '',
                $r['number_inventory'] ?? '',
                (int)$r['total'],
            ];
        }
    }
} elseif ($type === 'maintenance') {
    $title = 'Ranking Mantenimientos por Equipo';
    $headers = ['Equipo', 'Inventario', 'Total Mantenimientos'];
    $sql = "
        SELECT
            e.name AS equipment_name,
            e.number_inventory,
            COUNT(mr.id) AS total
        FROM maintenance_reports mr
        INNER JOIN equipments e ON e.id = mr.equipment_id
        LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
        WHERE mr.equipment_id IS NOT NULL {$branchAndM} {$depAnd}
        GROUP BY e.id, e.name, e.number_inventory
        ORDER BY total DESC
        LIMIT 100
    ";
    $res = @$conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                $r['equipment_name'] ?? '',
                $r['number_inventory'] ?? '',
                (int)$r['total'],
            ];
        }
    }
}

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(substr($title, 0, 31));

$sheet->setCellValue('A1', strtoupper($title));
$lastCol = chr(ord('A') + max(count($headers) - 1, 0));
$sheet->mergeCells("A1:{$lastCol}1");
$sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->setSize(14);

$sheet->setCellValue('A2', 'Generado: ' . date('d/m/Y H:i'));
$sheet->mergeCells("A2:{$lastCol}2");

$rowNum = 4;
$colNum = 0;
foreach ($headers as $h) {
    $col = chr(ord('A') + $colNum);
    $sheet->setCellValue($col . $rowNum, $h);
    $sheet->getStyle($col . $rowNum)->getFont()->setBold(true);
    $colNum++;
}

$rowNum = 5;
foreach ($rows as $r) {
    $colNum = 0;
    foreach ($r as $v) {
        $col = chr(ord('A') + $colNum);
        $sheet->setCellValue($col . $rowNum, $v);
        $colNum++;
    }
    $rowNum++;
}

for ($i = 0; $i < count($headers); $i++) {
    $col = chr(ord('A') + $i);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    // Establecer ancho mínimo para evitar compresión
    $minWidth = 15;
    if ($i == 0 || $i == 1) $minWidth = 20; // Equipo, Inventario
    if ($i == 2) $minWidth = 18; // Departamento
    if ($sheet->getColumnDimension($col)->getWidth() < $minWidth) {
        $sheet->getColumnDimension($col)->setWidth($minWidth);
    }
}

$filename = 'sprint5_' . strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $type)) . '_' . date('Ymd_His') . '.xlsx';

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
