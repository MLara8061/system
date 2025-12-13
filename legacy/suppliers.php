<?php require_once 'config/config.php'; ?>

<?php
// === RESUMEN DE TARJETAS ===
$total_proveedores = $conn->query("SELECT COUNT(*) as total FROM suppliers")->fetch_assoc()['total'];
$activos = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE estado = 1")->fetch_assoc()['total'];
$inactivos = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE estado = 0")->fetch_assoc()['total'];
$sectores = $conn->query("SELECT COUNT(DISTINCT sector) as total FROM suppliers")->fetch_assoc()['total'];
?>

<!-- TARJETAS DE RESUMEN -->
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

<!-- TABLA DE PROVEEDORES -->
<div class="col-lg-12">
    <div class="card">
        <div class="card-header border-0">
           
            <div class="card-tools">
                <!-- BOTÓN + (ORIGINAL) -->
                <a href="./index.php?page=new_supplier" class="btn btn-tool btn-sm" title="Agregar Proveedor">
                    <i class="fas fa-plus"></i>
                </a>
                <!-- BOTÓN DESCARGAR (ORIGINAL) -->
                <a href="javascript:void(0)" id="export_excel" class="btn btn-tool btn-sm" title="Exportar">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-valign-middle" id="list">
                <thead>
                    <tr>
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
                    $qry = $conn->query("SELECT * FROM suppliers ORDER BY empresa ASC");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                        <tr>
                            <!-- EMPRESA + RFC -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <i class="fas fa-building text-primary" style="font-size: 24px;"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['empresa']) ?></strong>
                                        <?php if (!empty($row['rfc'])): ?>
                                            <br>
                                            <small class="text-muted"><?php echo strtoupper(htmlspecialchars($row['rfc'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- REPRESENTANTE + CORREO -->
                            <td>
                                <strong><?php echo htmlspecialchars($row['representante']) ?></strong>
                                <?php if (!empty($row['correo'])): ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['correo']) ?></small>
                                <?php endif; ?>
                            </td>

                            <!-- TELÉFONO + SITIO WEB (CORREGIDO) -->
                            <td>
                                <small>
                                    <?php if (!empty($row['telefono'])): ?>
                                        <i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars($row['telefono']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($row['sitio_web'])): ?>
                                        <i class="fas fa-globe mr-1"></i>
                                        <a href="https://<?php echo htmlspecialchars($row['sitio_web']) ?>"
                                            target="_blank"
                                            class="text-primary">
                                            <?php echo htmlspecialchars($row['sitio_web']) ?>
                                        </a>
                                    <?php endif; ?>
                                </small>
                            </td>

                            <!-- SECTOR -->
                            <td><?php echo htmlspecialchars($row['sector']) ?></td>

                            <!-- ESTADO -->
                            <td>
                                <span class="badge <?php echo $row['estado'] == 1 ? 'badge-success' : 'badge-secondary' ?>">
                                    <?php echo $row['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>

                            <!-- ACCIONES -->
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs mr-1"></i> Opciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="./index.php?page=edit_supplier&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-edit mr-2 text-primary"></i> Editar
                                        </a>
                                        <a class="dropdown-item text-danger delete" href="javascript:void(0)"
                                            data-id="<?php echo $row['id'] ?>">
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
            <small class="text-muted">
                Total de proveedores: <strong id="total_proveedores_footer"><?php echo $total_proveedores; ?></strong>
            </small>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // === DATATABLES CON RENDER CORRECTO PARA CONTACTO ===
        $('#list').DataTable({
            language: {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible",
                "sInfo": "Mostrando _START_ a _END_ de _TOTAL_",
                "sInfoEmpty": "Mostrando 0 a 0 de 0",
                "sInfoFiltered": "(filtrado de _MAX_ total)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            order: [
                [0, 'asc']
            ],
            columnDefs: [{
                targets: 2, // Columna Contacto
                render: function(data, type, row, meta) {
                    if (type === 'display') {
                        return data; // Ya está bien formateado en PHP
                    }
                    // Para filtro/búsqueda: extraer texto limpio
                    let text = '';
                    if (row.telefono) text += row.telefono + ' ';
                    if (row.sitio_web) text += row.sitio_web;
                    return text.trim();
                }
            }]
        });

        // === EXPORTAR A CSV 100% FUNCIONAL Y DINÁMICO ===
        $('#export_excel').click(function(e) {
            e.preventDefault();
            let table = $('#list').DataTable();
            let rows = [];

            // === ENCABEZADOS ===
            let headers = [];
            $('#list thead th').each(function() {
                let th = $(this).text().trim();
                if (th && th !== 'Acciones') {
                    headers.push(th);
                }
            });
            rows.push(headers);

            // === FILAS VISIBLES ===
            table.rows({
                search: 'applied',
                page: 'current'
            }).every(function() {
                let data = this.data();
                let row = [];

                // Empresa + RFC
                let empresa = $(data[0]).find('strong').text().trim();
                let rfc = $(data[0]).find('small').text().trim();
                row.push(rfc ? `${empresa} (${rfc})` : empresa);

                // Representante + Correo
                let rep = $(data[1]).find('strong').text().trim();
                let correo = $(data[1]).find('small').text().trim();
                row.push(correo ? `${rep} (${correo})` : rep);

                // Contacto: Teléfono + Web
                let contacto = '';
                let telMatch = data[2].match(/fa-phone[^>]*>([^<]+)/);
                let webMatch = data[2].match(/href="[^"]*">([^<]+)</);
                if (telMatch) contacto += telMatch[1].trim();
                if (webMatch) contacto += (contacto ? ', ' : '') + webMatch[1].trim();
                row.push(contacto || '-');

                // Sector
                row.push($(data[3]).text().trim());

                // Estado
                row.push($(data[4]).text().trim());

                rows.push(row);
            });

            // === GENERAR Y DESCARGAR CSV ===
            let csv = rows.map(r => '"' + r.join('","') + '"').join('\n');
            let blob = new Blob(['\ufeff' + csv], {
                type: 'text/csv;charset=utf-8;'
            });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'proveedores_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // === ELIMINAR PROVEEDOR ===
        $(document).on('click', '.delete', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            confirm_toast(
                '¿Estás seguro de eliminar este proveedor? Esta acción no se puede deshacer.',
                function() { delete_supplier(id); }
            );
        });

        // Tooltip
        $('[title]').tooltip();

    }); // ← CIERRE CORRECTO

    // === FUNCIÓN GLOBAL DE ELIMINAR ===
    function delete_supplier(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_supplier',
            method: 'POST',
            data: {
                id: id
            },
            success: function(resp) {
                end_load();
                if (resp == 1) {
                    alert_toast('Proveedor eliminado correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast('Error al eliminar: ' + resp, 'error');
                }
            },
            error: function() {
                end_load();
                alert_toast('Error de conexión', 'error');
            }
        });
    }
</script>