<?php include 'db_connect.php' ?>

<?php
// Datos para las tarjetas de resumen
$total_proveedores = $conn->query("SELECT COUNT(*) as total FROM suppliers")->fetch_assoc()['total'];
$activos = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE estado = 1")->fetch_assoc()['total'];
$inactivos = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE estado = 0")->fetch_assoc()['total'];
$sectores = $conn->query("SELECT COUNT(DISTINCT sector) as total FROM suppliers")->fetch_assoc()['total'];
?>

<!-- Tarjetas de resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow">
            <div class="card-body">
                <h5>Total de Proveedores</h5>
                <h3 id="total_proveedores"><?php echo $total_proveedores; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                <h5>Activos</h5>
                <h3 id="total_activos"><?php echo $activos; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-danger text-white shadow">
            <div class="card-body">
                <h5>Inactivos</h5>
                <h3 id="total_inactivos"><?php echo $inactivos; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow">
            <div class="card-body">
                <h5>Sectores</h5>
                <h3 id="total_sectores"><?php echo $sectores; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de proveedores -->
<div class="col-lg-12">
    <div class="card">
        <div class="card-header border-0">
            <h3 class="card-title">Listado de Proveedores</h3>
            <div class="card-tools">
                <a href="./index.php?page=new_supplier" class="btn btn-tool btn-sm" title="Agregar Proveedor">
                    <i class="fas fa-plus"></i>
                </a>
                <a href="#" class="btn btn-tool btn-sm" title="Exportar">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-valign-middle" id="list">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empresa</th>
                        <th>Representante</th>
                        <th>Contacto</th>
                        <th>Sector</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                        <tr>
                            <td class="text-center"><strong class="text-primary"><?php echo $i++ ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <?php if (!empty($row['imagen'])): ?>
                                            <img src="assets/uploads/<?php echo $row['imagen'] ?>" alt="logo" width="40" height="40" class="rounded-circle">
                                        <?php else: ?>
                                            <i class="fas fa-building text-primary" style="font-size: 24px;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo $row['empresa'] ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo $row['rfc'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo $row['representante'] ?></strong>
                                <br>
                                <small class="text-muted"><?php echo $row['correo'] ?></small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-phone mr-1"></i> <?php echo $row['telefono'] ?><br>
                                    <?php if ($row['sitio_web']): ?>
                                        <i class="fas fa-globe mr-1"></i>
                                        <a href="<?php echo $row['sitio_web'] ?>" target="_blank">Web</a>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><?php echo $row['sector'] ?></td>
                            <td>
                                <!-- Botón interactivo de estado -->
                                <button class="btn btn-sm btn-toggle-status <?php echo ($row['estado'] == 1) ? 'btn-success' : 'btn-secondary'; ?>"
                                    data-id="<?php echo $row['id'] ?>" data-status="<?php echo $row['estado'] ?>">
                                    <?php echo ($row['estado'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                </button>
                            </td>

                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs mr-1"></i> Opciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="./index.php?page=edit_supplier&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-edit mr-2 text-primary"></i> Editar
                                        </a>
                                        <a class="dropdown-item text-danger delete" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
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
            <small class="text-muted">Total de proveedores: <strong id="total_proveedores_footer"><?php echo $total_proveedores; ?></strong></small>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#list').dataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        "responsive": true,
        "autoWidth": false,
    });

    // Eliminar proveedor
    $('.delete').click(function(e) {
        e.preventDefault();
        _conf("¿Deseas eliminar este proveedor?", "delete_supplier", [$(this).attr('data-id')])
    });

    // Cambiar estado desde el botón interactivo
    $(document).on('click', '.btn-toggle-status', function(){
        var btn = $(this);
        var id = btn.data('id');
        var status = btn.data('status');
        var newStatus = (status == 1) ? 0 : 1;

        start_load();
        $.ajax({
            url: 'ajax.php?action=toggle_supplier_status',
            method: 'POST',
            data: { id: id, status: newStatus },
            success: function(resp){
                end_load();
                if(resp == 1){
                    // Actualizar botón
                    btn.data('status', newStatus);
                    if(newStatus == 1){
                        btn.removeClass('btn-secondary').addClass('btn-success').text('Activo');
                    } else {
                        btn.removeClass('btn-success').addClass('btn-secondary').text('Inactivo');
                    }

                    // Actualizar contadores en tiempo real
                    var activos = parseInt($('#total_activos').text());
                    var inactivos = parseInt($('#total_inactivos').text());
                    if(newStatus == 1){
                        $('#total_activos').text(activos + 1);
                        $('#total_inactivos').text(inactivos - 1);
                    } else {
                        $('#total_activos').text(activos - 1);
                        $('#total_inactivos').text(inactivos + 1);
                    }

                    alert_toast("Estado actualizado correctamente", 'success');
                } else {
                    alert_toast("Error al actualizar estado", 'danger');
                }
            },
            error: function(){
                end_load();
                alert_toast("Error de conexión", 'danger');
            }
        });
    });

    $('[title]').tooltip();
});

// Función eliminar proveedor
function delete_supplier(id) {
    start_load();
    $.ajax({
        url: 'ajax.php?action=delete_supplier',
        method: 'POST',
        data: { id: id },
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Proveedor eliminado correctamente", 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }
        }
    });
}
</script>
