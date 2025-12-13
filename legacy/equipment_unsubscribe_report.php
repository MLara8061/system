<?php
define('ACCESS', true);
require_once 'config/config.php';

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

$sql = "SELECT eu.*, e.name AS equipment_name, e.number_inventory, e.brand, e.model,
               (SELECT COUNT(1) FROM maintenance_reports mr WHERE mr.equipment_id = eu.equipment_id) AS maintenance_total
        FROM equipment_unsubscribe eu
        INNER JOIN equipments e ON e.id = eu.equipment_id
        ORDER BY eu.date DESC, eu.time DESC, eu.id DESC";
$records = $conn->query($sql);
$rows = [];
while ($records && $row = $records->fetch_assoc()) {
    $rows[] = $row;
}
if ($records) {
    $records->free();
}
?>
<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius:16px; overflow:hidden;">
        <div class="card-header bg-light border-0 d-flex align-items-center justify-content-between flex-wrap">
            <div class="mb-2 mb-md-0">
                <h5 class="mb-0 text-dark">Reporte de equipos dados de baja</h5>
                <small class="text-muted">Resumen general de bajas registradas, folios y responsables.</small>
            </div>
        </div>
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="unsubscribe-table" style="width: 100%; font-size: 0.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 100px;">Folio</th>
                            <th style="min-width: 150px;">Equipo</th>
                            <th style="min-width: 80px;">N° inv.</th>
                            <th style="min-width: 100px;">Fecha</th>
                            <th style="min-width: 120px;">Usuario</th>
                            <th style="min-width: 100px;">Responsable</th>
                            <th style="min-width: 120px;">Destino</th>
                            <th style="min-width: 100px;">Dictamen</th>
                            <th style="min-width: 150px;">Causas</th>
                            <th style="min-width: 80px;">Mttos.</th>
                            <th style="min-width: 80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $date = !empty($row['date']) ? date('d/m/Y', strtotime($row['date'])) : '';
                                $time = !empty($row['time']) ? date('H:i', strtotime($row['time'])) : '';
                                $dictamen = isset($row['opinion']) ? (((int)$row['opinion'] === 1) ? 'Funcional' : 'Disfuncional') : 'Sin dictamen';
                                $destino = isset($destinationLabels[(int)$row['destination']]) ? $destinationLabels[(int)$row['destination']] : 'No especificado';
                                $responsable = isset($responsibleLabels[(int)$row['responsible']]) ? $responsibleLabels[(int)$row['responsible']] : 'No especificado';
                                $usuario = !empty($row['processed_by_name']) ? $row['processed_by_name'] : 'No registrado';
                                $maintenanceTotal = (int)($row['maintenance_total'] ?? 0);

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
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['folio'] ?: sprintf('BAJ-%s-%04d', date('Y', strtotime($row['date'] ?? 'now')), (int)$row['id'])) ?></td>
                                <td>
                                    <div class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($row['equipment_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars(trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''))) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['number_inventory']) ?></td>
                                <td><?= htmlspecialchars(trim($date . ($time ? ' ' . $time : ''))) ?></td>
                                <td><?= htmlspecialchars($usuario) ?></td>
                                <td><?= htmlspecialchars($responsable) ?></td>
                                <td><?= htmlspecialchars($destino) ?></td>
                                <td><?= htmlspecialchars($dictamen) ?></td>
                                <td><?= htmlspecialchars($reasonText) ?></td>
                                <td class="text-center"><?= $maintenanceTotal ?></td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="equipment_unsubscribe_pdf.php?id=<?= (int)$row['id'] ?>">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    if ($.fn.DataTable) {
        $('#unsubscribe-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            },
            order: [[3, 'desc']],
            pageLength: 25,
            responsive: true,
            scrollX: true,
            autoWidth: false,
            columnDefs: [
                { width: "100px", targets: 0 },
                { width: "150px", targets: 1 },
                { width: "80px", targets: 2 },
                { width: "100px", targets: 3 },
                { width: "80px", targets: 10 }
            ]
        });
    }
});
</script>

<style>
@media (max-width: 768px) {
    .card-body {
        padding: 0.5rem !important;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #unsubscribe-table {
        font-size: 0.75rem !important;
    }
    #unsubscribe-table th,
    #unsubscribe-table td {
        padding: 0.5rem !important;
        white-space: nowrap;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}
</style>
