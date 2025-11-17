<?php
require_once 'config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">ID inválido.</div>');
}
$id = (int)$_GET['id'];

// Cargar reporte
$qry = $conn->query("SELECT * FROM equipment_report_sistem WHERE id = $id");
if ($qry->num_rows == 0) die('<div class="alert alert-danger">Reporte no encontrado.</div>');
$report = $qry->fetch_array();

// Cargar inventario
$inventory = [];
$qry_inv = $conn->query("SELECT id, name, stock FROM inventory ORDER BY name");
while ($row = $qry_inv->fetch_array()) {
    $inventory[] = $row;
}
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 font-weight-bold text-dark">Editar Reporte #<?= $id ?></h4>
            <div class="badge badge-primary fs-5 px-3 py-2">Folio: <?= htmlspecialchars($report['orden_servicio']) ?></div>
        </div>
        <div class="card-body p-5">

            <form action="equipment_report_sistem_update.php" method="POST">

                <!-- ID oculto -->
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="orden_servicio" value="<?= htmlspecialchars($report['orden_servicio']) ?>">

                <!-- === DATOS DEL EQUIPO (NO EDITABLES) === -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Datos del Equipo</h5>
                        <table class="table table-sm">
                            <tr><th class="w-50">Nombre:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($report['nombre']) ?>"></td></tr>
                            <tr><th>N° Inventario:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($report['numero_inv']) ?>"></td></tr>
                            <tr><th>N° Serie:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($report['serie']) ?>"></td></tr>
                            <tr><th>Modelo:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($report['modelo']) ?>"></td></tr>
                            <tr><th>Marca:</th><td><input type="text" class="form-control" readonly value="<?= htmlspecialchars($report['marca']) ?>"></td></tr>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Tipo de Servicio</h5>
                        <div class="row">
                            <?php $tipos = ['Correctivo','Preventivo','Capacitacion','Operativo','Programado','Incidencias']; ?>
                            <?php foreach ($tipos as $t): ?>
                            <div class="form-check form-check-inline col-6 mb-2">
                                <input class="form-check-input" type="radio" name="tipo_servicio" value="<?= $t ?>" 
                                    id="edit_<?= strtolower($t) ?>" <?= $report['tipo_servicio'] == $t ? 'checked' : '' ?>>
                                <label class="form-check-label" for="edit_<?= strtolower($t) ?>"><?= $t ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- === FECHA Y HORARIOS === -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Fecha y Horario del Servicio</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label>Fecha del Servicio</label>
                                <input type="date" name="fecha_servicio" class="form-control" value="<?= $report['fecha_servicio'] ?>" required>
                            </div>
                            <div class="col-6">
                                <label>Hora de Inicio</label>
                                <input type="time" name="hora_inicio" class="form-control" value="<?= $report['hora_inicio'] ?>" required>
                            </div>
                            <div class="col-6">
                                <label>Hora de Término</label>
                                <input type="time" name="hora_fin" class="form-control" value="<?= $report['hora_fin'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="font-weight-bold text-dark mb-3">Fecha de Entrega Tentativa</h5>
                        <input type="date" name="fecha_entrega" class="form-control" value="<?= $report['fecha_entrega'] ?>" required>
                    </div>
                </div>

                <hr class="my-4">

                <!-- === DESCRIPCIÓN === -->
                <div class="row mb-5">
                    <div class="col-12">
                        <h5 class="font-weight-bold text-dark mb-3">Descripción del Servicio</h5>
                        <div class="form-group">
                            <label>Mantenimiento Preventivo</label>
                            <input name="mantenimientoPreventivo" type="text" class="form-control" value="<?= htmlspecialchars($report['mantenimientoPreventivo']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Limpieza de Unidad de Riesgo</label>
                            <input name="unidad_riesgo" type="text" class="form-control" value="<?= htmlspecialchars($report['unidad_riesgo']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Limpieza de Componentes</label>
                            <input name="componentes" type="text" class="form-control" value="<?= htmlspecialchars($report['componentes']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Extracción de Toner Residual</label>
                            <input name="toner" type="text" class="form-control" value="<?= htmlspecialchars($report['toner']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Impresión de Pruebas</label>
                            <input name="impresiom_pruebas" type="text" class="form-control" value="<?= htmlspecialchars($report['impresiom_pruebas']) ?>">
                        </div>
                    </div>
                </div>

                <!-- === MATERIAL UTILIZADO (EDITABLE) === -->
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
                        <tbody>
                            <?php
                            // Cargar materiales actuales
                            $current_materials = [];
                            if ($report['numero1']) {
                                $current_materials[] = ['qty' => $report['numero1'], 'name' => $report['material1']];
                            }
                            if ($report['numero2']) {
                                $current_materials[] = ['qty' => $report['numero2'], 'name' => $report['material2']];
                            }
                            foreach ($current_materials as $i => $m):
                                $item = array_filter($inventory, fn($inv) => $inv['name'] == $m['name']);
                                $item = reset($item);
                                $stock = $item['stock'] ?? 0;
                            ?>
                            <tr>
                                <td><input type="number" name="material_qty[]" class="form-control material-qty" min="1" value="<?= $m['qty'] ?>"></td>
                                <td>
                                    <select name="material_id[]" class="form-control select2 material-select">
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($inventory as $inv): ?>
                                        <option value="<?= $inv['id'] ?>" data-stock="<?= $inv['stock'] ?>" 
                                            <?= $inv['name'] == $m['name'] ? 'selected' : '' ?>>
                                            <?= $inv['name'] ?> (Stock: <?= $inv['stock'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="text-center"><span class="stock-status">-</span></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <!-- === BOTONES === -->
                <div class="text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5">Guardar Cambios</button>
                    <a href="index.php?page=equipment_report_sistem_list" class="btn btn-secondary btn-lg px-5">Cancelar</a>
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
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25);
    }
    .select2-container--default .select2-selection--single {
        border-radius: 10px !important;
        height: 38px;
        line-height: 36px;
    }
    .stock-ok { color: green; font-weight: bold; }
    .stock-no { color: red; font-weight: bold; }
</style>

<!-- === SCRIPT === -->
<script>
    const inventory = <?= json_encode($inventory) ?>;
    let materialCount = <?= count($current_materials) ?>;

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
        $('.select2').select2({ width: '100%' });
        $('#material_table tbody tr').each(function() {
            updateStockStatus(this);
        });
    });
</script>