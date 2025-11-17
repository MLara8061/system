<?php require_once 'config/config.php'; ?>

<?php
// === TOTALES GENERALES DE INVENTARIO ===
$total_items = $conn->query("SELECT COUNT(*) as total FROM inventory")->fetch_assoc()['total'] ?? 0;

// === CAMBIO 1: Contar ítems CON STOCK (stock > 0) ===
$con_stock = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE stock > 0")->fetch_assoc()['total'] ?? 0;

// === CAMBIO 2: Contar ítems SIN STOCK (stock = 0) ===
// Se mantiene la variable, pero se asegura la condición de conteo (stock = 0)
$sin_stock = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE stock = 0")->fetch_assoc()['total'] ?? 0;

// Se mantiene la lógica del valor total
$total_valor = $conn->query("SELECT COALESCE(SUM(cost * stock), 0) as total FROM inventory")->fetch_assoc()['total'] ?? 0;
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-boxes fa-2x text-primary mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Total Items</h6>
                    <h4 class="mb-0"><?= $total_items ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Con Stock</h6>
                    <h4 class="mb-0"><?= $con_stock ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x text-warning mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Sin Stock</h6>
                    <h4 class="mb-0"><?= $sin_stock ?></h4>
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
                    <h4 class="mb-0">$<?= number_format($total_valor, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="col-lg-12">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white border-0">
            <div class="card-tools float-right">
                <a href="index.php?page=manage_inventory" class="btn btn-tool btn-sm" title="Agregar Item">
                    <i class="fas fa-plus text-secondary"></i>
                </a>
                <a href="#" class="btn btn-tool btn-sm" title="Exportar a Excel" id="export-excel">
                    <i class="fas fa-download text-info"></i>
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped" id="inventory-table">
                <thead class="bg-light">
                    <tr>
                        <th style="width:50px;">Img</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Costo</th>
                        <th>Stock</th>
                        <th>Mín</th>
                        <th>Máx</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Creado</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $qry = $conn->query("
                        SELECT id, name, category, price, cost, stock, min_stock, max_stock, status, image_path, created_at
                        FROM inventory 
                        ORDER BY name ASC
                    ");
                    while ($row = $qry->fetch_assoc()):
                        $valor_total = $row['cost'] * $row['stock'];

                        // === STATUS DE STOCK ===
                        $stock_status = '';
                        if ($row['stock'] == 0) {
                            $stock_status = '<span class="badge badge-danger">Sin Stock</span>';
                        } elseif ($row['stock'] <= $row['min_stock']) {
                            $stock_status = '<span class="badge badge-warning">Bajo</span>';
                        } else {
                            $stock_status = '<span class="badge badge-success">Suficiente</span>';
                        }
                    ?>
                        <tr>
                            <td class="text-center">
                                <?php if (!empty($row['image_path']) && file_exists('uploads/' . $row['image_path'])): ?>
                                    <img src="uploads/<?= $row['image_path'] ?>" class="img-thumbnail"
                                        style="width:36px; height:36px; object-fit:cover; border-radius:6px;">
                                <?php else: ?>
                                    <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                        style="width:36px; height:36px;">
                                        <i class="fas fa-box text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= ucwords($row['name']) ?></strong></td>
                            <td><span class="text-muted"><?= ucwords($row['category'] ?? 'N/A') ?></span></td>
                            <td>$<?= number_format($row['price'], 2) ?></td>
                            <td>$<?= number_format($row['cost'], 2) ?></td>
                            <td class="text-center <?= $row['stock'] == 0 ? 'text-danger font-weight-bold' : '' ?>">
                                <?= $row['stock'] ?>
                            </td>
                            <td class="text-center"><?= $row['min_stock'] ?></td>
                            <td class="text-center"><?= $row['max_stock'] ?></td>
                            <td>$<?= number_format($valor_total, 2) ?></td>
                            <td class="text-center"><?= $stock_status ?></td>
                            <td><small><?= date('d/m/Y', strtotime($row['created_at'])) ?></small></td>

                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                        data-toggle="dropdown" title="Opciones">
                                        <i class="fas fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item view-inventory" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-eye text-primary mr-2"></i> Ver
                                        </a>
                                        <a class="dropdown-item delete-inventory text-danger" href="javascript:void(0)"
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
    </div>
</div>

<style>
    .card {
        transition: none !important;
    }

    .table th {
        font-weight: 600;
        font-size: 0.9rem;
    }

    #inventory-table .badge {
        font-size: 0.75rem;
    }

    .img-thumbnail {
        border: 1px solid #ddd;
    }

    /* Quitar fondo y negrita de categoría */
    #inventory-table td:nth-child(3) span {
        font-weight: normal !important;
        background: none !important;
        color: #6c757d !important;
    }
</style>

<script>
$(document).ready(function() {
    const table = $('#inventory-table').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 11] },
            { className: "text-center", targets: [0, 5, 6, 7, 9, 10, 11] }
        ],
        info: false,
        lengthChange: false
    });

    // === VER / EDITAR ITEM ===
    $(document).on('click', '.view-inventory', function() {
        const id = $(this).data('id');
        uni_modal(
            '<i class="fa fa-edit text-primary"></i> Editar Ítem',
            'view_inventory.php?id=' + id,
            'mid-large',
            '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">' +
                    '<i class="fas fa-times mr-1"></i> Cancelar' +
                '</button>' +
                '<button type="submit" form="update-inventory-form" class="btn btn-success font-weight-bold">' +
                    '<i class="fas fa-save mr-1"></i> Guardar Cambios' +
                '</button>' +
            '</div>'
        );
    });

    // === ELIMINAR ITEM ===
    $(document).on('click', '.delete-inventory', function() {
        const id = $(this).data('id');
        _conf("¿Eliminar este ítem del inventario?", "delete_inventory", [id]);
    });

    // === FUNCIÓN ELIMINAR ===
    window.delete_inventory = function(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_inventory',
            method: 'POST',
            data: { id: id },
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Eliminado correctamente", 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast("Error al eliminar", 'error');
                    end_load();
                }
            }
        });
    };

    // === EXPORTAR A EXCEL ===
    $('#export-excel').click(function(e) {
        e.preventDefault();
        let data = [['Img','Nombre','Categoría','Precio','Costo','Stock','Mín','Máx','Valor Total','Status','Creado']];
        table.rows({ search: 'applied' }).data().each(function(row) {
            const img = $(row[0]).find('img').length ? 'Sí' : 'No';
            const status = $(row[9]).text();
            data.push([
                img,
                $(row[1]).text(),
                $(row[2]).text(),
                $(row[3]).text().replace(/[$,]/g, ''),
                $(row[4]).text().replace(/[$,]/g, ''),
                $(row[5]).text(),
                $(row[6]).text(),
                $(row[7]).text(),
                $(row[8]).text().replace(/[$,]/g, ''),
                status,
                $(row[10]).text()
            ]);
        });

        let csv = data.map(r => r.join('\t')).join('\n');
        let blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        let url = URL.createObjectURL(blob);
        let link = document.createElement('a');
        link.href = url;
        link.download = 'inventario_' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
    });
});
</script>