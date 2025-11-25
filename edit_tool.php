<?php require_once 'config/config.php'; ?>

<?php
$id = isset($_GET['id']) ? $_GET['id'] : 0;
$tool = $conn->query("SELECT * FROM tools WHERE id = $id")->fetch_assoc();
if (!$tool) {
    echo "<script>alert('Herramienta no encontrada'); location.href='index.php?page=tools_list';</script>";
    exit;
}
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <form id="manage-tool" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $tool['id'] ?>">
                <input type="hidden" name="keep_image" value="1" id="keep_image">

                <div class="row g-0">
                    <!-- IMAGEN A LA IZQUIERDA -->
                    <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                        <div class="text-center w-100">
                            <?php if (!empty($tool['imagen']) && file_exists('uploads/' . $tool['imagen'])): ?>
                                <img src="uploads/<?= $tool['imagen'] ?>" class="img-fluid rounded shadow" 
                                     style="max-height: 380px; object-fit: contain;" id="current-img">
                                <br><br>
                                <button type="button" class="btn btn-danger btn-sm" id="remove-image">
                                    Eliminar imagen
                                </button>
                                <div id="upload-container" style="display:none;">
                                    <input type="file" name="imagen" id="imagen" class="form-control mt-3" accept="image/jpeg,image/png,image/jpg" onchange="displayImg(this)">
                                    <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                                    <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3" 
                                         style="display:none; max-height: 200px;">
                                </div>
                            <?php else: ?>
                                <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center" 
                                     style="height: 380px; border: 3px dashed #ccc;">
                                    <i class="fas fa-tools fa-3x text-muted"></i>
                                </div>
                                <input type="file" name="imagen" id="imagen2" class="form-control mt-3" accept="image/jpeg,image/png,image/jpg" onchange="displayImg(this)">
                                <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                                <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3" 
                                     style="display:none; max-height: 200px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- DATOS A LA DERECHA -->
                    <div class="col-lg-7 p-5">

                        <!-- NOMBRE -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Nombre de la Herramienta</label>
                            <input type="text" name="nombre" class="form-control" required 
                                   value="<?= htmlspecialchars($tool['nombre']) ?>" 
                                   placeholder="Ej: Taladro Inalámbrico">
                        </div>

                        <!-- MARCA Y COSTO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Marca</label>
                                <input type="text" name="marca" class="form-control" 
                                       value="<?= htmlspecialchars($tool['marca']) ?>" 
                                       placeholder="Ej: DeWalt">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Costo (MXN)</label>
                                <input type="number" step="0.01" min="0" name="costo" class="form-control" required 
                                       value="<?= $tool['costo'] ?>">
                            </div>
                        </div>

                        <!-- FECHA ADQUISICIÓN -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Fecha de Adquisición</label>
                            <input type="date" name="fecha_adquisicion" class="form-control" required 
                                   value="<?= $tool['fecha_adquisicion'] ?>">
                        </div>

                        <!-- PROVEEDOR -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Proveedor</label>
                            <select name="supplier_id" class="custom-select select2" required>
                                <option value="">Seleccionar proveedor</option>
                                <?php
                                $proveedores = $conn->query("SELECT id, empresa FROM suppliers WHERE estado = 1 ORDER BY empresa ASC");
                                while ($row = $proveedores->fetch_assoc()):
                                ?>
                                    <option value="<?= $row['id'] ?>" <?= $tool['supplier_id'] == $row['id'] ? 'selected' : '' ?>>
                                        <?= ucwords($row['empresa']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- ESTATUS Y FECHA BAJA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Estatus</label>
                                <select name="estatus" class="custom-select select2">
                                    <option value="Activa" <?= $tool['estatus'] == 'Activa' ? 'selected' : '' ?>>Activa</option>
                                    <option value="Inactiva" <?= $tool['estatus'] == 'Inactiva' ? 'selected' : '' ?>>Inactiva</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha de Baja (si aplica)</label>
                                <input type="date" name="fecha_baja" class="form-control" 
                                       value="<?= ($tool['fecha_baja'] && $tool['fecha_baja'] != '0000-00-00') ? $tool['fecha_baja'] : '' ?>">
                            </div>
                        </div>

                        <!-- CARACTERÍSTICAS -->
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0">
                                <h6 class="mb-0 text-dark">Características Técnicas</h6>
                            </div>
                            <div class="card-body">
                                <textarea name="caracteristicas" class="form-control" rows="3" 
                                          placeholder="Detalles técnicos, uso, accesorios..."><?= htmlspecialchars($tool['caracteristicas']) ?></textarea>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="text-center btn-container-mobile">
                            <button type="submit" class="btn btn-success btn-lg px-5">
                                Actualizar Herramienta
                            </button>
                            <a href="index.php?page=tools_list" class="btn btn-secondary btn-lg px-5">
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
    .form-control, .custom-select {
        border-radius: 10px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-control:focus, .custom-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25);
    }
    .border-dashed { border-style: dashed !important; }
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
    $('#imagen, #imagen2').on('change', function(e) {
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
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-img').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seleccionar',
            allowClear: false
        });
    });

    // === ELIMINAR IMAGEN ===
    $(document).on('click', '#remove-image', function() {
        if (confirm('¿Deseas eliminar esta imagen?')) {
            $('#current-img').remove();
            $(this).remove();
            $('#upload-container').show();
            $('#keep_image').val('0');
        }
    });

    // === ENVIAR ===
    $('#manage-tool').submit(function(e) {
        e.preventDefault();
        start_load();

        $.ajax({
            url: 'ajax.php?action=save_tool',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                end_load();
                resp = resp.trim();
                if (resp == 1) {
                    alert_toast('Herramienta actualizada correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=tools_list', 1200);
                } else {
                    alert_toast('Error: ' + resp, 'error');
                }
            },
            error: function() {
                end_load();
                alert_toast('Error de conexión', 'error');
            }
        });
    });
</script>