<?php include 'db_connect.php'; ?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">

            <form id="manage-tool" enctype="multipart/form-data">
                <input type="hidden" name="keep_image" value="1">

                <div class="row g-0">
                    <!-- IMAGEN A LA IZQUIERDA -->
                    <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                        <div class="text-center w-100">
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center" 
                                 style="height: 380px; border: 3px dashed #ccc;">
                                <i class="fas fa-tools fa-3x text-muted"></i>
                            </div>
                            <input type="file" name="imagen" class="form-control mt-3" accept="image/*" onchange="displayImg(this)">
                            <img id="preview-img" src="" alt="" class="img-fluid rounded shadow mt-3" 
                                 style="display:none; max-height: 200px;">
                        </div>
                    </div>

                    <!-- DATOS A LA DERECHA -->
                    <div class="col-lg-7 p-5">

                        <!-- NOMBRE -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Nombre de la Herramienta</label>
                            <input type="text" name="nombre" class="form-control" required 
                                   placeholder="Ej: Taladro Inalámbrico">
                        </div>

                        <!-- MARCA Y COSTO -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Marca</label>
                                <input type="text" name="marca" class="form-control" placeholder="Ej: DeWalt">
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Costo (MXN)</label>
                                <input type="number" step="0.01" min="0" name="costo" class="form-control" required 
                                       placeholder="0.00">
                            </div>
                        </div>

                        <!-- FECHA ADQUISICIÓN -->
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">Fecha de Adquisición</label>
                            <input type="date" name="fecha_adquisicion" class="form-control" required 
                                   value="<?= date('Y-m-d') ?>">
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
                                    <option value="<?= $row['id'] ?>"><?= ucwords($row['empresa']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- ESTATUS Y FECHA BAJA -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Estatus</label>
                                <select name="estatus" class="custom-select select2">
                                    <option value="Activa" selected>Activa</option>
                                    <option value="Inactiva">Inactiva</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="font-weight-bold text-dark">Fecha de Baja (si aplica)</label>
                                <input type="date" name="fecha_baja" class="form-control">
                            </div>
                        </div>

                        <!-- CARACTERÍSTICAS -->
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0">
                                <h6 class="mb-0 text-dark">Características Técnicas</h6>
                            </div>
                            <div class="card-body">
                                <textarea name="caracteristicas" class="form-control" rows="3" 
                                          placeholder="Detalles técnicos, uso, accesorios..."></textarea>
                            </div>
                        </div>

                        <!-- BOTONES -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                Guardar Herramienta
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
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
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
                    alert_toast('Herramienta guardada correctamente', 'success');
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