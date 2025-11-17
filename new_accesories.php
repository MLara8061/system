<?php require_once 'config/config.php'; ?>

<?php
$result = $conn->query("SHOW TABLE STATUS LIKE 'accessories'");
$row = $result->fetch_assoc();
$siguiente_inventario = $row['Auto_increment'];
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
                            <input type="file" name="imagen" class="form-control mt-3" accept="image/*" onchange="displayImg(this)">
                            <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3"
                                style="display:none; max-height: 200px;">
                        </div>
                    </div>

                    <!-- DATOS DERECHA -->
                    <div class="col-lg-7 p-5">
                        <!-- NOMBRE + INVENTARIO -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <input type="text" name="nombre" class="form-control" required
                                    placeholder="Nombre del Accesorio">
                            </div>
                            <div class="col-md-4">
                                <span class="badge badge-primary font-weight-bold p-2" style="font-size: 1.1rem;">
                                    #<?= $siguiente_inventario ?>
                                </span>
                                <input type="hidden" name="numero_inventario" value="<?= $siguiente_inventario ?>">
                            </div>
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
                                <input type="text" name="marca" class="form-control" placeholder="Marca">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Modelo</label>
                                <input type="text" name="modelo" class="form-control" placeholder="Modelo">
                            </div>
                        </div>

                        <!-- SERIE Y FECHA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Serie</label>
                                <input type="text" name="serie" class="form-control" placeholder="Serie">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha Adquisición</label>
                                <input type="date" name="fecha_adquisicion" class="form-control" required
                                    value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <!-- COSTO Y ADQUISICIÓN -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Costo (MXN)</label>
                                <input type="number" step="0.01" name="costo" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Tipo Adquisición</label>
                                <select name="acquisition_type" class="custom-select select2" required>
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
                                <textarea name="observaciones" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="text-center">
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
    });

    $('#manage_accessory').submit(function(e) {
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_accessory',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: resp => {
                resp = resp.trim();
                if (resp == '1') {
                    alert_toast('Guardado', 'success');
                    setTimeout(() => location.href = 'index.php?page=accesories_list', 1500);
                } else {
                    alert_toast('Error: ' + resp, 'error');
                }
                end_load();
            }
        });
    });
</script>