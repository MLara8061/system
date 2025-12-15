<?php 
include 'header.php'; 
require_once 'config/config.php';  // AÑADIDO: CONEXIÓN A LA BD
?>

<?php
// Tarjetas resumen
$activity_summary = [
    'total' => 0,
    'today' => 0,
    'last7' => 0,
    'users7' => 0,
];
try {
    $qTotal = $conn->query("SELECT COUNT(*) AS total FROM activity_log");
    if ($qTotal) {
        $activity_summary['total'] = (int)($qTotal->fetch_assoc()['total'] ?? 0);
    }
    $qToday = $conn->query("SELECT COUNT(*) AS total FROM activity_log WHERE DATE(created_at) = CURDATE()");
    if ($qToday) {
        $activity_summary['today'] = (int)($qToday->fetch_assoc()['total'] ?? 0);
    }
    $qLast7 = $conn->query("SELECT COUNT(*) AS total, COUNT(DISTINCT user_id) AS users FROM activity_log WHERE created_at >= (NOW() - INTERVAL 7 DAY)");
    if ($qLast7) {
        $r = $qLast7->fetch_assoc();
        $activity_summary['last7'] = (int)($r['total'] ?? 0);
        $activity_summary['users7'] = (int)($r['users'] ?? 0);
    }
} catch (Throwable $e) {
    // no-op
}
?>
<div class="col-lg-12">

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-list fa-2x text-primary mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Total Registros</h6>
                        <h4 class="mb-0"><?= (int)$activity_summary['total'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-calendar-day fa-2x text-success mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Hoy</h6>
                        <h4 class="mb-0"><?= (int)$activity_summary['today'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-history fa-2x text-warning mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Últimos 7 días</h6>
                        <h4 class="mb-0"><?= (int)$activity_summary['last7'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-user-friends fa-2x text-info mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Usuarios (7 días)</h6>
                        <h4 class="mb-0"><?= (int)$activity_summary['users7'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-history"></i> Registro de Actividad</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="activity-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>ID</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qry = $conn->query("
                            SELECT al.*, CONCAT(u.firstname,' ',u.lastname) as name 
                            FROM activity_log al 
                            LEFT JOIN users u ON u.id = al.user_id 
                            ORDER BY al.created_at DESC
                        ");
                        while ($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td><small><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small></td>
                            <td><b><?= ucwords($row['name'] ?: 'Sistema') ?></b></td>
                            <td><?= htmlspecialchars($row['action']) ?></td>
                            <td><code><?= htmlspecialchars($row['table_name']) ?></code></td>
                            <td class="text-center"><?= $row['record_id'] ?: '-' ?></td>
                            <td><small><?= htmlspecialchars($row['ip_address']) ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#activity-table').DataTable({
        language: { url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" },
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [4,5], className: "text-center" }
        ],
        info: false,
        lengthChange: false
    });
});
</script>

<?php include 'footer.php'; ?>