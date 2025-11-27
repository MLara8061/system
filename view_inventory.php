<?php define('ACCESS', true); require_once 'config/config.php'; ?>
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
                    <?php if (!empty($row['image_path']) && file_exists('uploads/'.$row['image_path'])): ?>
                        <div class="position-relative d-inline-block">
                            <img src="uploads/<?= $row['image_path'] ?>" 
                                 class="img-fluid rounded" 
                                 style="max-height: 180px;" 
                                 id="current-inv-img">
                            <button type="button" 
                                    class="btn btn-danger btn-sm position-absolute" 
                                    style="top: 5px; right: 5px; z-index: 10; padding: 2px 6px;" 
                                    id="remove-inv-image">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <br><small class="text-muted">Haz clic para eliminar</small>
                    <?php else: ?>
                        <div class="bg-light border rounded d-flex align-items-center justify-content-center" 
                             style="height:180px;" id="empty-inv-image">
                            <i class="fas fa-box fa-3x text-muted"></i>
                        </div>
                        <br><small class="text-muted">Sin imagen</small>
                    <?php endif; ?>
                </div>
                <div class="mt-2" id="upload-inv-container" 
                     style="display: <?= (!empty($row['image_path']) && file_exists('uploads/'.$row['image_path'])) ? 'none' : 'block' ?>;">
                    <input type="file" name="image_path" id="image-input" 
                           class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                    <small class="text-muted d-block mt-1">Formatos: JPG, PNG (máx. 5MB)</small>
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
            </div>
        </div>
    </form>
</div>

<style>
    #image-preview img { 
        max-width: 100%; 
        border: 2px dashed #ddd; 
        border-radius: 8px; 
    }
    #image-input { font-size: 0.85rem; }
    .form-control { font-size: 0.9rem; }
    .btn { min-width: 120px; }
</style>

<script>
    // Validar formato de imagen
    $('#image-input').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG y PNG', 'error');
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
            url: 'ajax.php',
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
});
</script>