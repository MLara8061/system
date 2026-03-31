<?php
require_once 'config/config.php';
require_once 'config/access_guard.php';
require_once 'app/helpers/permissions.php';

$canView = function_exists('can') ? can('view', 'reports') : true;
if (!$canView) {
    echo '<div class="alert alert-danger"><i class="fas fa-lock mr-2"></i>No tienes permiso para ver reportes.</div>';
    exit;
}

if (!function_exists('tr_col_exists')) {
    function tr_col_exists(mysqli $conn, $table, $column) {
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

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-t');
if ($from > $to) {
    $tmp = $from;
    $from = $to;
    $to = $tmp;
}

$hasAssignedTo = tr_col_exists($conn, 'tickets', 'assigned_to');
$hasPriority = tr_col_exists($conn, 'tickets', 'priority');
$hasTSH = tr_col_exists($conn, 'ticket_status_history', 'ticket_id');
$hasDateUpdated = tr_col_exists($conn, 'tickets', 'date_updated');

$prioritySelect = $hasPriority ? 'COALESCE(t.priority, "medium") AS priority,' : '"medium" AS priority,';
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
        {$prioritySelect}
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

$tickets = [];
$res = @$conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $tickets[] = $row;
    }
    $res->free();
}

$stats = [
    'total' => count($tickets),
    'avg_first_response_hours' => 0,
    'avg_close_hours' => 0,
    'closed_total' => 0,
    'in_progress_total' => 0,
    'open_total' => 0,
];

$sumFirstResponse = 0.0;
$countFirstResponse = 0;
$sumClose = 0.0;
$countClose = 0;

foreach ($tickets as $t) {
    $statusRaw = (string)($t['status'] ?? '0');
    if ($statusRaw === '1' || strtolower($statusRaw) === 'in_progress') {
        $stats['in_progress_total']++;
    } elseif ($statusRaw === '2' || $statusRaw === '3' || strtolower($statusRaw) === 'closed' || strtolower($statusRaw) === 'resolved') {
        $stats['closed_total']++;
    } else {
        $stats['open_total']++;
    }

    if (!empty($t['first_response_at']) && !empty($t['date_created'])) {
        $minutes = (strtotime($t['first_response_at']) - strtotime($t['date_created'])) / 60;
        if ($minutes >= 0) {
            $sumFirstResponse += ($minutes / 60.0);
            $countFirstResponse++;
        }
    }

    if (!empty($t['closed_at']) && !empty($t['date_created'])) {
        $minutes = (strtotime($t['closed_at']) - strtotime($t['date_created'])) / 60;
        if ($minutes >= 0) {
            $sumClose += ($minutes / 60.0);
            $countClose++;
        }
    }
}

$stats['avg_first_response_hours'] = $countFirstResponse > 0 ? round($sumFirstResponse / $countFirstResponse, 2) : 0;
$stats['avg_close_hours'] = $countClose > 0 ? round($sumClose / $countClose, 2) : 0;

$departments = [];
$depRes = @$conn->query('SELECT id, name FROM departments ORDER BY name ASC');
if ($depRes) {
    while ($d = $depRes->fetch_assoc()) $departments[] = $d;
    $depRes->free();
}

$technicians = [];
if ($hasAssignedTo) {
    $tecRes = @$conn->query("SELECT id, CONCAT(firstname, ' ', lastname) AS name FROM users ORDER BY firstname ASC, lastname ASC");
    if ($tecRes) {
        while ($u = $tecRes->fetch_assoc()) $technicians[] = $u;
        $tecRes->free();
    }
}

$exportBase = 'app/helpers/export_tickets_report.php?from=' . urlencode($from)
    . '&to=' . urlencode($to)
    . '&department_id=' . (int)$departmentId
    . '&assigned_to=' . (int)$assignedTo;
?>

<div class="container-fluid">
    <?php if (!$hasTSH): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        No se detectó la tabla ticket_status_history. Las métricas de tiempo se mostrarán en cero hasta ejecutar las migraciones de tickets.
    </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-3" style="border-radius: 14px;">
        <div class="card-body">
            <form class="form-row align-items-end" method="get" action="index.php">
                <input type="hidden" name="page" value="tickets_report">
                <div class="col-md-2">
                    <label class="font-weight-bold">Desde</label>
                    <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold">Hasta</label>
                    <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold">Departamento</label>
                    <select class="custom-select" name="department_id">
                        <option value="0">Todos</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= $departmentId === (int)$d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold">Técnico asignado</label>
                    <select class="custom-select" name="assigned_to" <?= !$hasAssignedTo ? 'disabled' : '' ?>>
                        <option value="0">Todos</option>
                        <?php foreach ($technicians as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= $assignedTo === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 text-md-right mt-3 mt-md-0">
                    <button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                </div>
            </form>
            <div class="mt-3 text-md-right">
                <a class="btn btn-outline-success" href="<?= $exportBase ?>&format=excel">
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
                <a class="btn btn-outline-danger" href="<?= $exportBase ?>&format=pdf" target="_blank">
                    <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="small-box bg-info">
                <div class="inner"><h3><?= (int)$stats['total'] ?></h3><p>Total Tickets</p></div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="small-box bg-warning">
                <div class="inner"><h3><?= number_format((float)$stats['avg_first_response_hours'], 2) ?>h</h3><p>Promedio primera respuesta</p></div>
                <div class="icon"><i class="fas fa-stopwatch"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="small-box bg-success">
                <div class="inner"><h3><?= number_format((float)$stats['avg_close_hours'], 2) ?>h</h3><p>Promedio cierre</p></div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="small-box bg-secondary">
                <div class="inner"><h3><?= (int)$stats['closed_total'] ?></h3><p>Tickets cerrados</p></div>
                <div class="icon"><i class="fas fa-lock"></i></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 14px;">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0"><i class="fas fa-chart-line mr-2 text-primary"></i>Detalle de tiempos por ticket</h5>
        </div>
        <div class="card-body table-responsive p-0" style="max-height: 520px;">
            <table class="table table-sm table-hover mb-0">
                <thead class="bg-light">
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
                    <?php foreach ($tickets as $t): ?>
                    <?php
                        $createdTs = !empty($t['date_created']) ? strtotime($t['date_created']) : 0;
                        $firstTs = !empty($t['first_response_at']) ? strtotime($t['first_response_at']) : 0;
                        $closeTs = !empty($t['closed_at']) ? strtotime($t['closed_at']) : 0;
                        $firstHours = ($createdTs > 0 && $firstTs >= $createdTs) ? round(($firstTs - $createdTs) / 3600, 2) : null;
                        $closeHours = ($createdTs > 0 && $closeTs >= $createdTs) ? round(($closeTs - $createdTs) / 3600, 2) : null;
                        $statusRaw = (string)($t['status'] ?? '0');
                        $statusLabel = 'Abierto';
                        if ($statusRaw === '1' || strtolower($statusRaw) === 'in_progress') $statusLabel = 'En Proceso';
                        if ($statusRaw === '2' || strtolower($statusRaw) === 'resolved') $statusLabel = 'Resuelto';
                        if ($statusRaw === '3' || strtolower($statusRaw) === 'closed') $statusLabel = 'Cerrado';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($t['ticket_number']) ?></td>
                        <td><?= htmlspecialchars($t['subject']) ?></td>
                        <td><?= htmlspecialchars($t['department_name']) ?></td>
                        <td><?= htmlspecialchars($t['assigned_name']) ?></td>
                        <td><?= htmlspecialchars($statusLabel) ?></td>
                        <td><?= !empty($t['date_created']) ? date('d/m/Y H:i', strtotime($t['date_created'])) : '—' ?></td>
                        <td><?= !empty($t['first_response_at']) ? date('d/m/Y H:i', strtotime($t['first_response_at'])) : '—' ?></td>
                        <td><?= !empty($t['closed_at']) ? date('d/m/Y H:i', strtotime($t['closed_at'])) : '—' ?></td>
                        <td class="text-right font-weight-bold"><?= $firstHours !== null ? number_format($firstHours, 2) : '—' ?></td>
                        <td class="text-right font-weight-bold"><?= $closeHours !== null ? number_format($closeHours, 2) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No hay tickets en el rango seleccionado</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
