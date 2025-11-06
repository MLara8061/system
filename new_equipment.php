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

                <div class="row">
                    <div class="col-md-12 border-right">

                        <!-- Nro Inventario -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Nro Inventario</label>
                            <input type="text" name="number_inventory" class="form-control form-control-sm"
                                readonly value="<?php echo $next_id; ?>">
                        </div>

                        <!-- Fecha Ingreso -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Fecha Ingreso</label>
                            <input type="date" name="date_created" class="form-control form-control-sm" required
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Serie -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm alfanumerico" required>
                        </div>

                        <!-- Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Equipo</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>

                        <div class="row">
                            <!-- Marca -->
                            <div class="form-group col-md-3">
                                <label class="control-label">Marca</label>
                                <input type="text" name="brand" class="form-control form-control-sm">
                            </div>

                            <!-- Modelo -->
                            <div class="form-group col-md-3">
                                <label class="control-label">Modelo</label>
                                <input type="text" name="model" class="form-control form-control-sm" required>
                            </div>

                            <!-- Características -->
                            <div class="form-group col-md-6 float-left">
                                <label class="control-label">Características</label>
                                <textarea name="characteristics" class="form-control" style="height: 80px;"></textarea>
                            </div>
                        </div>
                        <!-- Imagen del equipo -->
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
                        <!-- Valor del Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Valor del Equipo</label>
                            <input type="text" name="amount" class="form-control form-control-sm solonumeros" required>
                        </div>

                        <!-- Categoría -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Categoría</label>
                            <input type="text" name="discipline" class="form-control form-control-sm" required>
                        </div>

                        <!-- Tipo de Adquisición -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Tipo de Adquisición</label>
                            <select name="acquisition_type" class="custom-select custom-select-sm select2" required>
                                <option value="1">Compra</option>
                                <option value="2">Renta</option>
                                <option value="3">Comodato</option>
                            </select>
                        </div>

                        <!-- Periodo de Mantenimiento -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Periodo de Manto</label>
                            <select name="mandate_period" class="custom-select custom-select-sm select2" required>
                                <option value="1">Semanal</option>
                                <option value="2">Catorcenal</option>
                                <option value="3">Mensual</option>
                            </select>
                        </div>

                        <!-- PROVEEDOR (NUEVO CAMPO) -->
                        <div class="form-group col-md-4 float-left">
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

                        <!-- Departamento -->
                        <div class="form-group col-md-4 float-left">
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

                        <!-- Ubicación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Ubicación</label>
                            <select name="location_id" id="location_id" class="form-control select2" required>
                                <option value="">Seleccionar</option>
                                <?php
                                $locations = $conn->query("SELECT id, name FROM equipment_locations ORDER BY name ASC");
                                while ($row = $locations->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>">
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <!-- Cargo Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Cargo Responsable</label>
                            <select name="responsible_position" class="custom-select custom-select-sm select2">
                                <option value="">Seleccionar</option>
                                <?php
                                $positions = $conn->query("SELECT * FROM equipment_responsible_positions ORDER BY name ASC");
                                while ($row = $positions->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo ucwords($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Nombre Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Nombre Responsable</label>
                            <input type="text" name="responsible_name" class="form-control form-control-sm" required>
                        </div>

                        <!-- Fecha -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha</label>
                            <input type="date" name="date" class="form-control form-control-sm" required>
                        </div>

                        <!-- Fecha Capacitación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha Capacitación</label>
                            <input type="date" name="date_training" class="form-control form-control-sm" required>
                        </div>

                    </div>
                </div>

                <!-- === SECCIÓN DOCUMENTOS CONTROL === -->
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <b class="text-muted">Documentos Control</b>
                        <div class="form-group col-md-12 float-left">
                            <label class="control-label">Factura Nro</label>
                            <input type="text" class="form-control form-control-sm alfanumerico" name="invoice" required>
                            <small id="msg"></small>
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Comodato</label>
                            <input type="file" class="file form-control form-control-sm" name="bailment_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Contrato M</label>
                            <input type="file" class="file form-control form-control-sm" name="contract_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Manual Usuario</label>
                            <input type="file" class="file form-control form-control-sm" name="usermanual_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Guía Rápida</label>
                            <input type="file" class="file form-control form-control-sm" name="fast_guide_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Ficha Técnica</label>
                            <input type="file" class="file form-control form-control-sm" name="datasheet_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Man. Servicios</label>
                            <input type="file" class="file form-control form-control-sm" name="servicemanual_file">
                        </div>
                    </div>
                </div>

                <!-- === SECCIÓN PRUEBAS DE RECEPCIÓN === -->
                <hr>
                <div class="row">
                    <div class="col-md-12 border-right">
                        <b class="text-muted">Pruebas de Recepción de Equipo</b>
                        <br><br>
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Acepto</label>
                            <input type="radio" name="state" value="1" checked>&nbsp;&nbsp;
                            <label class="control-label">Rechazo</label>
                            <input type="radio" name="state" value="0">
                        </div>
                        <div class="form-group col-md-9 float-left">
                            <label class="control-label">Notas</label><br>
                            <textarea name="comments" style="width: 100%; height: 120px;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- === SECCIÓN RESPONSABLE RESGUARDO === -->
                <hr>
                <div class="row">
                    <div class="col-md-12 border-left">
                        <b class="text-muted">Resguardo Equipo</b>
                        <br><br>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Tiempo de Garantía</label>
                            <input
                                type="number"
                                name="warranty_time"
                                class="form-control form-control-sm"
                                min="1"
                                placeholder="años"
                                required
                                oninput="validity.valid||(value='');"
                                title="Ingresa un número mayor a 0 (ej: 1, 2, 3 años)">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha de Adquisición</label>
                            <input type="date" name="date_adquisition" class="form-control form-control-sm" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="col-lg-12 text-right justify-content-center d-flex">
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
                if (resp == 1) {
                    alert_toast('Equipo guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=equipment_list', 1000);
                } else {
                    alert_toast('Error al guardar', 'danger');
                    end_load();
                }
            }
        });
    });
</script>