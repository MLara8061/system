<?php require_once 'config/config.php'; ?>

<?php
// Datos para las tarjetas
$total_equipos = $conn->query("SELECT COUNT(*) as total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL")->fetch_assoc()['total'];
$costo_total = $conn->query("SELECT SUM(e.amount) as total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL")->fetch_assoc()['total'];
$preventivos = $conn->query("SELECT COUNT(*) as total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL AND e.mandate_period_id = 1")->fetch_assoc()['total'];
$correctivos = $conn->query("SELECT COUNT(*) as total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL AND e.mandate_period_id = 2")->fetch_assoc()['total'];
?>

<!-- Tarjetas de resumen de Equipos -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-desktop fa-2x text-primary mr-3"></i>
                <div>
                    <h6>Total de Equipos</h6>
                    <h4><?php echo $total_equipos; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-dollar-sign fa-2x text-success mr-3"></i>
                <div>
                    <h6>Costo Total</h6>
                    <h4>$<?php echo number_format($costo_total, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-tools fa-2x text-warning mr-3"></i>
                <div>
                    <h6>Mantenimientos Preventivos</h6>
                    <h4><?php echo $preventivos; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x text-danger mr-3"></i>
                <div>
                    <h6>Mantenimientos Correctivos</h6>
                    <h4><?php echo $correctivos; ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de equipos -->
<div class="col-lg-12">
    <div class="card">
        <div class="card-header border-0">
            <div class="card-tools">
                <a href="./index.php?page=new_equipment" class="btn btn-tool btn-sm" title="Agregar Equipo">
                    <i class="fas fa-plus"></i>
                </a>
                <a href="export_equipment.php" class="btn btn-tool btn-sm" title="Exportar">
                    <i class="fas fa-download"></i>
                </a>
                <a href="#" class="btn btn-tool btn-sm" title="Vista">
                    <i class="fas fa-bars"></i>
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-valign-middle" id="list">
                <thead>
                    <tr>
                        <th style="width: 60px;">Img</th>
                        <th>Equipo</th>
                        <th>Detalles</th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                        <th>Próximo Mtto.</th> <!-- NUEVA -->
                        <th>Último Mtto.</th> <!-- NUEVA -->
                        <th>Antigüedad</th> <!-- NUEVA -->
                        <th style="width: 60px;">QR</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $qry = $conn->query("
                        SELECT 
                            e.*,
                            s.empresa as supplier_name,
                            DATEDIFF(CURDATE(), IFNULL(e.purchase_date, e.date_created)) AS antiguedad_dias
                        FROM equipments e 
                        LEFT JOIN suppliers s ON e.supplier_id = s.id 
                        LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id
                        WHERE u.id IS NULL
                        ORDER BY e.id DESC
                    ");
                    while ($row = $qry->fetch_assoc()) :
                        $supplier_name = $row['supplier_name'] ?: 'Sin Proveedor';
                        $proximo = 'Sin periodo';
                        $ultimo = 'Sin registro';
                        $antiguedad = abs($row['antiguedad_dias']) . ' días';
                        $dias_restantes = 999;
                    ?>
                        <tr>
                            <!-- IMAGEN -->
                            <td class="text-center">
                                <?php
                                    $imgSrc = $row['image'] ?? '';
                                    $imgFile = __DIR__ . '/' . ltrim($imgSrc, '/');
                                    if (!empty($imgSrc) && file_exists($imgFile)): ?>
                                        <img src="<?php echo $imgSrc; ?>"
                                            class="rounded shadow-sm"
                                            style="width: 45px; height: 45px; object-fit: cover; border: 1px solid #ddd;">
                                    <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px; border: 1px dashed #ccc;">
                                        <i class="fas fa-camera text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- EQUIPO -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong><?php echo $row['name'] ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($row['date_created'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- DETALLES -->
                            <td>
                                <div>
                                    <strong>Inv: <?php echo $row['number_inventory'] ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $row['brand'] ?> - <?php echo $row['model'] ?>
                                    </small>
                                </div>
                            </td>

                            <!-- PROVEEDOR -->
                            <td>
                                <div>
                                    <strong><?php echo ucwords($supplier_name); ?></strong>
                                </div>
                            </td>

                            <!-- ESTADO -->
                            <td>
                                <div>
                                    <?php if ($row['revision'] == 1): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i> Con Revisión
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Sin Revisión
                                        </span>
                                    <?php endif ?>
                                    <br>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="fas fa-barcode mr-1"></i>
                                        #<?php echo $row['number_inventory'] ?>
                                    </small>
                                </div>
                            </td>

                            <!-- PRÓXIMO MTTO. -->
                            <td>
                                <div>
                                    <strong>
                                        <?php if ($dias_restantes < 0): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-circle"></i> <?php echo $proximo; ?>
                                            </span>
                                        <?php elseif ($dias_restantes <= 7): ?>
                                            <span class="text-warning">
                                                <i class="fas fa-clock"></i> <?php echo $proximo; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-success">
                                                <i class="fas fa-calendar-check"></i> <?php echo $proximo; ?>
                                            </span>
                                        <?php endif; ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $dias_restantes >= 0 ? "En $dias_restantes días" : "Vencido hace " . abs($dias_restantes) . " días"; ?>
                                    </small>
                                </div>
                            </td>

                            <!-- ÚLTIMO MTTO. -->
                            <td>
                                <div>
                                    <strong><?php echo $ultimo; ?></strong>
                                </div>
                            </td>

                            <!-- ANTIGÜEDAD -->
                            <td>
                                <div>
                                    <strong><?php echo $antiguedad; ?></strong>
                                </div>
                            </td>

                            <!-- QR -->
                            <td class="text-center">
                                <button type="button" class="btn btn-info btn-sm view-qr" data-id="<?php echo $row['id']; ?>" title="Ver QR">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                            </td>

                            <!-- ACCIONES -->
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <h6 class="dropdown-header">Acciones</h6>
                                        <a class="dropdown-item" href="./index.php?page=view_equipment&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-eye mr-2 text-primary"></i> Ver
                                        </a>
                                        <a class="dropdown-item" href="./index.php?page=edit_equipment&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-edit mr-2 text-secondary"></i> Editar
                                        </a>
                                        <a class="dropdown-item" href="./index.php?page=equipment_new_revision&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-tools mr-2 text-success"></i> Nueva Revisión
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">Reportes</h6>
                                        <a class="dropdown-item" href="./index.php?page=equipment_report_responsible&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-file-alt mr-2 text-info"></i> Informe Responsiva
                                        </a>
                                        <a class="dropdown-item" href="./index.php?page=equipment_report_sistem&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-chart-bar mr-2 text-warning"></i> Reporte de Sistemas
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">Zona Peligrosa</h6>
                                        <a class="dropdown-item" href="./index.php?page=equipment_unsubscribe&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-archive mr-2 text-warning"></i> Dar de Baja
                                        </a>
                                        <a class="dropdown-item delete text-danger" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
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

        <!-- Card Footer -->
        <div class="card-footer">
            <div class="row">
                <div class="col-sm-6">
                    <small class="text-muted">
                        Total de equipos: <strong><?php echo $total_equipos; ?></strong>
                    </small>
                </div>
                <div class="col-sm-6 text-right">
                    <small class="text-muted">
                        Con revisión:
                        <span class="badge badge-success"><?php echo $conn->query("SELECT e.* FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL AND e.revision = 1")->num_rows; ?></span>
                        Sin revisión:
                        <span class="badge badge-warning"><?php echo $conn->query("SELECT e.* FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL AND e.revision = 0")->num_rows; ?></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QR -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="qrModalLabel">Código QR del Equipo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="qrLoading">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <p class="text-muted">Generando QR...</p>
                </div>
                <div id="qrContent" class="d-none">
                    <img id="qrImage" src="" alt="QR" class="img-fluid rounded shadow-sm" style="max-width: 240px;">
                    <div class="mt-3 p-3 bg-light rounded">
                        <p class="mb-1"><strong id="qrName"></strong></p>
                        <p class="mb-1 text-muted"><small>Inventario: <span id="qrInventory"></span></small></p>
                        <p class="mb-0 text-muted"><small>Serie: <span id="qrSerie"></span></small></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="#" id="printLabelBtn" target="_blank" class="btn btn-success">Imprimir</a>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#list').DataTable({
            language: { url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
            responsive: true,
            autoWidth: false,
            columnDefs: [{ orderable: false, targets: [0,4,5,6,7,8,9] }]
        });

        $(document).on('click', '.view-qr', function() {
            const id = $(this).data('id');
            const $row = $(this).closest('tr');

            $('#qrLoading').show();
            $('#qrContent').addClass('d-none');
            $('#qrName').text($row.find('td:eq(1) strong').text().trim());
            $('#qrInventory').text($row.find('td:eq(2) strong').text().replace('Inv: ', '').trim());
            $('#qrSerie').text($row.find('td:eq(2) small').text().split(' - ')[1]?.trim() || 'N/A');
            $('#printLabelBtn').attr('href', 'print_label.php?id=' + id);

            const img = new Image();
            img.onload = function() {
                $('#qrImage').attr('src', this.src);
                $('#qrLoading').hide();
                $('#qrContent').removeClass('d-none');
            };
            img.onerror = function() {
                $('#qrLoading').html('<p class="text-danger">Error: No se pudo generar el QR</p>');
            };
            img.src = 'generate_qr.php?id=' + id + '&t=' + Date.now();

            $('#qrModal').modal('show');
        });

        // === ELIMINAR EQUIPO ===
        $(document).on('click', '.delete', function() {
            const id = $(this).data('id');
            confirm_toast(
                '¿Estás seguro de eliminar este equipo? Esta acción no se puede deshacer.',
                function() { delete_equipment(id); }
            );
        });

        window.delete_equipment = function(id) {
            start_load();
            $.ajax({
                url: 'ajax.php?action=delete_equipment',
                method: 'POST',
                data: { id: id },
                success: function(resp) {
                    end_load();
                    if (resp == 1) {
                        alert_toast("Equipo eliminado correctamente", 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert_toast("Error al eliminar el equipo", 'error');
                    }
                },
                error: function() {
                    end_load();
                    alert_toast("Error de conexión", 'error');
                }
            });
        };
    });
</script>