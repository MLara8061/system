<?php
require_once 'config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">ID inválido.</div>');
}
$id = (int)$_GET['id'];

$qry = $conn->query("SELECT * FROM equipments WHERE id = $id");
if ($qry->num_rows == 0) die('<div class="alert alert-danger">Equipo no encontrado.</div>');
$eq = $qry->fetch_array();

// === GENERAR ORDEN DE SERVICIO ===
$year = date('Y');
$next = $conn->query("SELECT COALESCE(MAX(CAST(SUBSTRING(orden_servicio, 9) AS UNSIGNED)), 0) + 1 AS next FROM equipment_report_sistem WHERE orden_servicio LIKE 'OS-$year-%'")->fetch_array()['next'];
$orden_servicio = "OS-$year-" . str_pad($next, 3, '0', STR_PAD_LEFT);

// === CARGAR INVENTARIO ===
$inventory = [];
$qry_inv = $conn->query("SELECT id, name, stock FROM inventory ORDER BY name");
while ($row = $qry_inv->fetch_array()) {
    $inventory[] = $row;
}
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 font-weight-bold text-dark">Reporte de Sistemas - Equipo #<?= $id ?></h4>
            <div class="badge badge-primary fs-5 px-3 py-2">Folio: <?= $orden_servicio ?></div>
        </div>
        <div class="card-body p-5">

            <form action="equipment_report_sistem_add.php" method="POST">

                <!-- === CAMPOS OCULTOS === -->
                <input type="hidden" name="orden_servicio" value="<?= $orden_servicio ?>">
                <input type="hidden" name="nombre" value="<?= htmlspecialchars($eq['name']) ?>">
                <input type="hidden" name="numero_inv" value="<?= htmlspecialchars($eq['number_inventory']) ?>">
                <input type="hidden" name="serie" value="<?= htmlspecialchars($eq['serie']) ?>">
                <input type="hidden" name="modelo" value="<?= htmlspecialchars($eq['model']) ?>">
                <input type="hidden" name="marca" value="<?= htmlspecialchars($eq['brand']) ?>">

                <!-- === SECCIÓN 1: SERVICIOS === -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Servicios a Realizar</h5>
                        <div class="row">
                            <?php $servicios = ['Correctivo', 'Preventivo', 'Capacitacion', 'Operativo', 'Programado', 'Incidencias']; ?>
                            <?php foreach ($servicios as $s): ?>
                            <div class="form-check form-check-inline col-6 mb-2">
                                <input class="form-check-input" type="radio" name="tipo_servicio" value="<?= $s ?>" id="<?= strtolower($s) ?>">
                                <label class="form-check-label" for="<?= strtolower($s) ?>"><?= $s ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Datos del Equipo</h5>
                        <table class="table table-sm">
                            <tr><th class="w-50">Nombre:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($eq['name']) ?>"></td></tr>
                            <tr><th>N° Inventario:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($eq['number_inventory']) ?>"></td></tr>
                            <tr><th>N° Serie:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($eq['serie']) ?>"></td></tr>
                            <tr><th>Modelo:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($eq['model']) ?>"></td></tr>
                            <tr><th>Marca:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($eq['brand']) ?>"></td></tr>
                        </table>
                    </div>
                </div>

                <hr class="my-4">

                <!-- === SECCIÓN: FECHA Y HORARIOS === -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Fecha y Horario del Servicio</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="font-weight-bold">Fecha del Servicio</label>
                                <input type="date" name="fecha_servicio" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="font-weight-bold">Hora de Inicio</label>
                                <input type="time" name="hora_inicio" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="font-weight-bold">Hora de Término</label>
                                <input type="time" name="hora_fin" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Fecha de Entrega Tentativa</h5>
                        <div class="form-group">
                            <input type="date" name="fecha_entrega" class="form-control" required>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- === RIESGOS === -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Evaluación de Riesgos</h5>
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr><th>Evaluación</th><th>Sí</th><th>No</th></tr>
                            </thead>
                            <tbody>
                                <?php $riesgos = ['Formato de Obra', 'Evaluación de Incendio', 'Delimitación de Área']; ?>
                                <?php foreach ($riesgos as $i => $r): ?>
                                <tr>
                                    <td><?= $r ?></td>
                                    <td><input type="radio" name="riesgo<?= $i+1 ?>" value="1"></td>
                                    <td><input type="radio" name="riesgo<?= $i+1 ?>" value="0" checked></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr class="my-4">

                <!-- === DESCRIPCIÓN === -->
                <div class="row mb-5">
                    <div class="col-12">
                        <h5 class="font-weight-bold text-dark mb-3">Descripción Completa del Servicio</h5>
                        <div class="form-group">
                            <label>Mantenimiento Preventivo</label>
                            <input name="mantenimientoPreventivo" type="text" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Limpieza de Unidad de Riesgo</label>
                            <input name="unidad_riesgo" type="text" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Limpieza de Componentes</label>
                            <input name="componentes" type="text" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Extracción de Toner Residual</label>
                            <input name="toner" type="text" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Impresión de Pruebas</label>
                            <input name="impresiom_pruebas" type="text" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- === ESTADO DEL EQUIPO === -->
                <div class="form-group mb-5">
                    <label class="font-weight-bold">Condiciones en las que se deja el equipo:</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="estado_equipo" value="funcionando" required>
                        <label class="form-check-label">Funcionando</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="estado_equipo" value="parcial">
                        <label class="form-check-label">Funcionando Parcialmente</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="estado_equipo" value="retirado">
                        <label class="form-check-label">Retirado</label>
                    </div>
                </div>

                <hr class="my-4">

                <!-- === MATERIAL UTILIZADO === -->
                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="font-weight-bold text-dark mb-0">Material Utilizado</h5>
                        <button type="button" id="add_material" class="btn btn-sm btn-outline-primary">+ Añadir</button>
                    </div>
                    <table class="table table-bordered" id="material_table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 15%">Cantidad</th>
                                <th style="width: 60%">Material</th>
                                <th style="width: 15%">Stock</th>
                                <th style="width: 10%"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- === OBSERVACIONES === -->
                <div class="form-group mb-5">
                    <label class="font-weight-bold">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="4" placeholder="Notas adicionales..."></textarea>
                </div>

                <!-- === BOTONES === -->
                <hr class="my-4">
                <div class="text-center btn-container-mobile">
                    <button type="submit" class="btn btn-primary btn-lg px-5">Guardar Reporte</button>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary btn-lg px-5">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- === ESTILOS === -->
<style>
    .form-control, .table input, .table select {
        border-radius: 10px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-control:focus, .table input:focus, .table select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .select2-container--default .select2-selection--single {
        border-radius: 10px !important;
        height: 38px;
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .stock-ok { color: green; font-weight: bold; }
    .stock-no { color: red; font-weight: bold; }
</style>

<!-- === SCRIPT === -->
<script>
    const inventory = <?= json_encode($inventory ?? []) ?>;
    let materialCount = 0;

    function addMaterialRow() {
        if (materialCount >= 2) {
            alert_toast('Máximo 2 materiales permitidos.', 'warning');
            return;
        }
        const row = `
            <tr>
                <td><input type="number" name="material_qty[]" class="form-control material-qty" min="1" value="1"></td>
                <td>
                    <select name="material_id[]" class="form-control select2 material-select">
                        <option value="">Seleccionar</option>
                        ${inventory.map(i => `<option value="${i.id}" data-stock="${i.stock}">${i.name} (Stock: ${i.stock})</option>`).join('')}
                    </select>
                </td>
                <td class="text-center"><span class="stock-status">-</span></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
            </tr>`;
        $('#material_table tbody').append(row);
        $('.select2').last().select2({ width: '100%' });
        updateStockStatus($('#material_table tbody tr:last'));
        materialCount++;
    }

    function updateStockStatus(row) {
        const $row = $(row);
        const qty = parseInt($row.find('.material-qty').val()) || 0;
        const stock = parseInt($row.find('.material-select').find(':selected').data('stock')) || 0;
        const $status = $row.find('.stock-status');
        $status.html(qty <= stock ? 'check' : 'X')
               .toggleClass('stock-ok', qty <= stock)
               .toggleClass('stock-no', qty > stock);
    }

    $(document).on('input change', '.material-qty, .material-select', function() {
        updateStockStatus($(this).closest('tr'));
    });

    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        materialCount--;
    });

    $('#add_material').click(addMaterialRow);

    $('form').submit(function(e) {
        let valid = true;
        $('#material_table tbody tr').each(function() {
            const qty = parseInt($(this).find('.material-qty').val()) || 0;
            const stock = parseInt($(this).find('.material-select').find(':selected').data('stock')) || 0;
            if (qty > stock) valid = false;
        });
        if (!valid) {
            e.preventDefault();
            alert_toast('Stock insuficiente.', 'error');
        }
    });

    $(function() {
        if (inventory.length > 0) addMaterialRow();
        $('input[type="date"]').val(new Date().toISOString().split('T')[0]);
    });
</script>