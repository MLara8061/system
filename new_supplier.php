<?php require_once 'config/config.php'; ?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-5">

            <form id="manage_supplier" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>">

                <div class="row">
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Empresa</label>
                            <input type="text" name="empresa" class="form-control" required 
                                   placeholder="Nombre de la empresa" value="<?= $supplier['empresa'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Sitio Web</label>
                            <input type="text" name="sitio_web" id="sitio_web" class="form-control" 
                                   placeholder="Ej: google.com" 
                                   value="<?= htmlspecialchars($supplier['sitio_web'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">RFC</label>
                            <input type="text" name="rfc" class="form-control" maxlength="13" 
                                   style="text-transform:uppercase;" 
                                   placeholder="RFC" value="<?= $supplier['rfc'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Sector</label>
                            <input type="text" name="sector" class="form-control" 
                                   placeholder="Ej: Tecnología, Médico" 
                                   value="<?= $supplier['sector'] ?? '' ?>">
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Representante</label>
                            <input type="text" name="representante" class="form-control" 
                                   placeholder="Nombre del contacto" 
                                   value="<?= $supplier['representante'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Teléfono</label>
                            <input type="text" name="telefono" class="form-control solonumeros" maxlength="10" 
                                   placeholder="10 dígitos" value="<?= $supplier['telefono'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Correo</label>
                            <input type="email" name="correo" class="form-control" 
                                   placeholder="contacto@empresa.com" 
                                   value="<?= $supplier['correo'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Estado</label>
                            <select name="estado" class="custom-select select2">
                                <option value="1" <?= ($supplier['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($supplier['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Notas</label>
                            <textarea name="notas" class="form-control" rows="4" 
                                      placeholder="Información adicional..."><?= $supplier['notas'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        Guardar Proveedor
                    </button>
                    <a href="index.php?page=suppliers" class="btn btn-secondary btn-lg px-5">
                        Cancelar
                    </a>
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
    .card { background: #fff; }
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
    // Solo números
    $('.solonumeros').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Limpiar sitio web
    $('#sitio_web').on('blur', function() {
        let val = this.value.trim();
        val = val.replace(/^https?:\/\//i, '')
                 .replace(/^http?:\/\//i, '')
                 .replace(/^www\./i, '');
        this.value = val;
    });

    // Select2
    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seleccionar',
            allowClear: false
        });
    });

    // Enviar
    $('#manage_supplier').submit(function(e) {
        e.preventDefault();

        if (!$('input[name="empresa"]').val().trim()) {
            alert_toast('La empresa es obligatoria', 'error');
            return;
        }

        start_load();
        $.ajax({
            url: 'ajax.php?action=save_supplier',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                end_load();
                resp = resp.trim();
                if (resp == 1) {
                    alert_toast('Proveedor guardado correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=suppliers', 1200);
                } else if (resp == 2) {
                    alert_toast('La empresa es obligatoria', 'error');
                } else if (resp == 5) {
                    alert_toast('RFC ya registrado', 'error');
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