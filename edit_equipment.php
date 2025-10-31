<?php
include 'db_connect.php';

// === VALIDAR ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID inválido'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$equipment_id = $_GET['id'];

// === CARGAR DATOS DEL EQUIPO (PRINCIPAL) ===
$qry = $conn->query("SELECT * FROM equipments WHERE id = $equipment_id");
if ($qry->num_rows == 0) {
    echo "<script>alert('Equipo no encontrado'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$eq = $qry->fetch_assoc();

// === CARGAR TABLAS RELACIONADAS (con nombres únicos) ===
$reception = $delivery = $safeguard = $documents = [];

$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $reception = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $delivery = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $safeguard = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $documents = $qry->fetch_assoc();
?>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form action="" id="manage_equipment" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $equipment_id; ?>">

                <div class="row">
                    <div class="col-md-12 border-right">

                        <!-- NRO INVENTARIO (CORREGIDO) -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Nro Inventario</label>
                            <input type="text" name="number_inventory" class="form-control form-control-sm" 
                                   value="<?php echo $eq['number_inventory']; ?>" readonly>
                        </div>

                        <!-- FECHA INGRESO -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Fecha Ingreso</label>
                            <input type="date" name="date_created" class="form-control form-control-sm" required 
                                   value="<?php echo date('Y-m-d', strtotime($eq['date_created'])); ?>">
                        </div>

                        <!-- SERIE -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm alfanumerico" required 
                                   value="<?php echo $eq['serie']; ?>">
                        </div>

                        <!-- NOMBRE -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Equipo</label>
                            <input type="text" name="name" class="form-control form-control-sm" required 
                                   value="<?php echo $eq['name']; ?>">
                        </div>

                        <!-- MARCA -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Marca</label>
                            <input type="text" name="brand" class="form-control form-control-sm" 
                                   value="<?php echo $eq['brand']; ?>">
                        </div>

                        <!-- MODELO -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Modelo</label>
                            <input type="text" name="model" class="form-control form-control-sm" required 
                                   value="<?php echo $eq['model']; ?>">
                        </div>

                        <!-- CARACTERÍSTICAS -->
                        <div class="form-group col-md-6 float-left">
                            <label class="control-label">Características</label>
                            <textarea name="characteristics" class="form-control" style="height: 80px;"><?php echo $eq['characteristics']; ?></textarea>
                        </div>

                        <!-- VALOR -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Valor del Equipo</label>
                            <input type="text" name="amount" class="form-control form-control-sm solonumeros" required 
                                   value="<?php echo $eq['amount']; ?>">
                        </div>

                        <!-- CATEGORÍA -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Categoría</label>
                            <input type="text" name="discipline" class="form-control form-control-sm" required 
                                   value="<?php echo $eq['discipline']; ?>">
                        </div>

                        <!-- TIPO ADQUISICIÓN -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Tipo de Adquisición</label>
                            <select name="acquisition_type" class="custom-select custom-select-sm select2" required>
                                <option value="1" <?php echo ($eq['acquisition_type'] == 1) ? 'selected' : ''; ?>>Compra</option>
                                <option value="2" <?php echo ($eq['acquisition_type'] == 2) ? 'selected' : ''; ?>>Renta</option>
                                <option value="3" <?php echo ($eq['acquisition_type'] == 3) ? 'selected' : ''; ?>>Comodato</option>
                            </select>
                        </div>

                        <!-- PERIODO MANTENIMIENTO -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Periodo de Manto</label>
                            <select name="mandate_period" class="custom-select custom-select-sm select2" required>
                                <option value="1" <?php echo ($eq['mandate_period'] == 1) ? 'selected' : ''; ?>>Semanal</option>
                                <option value="2" <?php echo ($eq['mandate_period'] == 2) ? 'selected' : ''; ?>>Catorcenal</option>
                                <option value="3" <?php echo ($eq['mandate_period'] == 3) ? 'selected' : ''; ?>>Mensual</option>
                            </select>
                        </div>

                        <!-- PROVEEDOR (CORREGIDO: desde safeguard) -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Proveedor</label>
                            <select name="supplier_id" class="custom-select custom-select-sm select2" required>
                                <option value="">Seleccionar Proveedor</option>
                                <?php
                                $suppliers = $conn->query("SELECT id, empresa FROM suppliers WHERE estado = 1 ORDER BY empresa ASC");
                                while($s = $suppliers->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $s['id']; ?>" 
                                        <?php echo (isset($safeguard['supplier_id']) && $safeguard['supplier_id'] == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($s['empresa']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- DEPARTAMENTO -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Departamento</label>
                            <select name="department_id" class="custom-select custom-select-sm select2" required>
                                <?php
                                $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                                while($row = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                        <?php echo (isset($delivery['department_id']) && $delivery['department_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- UBICACIÓN -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Ubicación</label>
                            <select name="location_id" class="custom-select custom-select-sm select2" required>
                                <?php
                                $locations = $conn->query("SELECT * FROM equipment_locations ORDER BY name ASC");
                                while($row = $locations->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                        <?php echo (isset($delivery['location_id']) && $delivery['location_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- CARGO RESPONSABLE -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Cargo Responsable</label>
                            <select name="responsible_position" class="custom-select custom-select-sm select2">
                                <?php
                                $positions = $conn->query("SELECT * FROM equipment_responsible_positions ORDER BY name ASC");
                                while($row = $positions->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                        <?php echo (isset($delivery['responsible_position']) && $delivery['responsible_position'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- NOMBRE RESPONSABLE -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Nombre Responsable</label>
                            <input type="text" name="responsible_name" class="form-control form-control-sm" required 
                                   value="<?php echo $delivery['responsible_name'] ?? ''; ?>">
                        </div>

                        <!-- FECHA ENTREGA -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha Entrega</label>
                            <input type="date" name="date" class="form-control form-control-sm" required 
                                   value="<?php echo isset($delivery['date']) ? date('Y-m-d', strtotime($delivery['date'])) : ''; ?>">
                        </div>

                        <!-- FECHA CAPACITACIÓN -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha Capacitación</label>
                            <input type="date" name="date_training" class="form-control form-control-sm" required 
                                   value="<?php echo isset($delivery['date_training']) ? date('Y-m-d', strtotime($delivery['date_training'])) : ''; ?>">
                        </div>

                    </div>
                </div>

                <!-- === DOCUMENTOS CONTROL === -->
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <b class="text-muted">Documentos Control</b>
                        <div class="form-group col-md-12 float-left">
                            <label class="control-label">Factura Nro</label>
                            <input type="text" class="form-control form-control-sm alfanumerico" name="invoice" 
                                   value="<?php echo $documents['invoice'] ?? ''; ?>">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Comodato</label>
                            <?php if (!empty($documents['bailment_file'])): ?>
                                <a href="<?php echo $documents['bailment_file']; ?>" target="_blank">Ver</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="bailment_file">
                        </div>
                        <!-- REPITE PARA OTROS ARCHIVOS -->
                    </div>
                </div>

                <!-- === RECEPCIÓN === -->
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <b class="text-muted">Pruebas de Recepción</b>
                        <div class="form-group col-md-3 float-left">
                            <label><input type="radio" name="state" value="1" <?php echo ($reception['state'] ?? 0) == 1 ? 'checked' : ''; ?>> Acepto</label>&nbsp;
                            <label><input type="radio" name="state" value="0" <?php echo ($reception['state'] ?? 0) == 0 ? 'checked' : ''; ?>> Rechazo</label>
                        </div>
                        <div class="form-group col-md-9 float-left">
                            <label>Notas</label>
                            <textarea name="comments" class="form-control" style="height: 120px;"><?php echo $reception['comments'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- === RESGUARDO === -->
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <b class="text-muted">Responsable Resguardo</b>
                        <div class="form-group col-md-4 float-left">
                            <label>Razón Social</label>
                            <input type="text" name="business_name" class="form-control form-control-sm" required 
                                   value="<?php echo $safeguard['business_name'] ?? ''; ?>">
                        </div>
                        <!-- REPITE PARA phone, email, warranty_time, date_adquisition, rfc_id -->
                    </div>
                </div>

                <hr>
                <div class="col-lg-12 text-right d-flex justify-content-end">
                    <button class="btn btn-primary mr-2">Guardar Cambios</button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Solo números
    $('.solonumeros').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Alfanumérico
    $('.alfanumerico').on('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    // === GUARDAR CON AJAX ===
    $('#manage_equipment').submit(function(e) {
        e.preventDefault();
        start_load();

        $.ajax({
            url: 'ajax.php?action=save_equipment',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Equipo actualizado", 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php?page=equipment_list';
                    }, 1500);
                } else {
                    alert_toast("Error al guardar", 'danger');
                }
                end_load();
            }
        });
    });
</script>