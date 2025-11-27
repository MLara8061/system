<?php
define('ACCESS', true);
require_once 'config/config.php';
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Validar y limpiar el ID recibido
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Datos del equipo
$qry_equipment = $conn->query("SELECT * FROM equipments WHERE id = {$id}");
$equip_data = $qry_equipment ? $qry_equipment->fetch_assoc() : [];
foreach ($equip_data as $k => $v) {
    $$k = $v;
}

// Datos de la baja (puede no existir)
$qry_unsub = $conn->query("SELECT * FROM equipment_unsubscribe WHERE equipment_id = {$id}");
$unsub_data = $qry_unsub ? $qry_unsub->fetch_assoc() : [];
foreach ($unsub_data as $k => $v) {
    $$k = $v;
}

// Causas disponibles
$causas = $conn->query("SELECT * FROM equipment_withdrawal_reason");

// Asegurar que withdrawal_reason sea siempre un array válido
$raw_reason = isset($withdrawal_reason) && !empty($withdrawal_reason) ? $withdrawal_reason : '[]';
$reasons = json_decode($raw_reason, true);
if (!is_array($reasons)) {
    $reasons = [];
}
$session_first = isset($_SESSION['login_firstname']) ? $_SESSION['login_firstname'] : '';
$session_middle = isset($_SESSION['login_middlename']) ? $_SESSION['login_middlename'] : '';
$session_last = isset($_SESSION['login_lastname']) ? $_SESSION['login_lastname'] : '';
$session_username = isset($_SESSION['login_username']) ? $_SESSION['login_username'] : '';
$current_user_name = trim(implode(' ', array_filter([$session_first, $session_middle, $session_last])));
if ($current_user_name === '') {
    $current_user_name = $session_username;
}
$current_user_name = $current_user_name ?: 'No registrado';

$default_date = isset($date) && !empty($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
$default_time = isset($time) && !empty($time) ? date('H:i', strtotime($time)) : date('H:i');
$existing_folio = isset($folio) ? $folio : '';
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius:16px; overflow:hidden;">
        <div class="card-header bg-light border-0">
            <h5 class="mb-0 text-dark">Dar de Baja Equipo</h5>
        </div>
        <div class="card-body">
            <form action="" id="manage_equipment">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <!-- Datos del equipo -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 text-dark">Datos del Equipo</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Fecha</label>
                                <input type="date" name="date" class="form-control" required value="<?php echo htmlspecialchars($default_date); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Hora</label>
                                <input type="time" name="time" class="form-control" required value="<?php echo htmlspecialchars($default_time); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Nro Inventario</label>
                                <input type="text" name="number_inventory" class="form-control solonumeros" value="<?php echo isset($number_inventory) ? $number_inventory : ''; ?>" disabled>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Serie</label>
                                <input type="text" name="serie" class="form-control alfanumerico" value="<?php echo isset($serie) ? $serie : ''; ?>" disabled>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Fecha Ingreso</label>
                                <input type="date" name="date_created" class="form-control" value="<?php echo isset($date_created) ? date('Y-m-d', strtotime($date_created)) : ''; ?>" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold text-dark">Equipo</label>
                                <input type="text" name="name" class="form-control" value="<?php echo isset($name) ? $name : ''; ?>" disabled>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold text-dark">Marca</label>
                                <input type="text" name="brand" class="form-control" value="<?php echo isset($brand) ? $brand : ''; ?>" disabled>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold text-dark">Modelo</label>
                                <input type="text" name="model" class="form-control" value="<?php echo isset($model) ? $model : ''; ?>" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold text-dark">Tipo de Adquisición</label>
                                <select name="acquisition_type" class="custom-select select2" disabled>
                                    <option value="1" selected>Compra</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold text-dark">Usuario que realiza la baja</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user_name); ?>" readonly>
                            </div>
                            <?php if (!empty($existing_folio)): ?>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold text-dark">Folio generado</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($existing_folio); ?>" readonly>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Estado funcional -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 text-dark">Descripción Estado Funcional</h6>
                    </div>
                    <div class="card-body">
                        <textarea name="description" class="form-control" rows="3" required><?php echo isset($description) ? $description : ''; ?></textarea>
                    </div>
                </div>

                <!-- Causas de retiro -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 text-dark">Causas de Retiro</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while ($row = $causas->fetch_object()): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <?php $checked = in_array($row->id, $reasons, true) ? 'checked' : ''; ?>
                                                <input class="form-check-input" type="checkbox" id="reason_<?php echo $row->id; ?>" name="withdrawal_reason[]" value="<?php echo $row->id; ?>" <?php echo $checked; ?>>
                                                <label class="form-check-label" for="reason_<?php echo $row->id; ?>"><?php echo htmlspecialchars($row->name); ?></label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Dictamen -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 text-dark">Dictamen</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opinion" id="op_ok" required value="1" <?php echo (isset($opinion) && $opinion == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="op_ok">Funcional</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="opinion" id="op_bad" required value="0" <?php echo (isset($opinion) && $opinion == 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="op_bad">Disfuncional</label>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <label class="font-weight-bold text-dark">Comentarios</label>
                                <textarea name="comments" class="form-control" rows="4" required><?php echo isset($comments) ? $comments : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responsable y destino -->
                <div class="card mb-4">
                    <div class="card-header bg-white border-0">
                        <h6 class="mb-0 text-dark">Responsable y Destino</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold text-dark d-block mb-2">Responsable de la evaluación</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="responsible" id="resp1" value="1" <?php echo (isset($responsible) && $responsible == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="resp1">Jefe de servicio</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="responsible" id="resp2" value="2" <?php echo (isset($responsible) && $responsible == 2) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="resp2">Proveedor Externo</label>
                                </div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="font-weight-bold text-dark d-block mb-2">Destino del equipo de baja</label>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="destination" id="dest1" required value="1" <?php echo (isset($destination) && $destination == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dest1">Guardar en bodega</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="destination" id="dest2" required value="2" <?php echo (isset($destination) && $destination == 2) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dest2">Devolución al Proveedor</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="destination" id="dest3" required value="3" <?php echo (isset($destination) && $destination == 3) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dest3">Donar</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="destination" id="dest4" required value="4" <?php echo (isset($destination) && $destination == 4) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dest4">Venta</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="destination" id="dest5" required value="5" <?php echo (isset($destination) && $destination == 5) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dest5">Basura</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center btn-container-mobile">
                    <button class="btn btn-primary btn-lg px-5" type="submit">Guardar</button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary btn-lg px-5">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-control, .custom-select { border-radius: 10px; }
    .card > .card-header h6, .card > .card-header h5 { font-weight: 600; }
    .form-check-label { cursor: pointer; }
    .form-check-input { cursor: pointer; }
    .card.mb-4 { border: 1px solid #e9ecef; border-radius: 12px; }
    .card.mb-4 .card-header { background-color: #f8f9fa !important; }
    textarea.form-control { resize: vertical; }
    @media (max-width: 576px) {
        .btn-lg { width: 100%; margin-bottom: .5rem; }
    }
    .select2-container--default .select2-selection--single { height: 38px; border-radius: 10px; border: 1px solid #ced4da; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    .text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .shadow-sm { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; }
    .border-0 { border: 0!important; }
    .bg-light { background-color: #f8f9fa!important; }
    .bg-white { background-color: #fff!important; }
    .text-dark { color: #343a40!important; }
    .font-weight-bold { font-weight: 600!important; }
    .mb-0 { margin-bottom: 0!important; }
</style>

<script>
$('.solonumeros').on('input', function () {
    this.value = this.value.replace(/[^0-9]/g,'');
});

$('.alfanumerico').on('input', function () {
    this.value = this.value.replace(/[^a-zA-Z0-9]/g,'');
});

$(function(){
    if ($.fn.select2) {
        $('.select2').select2({ width: '100%', placeholder: 'Seleccionar', allowClear: true });
    }
});

$('#manage_equipment').submit(function(e) {
    e.preventDefault();
    start_load();

    var postData = new FormData($(this)[0]);
    $.ajax({
        url: 'ajax.php?action=save_equipment_unsubscribe',
        data: postData,
        cache: false,
        dataType: 'json',
        contentType: false,
        processData: false,
        method: 'POST',
        success: function(resp) {
            end_load();
            if (!resp || typeof resp.status === 'undefined') {
                alert_toast('No se pudo guardar la baja. Intenta de nuevo.', 'error');
                return;
            }
            if (resp.status === 1) {
                var folio = resp.folio || '';
                var pdfUrl = resp.unsubscribe_id ? 'equipment_unsubscribe_pdf.php?id=' + resp.unsubscribe_id : '';
                var successMessage = folio ? 'Se generó el folio ' + folio + '.' : 'Datos guardados correctamente.';
                alert_toast(successMessage, 'success');

                if (pdfUrl && typeof confirm_toast === 'function') {
                    confirm_toast('¿Deseas imprimir el formato en PDF?', function() {
                        window.open(pdfUrl, '_blank');
                        location.replace('index.php?page=equipment_list');
                    }, function() {
                        location.replace('index.php?page=equipment_list');
                    });
                } else if (pdfUrl) {
                    var proceed = window.confirm('¿Deseas imprimir el formato en PDF?');
                    if (proceed) {
                        window.open(pdfUrl, '_blank');
                    }
                    location.replace('index.php?page=equipment_list');
                } else {
                    location.replace('index.php?page=equipment_list');
                }
            } else {
                var msg = resp.message ? resp.message : 'Error al guardar la baja.';
                alert_toast(msg, 'error');
            }
        },
        error: function() {
            end_load();
            alert_toast('No se pudo guardar la baja. Revisa tu conexión.', 'error');
        }
    });
});

// No se requiere confirm modal propio: confirm_toast cubre la interacción
</script>
