<?php
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    die('Acceso denegado.');
}
if (($_SESSION['login_type'] ?? 0) != 1) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Sin permisos para acceder a esta sección.</div></div>';
    return;
}

$entityLabels = [
    'equipment'  => 'Equipo',
    'tool'       => 'Herramienta',
    'accessory'  => 'Accesorio',
    'inventory'  => 'Insumo/Inventario',
];
$fieldTypeLabels = [
    'text'     => 'Texto',
    'number'   => 'Número',
    'date'     => 'Fecha',
    'select'   => 'Lista (select)',
    'textarea' => 'Área de texto',
    'checkbox' => 'Casilla',
];
?>

<div class="col-lg-12">

    <div class="card card-outline card-primary">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">
                <i class="fas fa-sliders-h mr-2"></i> Campos Personalizados
            </h5>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo">
                    <i class="fas fa-plus"></i> Nuevo Campo
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="tbl-campos" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:18%">Entidad</th>
                        <th>Etiqueta</th>
                        <th style="width:12%">Tipo</th>
                        <th style="width:10%">Orden</th>
                        <th style="width:8%">Req.</th>
                        <th style="width:8%">Activo</th>
                        <th style="width:14%">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- ===== Modal Crear / Editar ===== -->
<div class="modal fade" id="modal-campo" tabindex="-1" role="dialog" aria-labelledby="modal-campo-title">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="modal-campo-title">Nuevo Campo</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="campo-id" value="">

                <div class="form-group">
                    <label>Tipo de entidad <span class="text-danger">*</span></label>
                    <select class="form-control" id="campo-entity-type">
                        <?php foreach ($entityLabels as $val => $lbl): ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nombre interno (snake_case) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="campo-field-name"
                           placeholder="ej: numero_parte_alternativo">
                    <small class="text-muted">Solo letras, números y guiones bajos. Se normaliza automáticamente.</small>
                </div>

                <div class="form-group">
                    <label>Etiqueta visible <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="campo-field-label"
                           placeholder="ej: Número de parte alternativo">
                </div>

                <div class="form-group">
                    <label>Tipo de campo</label>
                    <select class="form-control" id="campo-field-type">
                        <?php foreach ($fieldTypeLabels as $val => $lbl): ?>
                            <option value="<?= $val ?>"><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="grp-options" style="display:none;">
                    <label>Opciones (una por línea)</label>
                    <textarea class="form-control" id="campo-options" rows="4"
                              placeholder="Opción 1&#10;Opción 2&#10;Opción 3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" class="form-control" id="campo-sort-order" min="0" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Requerido</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="campo-required">
                                <label class="form-check-label" for="campo-required">Sí</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3" id="grp-active" style="display:none;">
                        <div class="form-group">
                            <label>Activo</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="campo-active" checked>
                                <label class="form-check-label" for="campo-active">Sí</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-campo">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const ENDPOINT = 'public/ajax/custom_field.php';
    const ENTITY_LABELS = <?= json_encode($entityLabels) ?>;
    const TYPE_LABELS   = <?= json_encode($fieldTypeLabels) ?>;

    // ── DataTable ──────────────────────────────────────────────────────────
    const $tbl = $('#tbl-campos');
    let dt;

    function loadData() {
        $.get(ENDPOINT, { action: 'list' }, function (res) {
            if (!res.success) return;
            if (dt) { dt.destroy(); $tbl.find('tbody').empty(); }
            const rows = res.data;
            rows.forEach(function (r, i) {
                const reqBadge = r.is_required == 1
                    ? '<span class="badge badge-danger">Sí</span>'
                    : '<span class="badge badge-secondary">No</span>';
                const actBadge = r.active == 1
                    ? '<span class="badge badge-success">Sí</span>'
                    : '<span class="badge badge-secondary">No</span>';
                const entityLabel = ENTITY_LABELS[r.entity_type] || r.entity_type;
                const typeLabel   = TYPE_LABELS[r.field_type]   || r.field_type;

                $tbl.find('tbody').append(
                    '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td><span class="badge badge-info">' + entityLabel + '</span></td>' +
                    '<td>' + $('<span>').text(r.field_label).html() + '<br><small class="text-muted">' + r.field_name + '</small></td>' +
                    '<td>' + typeLabel + '</td>' +
                    '<td>' + r.sort_order + '</td>' +
                    '<td>' + reqBadge + '</td>' +
                    '<td>' + actBadge + '</td>' +
                    '<td>' +
                    '<button class="btn btn-xs btn-warning btn-edit mr-1" data-id="' + r.id + '" title="Editar"><i class="fas fa-edit"></i></button>' +
                    '<button class="btn btn-xs btn-danger btn-delete" data-id="' + r.id + '" title="Eliminar"><i class="fas fa-trash"></i></button>' +
                    '</td>' +
                    '</tr>'
                );
            });
            dt = $tbl.DataTable({
                language: { url: 'assets/plugins/datatables/i18n/Spanish.json' },
                order: [[1, 'asc'], [4, 'asc']]
            });
        }, 'json');
    }

    // ── Modal helpers ───────────────────────────────────────────────────────
    function openCreateModal() {
        $('#campo-id').val('');
        $('#campo-entity-type').val('equipment').prop('disabled', false);
        $('#campo-field-name').val('').prop('disabled', false);
        $('#campo-field-label').val('');
        $('#campo-field-type').val('text');
        $('#campo-options').val('');
        $('#campo-sort-order').val(0);
        $('#campo-required').prop('checked', false);
        $('#campo-active').prop('checked', true);
        $('#grp-options').hide();
        $('#grp-active').hide();
        $('#modal-campo-title').text('Nuevo Campo');
        $('#modal-campo').modal('show');
    }

    function openEditModal(id) {
        $.get(ENDPOINT, { action: 'get', id: id }, function (res) {
            if (!res.success) { alert('No se pudo cargar el campo.'); return; }
            const r = res.data;
            $('#campo-id').val(r.id);
            $('#campo-entity-type').val(r.entity_type).prop('disabled', true);
            $('#campo-field-name').val(r.field_name).prop('disabled', true);
            $('#campo-field-label').val(r.field_label);
            $('#campo-field-type').val(r.field_type);
            $('#campo-sort-order').val(r.sort_order);
            $('#campo-required').prop('checked', r.is_required == 1);
            $('#campo-active').prop('checked', r.active == 1);
            if (r.field_type === 'select' && r.options) {
                const opts = JSON.parse(r.options);
                $('#campo-options').val(opts.join('\n'));
                $('#grp-options').show();
            } else {
                $('#campo-options').val('');
                $('#grp-options').hide();
            }
            $('#grp-active').show();
            $('#modal-campo-title').text('Editar Campo');
            $('#modal-campo').modal('show');
        }, 'json');
    }

    // Mostrar/ocultar caja de opciones según tipo
    $('#campo-field-type').on('change', function () {
        $('#grp-options').toggle($(this).val() === 'select');
    });

    // ── Guardar ─────────────────────────────────────────────────────────────
    $('#btn-guardar-campo').on('click', function () {
        const id       = $('#campo-id').val();
        const isEdit   = !!id;
        const data     = {
            entity_type : $('#campo-entity-type').val(),
            field_name  : $('#campo-field-name').val().trim(),
            field_label : $('#campo-field-label').val().trim(),
            field_type  : $('#campo-field-type').val(),
            options     : $('#campo-options').val(),
            sort_order  : $('#campo-sort-order').val(),
            is_required : $('#campo-required').is(':checked') ? 1 : 0,
            active      : $('#campo-active').is(':checked') ? 1 : 0,
        };

        if (!data.field_name || !data.field_label) {
            alert('Nombre y etiqueta son requeridos.');
            return;
        }

        const url = isEdit
            ? ENDPOINT + '?action=update&id=' + id
            : ENDPOINT + '?action=create';

        $.post(url, data, function (res) {
            if (res.success) {
                $('#modal-campo').modal('hide');
                loadData();
                alert(res.message);
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json').fail(function () {
            alert('Error de comunicación.');
        });
    });

    // ── Eventos tabla ────────────────────────────────────────────────────────
    $tbl.on('click', '.btn-edit', function () {
        openEditModal($(this).data('id'));
    });

    $tbl.on('click', '.btn-delete', function () {
        if (!confirm('¿Eliminar este campo? También se borrarán todos sus valores guardados.')) return;
        $.post(ENDPOINT + '?action=delete', { id: $(this).data('id') }, function (res) {
            if (res.success) {
                loadData();
                alert(res.message);
            } else {
                alert('Error: ' + res.message);
            }
        }, 'json');
    });

    $('#btn-nuevo').on('click', openCreateModal);

    // ── Init ─────────────────────────────────────────────────────────────────
    loadData();
})();
</script>
