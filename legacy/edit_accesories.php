<?php require_once 'config/config.php'; ?>

<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "<script>alert('ID inválido'); window.location='index.php?page=accessories_list';</script>";
    exit;
}

// === CONSULTA SEGURA ===
$qry = $conn->query("SELECT * FROM accessories WHERE id = $id");
if (!$qry) {
    die("<div class='container-fluid'><div class='alert alert-danger text-center p-4'>
         <h5>Error de Base de Datos</h5>
         <p>" . $conn->error . "</p>
         </div></div>");
}
if ($qry->num_rows === 0) {
    echo "<script>alert('Accesorio no encontrado'); window.location='index.php?page=accessories_list';</script>";
    exit;
}
$acc = $qry->fetch_assoc();
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <form id="edit_accessory" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="keep_image" value="1" id="keep_image_flag">

                <div class="row g-0">
                    <!-- IMAGEN IZQUIERDA -->
                    <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                        <div class="text-center w-100 position-relative" id="image-container" style="min-height: 420px;">

                            <?php if (!empty($acc['image']) && file_exists('uploads/' . $acc['image'])): ?>
                                <div class="position-relative d-inline-block">
                                    <img src="uploads/<?= $acc['image'] ?>"
                                        class="img-fluid rounded shadow"
                                        style="max-height: 380px; object-fit: contain;"
                                        id="current-img">
                                    <button type="button"
                                        class="btn btn-danger btn-sm position-absolute"
                                        style="top: 10px; right: 10px; z-index: 10;"
                                        id="remove-image">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <br>
                                <small class="text-muted">Haz clic para eliminar</small>
                            <?php else: ?>
                                <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center"
                                    style="height: 380px; border: 3px dashed #ccc;" id="empty-image">
                                    <i class="fas fa-headset fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>

                            <div id="upload-container" class="mt-3"
                                style="display: <?= !empty($acc['image']) ? 'none' : 'block' ?>;">
                                <input type="file" name="imagen" id="imagen" class="form-control" accept="image/jpeg,image/png,image/jpg"
                                    onchange="displayImg(this)">
                                <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                                <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-2"
                                    style="display:none; max-height: 200px;">
                            </div>
                        </div>
                    </div>

                    <!-- DATOS DERECHA -->
                    <div class="col-lg-7 p-5">

                        <!-- NOMBRE + INVENTARIO -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($acc['name']) ?>"
                                    required placeholder="Nombre del Accesorio">
                            </div>
                            <div class="col-md-4">
                                <strong style="font-size: 1.1rem; color: #007bff;">
                                    #<?= $acc['inventory_number'] ?>
                                </strong>
                            </div>
                        </div>

                        <!-- TIPO + ESTATUS -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Tipo de Accesorio</label>
                                <select name="type" class="custom-select select2" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $result = $conn->query("SHOW COLUMNS FROM accessories LIKE 'type'");
                                    $row = $result->fetch_assoc();
                                    preg_match_all("/'([^']+)'/", $row['Type'], $matches);
                                    $enum_values = $matches[1];
                                    foreach ($enum_values as $type_value):
                                        $selected = (isset($acc['type']) && $acc['type'] == $type_value) ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($type_value) ?>" <?= $selected ?>>
                                            <?= htmlspecialchars(ucwords($type_value)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Estatus</label>
                                <select name="status" class="custom-select select2" required>
                                    <option value="Activo" <?= $acc['status'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="Inactivo" <?= $acc['status'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <!-- MARCA Y MODELO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Marca</label>
                                <input type="text" name="brand" class="form-control"
                                    value="<?= htmlspecialchars($acc['brand'] ?? '') ?>" placeholder="Marca">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Modelo</label>
                                <input type="text" name="model" class="form-control"
                                    value="<?= htmlspecialchars($acc['model'] ?? '') ?>" placeholder="Modelo">
                            </div>
                        </div>

                        <!-- SERIE Y FECHA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Serie</label>
                                <input type="text" name="serial" class="form-control"
                                    value="<?= htmlspecialchars($acc['serial'] ?? '') ?>" placeholder="Serie">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha Adquisición</label>
                                <input type="date" name="acquisition_date" class="form-control"
                                    value="<?= $acc['acquisition_date'] ?>" required>
                            </div>
                        </div>

                        <!-- COSTO Y ADQUISICIÓN -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Costo (MXN)</label>
                                <input type="number" step="0.01" min="0" name="cost" class="form-control"
                                    value="<?= $acc['cost'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Tipo Adquisición</label>
                                <select name="acquisition_type_id" class="custom-select select2" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $acq = $conn->query("SELECT id, name FROM acquisition_type ORDER BY name");
                                    while ($a = $acq->fetch_assoc()):
                                        $selected = ($acc['acquisition_type_id'] == $a['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $a['id'] ?>" <?= $selected ?>>
                                            <?= ucwords($a['name']) ?>
                                        </option>
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
                                while ($d = $areas->fetch_assoc()):
                                    $selected = ($acc['area_id'] == $d['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $d['id'] ?>" <?= $selected ?>>
                                        <?= ucwords($d['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- OBSERVACIONES -->
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0">
                                <h6 class="mb-0 text-dark">Observaciones</h6>
                            </div>
                            <div class="card-body">
                                <textarea name="observations" class="form-control" rows="3"
                                    placeholder="Notas adicionales..."><?= htmlspecialchars($acc['observations'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                Actualizar
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
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .form-control:focus,
    .custom-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
    }

    .border-dashed {
        border-style: dashed !important;
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

        $('#remove-image').click(function() {
            if (confirm('¿Eliminar imagen actual?')) {
                $('#current-img').parent().remove();
                $('#remove-image').remove();
                $('#empty-image').remove();
                $('#upload-container').show();
                $('#keep_image_flag').val('0');
            }
        });

        $('#edit_accessory').submit(function(e) {
            e.preventDefault();
            start_load();

            const formData = new FormData(this);
            const hasNewImage = $('input[name="imagen"]')[0].files.length > 0;

            if (!hasNewImage && $('#keep_image_flag').val() === '0') {
                formData.set('keep_image', '0');
            }

            $.ajax({
                url: 'ajax.php?action=save_accessory',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                success: function(resp) {
                    resp = resp.trim();
                    if (resp === '1') {
                        alert_toast('Accesorio actualizado', 'success');
                        setTimeout(() => location.href = 'index.php?page=accessories_list', 1500);
                    } else {
                        alert_toast('Error: ' + resp, 'error');
                    }
                    end_load();
                },
                error: function() {
                    end_load();
                    alert_toast('Error de conexión', 'error');
                }
            });
        });
    });
</script>