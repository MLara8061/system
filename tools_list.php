<?php include 'db_connect.php' ?>

<?php
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
                                <?php if (!empty($row['imagen'])) : ?>
                                    <img src="uploads/<?php echo $row['imagen']; ?>" alt="Imagen" style="max-width:50px; border-radius:4px;">
                                <?php else : ?>
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
    // === DATA TABLES CON IDIOMA LOCAL (SIN CORS) ===
    $('#list').DataTable({
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
            "sInfoFiltered": "(filtrado de _MAX_ registros)",
            "sSearch": "Buscar:",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        },
        "responsive": true,
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": [1,8] } // Imagen y Acciones
        ]
    });

    // === EXPORTAR A EXCEL (CORREGIDO: nombre archivo) ===
    $(document).on('click', 'a[title="Exportar"]', function(e) {
        e.preventDefault();

        var rows = [];
        var headerCells = [];
        $('#list thead th').each(function() {
            var text = $(this).text().trim();
            if (text !== 'Acciones') {
                headerCells.push(text);
            }
        });
        rows.push(headerCells);

        $('#list tbody tr:visible').each(function() {
            var rowData = [];
            $(this).find('td').each(function(index) {
                if (index < $(this).parent().find('td').length - 1) {
                    var cell = $(this);
                    var text = '';

                    if (cell.find('img').length > 0) {
                        text = 'Sí';
                    } else if (cell.find('.badge').length > 0) {
                        text = cell.find('.badge').text().trim();
                    } else {
                        text = cell.text().trim();
                    }

                    if (text.includes('$') || text.includes(',')) {
                        text = text.replace(/[$,]/g, '');
                    }

                    rowData.push(text);
                }
            });
            if (rowData.length > 0) rows.push(rowData);
        });

        if (rows.length <= 1) {
            alert("No hay datos visibles para exportar.");
            return;
        }

        var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
        html += '<table border="1" style="border-collapse: collapse; width: 100%;">';
        
        rows.forEach(function(row, i) {
            html += '<tr>';
            row.forEach(function(cell) {
                var style = i === 0 ? 'font-weight: bold; background-color: #f2f2f2;' : '';
                html += '<td style="' + style + ' padding: 8px;">' + cell + '</td>';
            });
            html += '</tr>';
        });
        
        html += '</table></body></html>';

        var blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'herramientas_' + new Date().toISOString().slice(0, 10) + '.xls'; // ← CORREGIDO
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });

    // === EDITAR HERRAMIENTA ===
    $(document).on('click', '.edit-tool', function() {
        var id = $(this).data('id');
        window.location.href = 'index.php?page=new_tool&id=' + id;
    });

    // === ELIMINAR HERRAMIENTA (AJAX) ===
    $(document).on('click', '.delete-tool', function() {
        var id = $(this).data('id');
        if (confirm("¿Eliminar esta herramienta?")) {
            $.ajax({
                url: 'ajax.php?action=delete_tool',
                method: 'POST',
                data: { id: id },
                success: function(resp) {
                    if (resp == 1) {
                        alert("Herramienta eliminada correctamente");
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        alert("Error al eliminar");
                    }
                },
                error: function() {
                    alert("Error de conexión");
                }
            });
        }
    });
});
</script>