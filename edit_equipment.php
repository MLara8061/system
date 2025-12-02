<?php
require_once 'config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID inválido'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$equipment_id = $_GET['id'];

$qry = $conn->query("SELECT * FROM equipments WHERE id = $equipment_id");
if ($qry->num_rows == 0) {
    echo "<script>alert('Equipo no encontrado'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$eq = $qry->fetch_assoc();

// Cargar relaciones
$reception = $delivery = $safeguard = $documents = $power_spec = [];
$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $reception = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $delivery = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $safeguard = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $documents = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_power_specs WHERE equipment_id = $equipment_id ORDER BY id DESC LIMIT 1");
if ($qry->num_rows > 0) $power_spec = $qry->fetch_assoc();
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <!-- === FICHA TÉCNICA: IMAGEN + INFO === -->
            <div class="row g-0">
                <!-- IMAGEN -->
                <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                    <div class="text-center w-100 position-relative" style="min-height: 420px;">
                        <?php if (!empty($eq['image']) && file_exists($eq['image'])): ?>
                            <div class="position-relative d-inline-block">
                                <img src="<?= $eq['image'] ?>" 
                                     class="img-fluid rounded shadow" 
                                     style="max-height: 380px; object-fit: contain;" 
                                     id="equipment-preview">
                                <button type="button" 
                                        class="btn btn-danger btn-sm position-absolute" 
                                        style="top: 10px; right: 10px; z-index: 10;" 
                                        id="remove-equipment-image">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <br>
                            <small class="text-muted">Haz clic para eliminar</small>
                        <?php else: ?>
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center" 
                                 style="height: 380px; border: 3px dashed #ccc;" id="empty-equipment-image">
                                <i class="fas fa-camera fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div id="upload-equipment-container" class="mt-3"
                             style="display: <?= (!empty($eq['image']) && file_exists($eq['image'])) ? 'none' : 'block' ?>;">
                            <input type="file" name="equipment_image" id="equipment_image" 
                                   class="form-control" accept="image/jpeg,image/png,image/jpg" 
                                   form="manage_equipment" onchange="previewEquipmentImg(this)">
                            <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                            <img id="equipment-preview-new" src="" alt="" 
                                 class="img-fluid rounded shadow mt-2"
                                 style="display:none; max-height: 200px;">
                        </div>
                    </div>
                </div>

                <!-- INFORMACIÓN CLAVE -->
                <div class="col-lg-7 p-5">
                    <form id="manage_equipment" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $equipment_id ?>">
                        <input type="hidden" name="delete_image" value="0" id="delete_image_flag">

                        <!-- NOMBRE + #INVENTARIO -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <input type="text" name="name" class="form-control" 
                                       required value="<?= $eq['name'] ?>" placeholder="Nombre del equipo">
                            </div>
                            <div class="col-md-4">
                                <span class="badge badge-primary font-weight-bold p-2" style="font-size: 1.1rem;">
                                    #<?= $eq['number_inventory'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- MARCA Y MODELO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" name="brand" class="form-control" 
                                       value="<?= $eq['brand'] ?>" placeholder="Marca">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="model" class="form-control" 
                                       required value="<?= $eq['model'] ?>" placeholder="Modelo">
                            </div>
                        </div>

                        <!-- SERIE (EDITABLE) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Serie</label>
                                <input type="text" name="serie" class="form-control alfanumerico" 
                                       required value="<?= $eq['serie'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha Ingreso</label>
                                <input type="date" name="date_created" class="form-control" 
                                       required value="<?= !empty($eq['date_created']) && $eq['date_created'] != '0000-00-00' ? date('Y-m-d', strtotime($eq['date_created'])) : '' ?>">
                            </div>
                        </div>

                        <!-- VALOR Y CATEGORÍA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Valor</label>
                                <input type="text" name="amount" class="form-control solonumeros" 
                                       required value="<?= $eq['amount'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Categoría</label>
                                <input type="text" name="discipline" class="form-control" 
                                       required value="<?= $eq['discipline'] ?>">
                            </div>
                        </div>

                        <!-- CONSUMO ELÉCTRICO CON ETIQUETAS -->
                        <div class="bg-light p-3 rounded mb-3">
                            <h6 class="mb-3 text-dark">Consumo Eléctrico</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="small text-muted">Voltaje (V)</label>
                                    <input type="number" step="0.01" min="0" name="voltage" class="form-control form-control-sm" 
                                           value="<?= $power_spec['voltage'] ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted">Amperaje (A)</label>
                                    <input type="number" step="0.01" min="0" name="amperage" class="form-control form-control-sm" 
                                           value="<?= $power_spec['amperage'] ?? '' ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted">Frecuencia (Hz)</label>
                                    <input type="number" step="0.01" min="0" name="frequency_hz" class="form-control form-control-sm" 
                                           value="<?= $power_spec['frequency_hz'] ?? '60.00' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- PROVEEDOR -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Proveedor</label>
                            <select name="supplier_id" class="custom-select select2" required>
                                <option value="">Seleccionar</option>
                                <?php
                                $suppliers = $conn->query("SELECT id,empresa FROM suppliers WHERE estado=1 ORDER BY empresa ASC");
                                while ($row = $suppliers->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>" <?= $eq['supplier_id'] == $row['id'] ? 'selected' : '' ?>>
                                        <?= ucwords($row['empresa']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- ADQUISICIÓN -->
                        <div class="row">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Tipo Adquisición</label>
                                <select name="acquisition_type" class="custom-select select2" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $types = $conn->query("SELECT id,name FROM acquisition_type ORDER BY name ASC");
                                    while ($row = $types->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>" <?= $eq['acquisition_type'] == $row['id'] ? 'selected' : '' ?>>
                                            <?= ucwords($row['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Periodo Mantenimiento</label>
                                <select name="mandate_period_id" class="custom-select select2" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $periods = $conn->query("SELECT id,name FROM maintenance_periods ORDER BY id ASC");
                                    while ($row = $periods->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>" <?= $eq['mandate_period_id'] == $row['id'] ? 'selected' : '' ?>>
                                            <?= ucwords($row['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- === SECCIONES INFERIORES === -->
            <div class="p-5 bg-white">

                <!-- ENTREGA -->
                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">Entrega del Equipo</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Departamento</label>
                                <select name="department_id" id="department_id" class="custom-select select2" form="manage_equipment" required>
                                    <option value="">Seleccionar departamento</option>
                                    <?php
                                    $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                                    while ($row = $departments->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>" <?= ($delivery['department_id'] ?? '') == $row['id'] ? 'selected' : '' ?>>
                                            <?= ucwords($row['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Ubicación</label>
                                <select name="location_id" id="location_id" class="custom-select select2" form="manage_equipment" required>
                                    <option value="">Seleccionar ubicación</option>
                                    <?php
                                    // Cargar ubicaciones del departamento seleccionado
                                    $current_department = $delivery['department_id'] ?? '';
                                    $current_location = $delivery['location_id'] ?? '';
                                    if($current_department){
                                        $locations = $conn->query("SELECT id, name FROM locations WHERE department_id = $current_department ORDER BY name ASC");
                                        while ($row = $locations->fetch_assoc()): ?>
                                            <option value="<?= $row['id'] ?>" <?= $current_location == $row['id'] ? 'selected' : '' ?>>
                                                <?= ucwords($row['name']) ?>
                                            </option>
                                        <?php endwhile;
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Cargo Responsable</label>
                                <select name="responsible_position" id="responsible_position" class="custom-select select2" form="manage_equipment">
                                    <option value="">Seleccionar cargo</option>
                                    <?php
                                    // Cargar cargos si ya hay ubicación seleccionada
                                    $current_position = $delivery['responsible_position'] ?? '';
                                    if($current_location){
                                        // Intentar primero con la nueva estructura (location_id en job_positions)
                                        $positions = $conn->query("SELECT id, name FROM job_positions WHERE location_id = $current_location ORDER BY name ASC");
                                        
                                        // Si no hay resultados, intentar con la tabla de relaciones
                                        if($positions->num_rows == 0){
                                            $positions = $conn->query("SELECT j.id, j.name 
                                                                      FROM job_positions j 
                                                                      INNER JOIN location_positions lp ON lp.job_position_id = j.id 
                                                                      WHERE lp.location_id = $current_location 
                                                                      ORDER BY j.name ASC");
                                        }
                                        
                                        while ($row = $positions->fetch_assoc()): ?>
                                            <option value="<?= $row['id'] ?>" <?= $current_position == $row['id'] ? 'selected' : '' ?>>
                                                <?= ucwords($row['name']) ?>
                                            </option>
                                        <?php endwhile;
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label>Nombre Responsable</label>
                                <input type="text" name="responsible_name" class="form-control" form="manage_equipment" required value="<?= $delivery['responsible_name'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label>Fecha Capacitación</label>
                                <input type="date" name="date_training" class="form-control" form="manage_equipment" required 
                                       value="<?= isset($delivery['date_training']) && !empty($delivery['date_training']) && $delivery['date_training'] != '0000-00-00' ? date('Y-m-d', strtotime($delivery['date_training'])) : '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARACTERÍSTICAS -->
                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">Características Técnicas</h6>
                    </div>
                    <div class="card-body">
                        <textarea name="characteristics" class="form-control" rows="3" form="manage_equipment"><?= $eq['characteristics'] ?></textarea>
                    </div>
                </div>

                <!-- DOCUMENTOS -->
                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">Documentos de Control</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Factura Nro</label>
                                <input type="text" name="invoice" class="form-control" form="manage_equipment" value="<?= $documents['invoice'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <?php
                            $doc_fields = [
                                'bailment_file' => 'Comodato',
                                'contract_file' => 'Contrato M',
                                'usermanual_file' => 'Manual Usuario',
                                'fast_guide_file' => 'Guía Rápida',
                                'datasheet_file' => 'Ficha Técnica',
                                'servicemanual_file' => 'Man. Servicios'
                            ];
                            foreach ($doc_fields as $field => $label):
                                $file_path = $documents[$field] ?? '';
                                $filename = basename($file_path);
                            ?>
                                <div class="col-md-4 mb-3">
                                    <label><?= $label ?></label>
                                    <?php if ($file_path && file_exists($file_path)): ?>
                                        <div class="border p-2 rounded bg-light mb-2 d-flex justify-content-between align-items-center">
                                            <small class="text-truncate" style="max-width: 150px;" title="<?= $filename ?>">
                                                <?= $filename ?>
                                            </small>
                                            <div>
                                                <a href="<?= $file_path ?>" target="_blank" class="text-primary mr-2" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="javascript:void(0)" class="text-danger delete-doc" data-field="<?= $field ?>" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <input type="hidden" name="delete_<?= $field ?>" value="0" class="delete-flag" form="manage_equipment">
                                        <!-- Input oculto hasta eliminar -->
                                        <div class="upload-doc-container" style="display:none;">
                                            <input type="file" name="<?= $field ?>" class="form-control form-control-sm mt-1" form="manage_equipment">
                                        </div>
                                    <?php else: ?>
                                        <input type="file" name="<?= $field ?>" class="form-control form-control-sm" form="manage_equipment">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- RECEPCIÓN Y RESGUARDO -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0"><h6 class="text-dark">Recepción</h6></div>
                            <div class="card-body">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="state" value="1" form="manage_equipment" <?= ($reception['state'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label">Acepto</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="state" value="0" form="manage_equipment" <?= ($reception['state'] ?? 0) == 0 ? 'checked' : '' ?>>
                                    <label class="form-check-label">Rechazo</label>
                                </div>
                                <textarea name="comments" class="form-control mt-2" rows="2" form="manage_equipment"><?= $reception['comments'] ?? '' ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0"><h6 class="text-dark">Resguardo</h6></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Garantía (Años)</label>
                                        <input type="number" name="warranty_time" class="form-control" min="1" form="manage_equipment" value="<?= $safeguard['warranty_time'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Fecha Adquisición</label>
                                        <input type="date" name="date_adquisition" class="form-control" form="manage_equipment" 
                                               value="<?= isset($safeguard['date_adquisition']) && !empty($safeguard['date_adquisition']) && $safeguard['date_adquisition'] != '0000-00-00' ? date('Y-m-d', strtotime($safeguard['date_adquisition'])) : '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HISTORIAL DE MANTENIMIENTOS -->
                <?php
                // Consultar historial de mantenimientos del equipo
                $maintenance_query = $conn->query("
                    SELECT * 
                    FROM maintenance_reports
                    WHERE equipment_id = {$equipment_id}
                    ORDER BY report_date DESC, report_time DESC
                ");
                ?>
                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">
                            <i class="fas fa-tools mr-2"></i>Historial de Mantenimientos
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($maintenance_query && $maintenance_query->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table id="maintenanceTable" class="table table-striped table-hover">
                                <thead class="bg-secondary">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Técnico</th>
                                        <th>Validado por</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($maint = $maintenance_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($maint['report_date'])) ?></td>
                                        <td><?= date('H:i', strtotime($maint['report_time'])) ?></td>
                                        <td>
                                            <?php if (!empty($maint['engineer_name'])): ?>
                                                <?= htmlspecialchars($maint['engineer_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">No asignado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($maint['received_by'])): ?>
                                                <?= htmlspecialchars($maint['received_by']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">No validado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($maint['execution_type'] == 'Correctivo'): ?>
                                                <span class="badge badge-danger">Correctivo</span>
                                            <?php elseif ($maint['execution_type'] == 'Preventivo'): ?>
                                                <span class="badge badge-success">Preventivo</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= $maint['execution_type'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="report_pdf.php?id=<?= $maint['id'] ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info"
                                               title="Descargar Reporte PDF">
                                                <i class="fas fa-file-pdf mr-1"></i>PDF
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            No hay registros de mantenimiento para este equipo.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>
                <div class="text-center btn-container-mobile">
                    <button type="submit" form="manage_equipment" class="btn btn-primary btn-lg px-5">Guardar Cambios</button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary btn-lg px-5">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-dashed { border-style: dashed !important; }
    .form-control, .custom-select { border-radius: 10px; }
    .text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .badge { font-size: 1.1rem; }
</style>

<script>
    $('.solonumeros').on('input', function(){ this.value = this.value.replace(/[^0-9]/g,''); });
    $('.alfanumerico').on('input', function(){ this.value = this.value.replace(/[^a-zA-Z0-9]/g,''); });

    // Previsualizar imagen de equipo
    function previewEquipmentImg(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#equipment-preview-new').attr('src', e.target.result).show();
                $('#empty-equipment-image').hide();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Validar formato de imagen
    $('#equipment_image').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG y PNG', 'error');
                $(this).val('');
                $('#equipment-preview-new').hide();
                return false;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert_toast('La imagen es muy grande. Máximo 5MB', 'error');
                $(this).val('');
                $('#equipment-preview-new').hide();
                return false;
            }
        }
    });

    $(function(){
        // Inicializar Select2
        $('.select2').select2({ 
            width: '100%', 
            placeholder: 'Seleccionar', 
            allowClear: true,
            dropdownAutoWidth: true,
            maximumInputLength: 0
        });
        
        // CASCADA 1: Cargar ubicaciones cuando se selecciona un departamento
        // Usar 'select2:select' en lugar de 'change' para mejor compatibilidad con Select2
        $('#department_id').on('select2:select change', function(){
            var department_id = $(this).val();
            console.log('Departamento seleccionado:', department_id);
            var $locationSelect = $('#location_id');
            var $positionSelect = $('#responsible_position');
            
            // Limpiar y deshabilitar los selectores dependientes
            $locationSelect.empty().append('<option value="">Cargando...</option>').prop('disabled', true);
            $positionSelect.empty().append('<option value="">Seleccionar ubicación primero</option>').prop('disabled', true);
            
            if(department_id){
                $.ajax({
                    url: 'ajax.php?action=get_locations_by_department',
                    method: 'POST',
                    data: { department_id: department_id },
                    dataType: 'text', // Cambiar a text primero para ver la respuesta cruda
                    success: function(response){
                        console.log('Respuesta cruda del servidor:', response);
                        try {
                            var locations = JSON.parse(response);
                            console.log('Ubicaciones recibidas:', locations);
                        $locationSelect.empty().append('<option value="">Seleccionar ubicación</option>');
                        if(locations.length > 0){
                            $.each(locations, function(index, location){
                                $locationSelect.append('<option value="'+ location.id +'">'+ location.name.toUpperCase() +'</option>');
                            });
                            $locationSelect.prop('disabled', false);
                        } else {
                            $locationSelect.append('<option value="">No hay ubicaciones en este departamento</option>');
                        }
                        // Destruir y reinicializar Select2
                        $locationSelect.select2('destroy').select2({
                            width: '100%',
                            placeholder: 'Seleccionar ubicación',
                            allowClear: true
                        });
                        } catch(e) {
                            console.error('Error parseando JSON:', e);
                            console.error('Respuesta del servidor:', response);
                            $locationSelect.empty().append('<option value="">Error al cargar ubicaciones</option>');
                        }
                    },
                    error: function(xhr, status, error){
                        console.error('Error al cargar ubicaciones:', error);
                        $locationSelect.empty().append('<option value="">Error al cargar ubicaciones</option>');
                    }
                });
            } else {
                $locationSelect.empty().append('<option value="">Seleccionar departamento primero</option>');
            }
        });
        
        // CASCADA 2: Cargar cargos cuando se selecciona una ubicación
        // Usar 'select2:select' en lugar de 'change' para mejor compatibilidad con Select2
        $('#location_id').on('select2:select change', function(){
            var location_id = $(this).val();
            var $responsiblePosition = $('#responsible_position');
            
            // Limpiar y deshabilitar select de cargo
            $responsiblePosition.empty().append('<option value="">Cargando...</option>').prop('disabled', true);
            
            if(location_id){
                $.ajax({
                    url: 'ajax.php?action=get_job_positions_by_location',
                    method: 'POST',
                    data: { location_id: location_id },
                    dataType: 'json',
                    success: function(positions){
                        $responsiblePosition.empty().append('<option value="">Seleccionar cargo</option>');
                        if(positions.length > 0){
                            $.each(positions, function(index, position){
                                $responsiblePosition.append('<option value="'+ position.id +'">'+ position.name.toUpperCase() +'</option>');
                            });
                            $responsiblePosition.prop('disabled', false);
                        } else {
                            $responsiblePosition.append('<option value="">No hay cargos para esta ubicación</option>');
                        }
                        // Destruir y reinicializar Select2
                        $responsiblePosition.select2('destroy').select2({
                            width: '100%',
                            placeholder: 'Seleccionar cargo',
                            allowClear: true
                        });
                    },
                    error: function(){
                        $responsiblePosition.empty().append('<option value="">Error al cargar cargos</option>');
                    }
                });
            } else {
                $responsiblePosition.empty().append('<option value="">Seleccionar ubicación primero</option>');
            }
        });
        
        // Eliminar imagen de equipo
        $('#remove-equipment-image').click(function() {
            if (confirm('¿Eliminar imagen actual?')) {
                $('#equipment-preview').parent().remove();
                $(this).remove();
                $('#empty-equipment-image').remove();
                $('#upload-equipment-container').show();
                $('#delete_image_flag').val('1');
            }
        });
        
        // Inicializar DataTable para historial de mantenimientos
        if ($('#maintenanceTable').length) {
            $('#maintenanceTable').DataTable({
                language: {
                    sProcessing: "Procesando...",
                    sLengthMenu: "Mostrar _MENU_ registros",
                    sZeroRecords: "No se encontraron resultados",
                    sEmptyTable: "Ningún dato disponible en esta tabla",
                    sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
                    sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
                    sSearch: "Buscar:",
                    oPaginate: {
                        sFirst: "Primero",
                        sLast: "Último",
                        sNext: "Siguiente",
                        sPrevious: "Anterior"
                    }
                },
                order: [[0, 'desc'], [1, 'desc']],
                pageLength: 10,
                responsive: true,
                autoWidth: false
            });
        }
    });

    // === ELIMINAR DOCUMENTO ===
    $(document).on('click', '.delete-doc', function(){
        if(confirm('¿Eliminar documento?')){
            const field = $(this).data('field');
            $(this).closest('.col-md-4').find('.border').remove();
            $(`input[name="delete_${field}"]`).val('1');
            $(this).closest('.col-md-4').find('.upload-doc-container').show();
        }
    });

    // === ENVÍO ===
    $('#manage_equipment').submit(function(e){
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_equipment',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp){
                resp = resp.trim();
                if(resp === '1'){
                    alert_toast('Equipo actualizado', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast('Error: ' + resp, 'error');
                }
                end_load();
            }
        });
    });
</script>