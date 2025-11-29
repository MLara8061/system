<?php require_once 'config/config.php'; ?>

<?php
// Obtener próximo número de inventario
$result = $conn->query("SHOW TABLE STATUS LIKE 'equipments'");
$row = $result->fetch_assoc();
$next_inventory = $row['Auto_increment'];
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <!-- === FICHA TÉCNICA: IMAGEN + INFO === -->
            <div class="row g-0">
                <!-- IMAGEN -->
                <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                    <div class="text-center w-100">
                        <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center" 
                             style="height: 380px; border: 3px dashed #ccc;">
                            <i class="fas fa-camera fa-3x text-muted"></i>
                        </div>
                        <input type="file" name="equipment_image" id="equipment_image" class="form-control mt-3" accept="image/jpeg,image/png,image/jpg" form="manage_equipment">
                        <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                    </div>
                </div>

                <!-- INFORMACIÓN CLAVE -->
                <div class="col-lg-7 p-5">
                    <form id="manage_equipment" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="">

                        <!-- NOMBRE + #INVENTARIO -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <input type="text" name="name" class="form-control" 
                                       required placeholder="Nombre del equipo">
                            </div>
                            <div class="col-md-4">
                                <span class="badge badge-primary font-weight-bold p-2" style="font-size: 1.1rem;">
                                    #<?= $next_inventory ?>
                                </span>
                                <input type="hidden" name="number_inventory" value="<?= $next_inventory ?>">
                            </div>
                        </div>

                        <!-- MARCA Y MODELO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" name="brand" class="form-control" placeholder="Marca">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="model" class="form-control" required placeholder="Modelo">
                            </div>
                        </div>

                        <!-- SERIE Y FECHA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Serie</label>
                                <input type="text" name="serie" class="form-control alfanumerico" required placeholder="Número de serie">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha Ingreso</label>
                                <input type="date" name="date_created" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <!-- VALOR Y CATEGORÍA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Valor</label>
                                <input type="text" name="amount" class="form-control solonumeros" required placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Categoría</label>
                                <input type="text" name="discipline" class="form-control" required placeholder="Ej: Informática">
                            </div>
                        </div>

                        <!-- CONSUMO ELÉCTRICO -->
                        <div class="bg-light p-3 rounded mb-3">
                            <h6 class="mb-3 text-dark">Consumo Eléctrico</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="small text-muted">Voltaje (V)</label>
                                    <input type="number" step="0.01" min="0" name="voltage" class="form-control form-control-sm" placeholder="110.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted">Amperaje (A)</label>
                                    <input type="number" step="0.01" min="0" name="amperage" class="form-control form-control-sm" placeholder="5.50">
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted">Frecuencia (Hz)</label>
                                    <input type="number" step="0.01" min="0" name="frequency_hz" class="form-control form-control-sm" value="60.00">
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
                                    <option value="<?= $row['id'] ?>"><?= ucwords($row['empresa']) ?></option>
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
                                        <option value="<?= $row['id'] ?>"><?= ucwords($row['name']) ?></option>
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
                                        <option value="<?= $row['id'] ?>"><?= ucwords($row['name']) ?></option>
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
                                <select name="department_id" class="custom-select select2" form="manage_equipment" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                                    while ($row = $departments->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= ucwords($row['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Ubicación</label>
                                <select name="location_id" id="location_id" class="custom-select select2" form="manage_equipment" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $locations = $conn->query("SELECT id,name FROM locations ORDER BY name ASC");
                                    while ($row = $locations->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= ucwords($row['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Cargo Responsable</label>
                                <select name="responsible_position" id="responsible_position" class="custom-select select2" form="manage_equipment">
                                    <option value="">Seleccionar ubicación primero</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label>Nombre Responsable</label>
                                <input type="text" name="responsible_name" class="form-control" form="manage_equipment" required>
                            </div>
                            <div class="col-md-6">
                                <label>Fecha Capacitación</label>
                                <input type="date" name="date_training" class="form-control" form="manage_equipment" required>
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
                        <textarea name="characteristics" class="form-control" rows="3" form="manage_equipment" placeholder="Detalles técnicos..."></textarea>
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
                                <input type="text" name="invoice" class="form-control" form="manage_equipment" placeholder="Nro de factura">
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
                            ?>
                                <div class="col-md-4 mb-3">
                                    <label><?= $label ?></label>
                                    <input type="file" name="<?= $field ?>" class="form-control form-control-sm" form="manage_equipment">
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
                                    <input class="form-check-input" type="radio" name="state" value="1" form="manage_equipment" checked>
                                    <label class="form-check-label">Acepto</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="state" value="0" form="manage_equipment">
                                    <label class="form-check-label">Rechazo</label>
                                </div>
                                <textarea name="comments" class="form-control mt-2" rows="2" form="manage_equipment" placeholder="Notas..."></textarea>
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
                                        <input type="number" name="warranty_time" class="form-control" min="1" form="manage_equipment" placeholder="1">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Fecha Adquisición</label>
                                        <input type="date" name="date_adquisition" class="form-control" form="manage_equipment" value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-center btn-container-mobile">
                    <button type="submit" form="manage_equipment" class="btn btn-primary btn-lg px-5">Guardar Equipo</button>
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

    // Validar formato de imagen
    $('#equipment_image').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG y PNG', 'error');
                $(this).val(''); // Limpiar input
                return false;
            }
            
            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert_toast('La imagen es muy grande. Máximo 5MB', 'error');
                $(this).val('');
                return false;
            }
        }
    });

    $(function(){
        $('.select2').select2({ 
            width: '100%', 
            placeholder: 'Seleccionar', 
            allowClear: true,
            dropdownAutoWidth: true,
            maximumInputLength: 0
        });

        // Filtrado en cascada: Cargar cargos según ubicación seleccionada
        $('#location_id').on('change', function(){
            var location_id = $(this).val();
            var $responsiblePosition = $('#responsible_position');
            
            console.log('Location changed:', location_id);
            
            // Limpiar y deshabilitar select de cargo
            $responsiblePosition.empty().append('<option value="">Cargando...</option>').prop('disabled', true);
            
            if(location_id){
                console.log('Making AJAX request with location_id:', location_id);
                $.ajax({
                    url: 'ajax.php?action=get_job_positions_by_location',
                    method: 'POST',
                    data: { location_id: location_id },
                    dataType: 'json',
                    success: function(positions){
                        console.log('AJAX Success. Received positions:', positions);
                        $responsiblePosition.empty().append('<option value="">Seleccionar cargo</option>');
                        if(positions.length > 0){
                            $.each(positions, function(index, position){
                                console.log('Adding position:', position);
                                $responsiblePosition.append('<option value="'+ position.id +'">'+ position.name.toUpperCase() +'</option>');
                            });
                            $responsiblePosition.prop('disabled', false);
                        } else {
                            console.log('No positions found for this location');
                            $responsiblePosition.append('<option value="">No hay cargos para esta ubicación</option>');
                        }
                    },
                    error: function(xhr, status, error){
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);
                        $responsiblePosition.empty().append('<option value="">Error al cargar cargos</option>');
                    }
                });
            } else {
                console.log('No location selected');
                $responsiblePosition.empty().append('<option value="">Seleccionar ubicación primero</option>');
            }
        });
    });

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
                    alert_toast('Equipo guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=equipment_list', 1500);
                } else {
                    alert_toast('Error: ' + resp, 'error');
                }
                end_load();
            }
        });
    });
</script>