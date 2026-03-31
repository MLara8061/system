<?php require_once 'config/config.php'; ?>
<?php require_once ROOT_PATH . 'app/helpers/CustomFieldRenderer.php'; ?>

<?php
$login_type = (int)($_SESSION['login_type'] ?? 0);
$active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
$needs_branch_select = ($login_type === 1 && $active_bid === 0);

$branches = [];
if ($needs_branch_select && isset($conn) && $conn) {
    $has_active = false;
    $col = @$conn->query("SHOW COLUMNS FROM branches LIKE 'active'");
    if ($col && $col->num_rows > 0) $has_active = true;

    $sql = "SELECT id, code, name" . ($has_active ? ", active" : "") . " FROM branches";
    if ($has_active) {
        $sql .= " WHERE active = 1";
    }
    $sql .= " ORDER BY name ASC";

    $res = @$conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $branches[] = $row;
        }
    }
}
?>

<?php
// Obtener el siguiente ID automático
$result = $conn->query("SHOW TABLE STATUS LIKE 'inventory'");
$row = $result->fetch_assoc();
$siguiente_id = $row['Auto_increment'];
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <form id="manage_inventory" enctype="multipart/form-data">
                <input type="hidden" name="id" value="">
                <?php if (!$needs_branch_select && $login_type === 1 && $active_bid > 0): ?>
                    <input type="hidden" name="branch_id" value="<?= (int)$active_bid ?>">
                <?php endif; ?>

                <div class="row g-0">
                    <!-- IMAGEN IZQUIERDA -->
                    <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                        <div class="text-center w-100">
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center"
                                style="height: 380px; border: 3px dashed #ccc;">
                                <i class="fas fa-box fa-3x text-muted"></i>
                            </div>
                            <input type="file" name="image_path" id="image_path" class="form-control mt-3" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="displayImg(this)">
                            <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                            <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3"
                                style="display:none; max-height: 200px;">
                        </div>
                    </div>

                    <!-- DATOS DERECHA -->
                    <div class="col-lg-7 p-5">
                        <?php if ($needs_branch_select): ?>
                            <div class="mb-3">
                                <label class="font-weight-bold text-dark">Sucursal</label>
                                <select name="branch_id" class="custom-select select2" required>
                                    <option value="">Seleccionar sucursal</option>
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?= (int)($b['id'] ?? 0) ?>"><?= htmlspecialchars(trim((string)($b['code'] ?? '')) . ' - ' . trim((string)($b['name'] ?? ''))) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Selecciona una sucursal para asignar el ítem.</small>
                            </div>
                        <?php endif; ?>
                        <!-- NOMBRE + ID AUTOMÁTICO -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-8">
                                <input type="text" name="name" class="form-control" required
                                    placeholder="Nombre del Ítem">
                            </div>
                            <div class="col-md-4">
                                <span class="badge badge-primary font-weight-bold p-2" style="font-size: 1.1rem;">
                                    #<?= $siguiente_id ?>
                                </span>
                            </div>
                        </div>

                        <!-- CATEGORÍA + STATUS -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Categoría</label>
                                <input type="text" name="category" class="form-control" placeholder="Ej: Consumibles, Herramientas" required>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Estatus</label>
                                <select name="status" class="custom-select select2" required>
                                    <option value="active" selected>Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="out_of_stock">Sin Stock</option>
                                </select>
                            </div>
                        </div>

                        <!-- PRECIO Y COSTO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Precio Venta (MXN)</label>
                                <input type="number" step="0.01" name="price" class="form-control" required min="0" value="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Costo (MXN)</label>
                                <input type="number" step="0.01" name="cost" class="form-control" required min="0" value="0.00">
                            </div>
                        </div>

                        <!-- STOCK MÍNIMO Y MÁXIMO -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="font-weight-bold text-dark">Stock Actual</label>
                                <input type="number" name="stock" class="form-control" required min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold text-dark">Stock Mínimo</label>
                                <input type="number" name="min_stock" class="form-control" required min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="font-weight-bold text-dark">Stock Máximo</label>
                                <input type="number" name="max_stock" class="form-control" required min="0" value="0">
                            </div>
                        </div>

                        <!-- SUSTANCIA PELIGROSA -->
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-2">
                                <span class="font-weight-bold text-dark">
                                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>Sustancia Peligrosa
                                </span>
                                <div class="custom-control custom-switch mb-0">
                                    <input type="hidden" name="is_hazardous" value="0">
                                    <input type="checkbox" class="custom-control-input" id="is_hazardous" name="is_hazardous" value="1">
                                    <label class="custom-control-label" for="is_hazardous"></label>
                                </div>
                            </div>
                            <div class="card-body pt-0" id="hazard-details" style="display:none;">
                                <div class="mb-3">
                                    <label class="font-weight-bold text-dark">Clase de Peligro</label>
                                    <select name="hazard_class" class="custom-select">
                                        <option value="">Seleccionar...</option>
                                        <option value="inflamable">Inflamable</option>
                                        <option value="corrosivo">Corrosivo</option>
                                        <option value="toxico">Tóxico</option>
                                        <option value="oxidante">Oxidante</option>
                                        <option value="explosivo">Explosivo</option>
                                        <option value="irritante">Irritante</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="font-weight-bold text-dark">Hoja de Seguridad (PDF/JPG/PNG)</label>
                                    <input type="file" name="safety_data_sheet" class="form-control"
                                           accept=".pdf,image/jpeg,image/png,image/jpg">
                                    <small class="text-muted">Máx. 10 MB. Formatos: PDF, JPG, PNG</small>
                                </div>
                            </div>
                        </div>

                        <?= CustomFieldRenderer::render('inventory', 0) ?>

                        <!-- BOTONES -->
                        <div class="text-center mt-4 btn-container-mobile">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                Guardar
                            </button>
                            <a href="index.php?page=insumos_list" class="btn btn-secondary btn-lg px-5">
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

    .border-dashed {
        border-style: dashed !important;
    }
</style>

<script>
    // Validar formato de imagen
    $('#image_path').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG, PNG y WebP', 'error');
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
            const reader = new FileReader();
            reader.onload = e => {
                $('#preview-img').attr('src', e.target.result).show();
                $('.border-dashed').html('<i class="fas fa-check text-success fa-2x"></i>');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seleccionar',
            allowClear: true
        });

        // Toggle sección sustancia peligrosa
        $('#is_hazardous').on('change', function() {
            if ($(this).is(':checked')) {
                $('#hazard-details').slideDown(200);
            } else {
                $('#hazard-details').slideUp(200);
                $('select[name="hazard_class"]').val('');
                $('input[name="safety_data_sheet"]').val('');
            }
        });
    });

    $('#manage_inventory').submit(function(e) {
        e.preventDefault();
        start_load();

        // Validar que stock no supere max_stock
        const stock = parseInt($('input[name="stock"]').val()) || 0;
        const max_stock = parseInt($('input[name="max_stock"]').val()) || 0;
        if (stock > max_stock && max_stock > 0) {
            alert_toast('El stock actual no puede superar el stock máximo', 'warning');
            end_load();
            return;
        }

        $.ajax({
            url: 'public/ajax/action.php?action=save_inventory',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                var res = {};
                try { res = JSON.parse(resp); } catch(e) {}
                if (res.s === 1) {
                    alert_toast('Ítem guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=insumos_list', 1500);
                } else {
                    alert_toast('Error: ' + (res.msg || resp), 'error');
                }
                end_load();
            },
            error: function() {
                alert_toast('Error de conexión', 'error');
                end_load();
            }
        });
    });
</script>
