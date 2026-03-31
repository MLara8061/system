<?php
require_once 'config/config.php';
require_once 'config/access_guard.php';
require_once 'app/helpers/permissions.php';

// Acceso por permiso al módulo (fallback admin por compatibilidad)
$has_access = false;
if (function_exists('can')) {
    $has_access = can('view', 'hazardous_materials');
}
if (!$has_access) {
    $login_type = (int)($_SESSION['login_type'] ?? 0);
    $has_access = ($login_type === 1);
}

if (!$has_access) {
    echo '<div class="alert alert-danger"><i class="fas fa-lock mr-2"></i>No tienes permiso para acceder a este módulo.</div>';
    exit;
}

$branch_where = function_exists('branch_sql') ? branch_sql('WHERE', 'branch_id', 'i') : '';
$branch_and   = function_exists('branch_sql') ? branch_sql('AND',   'branch_id', 'i') : '';

// Totales hazardous
$total_haz  = $conn->query("SELECT COUNT(*) as t FROM inventory i WHERE is_hazardous = 1 {$branch_and}")->fetch_assoc()['t'] ?? 0;
$sin_sds    = $conn->query("SELECT COUNT(*) as t FROM inventory i WHERE is_hazardous = 1 AND (safety_data_sheet IS NULL OR safety_data_sheet = '') {$branch_and}")->fetch_assoc()['t'] ?? 0;
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x text-danger mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Sustancias Peligrosas</h6>
                    <h4 class="mb-0"><?= $total_haz ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-file-alt fa-2x text-warning mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Sin Hoja de Seguridad</h6>
                    <h4 class="mb-0"><?= $sin_sds ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Con Hoja de Seguridad</h6>
                    <h4 class="mb-0"><?= $total_haz - $sin_sds ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius:16px; overflow:hidden;">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 font-weight-bold">
            <i class="fas fa-exclamation-triangle text-danger mr-2"></i>
            Sustancias Peligrosas
        </h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-success mr-2" id="btn-export-haz">
                <i class="fas fa-file-excel mr-1"></i> Exportar Excel
            </button>
            <a href="index.php?page=manage_insumos" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i> Nuevo Insumo
            </a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped" id="haz-table">
            <thead class="bg-light">
                <tr>
                    <th style="width:50px;">Img</th>
                    <th>Nombre</th>
                    <th>Clase de Peligro</th>
                    <th>Sucursal</th>
                    <th>Stock</th>
                    <th>Hoja Seguridad</th>
                    <th>Status</th>
                    <th style="width:80px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $qry = $conn->query("
                    SELECT i.id, i.name, i.hazard_class, i.safety_data_sheet,
                           i.stock, i.min_stock, i.status, i.image_path,
                           b.name AS branch_name
                    FROM inventory i
                    LEFT JOIN branches b ON b.id = i.branch_id
                    WHERE i.is_hazardous = 1 {$branch_and}
                    ORDER BY i.name ASC
                ");
                while ($row = $qry->fetch_assoc()):
                    $hazardLabels = [
                        'inflamable' => ['label' => 'Inflamable', 'color' => 'danger'],
                        'corrosivo'  => ['label' => 'Corrosivo',  'color' => 'warning'],
                        'toxico'     => ['label' => 'Tóxico',     'color' => 'dark'],
                        'oxidante'   => ['label' => 'Oxidante',   'color' => 'info'],
                        'explosivo'  => ['label' => 'Explosivo',  'color' => 'danger'],
                        'irritante'  => ['label' => 'Irritante',  'color' => 'warning'],
                        'otro'       => ['label' => 'Otro',       'color' => 'secondary'],
                    ];
                    $hc      = $row['hazard_class'] ?? '';
                    $hcInfo  = $hazardLabels[$hc] ?? ['label' => ucfirst($hc) ?: 'N/A', 'color' => 'secondary'];

                    $stock_badge = '';
                    if ($row['stock'] == 0)
                        $stock_badge = '<span class="badge badge-danger">Sin Stock</span>';
                    elseif ($row['stock'] <= $row['min_stock'])
                        $stock_badge = '<span class="badge badge-warning">Bajo</span>';
                    else
                        $stock_badge = '<span class="badge badge-success">Suficiente</span>';
                ?>
                <tr>
                    <td class="text-center">
                        <?php if (!empty($row['image_path']) && file_exists('uploads/' . $row['image_path'])): ?>
                            <img src="uploads/<?= $row['image_path'] ?>" class="img-thumbnail"
                                 style="width:36px;height:36px;object-fit:cover;border-radius:6px;">
                        <?php else: ?>
                            <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px;">
                                <i class="fas fa-flask text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= ucwords($row['name']) ?></strong>
                        <span class="badge badge-danger ml-1" title="Sustancia Peligrosa">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    </td>
                    <td>
                        <?php if ($hc): ?>
                            <span class="badge badge-<?= $hcInfo['color'] ?>">
                                <?= $hcInfo['label'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['branch_name'] ?? 'N/A') ?></td>
                    <td class="text-center"><?= $row['stock'] ?> <?= $stock_badge ?></td>
                    <td class="text-center">
                        <?php if (!empty($row['safety_data_sheet'])): ?>
                            <a href="<?= htmlspecialchars(rtrim(BASE_URL, '/') . '/' . $row['safety_data_sheet']) ?>"
                               target="_blank" class="btn btn-xs btn-outline-warning" title="Ver Hoja de Seguridad">
                                <i class="fas fa-file-alt"></i> Ver
                            </a>
                        <?php else: ?>
                            <span class="badge badge-secondary">Sin doc.</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php
                        $status_map = ['active' => ['Activo', 'success'], 'inactive' => ['Inactivo', 'secondary'], 'out_of_stock' => ['Sin Stock', 'danger']];
                        [$slabel, $scolor] = $status_map[$row['status']] ?? ['Desconocido', 'secondary'];
                        ?>
                        <span class="badge badge-<?= $scolor ?>"><?= $slabel ?></span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-secondary view-haz-inventory" data-id="<?= $row['id'] ?>"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    const table = $('#haz-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json' },
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 7] },
            { className: 'text-center', targets: [0, 4, 5, 6, 7] }
        ],
        info: false,
        lengthChange: false
    });

    // Ver / editar
    $(document).on('click', '.view-haz-inventory', function() {
        const id = $(this).data('id');
        const baseUrl = '<?= rtrim(BASE_URL, '/') ?>';
        uni_modal(
            '<i class="fas fa-edit text-primary"></i> Editar Insumo Peligroso',
            baseUrl + '/modals/view_inventory.php?id=' + id,
            'mid-large',
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Cancelar</button>' +
                '<button type="submit" form="update-inventory-form" class="btn btn-success font-weight-bold"><i class="fas fa-save mr-1"></i> Guardar Cambios</button>' +
            '</div>'
        );
    });

    // Exportar Excel
    $('#btn-export-haz').on('click', function() {
        let data = [['Nombre', 'Clase de Peligro', 'Sucursal', 'Stock', 'Hoja de Seguridad', 'Status']];
        table.rows({ search: 'applied' }).data().each(function(row) {
            data.push([
                $(row[1]).text().trim(),
                $(row[2]).text().trim(),
                $(row[3]).text().trim(),
                $(row[4]).text().trim(),
                $(row[5]).text().trim(),
                $(row[6]).text().trim()
            ]);
        });
        let csv  = data.map(r => r.join('\t')).join('\n');
        let blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        let url  = URL.createObjectURL(blob);
        let link = document.createElement('a');
        link.href = url;
        link.download = 'sustancias_peligrosas_' + new Date().toISOString().slice(0, 10) + '.csv';
        link.click();
    });
});
</script>
