<?php require_once 'config/config.php'; ?>

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

                <div class="row g-0">
                    <!-- IMAGEN IZQUIERDA -->
                    <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                        <div class="text-center w-100">
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center"
                                style="height: 380px; border: 3px dashed #ccc;">
                                <i class="fas fa-box fa-3x text-muted"></i>
                            </div>
                            <input type="file" name="image_path" id="image_path" class="form-control mt-3" accept="image/jpeg,image/png,image/jpg" onchange="displayImg(this)">
                            <small class="text-muted d-block mt-1">Formatos permitidos: JPG, PNG (máx. 5MB)</small>
                            <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3"
                                style="display:none; max-height: 200px;">
                        </div>
                    </div>

                    <!-- DATOS DERECHA -->
                    <div class="col-lg-7 p-5">
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

                        <!-- BOTONES -->
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                Guardar
                            </button>
                            <a href="index.php?page=inventory_list" class="btn btn-secondary btn-lg px-5">
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
            url: 'ajax.php?action=save_inventory',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                resp = resp.trim();
                if (resp === '1') {
                    alert_toast('Ítem guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=inventory_list', 1500);
                } else {
                    alert_toast('Error: ' + resp, 'error');
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