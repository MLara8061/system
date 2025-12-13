<?php require_once 'config/config.php'; ?>

<?php
$total_acc = $conn->query("SELECT COUNT(*) as total FROM accessories")->fetch_assoc()['total'] ?? 0;
$activos_acc = $conn->query("SELECT COUNT(*) as total FROM accessories WHERE status = 'Activo'")->fetch_assoc()['total'] ?? 0;
$inactivos_acc = $conn->query("SELECT COUNT(*) as total FROM accessories WHERE status = 'Inactivo'")->fetch_assoc()['total'] ?? 0;
$total_valor_acc = $conn->query("SELECT COALESCE(SUM(cost), 0) as total FROM accessories")->fetch_assoc()['total'];
?>

<!-- Tarjetas de resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-headset fa-2x text-primary mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Total Accesorios</h6>
                    <h4 class="mb-0"><?= $total_acc ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Activos</h6>
                    <h4 class="mb-0"><?= $activos_acc ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x text-secondary mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Inactivos</h6>
                    <h4 class="mb-0"><?= $inactivos_acc ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-dollar-sign fa-2x text-info mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Valor Total</h6>
                    <h4 class="mb-0">$<?= number_format($total_valor_acc, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Accesorios -->
<div class="col-lg-12">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white border-0">
            <div class="card-tools float-right">
                <!-- IGUAL QUE HERRAMIENTAS -->
                <a href="index.php?page=new_accesories" class="btn btn-tool btn-sm" title="Agregar Accesorio">
                    <i class="fas fa-plus text-success"></i>
                </a>
                <a href="#" class="btn btn-tool btn-sm" title="Exportar a Excel" id="export-excel">
                    <i class="fas fa-download text-info"></i>
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped" id="accessory-table">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center" style="width:60px;">Img</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Inventario</th>
                        <th>Área</th>
                        <th>Costo</th>
                        <th>Adquisición</th>
                        <th>Fecha</th>
                        <th>Status</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $qry = $conn->query("
                        SELECT 
                            a.*,
                            d.name as area_name,
                            at.name as acquisition_name
                        FROM accessories a 
                        LEFT JOIN departments d ON a.area_id = d.id 
                        LEFT JOIN acquisition_type at ON a.acquisition_type_id = at.id 
                        ORDER BY a.id DESC
                    ");
                    while ($row = $qry->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="text-center">
                                <?php if (!empty($row['image']) && file_exists('uploads/' . $row['image'])): ?>
                                    <img src="uploads/<?= $row['image'] ?>" alt="Img" class="img-thumbnail" 
                                         style="width:40px; height:40px; object-fit:cover; border-radius:8px;">
                                <?php else: ?>
                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center" 
                                         style="width:40px; height:40px;">
                                        <i class="fas fa-headset text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= ucwords($row['name']) ?></strong></td>
                            <td><span class="badge badge-info"><?= ucwords($row['type'] ?? 'N/A') ?></span></td>
                            <td><?= $row['brand'] ?? '-' ?></td>
                            <td><?= $row['model'] ?? '-' ?></td>
                            <td><?= $row['serial'] ?? '-' ?></td>
                            <td>#<?= $row['inventory_number'] ?></td>
                            <td><?= ucwords($row['area_name'] ?? 'Sin área') ?></td>
                            <td>$<?= number_format($row['cost'], 2) ?></td>
                            <td><small><?= ucwords($row['acquisition_name'] ?? 'N/A') ?></small></td>
                            <td><small><?= date('d/m/Y', strtotime($row['acquisition_date'])) ?></small></td>
                            <td>
                                <span class="badge <?= $row['status'] == 'Activo' ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= $row['status'] ?? 'N/A' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                            data-toggle="dropdown">
                                        <i class="fas fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item edit-accessory" href="javascript:void(0)" 
                                           data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-edit text-primary mr-2"></i> Editar
                                        </a>
                                        <a class="dropdown-item delete-accessory text-danger" href="javascript:void(0)" 
                                           data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-trash mr-2"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <!-- PIE ELIMINADO -->
    </div>
</div>

<style>
    /* SIN ANIMACIÓN */
    .card { transition: none !important; }

    /* SIN FONDO AZUL EN INVENTARIO */
    #accessory-table .badge-primary {
        background-color: transparent !important;
        color: #007bff !important;
        font-weight: bold;
    }

    .table th { font-weight: 600; font-size: 0.9rem; }
    .dropdown-menu { min-width: 160px; }
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcode.js@1.0.0/lib/qrcode.min.js"></script>

<script>
$(document).ready(function() {
    $('#accessory-table').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
        responsive: true,
        pageLength: 25,
        order: [[1, 'desc']],
        columnDefs: [
            { orderable: false, targets: [0, 12] },
            { className: "text-center", targets: [0, 6, 11, 12] }
        ],
        info: false,         /* Ocultar "Mostrando 1 a X de Y entradas" */
        lengthChange: false  /* Ocultar selector de filas por página */
    });

    $('#export-excel').click(function(e) {
        e.preventDefault();
        let table = $('#accessory-table').DataTable();
        let data = table.rows({ search: 'applied' }).data();
        let rows = [['Imagen','Nombre','Tipo','Marca','Modelo','Serie','Inventario','Área','Costo','Adquisición','Fecha','Status']];

        data.each(function(row) {
            let img = $(row[0]).find('img').length ? 'Sí' : 'No';
            rows.push([
                img,
                $(row[1]).text(),
                $(row[2]).text(),
                $(row[3]).text(),
                $(row[4]).text(),
                $(row[5]).text(),
                $(row[6]).text().replace('#', ''),
                $(row[7]).text(),
                $(row[8]).text().replace(/[$,]/g, ''),
                $(row[9]).text(),
                $(row[10]).text(),
                $(row[11]).text()
            ]);
        });

        let csv = rows.map(r => r.join('\t')).join('\n');
        let blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        let url = URL.createObjectURL(blob);
        let link = document.createElement('a');
        link.href = url;
        link.download = 'accesorios_' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
    });

    $(document).on('click', '.edit-accessory', function() {
        let id = $(this).data('id');
        location.href = 'index.php?page=edit_accesories&id=' + id;
    });

    $(document).on('click', '.delete-accessory', function() {
        let id = $(this).data('id');
        confirm_toast(
            '¿Estás seguro de eliminar este accesorio? Esta acción no se puede deshacer.',
            function() {
                start_load();
                $.post('ajax.php?action=delete_accessory', { id: id }, function(resp) {
                    end_load();
                    if (resp == 1) {
                        alert_toast('Accesorio eliminado correctamente', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert_toast('Error al eliminar el accesorio', 'error');
                    }
                }).fail(function() {
                    end_load();
                    alert_toast('Error de conexión', 'error');
                });
            }
        );
    });
});
</script>