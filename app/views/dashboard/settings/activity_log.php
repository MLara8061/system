<?php 
include 'header.php'; 
require_once 'config/config.php';  // AÑADIDO: CONEXIÓN A LA BD
?>
<div class="col-lg-12">
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