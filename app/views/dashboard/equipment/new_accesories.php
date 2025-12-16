<?php require_once 'config/config.php'; ?>

<?php
// Obtener sucursales
$branches = $conn->query("SELECT id, name FROM branches WHERE active = 1 ORDER BY name ASC");
// Próximo número de inventario se generará dinámicamente

// Para generar el número con el endpoint compartido, necesitamos una categoría de equipo.
// Intentamos resolver una categoría relacionada a "accesor"; si no existe, el badge mostrará aviso.
$accessories_category_id = 0;
try {
    $cat = $conn->query("SELECT id FROM equipment_categories WHERE LOWER(description) LIKE '%accesor%' OR LOWER(description) LIKE '%accessor%' OR LOWER(clave) LIKE '%acc%' ORDER BY id ASC LIMIT 1");
    if ($cat && ($r = $cat->fetch_assoc())) {
        $accessories_category_id = (int)($r['id'] ?? 0);
    }
} catch (Throwable $e) {
    $accessories_category_id = 0;
}
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <form id="manage_accessory" enctype="multipart/form-data">
                <input type="hidden" name="id" value="">

                <div class="row g-0">
                    <!-- IMAGEN IZQUIERDA -->
                    <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                        <div class="text-center w-100">
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center"
                                style="height: 380px; border: 3px dashed #ccc;">
                                <i class="fas fa-headset fa-3x text-muted"></i>
                            </div>
                            <input type="file" name="imagen" id="imagen" class="form-control mt-3" accept="image/jpeg,image/png,image/jpg" onchange="displayImg(this)">
                            <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                            <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3"
                                style="display:none; max-height: 200px;">
                        </div>
                    </div>

                    <!-- DATOS DERECHA -->
                    <div class="col-lg-7 p-5">
                        <!-- NOMBRE + INVENTARIO -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <input type="text" name="name" class="form-control" required
                                    placeholder="Nombre del Accesorio">
                            </div>
                            <div class="col-md-4">
                                <span class="badge badge-primary font-weight-bold p-2" id="inventory_badge" style="font-size: 1.1rem;">
                                    Seleccionar sucursal
                                </span>
                                <input type="hidden" name="inventory_number" id="numero_inventario" value="">
                                <input type="hidden" name="equipment_category_id" id="equipment_category_id" value="<?= (int)$accessories_category_id ?>">
                            </div>
                        </div>

                        <!-- SUCURSAL -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Sucursal</label>
                            <select name="branch_id" id="branch_id" class="custom-select select2" required>
                                <option value="">Seleccionar sucursal</option>
                                <?php while ($branch = $branches->fetch_assoc()): ?>
                                    <option value="<?= $branch['id'] ?>"><?= ucwords($branch['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- TIPO + ESTATUS -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Tipo de Accesorio</label>
                                <select name="type" class="custom-select select2" required>
                                    <option value="">Seleccionar tipo</option>
                                    <?php
                                    $result = $conn->query("SHOW COLUMNS FROM accessories LIKE 'type'");
                                    $row = $result->fetch_assoc();

                                    preg_match_all("/'([^']+)'/", $row['Type'], $matches);
                                    $enum_values = $matches[1];

                                    foreach ($enum_values as $type_value):
                                        $selected = (isset($accessory['type']) && $accessory['type'] == $type_value) ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($type_value) ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($type_value) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Estatus</label>
                                <select name="status" class="custom-select select2" required>
                                    <option value="Activo" selected>Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <!-- MARCA Y MODELO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Marca</label>
                                <input type="text" name="brand" class="form-control" placeholder="Marca">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Modelo</label>
                                <input type="text" name="model" class="form-control" placeholder="Modelo">
                            </div>
                        </div>

                        <!-- SERIE Y FECHA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Serie</label>
                                <input type="text" name="serial" class="form-control" placeholder="Serie">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha Adquisición</label>
                                <input type="date" name="acquisition_date" class="form-control" required
                                    value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <!-- INVENTARIO ANTERIOR Y NÚMERO DE PARTE -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Inventario Anterior</label>
                                <input type="text" name="inventario_anterior" class="form-control" placeholder="Número de inventario anterior">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Número de Parte</label>
                                <input type="text" name="numero_parte" class="form-control" placeholder="Número de parte">
                            </div>
                        </div>

                        <!-- COSTO Y ADQUISICIÓN -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Costo (MXN)</label>
                                <input type="number" step="0.01" name="cost" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Tipo Adquisición</label>
                                <select name="acquisition_type_id" class="custom-select select2" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $acq = $conn->query("SELECT id, name FROM acquisition_type ORDER BY name");
                                    while ($a = $acq->fetch_assoc()): ?>
                                        <option value="<?= $a['id'] ?>"><?= ucwords($a['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- ÁREA -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Área Asignada</label>
                            <select name="area_id" class="custom-select select2" required>
                                <option value="">Seleccionar</option>
                                <?php
                                $areas = $conn->query("SELECT id, name FROM departments ORDER BY name");
                                while ($d = $areas->fetch_assoc()): ?>
                                    <option value="<?= $d['id'] ?>"><?= ucwords($d['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- OBSERVACIONES -->
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0">
                                <h6 class="mb-0 text-dark">Observaciones</h6>
                            </div>
                            <div class="card-body">
                                <textarea name="observations" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="text-center btn-container-mobile">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                Guardar
                            </button>
                            <a href="index.php?page=accessories_list" class="btn btn-secondary btn-lg px-5">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-control,
    .custom-select {
        border-radius: 10px !important;
    }

    .select2-container--default .select2-selection--single {
        border-radius: 10px !important;
        height: 38px;
        line-height: 36px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>

<script>
    // Validar formato de imagen
    $('#imagen').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG y PNG', 'error');
                $(this).val('');
                $('#preview-img').hide();
                return false;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert_toast('La imagen es muy grande. Máximo 5MB', 'error');
                $(this).val('');
                $('#preview-img').hide();
                return false;
            }
        }
    });

    function displayImg(input) {
        if (input.files[0]) {
            var reader = new FileReader();
            reader.onload = e => $('#preview-img').attr('src', e.target.result).show();
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seleccionar',
            allowClear: true
        });

        function refresh_inventory_number(){
            var branch_id = $('#branch_id').val();

            if(!branch_id){
                $('#inventory_badge').text('Seleccionar sucursal');
                $('#numero_inventario').val('');
                return;
            }

            $.ajax({
                url: 'public/ajax/action.php?action=get_next_inventory_number',
                method: 'POST',
                data: {
                    branch_id: branch_id
                },
                dataType: 'json',
                success: function(data){
                    if(data && data.success && data.number){
                        $('#inventory_badge').text(data.number);
                        $('#numero_inventario').val(data.number);
                    } else {
                        var msg = (data && data.error) ? data.error : 'Error al generar número de inventario';
                        $('#inventory_badge').text('Error');
                        $('#numero_inventario').val('');
                        alert_toast(msg, 'error');
                    }
                },
                error: function(xhr){
                    var msg = 'Error de conexión';
                    try {
                        if (xhr && xhr.responseText) msg = String(xhr.responseText).trim() || msg;
                    } catch (e) {}
                    $('#inventory_badge').text('Error');
                    $('#numero_inventario').val('');
                    alert_toast(msg, 'error');
                }
            });
        }

        $('#branch_id').on('change', refresh_inventory_number);
    });

    $('#manage_accessory').submit(function(e) {
        e.preventDefault();
        console.log('=== SUBMIT ACCESSORY ===');
        
        // Debug: Verificar datos del formulario
        var formData = new FormData(this);
        console.log('FormData entries:');
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        start_load();
        $.ajax({
            url: 'public/ajax/action.php?action=save_accessory',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                console.log('=== AJAX SUCCESS ===');
                console.log('Raw response:', resp);
                console.log('Response type:', typeof resp);
                console.log('Response length:', resp ? resp.length : 0);
                
                resp = String(resp).trim();
                console.log('Trimmed response:', resp);
                
                if (resp == '1') {
                    alert_toast('Accesorio guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=accessories_list', 1500);
                } else {
                    console.error('Save failed. Response:', resp);
                    alert_toast('Error al guardar: ' + resp, 'error');
                }
            },
            error: function(xhr, status, error){
                console.error('=== AJAX ERROR ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR status:', xhr.status);
                console.error('XHR statusText:', xhr.statusText);
                console.error('Response text:', xhr.responseText);
                var msg = 'Error de conexión';
                try {
                    if (xhr && xhr.responseText) {
                        msg += ': ' + String(xhr.responseText).trim();
                    }
                } catch (e) {}
                alert_toast(msg, 'error');
            },
            complete: function(){
                end_load();
            }
        });
    });
</script>

