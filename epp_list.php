<?php include 'db_connect.php' ?>

<?php
// Tarjetas de resumen para EPP
$total_epp = $conn->query("SELECT COUNT(*) as total FROM equipment_epp")->fetch_assoc()['total'];
$activos_epp = $conn->query("SELECT COUNT(*) as total FROM equipment_epp WHERE status = 'Activo'")->fetch_assoc()['total'];
$inactivos_epp = $conn->query("SELECT COUNT(*) as total FROM equipment_epp WHERE status = 'Inactivo'")->fetch_assoc()['total'];
$total_valor_epp = $conn->query("SELECT SUM(costo) as total FROM equipment_epp")->fetch_assoc()['total'];
?>

<!-- Tarjetas de resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-hard-hat fa-2x text-primary mr-3"></i>
                <div>
                    <h6>Total EPP</h6>
                    <h4><?php echo $total_epp; ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x text-success mr-3"></i>
                <div>
                    <h6>Activos</h6>
                    <h4><?php echo $activos_epp; ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-times-circle fa-2x text-secondary mr-3"></i>
                <div>
                    <h6>Inactivos</h6>
                    <h4><?php echo $inactivos_epp; ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-dollar-sign fa-2x text-info mr-3"></i>
                <div>
                    <h6>Valor Total EPP</h6>
                    <h4>$<?php echo number_format($total_valor_epp, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de EPP -->
<div class="col-lg-12">
    <div class="card">
        <div class="card-header border-0">
            <div class="card-tools">
                <a href="./index.php?page=new_epp" class="btn btn-tool btn-sm" title="Agregar EPP">
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
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Propiedad</th>
                        <th>Inventario</th>
                        <th>Área</th>
                        <th>Costo</th>
                        <th>Fecha Adquisición</th>
                        <th>Status</th>
                        <th>Observaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $qry = $conn->query("SELECT e.*, d.name as area FROM equipment_epp e LEFT JOIN departments d ON e.area_id = d.id ORDER BY e.id DESC");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                        <tr>
                            <td class="text-center">
                                <?php if (!empty($row['imagen'])) : ?>
                                    <img src="uploads/<?php echo $row['imagen']; ?>" alt="Imagen" style="max-width:50px; border-radius:4px;">
                                <?php else : ?>
                                    <i class="fas fa-image text-secondary"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['marca']; ?></td>
                            <td><?php echo $row['modelo']; ?></td>
                            <td><?php echo $row['serie']; ?></td>
                            <td><?php echo $row['propiedad']; ?></td>
                            <td><?php echo $row['numero_inventario']; ?></td>
                            <td><?php echo $row['area']; ?></td>
                            <td>$<?php echo number_format($row['costo'], 2); ?></td>
                            <td><?php echo $row['fecha_adquisicion']; ?></td>
                            <td>
                                <span class="badge <?php echo ($row['status'] == 'Activo') ? 'badge-success' : 'badge-secondary'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['observaciones']; ?></td>

                            <!-- BOTÓN DE ACCIONES (ESTILO PROVEEDORES) -->
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs mr-1"></i> Opciones
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item edit-epp" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-edit mr-2 text-primary"></i> Editar
                                        </a>
                                        <a class="dropdown-item delete-epp text-danger" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">
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

        <div class="card-footer">
            <small class="text-muted">Total de equipos EPP: <strong><?php echo $total_epp; ?></strong></small>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    var table = $('#list').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json" },
        responsive: true,
        autoWidth: false,
        columnDefs: [
            { orderable: false, targets: [0, 12] } // Imagen y Acciones
        ]
    });

    // === EXPORTAR A EXCEL ===
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
            if (rowData.length > 0) {
                rows.push(rowData);
            }
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
        link.download = 'epp_' + new Date().toISOString().slice(0, 10) + '.xls';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });

    // === EDITAR EPP ===
    $(document).on('click', '.edit-epp', function() {
        var id = $(this).data('id');
        window.location.href = 'index.php?page=edit_epp&id=' + id;
    });

    // === ELIMINAR EPP (AJAX) ===
    $(document).on('click', '.delete-epp', function() {
        var id = $(this).data('id');
        if (confirm("¿Deseas eliminar este equipo EPP?")) {
            $.ajax({
                url: 'ajax.php?action=delete_epp',
                method: 'POST',
                data: { id: id },
                success: function(resp) {
                    if (resp == 1) {
                        alert("Equipo EPP eliminado correctamente");
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