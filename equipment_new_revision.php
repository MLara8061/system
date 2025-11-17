<?php
require_once 'config/config.php';

// === SEGURIDAD: Validar ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">ID inválido.</div>');
}
$id = (int)$_GET['id'];

// === Cargar datos del equipo ===
$qry = $conn->query("SELECT * FROM equipments WHERE id = $id");
if ($qry->num_rows == 0) die('<div class="alert alert-danger">Equipo no encontrado.</div>');
$eq = $qry->fetch_array();

// === Frecuencia actual ===
$frecuencia_actual = 30;
$qry_freq = $conn->query("SELECT frecuencia FROM equipment_revision WHERE equipment_id = $id ORDER BY date_revision DESC LIMIT 1");
if ($qry_freq->num_rows > 0) {
    $frecuencia_actual = $qry_freq->fetch_array()['frecuencia'];
}
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-5">

            <form id="manage_revision" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="">
                <input type="hidden" name="equipment_id" value="<?= $id ?>">

                <div class="row">
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Fecha de Revisión</label>
                            <input type="date" name="date_revision" class="form-control" required 
                                   value="<?= date('Y-m-d') ?>">
                            <div class="invalid-feedback">Selecciona una fecha.</div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">No. Inventario</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= htmlspecialchars($eq['number_inventory'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">No. Serie</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= htmlspecialchars($eq['serie'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Equipo</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= htmlspecialchars($eq['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Marca</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= htmlspecialchars($eq['brand'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Modelo</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= htmlspecialchars($eq['model'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA -->
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Características</label>
                            <textarea class="form-control" rows="4" disabled 
                                      style="resize: none;"><?= htmlspecialchars($eq['characteristics'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Valor del Equipo</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= number_format($eq['amount'] ?? 0, 2) ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Disciplina</label>
                            <input type="text" class="form-control" disabled 
                                   value="<?= htmlspecialchars($eq['discipline'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Tipo de Adquisición</label>
                            <select class="custom-select" disabled>
                                <option value="1" selected>Compra</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Frecuencia de Revisión</label>
                            <select name="frecuencia" class="custom-select select2" required>
                                <option value="7" <?= $frecuencia_actual == 7 ? 'selected' : '' ?>>Semanal</option>
                                <option value="15" <?= $frecuencia_actual == 15 ? 'selected' : '' ?>>Quincenal</option>
                                <option value="30" <?= $frecuencia_actual == 30 ? 'selected' : '' ?>>Mensual</option>
                                <option value="60" <?= $frecuencia_actual == 60 ? 'selected' : '' ?>>Bimensual</option>
                                <option value="90" <?= $frecuencia_actual == 90 ? 'selected' : '' ?>>Trimestral</option>
                                <option value="180" <?= $frecuencia_actual == 180 ? 'selected' : '' ?>>Semestral</option>
                                <option value="365" <?= $frecuencia_actual == 365 ? 'selected' : '' ?>>Anual</option>
                            </select>
                            <div class="invalid-feedback">Selecciona una frecuencia.</div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        Guardar Revisión
                    </button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary btn-lg px-5">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ESTILOS 100% IGUALES A PROVEEDORES -->
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
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    textarea.form-control {
        border-radius: 10px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style>

<!-- SCRIPTS -->
<script>
    $(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seleccionar',
            allowClear: false
        });
    });

    // Validación Bootstrap
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();

    // Envío AJAX
    $('#manage_revision').submit(function(e) {
        e.preventDefault();
        if (!this.checkValidity()) return;

        start_load();
        $.ajax({
            url: 'ajax.php?action=save_equipment_revision',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp) {
                resp = resp.trim();
                end_load();
                if (resp == 1) {
                    alert_toast('Revisión guardada correctamente', 'success');
                    setTimeout(() => location.href = 'index.php?page=equipment_list', 1000);
                } else {
                    alert_toast('Error al guardar', 'error');
                }
            },
            error: function() {
                end_load();
                alert_toast('Error de conexión', 'error');
            }
        });
    });
</script>