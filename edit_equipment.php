<?php
include 'db_connect.php';

// Cargar datos del equipo
$qry = $conn->query("SELECT * FROM equipments WHERE id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}

// Cargar datos de otras tablas relacionadas
$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) $$k = $v;

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) $$k = $v;

$qry = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) $$k = $v;

$qry = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) $$k = $v;

// ID del equipo
$id = $_GET['id'];
?>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form action="" id="manage_customer" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="row">
                    <div class="col-md-12 border-right">

                        <!-- Nro Inventario -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Nro Inventario</label>
                            <input type="text" name="number_inventory" class="form-control form-control-sm" value="<?php echo $id ?>" readonly>
                        </div>

                        <!-- Fecha Ingreso -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Fecha Ingreso</label>
                            <input type="date" name="date_created" class="form-control form-control-sm" required 
                                   value="<?php echo isset($date_created) ? date('Y-m-d', strtotime($date_created)) : ''; ?>">
                        </div>

                        <!-- Serie -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm alfanumerico" required 
                                   value="<?php echo isset($serie) ? $serie : ''; ?>">
                        </div>

                        <!-- Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Equipo</label>
                            <input type="text" name="name" class="form-control form-control-sm" required 
                                   value="<?php echo isset($name) ? $name : ''; ?>">
                        </div>

                        <!-- Marca -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Marca</label>
                            <input type="text" name="brand" class="form-control form-control-sm" 
                                   value="<?php echo isset($brand) ? $brand : ''; ?>">
                        </div>

                        <!-- Modelo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Modelo</label>
                            <input type="text" name="model" class="form-control form-control-sm" required 
                                   value="<?php echo isset($model) ? $model : ''; ?>">
                        </div>

                        <!-- Características -->
                        <div class="form-group col-md-6 float-left">
                            <label class="control-label">Características</label>
                            <textarea name="characteristics" class="form-control" style="height: 80px;"><?php echo isset($characteristics) ? $characteristics : ''; ?></textarea>
                        </div>

                        <!-- Valor del Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Valor del Equipo</label>
                            <input type="text" name="amount" class="form-control form-control-sm solonumeros" required 
                                   value="<?php echo isset($amount) ? $amount : ''; ?>">
                        </div>

                        <!-- Categoría -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Categoría</label>
                            <input type="text" name="discipline" class="form-control form-control-sm" required 
                                   value="<?php echo isset($discipline) ? $discipline : ''; ?>">
                        </div>

                        <!-- Tipo de Adquisición -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Tipo de Adquisición</label>
                            <select name="acquisition_type" class="custom-select custom-select-sm select2" required>
                                <?php
                                $options1 = ['Compra', 'Renta', 'Comodato'];
                                $options = ['1', '2', '3'];
                                foreach($options as $key => $value):
                                ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo (isset($acquisition_type) && $acquisition_type == $value) ? 'selected' : ''; ?>>
                                    <?php echo $options1[$key]; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Periodo de Mantenimiento -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Periodo de Manto</label>
                            <select name="mandate_period" class="custom-select custom-select-sm select2" required>
                                <option value="1" <?php echo (isset($mandate_period) && $mandate_period == 1) ? 'selected' : ''; ?>>Semanal</option>
                                <option value="2" <?php echo (isset($mandate_period) && $mandate_period == 2) ? 'selected' : ''; ?>>Catorcenal</option>
                                <option value="3" <?php echo (isset($mandate_period) && $mandate_period == 3) ? 'selected' : ''; ?>>Mensual</option>
                            </select>
                        </div>

                        <!-- PROVEEDOR (NUEVO CAMPO) -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Proveedor</label>
                            <select name="supplier_id" class="custom-select custom-select-sm select2" required>
                                <option value="">Seleccionar Proveedor</option>
                                <?php
                                $suppliers = $conn->query("SELECT id, empresa FROM suppliers WHERE estado = 1 ORDER BY empresa ASC");
                                while($s = $suppliers->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $s['id']; ?>" 
                                        <?php echo (isset($supplier_id) && $supplier_id == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($s['empresa']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Departamento -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Departamento</label>
                            <select name="department_id" class="custom-select custom-select-sm select2" required>
                                <?php
                                $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                                while($row = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                        <?php echo (isset($department_id) && $department_id == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Ubicación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Ubicación</label>
                            <select name="location_id" class="custom-select custom-select-sm select2" required>
                                <?php
                                $locations = $conn->query("SELECT * FROM equipment_locations ORDER BY name ASC");
                                while($row = $locations->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                        <?php echo (isset($location_id) && $location_id == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Cargo Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Cargo Responsable</label>
                            <select name="responsible_position" class="custom-select custom-select-sm select2">
                                <?php
                                $positions = $conn->query("SELECT * FROM equipment_responsible_positions ORDER BY name ASC");
                                while($row = $positions->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" 
                                        <?php echo (isset($responsible_position) && $responsible_position == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Nombre Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Nombre Responsable</label>
                            <input type="text" name="responsible_name" class="form-control form-control-sm" required 
                                   value="<?php echo isset($responsible_name) ? $responsible_name : ''; ?>">
                        </div>

                        <!-- Fecha -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha</label>
                            <input type="date" name="date" class="form-control form-control-sm" required 
                                   value="<?php echo isset($date) ? date('Y-m-d', strtotime($date)) : ''; ?>">
                        </div>

                        <!-- Fecha Capacitación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha Capacitación</label>
                            <input type="date" name="date_training" class="form-control form-control-sm" required 
                                   value="<?php echo isset($date_training) ? date('Y-m-d', strtotime($date_training)) : ''; ?>">
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
                            <input type="text" class="form-control form-control-sm alfanumerico" name="invoice" required 
                                   value="<?php echo isset($invoice) ? $invoice : ''; ?>">
                            <small id="msg"></small>
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Comodato</label>
                            <?php if ($bailment_file): ?>
                                / <a href="<?php echo $bailment_file ?>" target="_blank">Ver Archivo</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="bailment_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Contrato M</label>
                            <?php if ($contract_file): ?>
                                / <a href="<?php echo $contract_file ?>" target="_blank">Ver Archivo</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="contract_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Manual Usuario</label>
                            <?php if ($usermanual_file): ?>
                                / <a href="<?php echo $usermanual_file ?>" target="_blank">Ver Archivo</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="usermanual_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Guía Rápida</label>
                            <?php if ($fast_guide_file): ?>
                                / <a href="<?php echo $fast_guide_file ?>" target="_blank">Ver Archivo</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="fast_guide_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Ficha Técnica</label>
                            <?php if ($datasheet_file): ?>
                                / <a href="<?php echo $datasheet_file ?>" target="_blank">Ver Archivo</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="datasheet_file">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Man. Servicios</label>
                            <?php if ($servicemanual_file): ?>
                                / <a href="<?php echo $servicemanual_file ?>" target="_blank">Ver Archivo</a>
                            <?php endif ?>
                            <input type="file" class="form-control form-control-sm" name="servicemanual_file">
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
                            <input type="radio" name="state" value="1" <?php echo ($state == 1) ? 'checked' : ''; ?>>&nbsp;&nbsp;
                            <label class="control-label">Rechazo</label>
                            <input type="radio" name="state" value="0" <?php echo ($state == 0) ? 'checked' : ''; ?>>
                        </div>
                        <div class="form-group col-md-9 float-left">
                            <label class="control-label">Notas</label>
                            <textarea name="comments" class="form-control" style="height: 120px;"><?php echo isset($comments) ? $comments : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- === SECCIÓN RESPONSABLE RESGUARDO === -->
                <hr>
                <div class="row">
                    <div class="col-md-12 border-left">
                        <b class="text-muted">Responsable Resguardo Equipo</b>
                        <br><br>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Razón Social</label>
                            <input type="text" name="business_name" class="form-control form-control-sm" required 
                                   value="<?php echo isset($business_name) ? $business_name : ''; ?>">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control form-control-sm" required 
                                   value="<?php echo isset($phone) ? $phone : ''; ?>">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Email</label>
                            <input type="text" name="email" class="form-control form-control-sm" required 
                                   value="<?php echo isset($email) ? $email : ''; ?>">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Tiempo de Garantía</label>
                            <input type="number" name="warranty_time" class="form-control form-control-sm" required 
                                   value="<?php echo isset($warranty_time) ? $warranty_time : ''; ?>">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha de Adquisición</label>
                            <input type="date" name="date_adquisition" class="form-control form-control-sm" required 
                                   value="<?php echo isset($date_adquisition) ? date('Y-m-d', strtotime($date_adquisition)) : ''; ?>">
                        </div>
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">RFC</label>
                            <select name="rfc_id" class="custom-select custom-select-sm select2">
                                <option value="1" <?php echo ($rfc_id == 1) ? 'selected' : ''; ?>>RFC 1</option>
                                <option value="2" <?php echo ($rfc_id == 2) ? 'selected' : ''; ?>>RFC 2</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="col-lg-12 text-right justify-content-center d-flex">
                    <button class="btn btn-primary mr-2">Guardar Cambios</button>
                    <button class="btn btn-secondary" type="reset">Resetear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $('.solonumeros').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $('.alfanumerico').on('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    $('#manage_customer').submit(function(e) {
