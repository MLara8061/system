<?php
define('ACCESS', true);

if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/../../..'));
}

// Usar rutas absolutas para soportar acceso directo a /app/views/pages/view_inventory.php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

if (!validate_session()) {
    if (defined('IS_MODAL') && IS_MODAL) {
        http_response_code(401);
        header('Content-Type: text/html; charset=utf-8');
        echo '<div class="alert alert-warning mb-0">Sesión expirada. Recarga la página e inicia sesión nuevamente.</div>';
        exit;
    }
    header('location: ' . rtrim(BASE_URL, '/') . '/app/views/auth/login.php');
    exit;
}
?>
<?php
$id = $_GET['id'] ?? 0;
$qry = $conn->query("SELECT * FROM inventory WHERE id = " . intval($id));
if (!$qry || $qry->num_rows == 0) {
    echo "<div class='alert alert-danger'>Ítem no encontrado.</div>";
    exit;
}
$row = $qry->fetch_assoc();
?>

<div class="container-fluid">
    <form id="update-inventory-form">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <input type="hidden" name="delete_image" value="0" id="delete_image_flag">
        
        <div class="row">
            <!-- IMAGEN -->
            <div class="col-md-4 text-center mb-3">
                <div id="image-preview" class="position-relative d-inline-block">
                    <?php
                    $baseUrl = rtrim(BASE_URL, '/');
                    $relUploadPath = (!empty($row['image_path'])) ? ('uploads/' . $row['image_path']) : '';
                    $fullUploadPath = (!empty($relUploadPath)) ? (ROOT . '/' . $relUploadPath) : '';
                    $hasImage = (!empty($relUploadPath) && is_string($fullUploadPath) && file_exists($fullUploadPath));
                    ?>
                    <?php if ($hasImage): ?>
                        <div class="position-relative d-inline-block inventory-image-wrapper">
                            <img src="<?= $baseUrl . '/' . $relUploadPath ?>" 
                                 class="img-fluid rounded inventory-image" 
                                 style="max-height: 180px;" 
                                 id="current-inv-img">
                            <!-- Botón eliminar imagen -->
                            <button type="button" 
                                    class="inventory-delete-btn" 
                                    id="remove-inv-image"
                                    title="Eliminar imagen">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="bg-light border rounded d-flex align-items-center justify-content-center" 
                             style="height:180px;" id="empty-inv-image">
                            <i class="fas fa-box fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3" id="upload-inv-container" 
                     style="display: <?= $hasImage ? 'none' : 'block' ?>;">
                    <!-- Botón cámara moderno -->
                    <label for="image-input" class="inventory-camera-btn">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" name="image_path" id="image-input" 
                           class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp">
                    <small class="text-muted d-block mt-2">Haz clic para añadir imagen</small>
                    <img id="preview-inv-img" src="" alt="" 
                         class="img-fluid rounded mt-2" 
                         style="display:none; max-height: 120px;">
                </div>
            </div>

            <!-- CAMPOS EDITABLES -->
            <div class="col-md-8">
                <div class="form-group">
                    <label for="inv-name"><strong>Nombre</strong></label>
                    <input type="text" name="name" id="inv-name" class="form-control" value="<?= ucwords($row['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="inv-category"><strong>Categoría</strong></label>
                    <input type="text" name="category" id="inv-category" class="form-control" value="<?= ucwords($row['category'] ?? '') ?>" placeholder="Ej: Papelería">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="inv-price"><strong>Precio</strong></label>
                            <input type="number" name="price" id="inv-price" class="form-control" value="<?= $row['price'] ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="inv-cost"><strong>Costo</strong></label>
                            <input type="number" name="cost" id="inv-cost" class="form-control" value="<?= $row['cost'] ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="stock"><strong>Stock</strong></label>
                            <input type="number" name="stock" id="stock" class="form-control" value="<?= $row['stock'] ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="min_stock"><strong>Mín Stock</strong></label>
                            <input type="number" name="min_stock" id="min_stock" class="form-control" value="<?= $row['min_stock'] ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="max_stock"><strong>Máx Stock</strong></label>
                            <input type="number" name="max_stock" id="max_stock" class="form-control" value="<?= $row['max_stock'] ?>" min="0" required>
                        </div>
                    </div>
                </div>

                <!-- STATUS AUTOMÁTICO -->
                <div class="form-group">
                    <label for="status-preview"><strong>Status</strong></label>
                    <div id="status-preview" class="mt-2">
                        <?php
                        if ($row['stock'] == 0) echo '<span class="badge badge-danger">Sin Stock</span>';
                        elseif ($row['stock'] <= $row['min_stock']) echo '<span class="badge badge-warning">Bajo</span>';
                        else echo '<span class="badge badge-success">Suficiente</span>';
                        ?>
                    </div>
                </div>

                <!-- SUSTANCIA PELIGROSA -->
                <div class="card border-warning mt-3 mb-0">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-2">
                        <span class="font-weight-bold">
                            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>Sustancia Peligrosa
                        </span>
                        <div class="custom-control custom-switch mb-0">
                            <input type="hidden" name="is_hazardous" value="0">
                            <input type="checkbox" class="custom-control-input" id="edit_is_hazardous" name="is_hazardous" value="1"
                                   <?= !empty($row['is_hazardous']) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="edit_is_hazardous"></label>
                        </div>
                    </div>
                    <div class="card-body pt-0" id="edit-hazard-details"
                         style="display:<?= !empty($row['is_hazardous']) ? 'block' : 'none' ?>;">
                        <div class="form-group mb-2">
                            <label><strong>Clase de Peligro</strong></label>
                            <select name="hazard_class" class="form-control form-control-sm">
                                <option value="">Seleccionar...</option>
                                <?php
                                $hazardClasses = ['inflamable' => 'Inflamable', 'corrosivo' => 'Corrosivo', 'toxico' => 'Tóxico',
                                                  'oxidante' => 'Oxidante', 'explosivo' => 'Explosivo', 'irritante' => 'Irritante', 'otro' => 'Otro'];
                                foreach ($hazardClasses as $val => $label):
                                ?>
                                    <option value="<?= $val ?>" <?= ($row['hazard_class'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($row['safety_data_sheet'])): ?>
                        <div class="mb-2">
                            <?php
                                $sds_path = ltrim($row['safety_data_sheet'], '/');
                                $sds_url = rtrim(BASE_URL, '/') . '/' . $sds_path;
                                $full_path = ROOT . '/' . $sds_path;
                                $file_exists = file_exists($full_path);
                            ?>
                            <?php if ($file_exists): ?>
                                <a href="<?= htmlspecialchars($sds_url) ?>"
                                   target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-warning" title="Descargar o abrir hoja de seguridad">
                                    <i class="fas fa-file-pdf mr-1"></i> Ver Hoja de Seguridad
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Archivo no disponible">
                                    <i class="fas fa-file-pdf mr-1"></i> Hoja no disponible
                                </button>
                                <small class="text-muted d-block">Archivo no encontrado en servidor</small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="form-group mb-0">
                            <label><strong>Reemplazar Hoja de Seguridad</strong></label>
                            <input type="file" name="safety_data_sheet" class="form-control form-control-sm"
                                   accept=".pdf,image/jpeg,image/png,image/jpg">
                            <small class="text-muted">Máx. 10 MB. PDF, JPG, PNG</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    #image-preview img { 
        max-width: 100%; 
        border-radius: 8px; 
    }
    .form-control { font-size: 0.9rem; }
    .btn { min-width: 120px; }

    /* === ESTILOS MODERNOS PARA IMAGEN DE INVENTARIO === */
    .inventory-image-wrapper {
        position: relative;
        display: inline-block;
    }

    .inventory-image {
        max-height: 180px;
        object-fit: contain;
        border-radius: 8px;
    }

    /* Botón cámara */
    .inventory-camera-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        border: none;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 0;
    }

    .inventory-camera-btn:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
    }

    .inventory-camera-btn:active {
        transform: scale(0.95);
    }

    .inventory-camera-btn i {
        font-size: 18px;
    }

    /* Botón eliminar */
    .inventory-delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
        width: 30px;
        height: 30px;
        border: none;
        border-radius: 50%;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .inventory-delete-btn:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 25px rgba(245, 87, 108, 0.6);
    }

    .inventory-delete-btn:active {
        transform: scale(0.95);
    }

    .inventory-delete-btn i {
        font-size: 12px;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .inventory-camera-btn {
            width: 40px;
            height: 40px;
        }

        .inventory-camera-btn i {
            font-size: 16px;
        }

        .inventory-delete-btn {
            width: 26px;
            height: 26px;
        }

        .inventory-delete-btn i {
            font-size: 10px;
        }
    }
</style>

<script>
    const baseUrl = '<?= rtrim(BASE_URL, '/') ?>';

    // Validar formato de imagen
    $('#image-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG, PNG y WebP', 'error');
                $(this).val('');
                $('#preview-inv-img').hide();
                return false;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert_toast('La imagen es muy grande. Máximo 5MB', 'error');
                $(this).val('');
                $('#preview-inv-img').hide();
                return false;
            }
            
            // Previsualizar
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-inv-img').attr('src', e.target.result).show();
                $('#empty-inv-image').hide();
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).ready(function() {
    // === ELIMINAR IMAGEN ===
    $('#remove-inv-image').click(function() {
        if (confirm('¿Eliminar imagen actual?')) {
            $('#current-inv-img').parent().remove();
            $(this).remove();
            $('#empty-inv-image').remove();
            $('#upload-inv-container').show();
            $('#delete_image_flag').val('1');
        }
    });

    // === ACTUALIZAR STATUS EN TIEMPO REAL ===
    function updateStatus() {
        const stock = parseInt($('#stock').val()) || 0;
        const min = parseInt($('#min_stock').val()) || 0;
        let badge = '';
        if (stock == 0) badge = '<span class="badge badge-danger">Sin Stock</span>';
        else if (stock <= min) badge = '<span class="badge badge-warning">Bajo</span>';
        else badge = '<span class="badge badge-success">Suficiente</span>';
        $('#status-preview').html(badge);
    }
    $('#stock, #min_stock').on('input', updateStatus);

    // === GUARDAR CON AJAX ===
    $('#update-inventory-form').submit(function(e) {
        e.preventDefault();
        start_load();

        const formData = new FormData(this);
        formData.append('action', 'save_inventory');

        $.ajax({
            url: baseUrl + '/public/ajax/action.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Actualizado correctamente", 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert_toast("Error al guardar", 'error');
                    end_load();
                }
            },
            error: function() {
                alert_toast("Error de conexión", 'error');
                end_load();
            }
        });
    });

    // Toggle sección sustancia peligrosa
    $('#edit_is_hazardous').on('change', function() {
        if ($(this).is(':checked')) {
            $('#edit-hazard-details').slideDown(200);
        } else {
            $('#edit-hazard-details').slideUp(200);
            $('select[name="hazard_class"]').val('');
            $('input[name="safety_data_sheet"]').val('');
        }
    });
});
</script>
