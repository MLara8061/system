<?php
include 'db_connect.php';

// === VALIDAR ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID inválido'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$equipment_id = $_GET['id'];

// === CARGAR DATOS DEL EQUIPO ===
$qry = $conn->query("SELECT * FROM equipments WHERE id = $equipment_id");
if ($qry->num_rows == 0) {
    echo "<script>alert('Equipo no encontrado'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$eq = $qry->fetch_assoc();

// === CARGAR TABLAS RELACIONADAS ===
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
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="" id="manage_equipment" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $equipment_id; ?>">
                <input type="hidden" name="delete_image" value="0" id="delete_image_flag">

                <div class="row">
                    <div class="col-md-12 border-right">

                        <!-- Nro Inventario -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Nro Inventario</label>
                            <input type="text" name="number_inventory" class="form-control form-control-sm"
                                readonly value="<?php echo $eq['number_inventory']; ?>">
                        </div>

                        <!-- Fecha Ingreso -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Fecha Ingreso</label>
                            <input type="date" name="date_created" class="form-control form-control-sm" required
                                value="<?php echo date('Y-m-d', strtotime($eq['date_created'])); ?>">
                        </div>

                        <!-- Serie -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm alfanumerico" required
                                value="<?php echo $eq['serie']; ?>">
                        </div>

                        <!-- Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Equipo</label>
                            <input type="text" name="name" class="form-control form-control-sm" required
                                value="<?php echo $eq['name']; ?>">
                        </div>

                        <div class="row">
                            <!-- Marca -->
                            <div class="form-group col-md-3">
                                <label class="control-label">Marca</label>
                                <input type="text" name="brand" class="form-control form-control-sm"
                                    value="<?php echo $eq['brand']; ?>">
                            </div>

                            <!-- Modelo -->
                            <div class="form-group col-md-3">
                                <label class="control-label">Modelo</label>
                                <input type="text" name="model" class="form-control form-control-sm" required
                                    value="<?php echo $eq['model']; ?>">
                            </div>

                            <!-- Características -->
                            <div class="form-group col-md-6 float-left">
                                <label class="control-label">Características</label>
                                <textarea name="characteristics" class="form-control" style="height: 80px;"><?php echo $eq['characteristics']; ?></textarea>
                            </div>
                        </div>

                        <!-- Imagen del equipo -->
                        <div class="form-group col-md-6">
                            <label class="control-label">Imagen del Equipo</label>
                            <input type="file" name="equipment_image" class="form-control form-control-sm" accept="image/*">
                            <?php if (!empty($eq['image'])): ?>
                                <div class="mt-2" id="current-image">
                                    <img src="<?php echo $eq['image']; ?>" class="img-thumbnail" style="max-height: 120px;">
                                    <br>
                                    <a href="javascript:void(0)" class="text-danger delete-image">
                                        <i class="fas fa-trash"></i> Eliminar imagen
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Valor del Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Valor del Equipo</label>
                            <input type="text" name="amount" class="form-control form-control-sm solonumeros" required
                                value="<?php echo $eq['amount']; ?>">
                        </div>

                        <!-- Categoría -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Categoría</label>
                            <input type="text" name="discipline" class="form-control form-control-sm" required
                                value="<?php echo $eq['discipline']; ?>">
                        </div>

                        <!-- Tipo de Adquisición -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Tipo de Adquisición</label>
                            <select name="acquisition_type" class="custom-select custom-select-sm select2" required>
                                <option value="1" <?php echo ($eq['acquisition_type'] == 1) ? 'selected' : ''; ?>>Compra</option>
                                <option value="2" <?php echo ($eq['acquisition_type'] == 2) ? 'selected' : ''; ?>>Renta</option>
                                <option value="3" <?php echo ($eq['acquisition_type'] == 3) ? 'selected' : ''; ?>>Comodato</option>
                            </select>
                        </div>

                        <!-- Periodo de Mantenimiento -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Periodo de Manto</label>
                            <select name="mandate_period" class="custom-select custom-select-sm select2" required>
                                <option value="1" <?php echo ($eq['mandate_period'] == 1) ? 'selected' : ''; ?>>Semanal</option>
                                <option value="2" <?php echo ($eq['mandate_period'] == 2) ? 'selected' : ''; ?>>Catorcenal</option>
                                <option value="3" <?php echo ($eq['mandate_period'] == 3) ? 'selected' : ''; ?>>Mensual</option>
                            </select>
                        </div>

                        <!-- PROVEEDOR -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Proveedor</label>
                            <select name="supplier_id" class="custom-select custom-select-sm select2" required>
                                <option value="">Seleccionar Proveedor</option>
                                <?php
                                $suppliers = $conn->query("SELECT id, empresa FROM suppliers WHERE estado = 1 ORDER BY empresa ASC");
                                while ($row = $suppliers->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo ($eq['supplier_id'] == $row['id']) ? 'selected' : ''; ?>>
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
                            <select name="location_id" id="location_id" class="form-control select2" required>
                                <option value="">Seleccionar</option>
                                <?php
                                $locations = $conn->query("SELECT id, name FROM equipment_locations ORDER BY name ASC");
                                while ($row = $locations->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo (isset($delivery['location_id']) && $delivery['location_id'] == $row['id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo (isset($delivery['responsible_position']) && $delivery['responsible_position'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Nombre Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Nombre Responsable</label>
                            <input type="text" name="responsible_name" class="form-control form-control-sm" required
                                value="<?php echo $delivery['responsible_name'] ?? ''; ?>">
                        </div>

                        <!-- Fecha Entrega -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha</label>
                            <input type="date" name="date" class="form-control form-control-sm" required
                                value="<?php echo isset($delivery['date']) ? date('Y-m-d', strtotime($delivery['date'])) : ''; ?>">
                        </div>

                        <!-- Fecha Capacitación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha Capacitación</label>
                            <input type="date" name="date_training" class="form-control form-control-sm" required
                                value="<?php echo isset($delivery['date_training']) ? date('Y-m-d', strtotime($delivery['date_training'])) : ''; ?>">
                        </div>

                    </div>
                </div>

                <!-- === SECCIÓN DOCUMENTOS CONTROL === -->
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <b class="text-muted">Documentos Control</b>
                        <div class="form-group col-md-12 float-left">
                            Factura Nro
                            <input type="text" class="form-control form-control-sm alfanumerico" name="invoice" required
                                value="<?php echo $documents['invoice'] ?? ''; ?>">
                            <small id="msg"></small>
                        </div>
                        <div class="form-group col-md-4 float-left">
                            Comodato
                            <?php if (!empty($documents['bailment_file'])): ?>
                                <a href="<?php echo $documents['bailment_file']; ?>" target="_blank" class="d-block mb-1 text-primary">Ver actual</a>
                            <?php endif ?>
                            <input type="file" class="file form-control form-control-sm" name="bailment_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            Contrato M
                            <?php if (!empty($documents['contract_file'])): ?>
                                <a href="<?php echo $documents['contract_file']; ?>" target="_blank" class="d-block mb-1 text-primary">Ver actual</a>
                            <?php endif ?>
                            <input type="file" class="file form-control form-control-sm" name="contract_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            Manual Usuario
                            <?php if (!empty($documents['usermanual_file'])): ?>
                                <a href="<?php echo $documents['usermanual_file']; ?>" target="_blank" class="d-block mb-1 text-primary">Ver actual</a>
                            <?php endif ?>
                            <input type="file" class="file form-control form-control-sm" name="usermanual_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            Guía Rápida
                            <?php if (!empty($documents['fast_guide_file'])): ?>
                                <a href="<?php echo $documents['fast_guide_file']; ?>" target="_blank" class="d-block mb-1 text-primary">Ver actual</a>
                            <?php endif ?>
                            <input type="file" class="file form-control form-control-sm" name="fast_guide_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            Ficha Técnica
                            <?php if (!empty($documents['datasheet_file'])): ?>
                                <a href="<?php echo $documents['datasheet_file']; ?>" target="_blank" class="d-block mb-1 text-primary">Ver actual</a>
                            <?php endif ?>
                            <input type="file" class="file form-control form-control-sm" name="datasheet_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            Man. Servicios
                            <?php if (!empty($documents['servicemanual_file'])): ?>
                                <a href="<?php echo $documents['servicemanual_file']; ?>" target="_blank" class="d-block mb-1 text-primary">Ver actual</a>
                            <?php endif ?>
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
                            <input type="radio" name="state" value="1" <?php echo ($reception['state'] ?? 0) == 1 ? 'checked' : ''; ?>>&nbsp;&nbsp;
                            <label class="control-label">Rechazo</label>
                            <input type="radio" name="state" value="0" <?php echo ($reception['state'] ?? 0) == 0 ? 'checked' : ''; ?>>
                        </div>
                        <div class="form-group col-md-9 float-left">
                            <label class="control-label">Notas</label><br>
                            <textarea name="comments" style="width: 100%; height: 120px;"><?php echo $reception['comments'] ?? ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- === SECCIÓN RESGUARDO === -->
                <hr>
                <div class="row">
                    <div class="col-md-12 border-left">
                        <b class="text-muted">Resguardo Equipo</b>
                        <br><br>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Tiempo de Garantía</label>
                            <input type="number" name="warranty_time" class="form-control form-control-sm" min="1" placeholder="años" required
                                value="<?php echo $safeguard['warranty_time'] ?? ''; ?>"
                                oninput="validity.valid||(value='');"
                                title="Ingresa un número mayor a 0 (ej: 1, 2, 3 años)">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha de Adquisición</label>
                            <input type="date" name="date_adquisition" class="form-control form-control-sm" required
                                value="<?php echo isset($safeguard['date_adquisition']) ? date('Y-m-d', strtotime($safeguard['date_adquisition'])) : ''; ?>">
                        </div>
                    </div>
                </div>

                <hr>
                <div class="col-lg-12 text-right justify-content-center d-flex">
                    <button class="btn btn-primary mr-2">Guardar Cambios</button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // === VALIDAR CAMPOS ===
    $('.solonumeros').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $('.alfanumerico').on('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    // === INICIALIZAR SELECT2 Y CARGAR VALORES ===
    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: "Seleccionar",
            allowClear: true
        });

        // Forzar ubicación
        var loc_id = '<?php echo $eq["location_id"] ?? ""; ?>';
        if (loc_id) {
            $('#location_id').val(loc_id).trigger('change.select2');
        }

        // Forzar proveedor
        var sup_id = '<?php echo $eq["supplier_id"] ?? ""; ?>';
        if (sup_id) {
            $('select[name="supplier_id"]').val(sup_id).trigger('change.select2');
        }
    });

    // === ELIMINAR IMAGEN ===
    $(document).on('click', '.delete-image', function(e) {
        e.preventDefault();
        if (confirm('¿Eliminar imagen del equipo?')) {
            $('#current-image').remove();
            $('#delete_image_flag').val('1');
        }
    });

    // === GUARDAR FORMULARIO ===
    $('#manage_equipment').submit(function(e) {
        e.preventDefault();
        start_load();

        var formData = new FormData(this);

        $.ajax({
            url: 'ajax.php?action=save_equipment',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                end_load();
                resp = resp.trim();
                if (resp === '1') {
                    alert_toast('Equipo actualizado', 'success');
                    setTimeout(() => location.href = 'index.php?page=equipment_list', 1000);
                } else {
                    alert_toast('Error: ' + resp, 'danger');
                }
            },
            error: function() {
                end_load();
                alert_toast('Error de conexión', 'danger');
            }
        });
    });
</script>