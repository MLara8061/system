<?php
/**
 * app/helpers/export_maintenance_calendar.php
 * Exporta el calendario de mantenimiento a Excel (PhpSpreadsheet).
 *
 * Parametros GET:
 *   format  = excel | pdf
 *   from    = YYYY-MM-DD  (opcional, default: primer dia del mes actual)
 *   to      = YYYY-MM-DD  (opcional, default: ultimo dia del mes actual)
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!defined('ROOT')) define('ROOT', realpath(__DIR__ . '/../..'));
if (!defined('ACCESS')) define('ACCESS', true);

$root = ROOT;

require_once $root . '/config/session.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    die('Sesión expirada');
}

require_once $root . '/config/config.php';
require_once $root . '/app/helpers/permissions.php';

$canExport = function_exists('can') ? can('export', 'reports') : ((int)($_SESSION['login_type'] ?? 0) === 1);
if (!$canExport && (int)($_SESSION['login_type'] ?? 0) !== 1) {
    http_response_code(403);
    die('Sin permisos para exportar');
}

/* ── Rango de fechas ──────────────────────────────────────── */
$from   = $_GET['from'] ?? date('Y-m-01');
$to     = $_GET['to']   ?? date('Y-m-t');
$format = strtolower(preg_replace('/[^a-z]/', '', $_GET['format'] ?? 'excel'));

// Validar fechas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-t');
if ($from > $to) [$from, $to] = [$to, $from];

/* ── Consulta ─────────────────────────────────────────────── */
$branch_sql = '';
if (function_exists('branch_sql')) {
    $branch_sql = branch_sql('AND', 'branch_id', 'e');
}

$sql = "SELECT
            m.id,
            m.fecha_programada,
            m.hora_programada,
            m.tipo_mantenimiento,
            m.estatus,
            m.descripcion,
            e.name           AS equipo_nombre,
            e.number_inventory AS numero_inventario,
            e.serie,
            d.name           AS departamento
        FROM mantenimientos m
        JOIN equipments e ON m.equipo_id = e.id
        LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
        LEFT JOIN departments d         ON d.id = ed.department_id
        WHERE m.fecha_programada BETWEEN ? AND ?
        " . $branch_sql . "
        ORDER BY m.fecha_programada ASC, m.hora_programada ASC";

try {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$from, $to]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("export_maintenance_calendar: " . $e->getMessage());
    die("Error al obtener datos");
}

/* ╔══════════════════════════════════════════════════════════╗
   ║  FORMATO EXCEL                                           ║
   ╚══════════════════════════════════════════════════════════╝ */
if ($format === 'excel') {

    $zipStreamStub = $root . '/lib/ZipStream.php';
    if (file_exists($zipStreamStub)) require_once $zipStreamStub;
    require_once $root . '/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Calendario Mantenimiento');

    /* Cabecera general */
    $sheet->mergeCells('A1:H1');
    $sheet->setCellValue('A1', 'CALENDARIO DE MANTENIMIENTO');
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(28);

    /* Subtítulo con rango */
    $sheet->mergeCells('A2:H2');
    $sheet->setCellValue('A2', 'Periodo: ' . date('d/m/Y', strtotime($from)) . ' — ' . date('d/m/Y', strtotime($to)));
    $sheet->getStyle('A2')->applyFromArray([
        'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ]);

    /* Encabezados de columna */
    $headers = ['#', 'Fecha', 'Hora', 'Equipo', 'Num. Inventario', 'Tipo', 'Departamento', 'Estatus'];
    $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $headerStyle = [
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '37474F']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
    ];
    foreach ($headers as $i => $h) {
        $sheet->setCellValue($cols[$i] . '3', $h);
    }
    $sheet->getStyle('A3:H3')->applyFromArray($headerStyle);

    /* Anchos */
    $widths = [6, 14, 10, 36, 18, 16, 26, 14];
    foreach ($cols as $i => $col) {
        $sheet->getColumnDimension($col)->setWidth($widths[$i]);
    }

    /* Filas de datos */
    $statusColors = [
        'completado' => ['bg' => 'E8F5E9', 'fg' => '1B5E20'],
        'pendiente'  => ['bg' => 'FFF8E1', 'fg' => 'F57F17'],
    ];
    $tipoColors = [
        'preventivo' => '1565C0',
        'correctivo' => 'B71C1C',
        'predictivo' => '4A148C',
    ];

    $row = 4;
    foreach ($rows as $idx => $r) {
        $estatus    = strtolower($r['estatus'] ?? 'pendiente');
        $tipo       = strtolower($r['tipo_mantenimiento'] ?? '');
        $sc         = $statusColors[$estatus] ?? ['bg' => 'FFFFFF', 'fg' => '000000'];
        $tipoColor  = $tipoColors[$tipo] ?? '000000';

        $sheet->setCellValue('A' . $row, $idx + 1);
        $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($r['fecha_programada'])));
        $sheet->setCellValue('C' . $row, $r['hora_programada'] ? substr($r['hora_programada'], 0, 5) : '—');
        $sheet->setCellValue('D' . $row, $r['equipo_nombre'] ?? '');
        $sheet->setCellValue('E' . $row, $r['numero_inventario'] ?? '—');
        $sheet->setCellValue('F' . $row, ucfirst($r['tipo_mantenimiento'] ?? ''));
        $sheet->setCellValue('G' . $row, $r['departamento'] ?? '—');
        $sheet->setCellValue('H' . $row, ucfirst($estatus));

        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $sc['bg']]],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
        ]);
        $sheet->getStyle('F' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($tipoColor))->setBold(true);
        $sheet->getStyle('H' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($sc['fg']))->setBold(true);
        $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row++;
    }

    /* Fila de totales */
    $total       = count($rows);
    $completados = count(array_filter($rows, fn($r) => strtolower($r['estatus'] ?? '') === 'completado'));
    $pendientes  = $total - $completados;

    $sheet->mergeCells('A' . $row . ':C' . $row);
    $sheet->setCellValue('A' . $row, 'TOTALES');
    $sheet->setCellValue('D' . $row, 'Total: ' . $total);
    $sheet->setCellValue('F' . $row, 'Completados: ' . $completados);
    $sheet->setCellValue('H' . $row, 'Pendientes: ' . $pendientes);
    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECEFF1']],
    ]);

    /* Freeze panes */
    $sheet->freezePane('A4');

    /* Descargar */
    while (ob_get_level()) ob_end_clean();

    $filename = 'calendario_mantenimiento_' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/* ╔══════════════════════════════════════════════════════════╗
   ║  FORMATO PDF (HTML imprimible)                           ║
   ╚══════════════════════════════════════════════════════════╝ */
if ($format === 'pdf') {
    $fromDisplay = date('d/m/Y', strtotime($from));
    $toDisplay   = date('d/m/Y', strtotime($to));
    $generado    = date('d/m/Y H:i');
    $usuario     = htmlspecialchars(($_SESSION['login_firstname'] ?? '') . ' ' . ($_SESSION['login_lastname'] ?? ''));

    $totalRows       = count($rows);
    $completadosRows = count(array_filter($rows, fn($r) => strtolower($r['estatus'] ?? '') === 'completado'));
    $pendientesRows  = $totalRows - $completadosRows;

    $tipoLabel = [
        'preventivo' => ['label' => 'Preventivo', 'color' => '#1565C0'],
        'correctivo' => ['label' => 'Correctivo', 'color' => '#B71C1C'],
        'predictivo' => ['label' => 'Predictivo', 'color' => '#4A148C'],
    ];

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Calendario de Mantenimiento</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #212121; background: #fff; }
    .header { background: #1565C0; color: #fff; padding: 14px 20px; }
    .header h1 { font-size: 18px; font-weight: bold; }
    .header p  { font-size: 11px; margin-top: 4px; opacity: .85; }
    .meta { display: flex; gap: 40px; padding: 10px 20px; background: #F5F5F5; border-bottom: 1px solid #E0E0E0; font-size: 10px; color: #555; }
    .summary { display: flex; gap: 12px; padding: 10px 20px; }
    .summary-card { flex: 1; border: 1px solid #E0E0E0; border-radius: 6px; padding: 8px 12px; text-align: center; }
    .summary-card .num  { font-size: 22px; font-weight: bold; }
    .summary-card .lbl  { font-size: 9px; color: #777; text-transform: uppercase; margin-top: 2px; }
    .total-card  { color: #1565C0; }
    .done-card   { color: #2E7D32; }
    .pend-card   { color: #F57F17; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead th { background: #37474F; color: #fff; padding: 7px 8px; text-align: left; font-size: 10px; border: 1px solid #607D8B; }
    tbody tr:nth-child(even) { background: #FAFAFA; }
    tbody td { padding: 6px 8px; border: 1px solid #E0E0E0; font-size: 10.5px; vertical-align: middle; }
    .badge-completado { background: #E8F5E9; color: #1B5E20; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-pendiente  { background: #FFF3E0; color: #E65100; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .tipo-preventivo { color: #1565C0; font-weight: bold; }
    .tipo-correctivo { color: #B71C1C; font-weight: bold; }
    .tipo-predictivo { color: #4A148C; font-weight: bold; }
    .footer { margin-top: 16px; padding: 8px 20px; font-size: 9px; color: #999; text-align: right; border-top: 1px solid #eee; }
    .table-wrap { padding: 0 20px 20px; }
    .no-data { padding: 30px; text-align: center; color: #999; font-size: 13px; }
    @media print {
        .no-print { display: none !important; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body onload="window.print()">

<div class="header">
    <h1>Calendario de Mantenimiento</h1>
    <p>Periodo: <?= $fromDisplay ?> &mdash; <?= $toDisplay ?></p>
</div>

<div class="meta">
    <span>Generado: <?= $generado ?></span>
    <span>Por: <?= $usuario ?></span>
    <span>Total de registros: <?= $totalRows ?></span>
</div>

<div class="summary">
    <div class="summary-card total-card">
        <div class="num"><?= $totalRows ?></div>
        <div class="lbl">Programados</div>
    </div>
    <div class="summary-card done-card">
        <div class="num"><?= $completadosRows ?></div>
        <div class="lbl">Completados</div>
    </div>
    <div class="summary-card pend-card">
        <div class="num"><?= $pendientesRows ?></div>
        <div class="lbl">Pendientes</div>
    </div>
</div>

<div class="table-wrap">
    <?php if (empty($rows)): ?>
        <div class="no-data">No se encontraron mantenimientos en el periodo seleccionado.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:11%">Fecha</th>
                <th style="width:8%">Hora</th>
                <th style="width:28%">Equipo</th>
                <th style="width:13%">Num. Inventario</th>
                <th style="width:12%">Tipo</th>
                <th style="width:16%">Departamento</th>
                <th style="width:8%">Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r):
                $estatus   = strtolower($r['estatus'] ?? 'pendiente');
                $tipo      = strtolower($r['tipo_mantenimiento'] ?? '');
                $tipoClass = 'tipo-' . $tipo;
                $badgeClass = $estatus === 'completado' ? 'badge-completado' : 'badge-pendiente';
            ?>
            <tr>
                <td style="text-align:center"><?= $i + 1 ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha_programada'])) ?></td>
                <td style="text-align:center"><?= $r['hora_programada'] ? substr($r['hora_programada'], 0, 5) : '—' ?></td>
                <td><?= htmlspecialchars($r['equipo_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['numero_inventario'] ?? '—') ?></td>
                <td class="<?= $tipoClass ?>"><?= ucfirst(htmlspecialchars($r['tipo_mantenimiento'] ?? '')) ?></td>
                <td><?= htmlspecialchars($r['departamento'] ?? '—') ?></td>
                <td style="text-align:center"><span class="<?= $badgeClass ?>"><?= ucfirst($estatus) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="footer">
    Sistema de Gestion &mdash; <?= date('Y') ?>
</div>

</body>
</html>
    <?php
    exit;
}

// Formato no soportado
http_response_code(400);
die('Formato no soportado. Use: ?format=excel o ?format=pdf');
