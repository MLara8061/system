<?php
include 'db_connect.php';
$id = $_GET['id'] ?? '';
if (empty($id)) {
    echo "<script>location.href='index.php?page=suppliers';</script>";
    exit;
}
$qry = $conn->query("SELECT * FROM suppliers WHERE id = " . (int)$id);
if ($qry->num_rows == 0) {
    echo "<script>alert('Proveedor no encontrado'); location.href='index.php?page=suppliers';</script>";
    exit;
}
$supplier = $qry->fetch_assoc();
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-5">

            <form id="manage_supplier" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row">
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Empresa</label>
                            <input type="text" name="empresa" class="form-control" required 
                                   value="<?= htmlspecialchars($supplier['empresa']) ?>" 
                                   placeholder="Nombre de la empresa">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Sitio Web</label>
                            <input type="text" name="sitio_web" id="sitio_web" class="form-control" 
                                   value="<?= htmlspecialchars($supplier['sitio_web'] ?? '') ?>" 
                                   placeholder="Ej: google.com">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">RFC</label>
                            <input type="text" name="rfc" class="form-control" maxlength="13" 
                                   style="text-transform:uppercase;" 
                                   value="<?= htmlspecialchars($supplier['rfc'] ?? '') ?>" 
                                   placeholder="RFC">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Sector</label>
                            <input type="text" name="sector" class="form-control" 
                                   value="<?= htmlspecialchars($supplier['sector'] ?? '') ?>" 
                                   placeholder="Ej: Tecnología, Médico">
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Representante</label>
                            <input type="text" name="representante" class="form-control" 
                                   value="<?= htmlspecialchars($supplier['representante'] ?? '') ?>" 
                                   placeholder="Nombre del contacto">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Teléfono</label>
                            <input type="text" name="telefono" class="form-control solonumeros" maxlength="10" 
                                   value="<?= htmlspecialchars($supplier['telefono'] ?? '') ?>" 
                                   placeholder="10 dígitos">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Correo</label>
                            <input type="email" name="correo" class="form-control" 
                                   value="<?= htmlspecialchars($supplier['correo'] ?? '') ?>" 
                                   placeholder="contacto@empresa.com">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Estado</label>
                            <select name="estado" class="custom-select select2">
                                <option value="1" <?= $supplier['estado'] == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $supplier['estado'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Notas</label>
                            <textarea name="notas" class="form-control" rows="4" 
                                      placeholder="Información adicional..."><?= htmlspecialchars($supplier['notas'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        Actualizar Proveedor
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
        if (val && !val.includes('.')) val = '';
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

    // Enviar edición
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
                    alert_toast('Proveedor actualizado correctamente', 'success');
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