<?php
$root = defined('ROOT') ? ROOT : realpath(__DIR__ . '/../../../../');
require_once $root . '/config/config.php';
require_once $root . '/app/helpers/company_config_helper.php';

$branch_id = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

// Obtener nombre de la sucursal activa
$branch_name = 'Sin sucursal';
if ($branch_id > 0) {
    $bStmt = $conn->prepare("SELECT name FROM branches WHERE id = ? LIMIT 1");
    if ($bStmt) {
        $bStmt->bind_param('i', $branch_id);
        $bStmt->execute();
        $bRes = $bStmt->get_result();
        $bRow = $bRes ? $bRes->fetch_assoc() : null;
        if ($bRow) $branch_name = $bRow['name'];
        $bStmt->close();
    }
}

$config = get_company_config($conn, $branch_id);
$logo_url = !empty($config['logo_path']) ? get_company_logo_url($conn, $branch_id) : '';
$logo_name = !empty($config['logo_path']) ? basename((string)$config['logo_path']) : '';
?>

<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Datos de Empresa</h3>
        </div>
        <div class="card-body">
            <?php if ($branch_id <= 0): ?>
                <div class="alert alert-warning">
                    Seleccione una sucursal para configurar los datos de empresa.
                </div>
            <?php else: ?>
            <form id="company_config_form" enctype="multipart/form-data">
                <input type="hidden" name="branch_id" value="<?= $branch_id ?>">

                <div class="row">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3">Informacion de Membrete (se imprime en reportes y PDFs)</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="font-weight-bold">Nombre / Razon Social de la Empresa</label>
                        <input type="text" name="company_name" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($config['company_name']) ?>"
                               placeholder="Ej: Venta, Mantenimiento Preventivo y Correctivo de Equipo Medico">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Direccion Linea 1</label>
                        <input type="text" name="address_line_1" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($config['address_line_1']) ?>"
                               placeholder="Ej: Calle 20 Manzana 58 Lote 23">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Direccion Linea 2</label>
                        <input type="text" name="address_line_2" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($config['address_line_2']) ?>"
                               placeholder="Ej: Fracc. Villas del Arte">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Ciudad, Estado, C.P.</label>
                        <input type="text" name="city_state_zip" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($config['city_state_zip']) ?>"
                               placeholder="Ej: Benito Juarez Cancun, Quintana Roo C.P 77560">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold">Telefono(s)</label>
                        <input type="text" name="phone_number" class="form-control" maxlength="255"
                               value="<?= htmlspecialchars($config['phone_number']) ?>"
                               placeholder="Ej: TEL: (998) 214 86 73">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="font-weight-bold">Descripcion / Giro de la Empresa</label>
                        <textarea name="company_description" class="form-control" rows="2" maxlength="500"
                                  placeholder="Ej: Empresa dedicada a la infraestructura tecnologica Redes | Computo | CCTV"><?= htmlspecialchars($config['company_description']) ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="font-weight-bold">Logo de la Organización</label>
                        <div class="border rounded p-3 bg-light">
                            <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                            <div id="current_logo_block" class="<?= !empty($logo_url) ? '' : 'd-none' ?> mb-3">
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border rounded bg-white p-3">
                                    <div>
                                        <div class="text-muted small mb-2">Logo guardado actualmente</div>
                                        <img id="current_logo_preview" src="<?= htmlspecialchars($logo_url) ?>"
                                             alt="Logo actual" style="max-height:110px; max-width:100%; object-fit:contain;">
                                        <div class="small text-muted mt-2" id="current_logo_name"><?= htmlspecialchars($logo_name) ?></div>
                                    </div>
                                    <div class="mt-3 mt-md-0 ml-md-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="remove_logo_btn">
                                            Eliminar logo
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div id="new_logo_preview_block" class="d-none mb-3">
                                <div class="border rounded bg-white p-3">
                                    <div class="text-muted small mb-2">Vista previa del archivo seleccionado</div>
                                    <img id="new_logo_preview" src="" alt="Vista previa del nuevo logo"
                                         style="max-height:110px; max-width:100%; object-fit:contain;">
                                    <div class="small text-muted mt-2" id="new_logo_name"></div>
                                </div>
                            </div>

                            <input type="file" name="logo_file" id="logo_file" class="form-control" accept="image/jpeg,image/png,image/jpg">
                            <small class="text-muted d-block mt-1">JPG/PNG, máximo 2 MB. Recomendado 400x200 px.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3 mt-3">Nomenclatura de Folios</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Prefijo Ordenes de Trabajo (Reportes)</label>
                        <input type="text" name="report_prefix" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($config['report_prefix']) ?>"
                               placeholder="O.T">
                        <small class="text-muted">Formato: PREFIJO-AÑO-MES-CONSECUTIVO</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Prefijo Bajas de Equipo</label>
                        <input type="text" name="unsubscribe_prefix" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($config['unsubscribe_prefix']) ?>"
                               placeholder="BAJA">
                        <small class="text-muted">Formato: PREFIJO-AÑO-MES-CONSECUTIVO</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold">Vista previa</label>
                        <div class="mt-2">
                            <span class="badge badge-info" id="preview_report">
                                <?= htmlspecialchars($config['report_prefix'] ?: 'O.T') ?>-<?= date('Y') ?>-<?= date('m') ?>-001
                            </span>
                            <br>
                            <span class="badge badge-warning mt-1" id="preview_unsub">
                                <?= htmlspecialchars($config['unsubscribe_prefix'] ?: 'BAJA') ?>-<?= date('Y') ?>-<?= date('m') ?>-001
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info py-2">
                            Los consecutivos se reinician automaticamente al inicio de cada mes.
                            Reportes actuales este mes: <strong><?= (int)$config['report_current_number'] ?></strong> |
                            Bajas actuales este mes: <strong><?= (int)$config['unsubscribe_current_number'] ?></strong>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuracion
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(function() {
    var hasCurrentLogo = <?= !empty($logo_url) ? 'true' : 'false' ?>;

    function resetNewLogoPreview() {
        $('#new_logo_preview').attr('src', '');
        $('#new_logo_name').text('');
        $('#new_logo_preview_block').addClass('d-none');
    }

    function setRemoveLogoState(shouldRemove) {
        $('#remove_logo').val(shouldRemove ? '1' : '0');
        if (hasCurrentLogo) {
            $('#current_logo_block').toggleClass('d-none', shouldRemove);
            $('#remove_logo_btn').text(shouldRemove ? 'Restaurar logo actual' : 'Eliminar logo');
            $('#remove_logo_btn').toggleClass('btn-outline-danger', !shouldRemove);
            $('#remove_logo_btn').toggleClass('btn-outline-secondary', shouldRemove);
        }
    }

    // Vista previa de folios al cambiar prefijo
    $('input[name="report_prefix"]').on('input', function() {
        var prefix = $(this).val() || 'O.T';
        $('#preview_report').text(prefix + '-<?= date("Y") ?>-<?= date("m") ?>-001');
    });
    $('input[name="unsubscribe_prefix"]').on('input', function() {
        var prefix = $(this).val() || 'BAJA';
        $('#preview_unsub').text(prefix + '-<?= date("Y") ?>-<?= date("m") ?>-001');
    });

    $('#logo_file').on('change', function() {
        var file = this.files && this.files[0] ? this.files[0] : null;
        resetNewLogoPreview();

        if (!file) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var src = (e && e.target && e.target.result) ? String(e.target.result) : '';
            if (!src) {
                return;
            }
            $('#new_logo_preview').attr('src', src);
            $('#new_logo_name').text(file.name);
            $('#new_logo_preview_block').removeClass('d-none');
            $('#remove_logo').val('0');
        };
        reader.readAsDataURL(file);
    });

    $('#remove_logo_btn').on('click', function() {
        if (!hasCurrentLogo) {
            return;
        }
        var shouldRemove = $('#remove_logo').val() !== '1';
        setRemoveLogoState(shouldRemove);
    });

    setRemoveLogoState(false);

    // Guardar configuracion
    $('#company_config_form').submit(function(e) {
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'public/ajax/action.php?action=save_company_config',
            data: new FormData(this),
            method: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(resp) {
                end_load();
                if (resp && resp.status === 1) {
                    alert_toast('Configuracion guardada correctamente.', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    alert_toast(resp.message || 'Error al guardar.', 'error');
                }
            },
            error: function() {
                end_load();
                alert_toast('Error de conexion.', 'error');
            }
        });
    });
});
</script>
