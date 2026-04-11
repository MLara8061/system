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

if (!function_exists('tre_col_exists')) {
    function tre_col_exists(mysqli $conn, $table, $column) {
        static $cache = [];
        $key = $table . '.' . $column;
        if (isset($cache[$key])) return $cache[$key];
        $t = $conn->real_escape_string($table);
        $c = $conn->real_escape_string($column);
        $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
        $exists = $res && $res->num_rows > 0;
        if ($res instanceof mysqli_result) $res->free();
        $cache[$key] = $exists;
        return $exists;
    }
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');
$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$assignedTo = isset($_GET['assigned_to']) ? (int)$_GET['assigned_to'] : 0;
$format = strtolower(trim($_GET['format'] ?? 'excel'));
if (!in_array($format, ['excel', 'pdf'], true)) $format = 'excel';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-t');
if ($from > $to) {
    $tmp = $from;
    $from = $to;
    $to = $tmp;
}

$hasAssignedTo = tre_col_exists($conn, 'tickets', 'assigned_to');
$hasTSH = tre_col_exists($conn, 'ticket_status_history', 'ticket_id');
$hasDateUpdated = tre_col_exists($conn, 'tickets', 'date_updated');

$assignedJoin = $hasAssignedTo ? 'LEFT JOIN users au ON au.id = t.assigned_to' : '';
$assignedSelect = $hasAssignedTo
    ? "COALESCE(CONCAT(au.firstname, ' ', au.lastname), 'Sin asignar') AS assigned_name,"
    : "'Sin asignar' AS assigned_name,";
$assignedFilter = ($hasAssignedTo && $assignedTo > 0) ? " AND t.assigned_to = {$assignedTo} " : '';

$firstResponseSelect = 'NULL AS first_response_at';
$closedAtSelect = 'NULL AS closed_at';
if ($hasTSH) {
    $firstResponseSelect = "(
        SELECT MIN(h.created_at)
        FROM ticket_status_history h
        WHERE h.ticket_id = t.id AND h.new_status = 1
    ) AS first_response_at";

    $closedFallback = $hasDateUpdated ? 't.date_updated' : 'NULL';
    $closedAtSelect = "COALESCE((
        SELECT MIN(hc.created_at)
        FROM ticket_status_history hc
        WHERE hc.ticket_id = t.id AND hc.new_status IN (2,3)
    ), {$closedFallback}) AS closed_at";
}

$branchAnd = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'e') : '';
$depAnd = $departmentId > 0 ? " AND t.department_id = {$departmentId} " : '';

$sql = "
    SELECT
        t.id,
        COALESCE(t.ticket_number, CONCAT('TKT-', t.id)) AS ticket_number,
        COALESCE(t.subject, t.title, '') AS subject,
        COALESCE(t.status, 0) AS status,
        t.date_created,
        COALESCE(d.name, 'Sin departamento') AS department_name,
        {$assignedSelect}
        {$firstResponseSelect},
        {$closedAtSelect}
    FROM tickets t
    LEFT JOIN departments d ON d.id = t.department_id
    LEFT JOIN equipments e ON e.id = t.equipment_id
    {$assignedJoin}
    WHERE DATE(t.date_created) BETWEEN '{$from}' AND '{$to}'
      {$depAnd}
      {$assignedFilter}
      {$branchAnd}
    ORDER BY t.date_created DESC
";

$rows = [];
$res = @$conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $createdTs = !empty($r['date_created']) ? strtotime($r['date_created']) : 0;
        $firstTs = !empty($r['first_response_at']) ? strtotime($r['first_response_at']) : 0;
        $closeTs = !empty($r['closed_at']) ? strtotime($r['closed_at']) : 0;
        $firstHours = ($createdTs > 0 && $firstTs >= $createdTs) ? round(($firstTs - $createdTs) / 3600, 2) : null;
        $closeHours = ($createdTs > 0 && $closeTs >= $createdTs) ? round(($closeTs - $createdTs) / 3600, 2) : null;

        $statusRaw = (string)($r['status'] ?? '0');
        $statusLabel = 'Abierto';
        if ($statusRaw === '1' || strtolower($statusRaw) === 'in_progress') $statusLabel = 'En Proceso';
        if ($statusRaw === '2' || strtolower($statusRaw) === 'resolved') $statusLabel = 'Resuelto';
        if ($statusRaw === '3' || strtolower($statusRaw) === 'closed') $statusLabel = 'Cerrado';

        $rows[] = [
            'ticket_number' => $r['ticket_number'] ?? '',
            'subject' => $r['subject'] ?? '',
            'department_name' => $r['department_name'] ?? '',
            'assigned_name' => $r['assigned_name'] ?? 'Sin asignar',
            'status_label' => $statusLabel,
            'created_at' => !empty($r['date_created']) ? date('d/m/Y H:i', strtotime($r['date_created'])) : '',
            'first_response_at' => !empty($r['first_response_at']) ? date('d/m/Y H:i', strtotime($r['first_response_at'])) : '',
            'closed_at' => !empty($r['closed_at']) ? date('d/m/Y H:i', strtotime($r['closed_at'])) : '',
            'first_hours' => $firstHours,
            'close_hours' => $closeHours,
        ];
    }
    $res->free();
}

if ($format === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Tickets</title>
<style>
body { font-family: Arial, sans-serif; color: #222; margin: 20px; }
h1 { margin: 0 0 8px; }
.meta { color: #666; font-size: 12px; margin-bottom: 14px; }
table { width: 100%; border-collapse: collapse; font-size: 11px; }
th, td { border: 1px solid #d9d9d9; padding: 6px; }
th { background: #f3f4f6; text-align: left; }
.text-right { text-align: right; }
@media print { body { margin: 8px; } }
</style>
</head>
<body onload="window.print()">
    <h1>Reporte de Tickets</h1>
    <div class="meta">Periodo: <?= htmlspecialchars($from) ?> a <?= htmlspecialchars($to) ?> | Registros: <?= count($rows) ?></div>
    <table>
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Asunto</th>
                <th>Departamento</th>
                <th>Técnico</th>
                <th>Estado</th>
                <th>Creado</th>
                <th>1ra respuesta</th>
                <th>Cierre</th>
                <th class="text-right">Horas 1ra resp.</th>
                <th class="text-right">Horas cierre</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['ticket_number']) ?></td>
                <td><?= htmlspecialchars($r['subject']) ?></td>
                <td><?= htmlspecialchars($r['department_name']) ?></td>
                <td><?= htmlspecialchars($r['assigned_name']) ?></td>
                <td><?= htmlspecialchars($r['status_label']) ?></td>
                <td><?= htmlspecialchars($r['created_at']) ?></td>
                <td><?= htmlspecialchars($r['first_response_at'] ?: '—') ?></td>
                <td><?= htmlspecialchars($r['closed_at'] ?: '—') ?></td>
                <td class="text-right"><?= $r['first_hours'] !== null ? number_format((float)$r['first_hours'], 2) : '—' ?></td>
                <td class="text-right"><?= $r['close_hours'] !== null ? number_format((float)$r['close_hours'], 2) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="10" style="text-align:center;color:#777;">Sin datos en el periodo</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
    exit;
}

$zipStreamStub = ROOT . '/lib/ZipStream.php';
if (file_exists($zipStreamStub)) require_once $zipStreamStub;
require_once ROOT . '/vendor/autoload.php';

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Tickets Reporte');

$headers = ['Ticket', 'Asunto', 'Departamento', 'Tecnico', 'Estado', 'Creado', '1ra respuesta', 'Cierre', 'Horas 1ra resp.', 'Horas cierre'];
$sheet->setCellValue('A1', 'REPORTE DE TICKETS');
$sheet->mergeCells('A1:J1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->setCellValue('A2', 'Periodo: ' . $from . ' a ' . $to);
$sheet->mergeCells('A2:J2');

$rowNum = 4;
for ($i = 0; $i < count($headers); $i++) {
    $col = chr(ord('A') + $i);
    $sheet->setCellValue($col . $rowNum, $headers[$i]);
    $sheet->getStyle($col . $rowNum)->getFont()->setBold(true);
}

$rowNum = 5;
foreach ($rows as $r) {
    $sheet->setCellValue('A' . $rowNum, $r['ticket_number']);
    $sheet->setCellValue('B' . $rowNum, $r['subject']);
    $sheet->setCellValue('C' . $rowNum, $r['department_name']);
    $sheet->setCellValue('D' . $rowNum, $r['assigned_name']);
    $sheet->setCellValue('E' . $rowNum, $r['status_label']);
    $sheet->setCellValue('F' . $rowNum, $r['created_at']);
    $sheet->setCellValue('G' . $rowNum, $r['first_response_at']);
    $sheet->setCellValue('H' . $rowNum, $r['closed_at']);
    $sheet->setCellValue('I' . $rowNum, $r['first_hours'] !== null ? (float)$r['first_hours'] : '');
    $sheet->setCellValue('J' . $rowNum, $r['close_hours'] !== null ? (float)$r['close_hours'] : '');
    $rowNum++;
}

for ($i = 0; $i < count($headers); $i++) {
    $col = chr(ord('A') + $i);
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// === AJUSTE DE ANCHO MÍNIMO POR COLUMNA (PARA EVITAR COMPRESIÓN) ===
$columnWidths = [
    'A' => 15, // Ticket
    'B' => 35, // Asunto
    'C' => 20, // Departamento
    'D' => 18, // Técnico
    'E' => 12, // Estado
    'F' => 18, // Creado
    'G' => 18, // 1ra respuesta
    'H' => 18, // Cierre
    'I' => 15, // Horas 1ra resp
    'J' => 12  // Horas cierre
];

foreach ($columnWidths as $col => $width) {
    $sheet->getColumnDimension($col)->setWidth($width);
}

while (ob_get_level()) ob_end_clean();
$filename = 'tickets_report_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
