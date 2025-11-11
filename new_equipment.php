<?php
include 'db_connect.php';

// Obtener el próximo ID autoincremental para Nro Inventario
$result = $conn->query("SHOW TABLE STATUS LIKE 'equipments'");
$row = $result->fetch_assoc();
$next_id = $row['Auto_increment'];
?>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form action="" id="manage_customer" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">

                <!-- === INFORMACIÓN GENERAL DEL EQUIPO === -->
                <div class="row mb-4">
                    <div class="col-12 mb-2"><b class="text-muted">Información General del Equipo</b></div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Nro Inventario</label>
                        <input type="text" name="number_inventory" class="form-control form-control-sm" readonly value="<?php echo $next_id; ?>">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Fecha Ingreso</label>
                        <input type="date" name="date_created" class="form-control form-control-sm" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Serie</label>
                        <input type="text" name="serie" class="form-control form-control-sm alfanumerico" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Equipo</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Marca</label>
                        <input type="text" name="brand" class="form-control form-control-sm">
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Modelo</label>
                        <input type="text" name="model" class="form-control form-control-sm" required>
                    </div>

                    <div class="row">
                        <!-- VOLTAJE -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Voltaje (V)</label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    name="voltage"
                                    class="form-control"
                                    placeholder="110.00"
                                    oninput="validity.valid||(value='');">
                            </div>
                        </div>

                        <!-- AMPERAJE -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Amperaje (A)</label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    name="amperage"
                                    class="form-control"
                                    placeholder="5.50"
                                    oninput="validity.valid||(value='');">
                            </div>
                        </div>

                        <!-- FRECUENCIA -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Frecuencia (Hz)</label>
                                <input type="number"
                                    step="0.01"
                                    min="0"
                                    name="frequency_hz"
                                    value="60.00"
                                    class="form-control"
                                    oninput="validity.valid||(value='');">
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-md-6">
                        <label class="control-label">Características</label>
                        <textarea name="characteristics" class="form-control" style="height: 80px;"></textarea>
                    </div>

                    <div class="form-group col-md-6">
                        <label class="control-label">Imagen del Equipo</label>
                        <input type="file" name="equipment_image" class="form-control form-control-sm" accept="image/*">
                        <?php if (!empty($equipment['image'])): ?>
                            <div class="mt-2">
                                <img src="<?php echo $equipment['image']; ?>" class="img-thumbnail" style="max-height: 120px;">
                                <br>
                                <a href="javascript:void(0)" class="text-danger delete-image" data-id="<?php echo $equipment['id']; ?>">
                                    <i class="fas fa-trash"></i> Eliminar imagen
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Valor del Equipo</label>
                        <input type="text" name="amount" class="form-control form-control-sm solonumeros" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Categoría</label>
                        <input type="text" name="discipline" class="form-control form-control-sm" required>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Tipo de Adquisición</label>
                        <select name="acquisition_type" class="custom-select custom-select-sm select2" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $types = $conn->query("SELECT id, name FROM equipment_acquisition_type ORDER BY name ASC");
                            while ($row = $types->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo (isset($eq['acquisition_type']) && $eq['acquisition_type'] == $row['id']) ? 'selected' : ''; ?>>
                                    <?php echo ucwords($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Periodo de Manto</label>
                        <select name="mandate_period_id" class="custom-select custom-select-sm select2" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $periods = $conn->query("SELECT id, name FROM maintenance_periods ORDER BY id ASC");
                            while ($row = $periods->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo (isset($equipment['mandate_period_id']) && $equipment['mandate_period_id'] == $row['id']) ? 'selected' : ''; ?>>
                                    <?php echo ucwords($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>


                    <div class="form-group col-md-4">
                        <label class="control-label">Proveedor</label>
                        <select name="supplier_id" class="custom-select custom-select-sm select2" required>
                            <option value="">Seleccionar Proveedor</option>
                            <?php
                            $suppliers = $conn->query("SELECT id, empresa FROM suppliers WHERE estado = 1 ORDER BY empresa ASC");
                            while ($row = $suppliers->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo ucwords($row['empresa']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Departamento</label>
                        <select name="department_id" class="custom-select custom-select-sm select2" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                            while ($row = $departments->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo ucwords($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Ubicación</label>
                        <select name="location_id" id="location_id" class="form-control select2" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $locations = $conn->query("SELECT id, name FROM equipment_locations ORDER BY name ASC");
                            while ($row = $locations->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo ucwords($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- === RESPONSABLE DEL EQUIPO === -->
                <div class="row mb-4">
                    <div class="col-12 mb-2"><b class="text-muted">Responsable del Equipo</b></div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Cargo Responsable</label>
                        <select name="responsible_position" class="custom-select custom-select-sm select2">
                            <option value="">Seleccionar</option>
                            <?php
                            $positions = $conn->query("SELECT * FROM job_positions ORDER BY name ASC");
                            while ($row = $positions->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo ucwords($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Nombre Responsable</label>
                        <input type="text" name="responsible_name" class="form-control form-control-sm" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Fecha Capacitación</label>
                        <input type="date" name="date_training" class="form-control form-control-sm" required>
                    </div>
                </div>

                <!-- === DOCUMENTOS DE CONTROL === -->
                <div class="row mb-4">
                    <div class="col-12 mb-2"><b class="text-muted">Documentos Control</b></div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Factura Nro</label>
                        <input type="text" class="form-control form-control-sm alfanumerico" name="invoice">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Comodato</label>
                        <input type="file" class="form-control form-control-sm" name="bailment_file">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Contrato M</label>
                        <input type="file" class="form-control form-control-sm" name="contract_file">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Manual Usuario</label>
                        <input type="file" class="form-control form-control-sm" name="usermanual_file">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Guía Rápida</label>
                        <input type="file" class="form-control form-control-sm" name="fast_guide_file">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Ficha Técnica</label>
                        <input type="file" class="form-control form-control-sm" name="datasheet_file">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Man. Servicios</label>
                        <input type="file" class="form-control form-control-sm" name="servicemanual_file">
                    </div>
                </div>

                <!-- === PRUEBAS DE RECEPCIÓN === -->
                <div class="row mb-4">
                    <div class="col-12 mb-2"><b class="text-muted">Pruebas de Recepción de Equipo</b></div>

                    <div class="form-group col-md-3">
                        <label class="control-label">Acepto</label>
                        <input type="radio" name="state" value="1" checked>&nbsp;&nbsp;
                        <label class="control-label">Rechazo</label>
                        <input type="radio" name="state" value="0">
                    </div>

                    <div class="form-group col-md-9">
                        <label class="control-label">Notas</label>
                        <textarea name="comments" class="form-control" style="height: 120px;"></textarea>
                    </div>
                </div>

                <!-- === RESGUARDO DEL EQUIPO === -->
                <div class="row mb-4">
                    <div class="col-12 mb-2"><b class="text-muted">Resguardo Equipo</b></div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Tiempo de Garantía</label>
                        <input type="number" name="warranty_time" class="form-control form-control-sm" min="1" placeholder="años" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="control-label">Fecha de Adquisición</label>
                        <input type="date" name="date_adquisition" class="form-control form-control-sm" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- === BOTONES === -->
                <div class="col-12 text-right">
                    <button class="btn btn-primary mr-2">Guardar</button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $('.solonumeros').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $('.alfanumerico').on('input', function(e) {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    $('#manage_customer').submit(function(e) {
        e.preventDefault();
        start_load();

        var postData = new FormData(this);

        $.ajax({
            url: 'ajax.php?action=save_equipment',
            data: postData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                end_load();
                if (resp == 1) {
                    alert_toast('Equipo guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=equipment_list', 1000);
                } else {
                    alert_toast('Error al guardar', 'danger');
                }
            },
            error: function() {
                end_load();
                alert_toast('Error de conexión', 'danger');
            }
        });
    });
</script>