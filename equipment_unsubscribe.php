<?php
include 'db_connect.php';
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
?>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form action="" id="manage_equipment">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="row">
                    <div class="col-md-12 border-right">
                        <b class="text-muted">Dar de Baja Equipo</b><br/><br/>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Fecha</label>
                            <input type="date" name="date" class="form-control form-control-sm" required 
                                   value="<?php echo isset($date) ? $date : ''; ?>">
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Nro Inventario</label>
                            <input type="text" name="number_inventory" class="form-control form-control-sm solonumeros" 
                                   value="<?php echo isset($number_inventory) ? $number_inventory : ''; ?>" disabled>
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm alfanumerico"
                                   value="<?php echo isset($serie) ? $serie : ''; ?>" disabled>
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Fecha Ingreso</label>
                            <input type="date" name="date_created" class="form-control form-control-sm"
                                   value="<?php echo isset($date_created) ? date('Y-m-d', strtotime($date_created)) : ''; ?>" disabled>
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Equipo</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   value="<?php echo isset($name) ? $name : ''; ?>" disabled>
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Marca</label>
                            <input type="text" name="brand" class="form-control form-control-sm"
                                   value="<?php echo isset($brand) ? $brand : ''; ?>" disabled>
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Modelo</label>
                            <input type="text" name="model" class="form-control form-control-sm"
                                   value="<?php echo isset($model) ? $model : ''; ?>" disabled>
                        </div>

                        <div class="form-group float-left col-md-3">
                            <label class="control-label">Tipo de Adquisición</label>
                            <select name="acquisition_type" class="custom-select custom-select-sm select2" disabled>
                                <option value="1" selected>Compra</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12 border-right">
                        <div class="form-group">
                            <label class="control-label">Descripción Estado Funcional Equipo</label><br/>
                            <textarea name="description" cols="15" rows="3" style="width:100%" required><?php
                                echo isset($description) ? $description : '';
                            ?></textarea>
                        </div>
                    </div>
                </div>
                <hr/>

                <div class="row">
                    <div class="col-md-12 border-right">
                        <b class="text-muted">Causas de Retiro</b><br/><br/>
                        <?php while ($row = $causas->fetch_object()): ?>
                            <div class="form-group col-md-6 float-left">
                                <label>
                                    <?php
                                    $checked = in_array($row->id, $reasons, true) ? 'checked' : '';
                                    echo htmlspecialchars($row->name);
                                    ?>
                                    &nbsp;&nbsp;
                                    <input type="checkbox" name="withdrawal_reason[]" value="<?php echo $row->id; ?>" <?php echo $checked; ?>>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <hr>

                <div class="row">
                    <div class="col-md-12 border-right">
                        <b class="text-muted">Dictamen</b><br/><br/>
                        <div class="form-group col-md-2 float-left">
                            <label>Funcional
                                <input type="radio" name="opinion" required value="1" <?php echo (isset($opinion) && $opinion == 1) ? 'checked' : ''; ?>>
                            </label><br/>
                            <label>Disfuncional
                                <input type="radio" name="opinion" required value="0" <?php echo (isset($opinion) && $opinion == 0) ? 'checked' : ''; ?>>
                            </label>
                        </div>
                        <div class="col-md-1 float-left">
                            <label class="control-label">Comentarios</label>
                        </div>
                        <div class="col-md-8 float-left">
                            <textarea name="comments" required style="margin-left:20px;width:100%;height:120px"><?php
                                echo isset($comments) ? $comments : '';
                            ?></textarea>
                        </div>
                    </div>
                </div>
                <hr>

                <div class="row">
                    <div class="col-md-12 border-right">
                        <div class="form-group col-md-3 float-left">
                            <b class="text-muted">Responsable de la evaluación</b><br/><br/>
                            <label>Ingeniero Sistemas
                                <input type="radio" name="responsible" value="1" <?php echo (isset($responsible) && $responsible == 1) ? 'checked' : ''; ?>>
                            </label><br/>
                            <label>Proveedor Externo
                                <input type="radio" name="responsible" value="2" <?php echo (isset($responsible) && $responsible == 2) ? 'checked' : ''; ?>>
                            </label>
                        </div>

                        <div class="form-group col-md-9 float-left">
                            <b class="text-muted">Destino del equipo de baja</b><br/><br/>
                            <label>Guardar en bodega
                                <input type="radio" name="destination" required value="1" <?php echo (isset($destination) && $destination == 1) ? 'checked' : ''; ?>>
                            </label>&nbsp;&nbsp;
                            <label>Devolución al Proveedor
                                <input type="radio" name="destination" required value="2" <?php echo (isset($destination) && $destination == 2) ? 'checked' : ''; ?>>
                            </label><br/><br/>
                            <label>Donar
                                <input type="radio" name="destination" required value="3" <?php echo (isset($destination) && $destination == 3) ? 'checked' : ''; ?>>
                            </label>&nbsp;&nbsp;
                            <label>Venta
                                <input type="radio" name="destination" required value="4" <?php echo (isset($destination) && $destination == 4) ? 'checked' : ''; ?>>
                            </label>&nbsp;&nbsp;
                            <label>Basura
                                <input type="radio" name="destination" required value="5" <?php echo (isset($destination) && $destination == 5) ? 'checked' : ''; ?>>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 text-right justify-content-center d-flex">
                    <button class="btn btn-primary mr-2">Guardar</button>
                    <button class="btn btn-secondary" type="reset">Resetear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('.solonumeros').on('input', function () {
    this.value = this.value.replace(/[^0-9]/g,'');
});

$('.alfanumerico').on('input', function () {
    this.value = this.value.replace(/[^a-zA-Z0-9]/g,'');
});

$('#manage_equipment').submit(function(e) {
    e.preventDefault();
    start_load();

    var postData = new FormData($(this)[0]);
    $.ajax({
        url: 'ajax.php?action=save_equipment_unsubscribe',
        data: postData,
        cache: false,
        dataType: 'text',
        contentType: false,
        processData: false,
        method: 'POST',
        success: function(resp) {
            if (resp == 1) {
                end_load();
                alert_toast('Datos guardados correctamente', "success");
                setTimeout(function() {
                    location.replace('index.php?page=equipment_list');
                }, 750);
            } else if (resp == 2) {
                $('#msg').html("<div class='alert alert-danger'>Error al guardar el equipo.</div>");
                end_load();
            }
        }
    });
});
</script>
