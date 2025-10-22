<?php include 'db_connect.php' ?>

<?php
// Datos para las tarjetas de resumen (usando cantidad real)
$total_herramientas = $conn->query("SELECT SUM(cantidad) as total FROM tools")->fetch_assoc()['total'];
$activos = $conn->query("SELECT SUM(cantidad) as total FROM tools WHERE estatus = 'Activa'")->fetch_assoc()['total'];
$inactivos = $conn->query("SELECT SUM(cantidad) as total FROM tools WHERE estatus = 'Inactiva'")->fetch_assoc()['total'];
$total_valor = $conn->query("SELECT SUM(costo * cantidad) as total FROM tools")->fetch_assoc()['total'];
?>

<!-- Tarjetas de resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-boxes fa-2x text-primary mr-3"></i>
                <div>
                    <h6>Total de Herramientas</h6>
                    <h4><?php echo $total_herramientas; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                <div>
                    <h6>Activas</h6>
                    <h4><?php echo $activos; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x text-secondary mr-3"></i>
                <div>
                    <h6>Inactivas</h6>
                    <h4><?php echo $inactivos; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-dollar-sign fa-2x text-info mr-3"></i>
                <div>
                    <h6>Valor Total Inventario</h6>
                    <h4>$<?php echo number_format($total_valor, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de herramientas -->
<div class="col-lg-12">
    <div class="card">
        <div class="card-header border-0">
            <h3 class="card-title">Listado de Herramientas</h3>
            <div class="card-tools">
                <a href="./index.php?page=new_tool" class="btn btn-tool btn-sm" title="Agregar Herramienta">
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
                        <th>Cantidad</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Costo</th>
                        <th>Proveedor</th>
                        <th>Estatus</th>
                        <th>Fecha Adquisición</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $qry = $conn->query("SELECT t.*, s.empresa FROM tools t LEFT JOIN suppliers s ON t.supplier_id = s.id ORDER BY t.id DESC");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $row['cantidad']; ?></td>
                            <td class="text-center">
                                <?php if(!empty($row['imagen'])): ?>
                                    <img src="uploads/<?php echo $row['imagen']; ?>" alt="Imagen" style="max-width:50px; border-radius:4px;">
                                <?php else: ?>
                                    <i class="fas fa-box text-secondary"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['marca']; ?></td>
                            <td>$<?php echo number_format($row['costo'], 2); ?></td>
                            <td><?php echo $row['empresa']; ?></td>
                            <td>
                                <span class="badge <?php echo ($row['estatus'] == 'Activa') ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $row['estatus']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['fecha_adquisicion']; ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary edit-tool" data-id="<?php echo $row['id']; ?>">Editar</button>
                                <button class="btn btn-sm btn-danger delete-tool" data-id="<?php echo $row['id']; ?>">Eliminar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            <small class="text-muted">Total de herramientas: <strong><?php echo $total_herramientas; ?></strong></small>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#list').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
        "responsive": true,
        "autoWidth": false
    });

    // Eliminar herramienta
    $('.delete-tool').click(function() {
        var id = $(this).data('id');
        if(confirm("¿Deseas eliminar esta herramienta?")){
            $.ajax({
                url: 'ajax.php?action=delete_tool',
                method: 'POST',
                data: {id: id},
                success: function(resp){
                    if(resp == 1){
                        alert("Herramienta eliminada correctamente");
                        location.reload();
                    } else {
                        alert("Error al eliminar");
                    }
                }
            });
        }
    });

    // Editar herramienta
    $('.edit-tool').click(function() {
        var id = $(this).data('id');
        window.location.href = 'index.php?page=edit_tool&id=' + id;
    });
});
</script>
