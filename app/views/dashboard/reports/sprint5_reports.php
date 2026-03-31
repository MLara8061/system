<?php
require_once 'config/config.php';
require_once 'config/access_guard.php';
require_once 'app/helpers/permissions.php';

$pageTitle = 'Analitica Operativa';

$can_view = function_exists('can') ? can('view', 'reports') : true;
if (!$can_view) {
    echo '<div class="alert alert-danger"><i class="fas fa-lock mr-2"></i>No tienes permiso para ver reportes.</div>';
    exit;
}

$departmentFilter = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$branchAndE = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'e') : '';
$branchAndA = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'a') : '';
$branchAndT = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'e') : '';
$branchAndM = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'mr') : '';
$depAnd = $departmentFilter > 0 ? " AND ed.department_id = {$departmentFilter}" : '';

// Detectar columnas opcionales
$hasUsageHours = false;
$hasEqInAccessories = false;
$col = @$conn->query("SHOW COLUMNS FROM equipment_power_specs LIKE 'daily_usage_hours'");
if ($col && $col->num_rows > 0) $hasUsageHours = true;
$col2 = @$conn->query("SHOW COLUMNS FROM accessories LIKE 'equipment_id'");
if ($col2 && $col2->num_rows > 0) $hasEqInAccessories = true;

$usageExpr = $hasUsageHours ? 'COALESCE(eps.daily_usage_hours, 8)' : '8';

// Filtro de departamentos
$departments = [];
$depSql = "SELECT id, name FROM departments ORDER BY name ASC";
$depRes = @$conn->query($depSql);
if ($depRes) {
    while ($d = $depRes->fetch_assoc()) {
        $departments[] = $d;
    }
}

// E6.1 Consumo energético (kWh/mes)
$energyByEquipment = [];
$energyByDepartment = [];
$sqlEnergy = "
    SELECT
        e.id AS equipment_id,
        e.name AS equipment_name,
        e.number_inventory,
        d.name AS department_name,
        COALESCE(eps.power_w, (eps.voltage * eps.amperage)) AS power_w,
        {$usageExpr} AS daily_usage_hours,
        ROUND((COALESCE(eps.power_w, (eps.voltage * eps.amperage)) * {$usageExpr} * 30) / 1000, 2) AS kwh_monthly
    FROM equipment_power_specs eps
    INNER JOIN equipments e ON e.id = eps.equipment_id
    LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
    LEFT JOIN departments d ON d.id = ed.department_id
    WHERE 1=1 {$branchAndE} {$depAnd}
    ORDER BY kwh_monthly DESC
";
$eqRes = @$conn->query($sqlEnergy);
if ($eqRes) {
    while ($r = $eqRes->fetch_assoc()) {
        $energyByEquipment[] = $r;
        $dep = $r['department_name'] ?: 'Sin departamento';
        if (!isset($energyByDepartment[$dep])) {
            $energyByDepartment[$dep] = 0;
        }
        $energyByDepartment[$dep] += (float)($r['kwh_monthly'] ?? 0);
    }
}

// E6.2 Top gasto accesorios
$accessoriesRanking = [];
$accWarning = '';
if ($hasEqInAccessories) {
    $sqlAcc = "
        SELECT
            e.id AS equipment_id,
            e.name AS equipment_name,
            e.number_inventory,
            COUNT(a.id) AS total_piezas,
            ROUND(COALESCE(SUM(a.cost), 0), 2) AS total_monto
        FROM accessories a
        INNER JOIN equipments e ON e.id = a.equipment_id
        WHERE 1=1 {$branchAndA}
        GROUP BY e.id, e.name, e.number_inventory
        ORDER BY total_monto DESC, total_piezas DESC
        LIMIT 20
    ";
    $accRes = @$conn->query($sqlAcc);
    if ($accRes) {
        while ($r = $accRes->fetch_assoc()) {
            $accessoriesRanking[] = $r;
        }
    }
} else {
    $accWarning = 'La tabla accessories aún no tiene la columna equipment_id. Ejecuta la migración de Sprint 5 para habilitar este ranking.';
}

// E6.3 Ranking equipos con más tickets
$ticketRanking = [];
$sqlTickets = "
    SELECT
        e.id AS equipment_id,
        e.name AS equipment_name,
        e.number_inventory,
        COUNT(t.id) AS total
    FROM tickets t
    INNER JOIN equipments e ON e.id = t.equipment_id
    LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
    WHERE t.equipment_id IS NOT NULL {$branchAndT} {$depAnd}
    GROUP BY e.id, e.name, e.number_inventory
    ORDER BY total DESC
    LIMIT 20
";
$tRes = @$conn->query($sqlTickets);
if ($tRes) {
    while ($r = $tRes->fetch_assoc()) {
        $ticketRanking[] = $r;
    }
}

// E6.3 Ranking equipos con más mantenimientos
$maintenanceRanking = [];
$sqlMaint = "
    SELECT
        e.id AS equipment_id,
        e.name AS equipment_name,
        e.number_inventory,
        COUNT(mr.id) AS total
    FROM maintenance_reports mr
    INNER JOIN equipments e ON e.id = mr.equipment_id
    LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
    WHERE mr.equipment_id IS NOT NULL {$branchAndM} {$depAnd}
    GROUP BY e.id, e.name, e.number_inventory
    ORDER BY total DESC
    LIMIT 20
";
$mRes = @$conn->query($sqlMaint);
if ($mRes) {
    while ($r = $mRes->fetch_assoc()) {
        $maintenanceRanking[] = $r;
    }
}

$depLabels = array_keys($energyByDepartment);
$depValues = array_map(fn($v) => round($v, 2), array_values($energyByDepartment));
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-3" style="border-radius: 14px;">
        <div class="card-body">
            <form class="form-row align-items-end" method="get" action="index.php">
                <input type="hidden" name="page" value="sprint5_reports">
                <div class="col-md-4">
                    <label class="font-weight-bold">Departamento</label>
                    <select name="department_id" class="custom-select">
                        <option value="0">Todos</option>
                        <?php foreach ($departments as $dep): ?>
                            <option value="<?= (int)$dep['id'] ?>" <?= $departmentFilter === (int)$dep['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 text-md-right mt-3 mt-md-0">
                    <button class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                    <a class="btn btn-outline-success" href="app/helpers/export_sprint5_reports.php?type=energy&department_id=<?= $departmentFilter ?>">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Energía
                    </a>
                    <a class="btn btn-outline-success" href="app/helpers/export_sprint5_reports.php?type=tickets&department_id=<?= $departmentFilter ?>">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Tickets
                    </a>
                    <a class="btn btn-outline-success" href="app/helpers/export_sprint5_reports.php?type=maintenance&department_id=<?= $departmentFilter ?>">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Mantenimientos
                    </a>
                    <?php if ($hasEqInAccessories): ?>
                    <a class="btn btn-outline-success" href="app/helpers/export_sprint5_reports.php?type=accessories&department_id=<?= $departmentFilter ?>">
                        <i class="fas fa-file-excel mr-1"></i> Exportar Accesorios
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius:14px;">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-bolt text-warning mr-2"></i>E6.1 Consumo Eléctrico por Departamento (kWh/mes)</h5>
                </div>
                <div class="card-body">
                    <canvas id="energyDeptChart" height="140"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0" style="border-radius:14px;">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-plug text-info mr-2"></i>Top Equipos por kWh</h5>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 340px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr><th>Equipo</th><th>#Inv</th><th class="text-right">kWh/mes</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($energyByEquipment, 0, 15) as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['equipment_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($r['number_inventory'] ?? '—') ?></td>
                                <td class="text-right font-weight-bold"><?= number_format((float)$r['kwh_monthly'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($energyByEquipment)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Sin datos</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0" style="border-radius:14px;">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-toolbox text-danger mr-2"></i>E6.2 Top Gasto en Accesorios</h5>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 360px;">
                    <?php if ($accWarning): ?>
                        <div class="alert alert-warning m-3 mb-0"><?= htmlspecialchars($accWarning) ?></div>
                    <?php endif; ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr><th>#</th><th>Equipo</th><th>Piezas</th><th class="text-right">Monto</th></tr>
                        </thead>
                        <tbody>
                            <?php $pos = 1; foreach ($accessoriesRanking as $r): ?>
                            <tr>
                                <td><?= $pos++ ?></td>
                                <td><?= htmlspecialchars(($r['equipment_name'] ?? 'N/A') . ' #' . ($r['number_inventory'] ?? '')) ?></td>
                                <td><?= (int)$r['total_piezas'] ?></td>
                                <td class="text-right font-weight-bold">$<?= number_format((float)$r['total_monto'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($accessoriesRanking)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Sin datos</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0" style="border-radius:14px;">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt text-primary mr-2"></i>E6.3 Ranking por Tickets</h5>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 170px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light"><tr><th>Equipo</th><th class="text-right">Tickets</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($ticketRanking, 0, 10) as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars(($r['equipment_name'] ?? 'N/A') . ' #' . ($r['number_inventory'] ?? '')) ?></td>
                                <td class="text-right font-weight-bold"><?= (int)$r['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ticketRanking)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin datos</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-header bg-white border-top border-0">
                    <h5 class="mb-0"><i class="fas fa-wrench text-success mr-2"></i>E6.3 Ranking por Mantenimientos</h5>
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 170px;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light"><tr><th>Equipo</th><th class="text-right">Mantenimientos</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($maintenanceRanking, 0, 10) as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars(($r['equipment_name'] ?? 'N/A') . ' #' . ($r['number_inventory'] ?? '')) ?></td>
                                <td class="text-right font-weight-bold"><?= (int)$r['total'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($maintenanceRanking)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin datos</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    const labels = <?= json_encode($depLabels, JSON_UNESCAPED_UNICODE) ?>;
    const values = <?= json_encode($depValues, JSON_UNESCAPED_UNICODE) ?>;

    const ctx = document.getElementById('energyDeptChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'kWh/mes',
                    data: values,
                    backgroundColor: '#17a2b8',
                    borderColor: '#117a8b',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });
    }
});
</script>
