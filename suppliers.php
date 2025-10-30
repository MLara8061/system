<?php include 'db_connect.php' ?>

<?php
// Datos para las tarjetas de resumen
$total_proveedores = $conn->query("SELECT COUNT(*) as total FROM suppliers")->fetch_assoc()['total'];
$activos = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE estado = 1")->fetch_assoc()['total'];
$inactivos = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE estado = 0")->fetch_assoc()['total'];
$sectores = $conn->query("SELECT COUNT(DISTINCT sector) as total FROM suppliers")->fetch_assoc()['total'];
?>

<!-- Tarjetas de resumen de Proveedores -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-building fa-2x text-primary mr-3"></i>
                <div>
                    <h6>Total de Proveedores</h6>
                    <h4 id="total_proveedores"><?php echo $total_proveedores; ?></h4>
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
                    <h4 id="total_activos"><?php echo $activos; ?></h4>
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
                    <h4 id="total_inactivos"><?php echo $inactivos; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-layer-group fa-2x text-info mr-3"></i>
                <div>
                    <h6>Sectores</h6>
                    <h4 id="total_sectores"><?php echo $sectores; ?></h4>
                </div>
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
                <a href="export_suppliers.php" class="btn btn-tool btn-sm" title="Exportar">
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
                                <span class="btn btn-sm <?php echo ($row['estado'] == 1) ? 'btn-success' : 'btn-secondary'; ?>">
                                    <?php echo ($row['estado'] == 1) ? 'Activo' : 'Inactivo'; ?>
                                </span>
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
    // Inicializar DataTable
    var table = $('#list').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        responsive: true,
        autoWidth: false
    });

    // === EXPORTAR A EXCEL (FORZADO Y SEGURO) ===
    $(document).on('click', 'a[title="Exportar"]', function(e) {
        e.preventDefault();

        var rows = [];

        // Encabezados
        var headers = [];
        $('#list thead th').each(function() {
            var text = $(this).text().trim();
            if (text !== 'Acciones') {
                headers.push(text);
            }
        });
        rows.push(headers);

        // Filas visibles
        $('#list tbody tr:visible').each(function() {
            var rowData = [];
            $(this).find('td').each(function(index) {
                if (index < 6) { // Excluir "Acciones"
                    var cell = $(this);
                    var text = '';

                    if (cell.find('img').length > 0) {
                        text = 'Sí';
                    } else if (cell.find('span').length > 0) {  // span para estado
                        text = cell.find('span').text().trim();
                    } else {
                        text = cell.text().trim().replace(/\s+/g, ' ').replace(/Web/g, '').trim();
                    }

                    rowData.push(text);
                }
            });
            if (rowData.length > 0) {
                rows.push(rowData);
            }
        });

        if (rows.length <= 1) {
            alert("No hay datos para exportar.");
            return;
        }

        // === GENERAR HTML ===
        var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        html += '<head><meta charset="UTF-8">[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
        html += '<x:Name>Proveedores</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
        html += '<body><table border="1" style="border-collapse:collapse;">';

        rows.forEach(function(row, i) {
            html += '<tr>';
            row.forEach(function(cell) {
                var style = i === 0 ? 'font-weight:bold;background:#f0f0f0;' : '';
                html += '<td style="' + style + 'padding:8px;">' + cell + '</td>';
            });
            html += '</tr>';
        });

        html += '</table></body></html>';

        // === FORZAR DESCARGA ===
        var blob = new Blob(['\ufeff' + html], {
            type: 'application/vnd.ms-excel'
        });
        var filename = 'proveedores_' + new Date().toISOString().slice(0, 10) + '.xls';

        if (navigator.msSaveBlob) {
            navigator.msSaveBlob(blob, filename);
        } else {
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            setTimeout(function() {
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            }, 100);
        }
    });

    // === ELIMINAR PROVEEDOR ===
    $('.delete').click(function(e) {
        e.preventDefault();
        _conf("¿Deseas eliminar este proveedor?", "delete_supplier", [$(this).attr('data-id')]);
    });

    $('[title]').tooltip();
});
</script>
