<?php include 'db_connect.php' ?>
<div class="col-lg-12">
    <!-- Card principal con nuevo estilo -->
    <div class="card">
        <div class="card-header border-0">
            <h3 class="card-title">Inventario de Equipos</h3>
            <div class="card-tools">
                <a href="./index.php?page=new_equipment" class="btn btn-tool btn-sm" title="Agregar Equipo">
                    <i class="fas fa-plus"></i>
                </a>
                <a href="#" class="btn btn-tool btn-sm" title="Exportar">
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
                        <th>#</th>
                        <th>Equipo</th>
                        <th>Detalles</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM equipments order by id desc");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                        <tr>
                            <td class="text-center">
                                <strong class="text-primary"><?php echo $i++ ?></strong>
                            </td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <i class="fas fa-laptop text-info" style="font-size: 24px;"></i>
                                    </div>
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
                            
                            <td>
                                <div>
                                    <strong>Inv: <?php echo $row['number_inventory'] ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $row['brand'] ?> - <?php echo $row['model'] ?>
                                    </small>
                                </div>
                            </td>
                            
                            <td>
                                <div>
                                    <?php if($row['revision'] == 1): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i>
                                            Con Revisión
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Sin Revisión
                                        </span>
                                    <?php endif ?>
                                    <br>
                                    <small class="text-muted mt-1 d-block">
                                        <i class="fas fa-barcode mr-1"></i>
                                        #<?php echo $row['number_inventory'] ?>
                                    </small>
                                </div>
                            </td>
                            
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cogs mr-1"></i>
                                        Opciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <h6 class="dropdown-header">Acciones Principales</h6>
                                        <a class="dropdown-item" href="./index.php?page=edit_equipment&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-edit mr-2 text-primary"></i>
                                            Editar
                                        </a>
                                        <a class="dropdown-item" href="./index.php?page=equipment_new_revision&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-tools mr-2 text-success"></i>
                                            Nueva Revisión
                                        </a>
                                        
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">Reportes</h6>
                                        <a class="dropdown-item" href="./index.php?page=equipment_report_responsible&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-file-alt mr-2 text-info"></i>
                                            Informe Responsiva
                                        </a>
                                        <a class="dropdown-item" href="./index.php?page=equipment_report_sistem&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-chart-bar mr-2 text-warning"></i>
                                            Reporte de Sistemas
                                        </a>
                                        
                                        <div class="dropdown-divider"></div>
                                        <h6 class="dropdown-header">Zona Peligrosa</h6>
                                        <a class="dropdown-item" href="./index.php?page=equipment_unsubscribe&id=<?php echo $row['id'] ?>">
                                            <i class="fas fa-archive mr-2 text-warning"></i>
                                            Dar de Baja
                                        </a>
                                        <a class="dropdown-item delete text-danger" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                            <i class="fas fa-trash mr-2"></i>
                                            Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Card Footer con información adicional -->
        <div class="card-footer">
            <div class="row">
                <div class="col-sm-6">
                    <small class="text-muted">
                        Total de equipos: <strong><?php echo $conn->query("SELECT * FROM equipments")->num_rows; ?></strong>
                    </small>
                </div>
                <div class="col-sm-6 text-right">
                    <small class="text-muted">
                        Con revisión: 
                        <span class="badge badge-success">
                            <?php echo $conn->query("SELECT * FROM equipments WHERE revision = 1")->num_rows; ?>
                        </span>
                        Sin revisión: 
                        <span class="badge badge-warning">
                            <?php echo $conn->query("SELECT * FROM equipments WHERE revision = 0")->num_rows; ?>
                        </span>
                    </small>
                </div>
            </div>
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
    
    $('.delete').click(function(e) {
        e.preventDefault();
        _conf("¿Deseas eliminar este equipo?", "delete_equipment", [$(this).attr('data-id')])
    })
    
    // Tooltip para botones
    $('[title]').tooltip();
})

function delete_equipment($id) {
    start_load()
    $.ajax({
        url: 'ajax.php?action=delete_equipment',
        method: 'POST',
        data: {
            id: $id
        },
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Datos eliminados correctamente", 'success')
                setTimeout(function() {
                    location.reload()
                }, 1500)
            }
        }
    })
}</script>