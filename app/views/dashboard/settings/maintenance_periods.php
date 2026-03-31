<?php
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    die('Acceso denegado.');
}
if (($_SESSION['login_type'] ?? 0) != 1) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Sin permisos para acceder a esta sección.</div></div>';
    return;
}
?>

<div class="col-lg-12">

    <div class="card card-outline card-primary">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">
                <i class="fas fa-calendar-check mr-2"></i> Periodos de Mantenimiento
            </h5>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="btn-nuevo">
                    <i class="fas fa-plus"></i> Nuevo Periodo
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="tbl-periodos" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th>Nombre</th>
                        <th style="width: 20%">Intervalo (días)</th>
                        <th style="width: 18%">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Crear / Editar -->
<div class="modal fade" id="modal-periodo" tabindex="-1" role="dialog" aria-labelledby="modal-periodo-title">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="modal-periodo-title">Nuevo Periodo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="form-periodo">
                <div class="modal-body">
                    <input type="hidden" id="periodo-id">
                    <div class="form-group">
                        <label for="periodo-name">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="periodo-name"
                               placeholder="Ej: Trimestral" maxlength="50">
                    </div>
                    <div class="form-group mb-0">
                        <label for="periodo-days">Intervalo en días <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="periodo-days"
                               placeholder="Ej: 90" min="1" max="9999">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-guardar">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const ENDPOINT = 'public/ajax/maintenance_period.php';
    let dt;

    /* ── utilidades ─────────────────────────────────────────── */
    function toast(msg, type) {
        if (typeof alert_toast === 'function') {
            alert_toast(msg, type);
        } else {
            alert(msg);
        }
    }

    /* ── cargar tabla ────────────────────────────────────────── */
    function loadData() {
        if (typeof start_loader === 'function') start_loader();

        $.get(ENDPOINT, { action: 'list' }, function (res) {
            if (dt) { dt.destroy(); }
            const $tbody = $('#tbl-periodos tbody').empty();

            if (res.success && res.data.length) {
                res.data.forEach(function (row, i) {
                    $tbody.append(
                        '<tr>' +
                        '<td class="text-center">' + (i + 1) + '</td>' +
                        '<td><strong>' + $('<span>').text(row.name).html() + '</strong></td>' +
                        '<td class="text-center">' + parseInt(row.days_interval).toLocaleString() + '</td>' +
                        '<td class="text-center">' +
                            '<button class="btn btn-xs btn-default mr-1 btn-edit" data-id="' + row.id + '">' +
                                '<i class="fas fa-edit text-primary"></i> Editar' +
                            '</button>' +
                            '<button class="btn btn-xs btn-default btn-eliminar" data-id="' + row.id + '" data-name="' + $('<span>').text(row.name).html() + '">' +
                                '<i class="fas fa-trash text-danger"></i> Eliminar' +
                            '</button>' +
                        '</td>' +
                        '</tr>'
                    );
                });
            }

            dt = $('#tbl-periodos').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                columnDefs: [{ orderable: false, targets: [0, 3] }],
                pageLength: 25,
                dom: 'lBfrtip',
                buttons: []
            });

            bindRowEvents();
            if (typeof end_loader === 'function') end_loader();
        }).fail(function () {
            toast('Error al cargar los periodos', 'error');
            if (typeof end_loader === 'function') end_loader();
        });
    }

    /* ── eventos de fila ─────────────────────────────────────── */
    function bindRowEvents() {
        /* editar */
        $('#tbl-periodos tbody').off('click', '.btn-edit').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            $.get(ENDPOINT, { action: 'get', id: id }, function (res) {
                if (!res.success) { toast(res.message, 'error'); return; }
                $('#periodo-id').val(res.data.id);
                $('#periodo-name').val(res.data.name);
                $('#periodo-days').val(res.data.days_interval);
                $('#modal-periodo-title').text('Editar Periodo');
                $('#modal-periodo').modal('show');
            });
        });

        /* eliminar */
        $('#tbl-periodos tbody').off('click', '.btn-eliminar').on('click', '.btn-eliminar', function () {
            const id   = $(this).data('id');
            const name = $(this).data('name');
            if (!confirm('¿Eliminar el periodo "' + name + '"?\nEsta acción no se puede deshacer.')) return;
            $.post(ENDPOINT, { action: 'delete', id: id }, function (res) {
                toast(res.message, res.success ? 'success' : 'error');
                if (res.success) loadData();
            }).fail(function () { toast('Error al eliminar', 'error'); });
        });
    }

    /* ── abrir modal nuevo ───────────────────────────────────── */
    $('#btn-nuevo').on('click', function () {
        $('#form-periodo')[0].reset();
        $('#periodo-id').val('');
        $('#modal-periodo-title').text('Nuevo Periodo');
        $('#modal-periodo').modal('show');
    });

    /* ── guardar (crear o actualizar) ───────────────────────── */
    $('#form-periodo').on('submit', function (e) {
        e.preventDefault();

        const id   = $('#periodo-id').val();
        const name = $.trim($('#periodo-name').val());
        const days = $('#periodo-days').val();

        if (!name) { toast('El nombre es requerido', 'error'); return; }
        if (!days || parseInt(days) <= 0) { toast('Ingresa un intervalo válido en días', 'error'); return; }

        const payload = { name: name, days_interval: days };

        if (id) {
            payload.action = 'update';
            payload.id     = id;
        } else {
            payload.action = 'create';
        }

        $('#btn-guardar').prop('disabled', true);

        $.post(ENDPOINT, payload, function (res) {
            toast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                $('#modal-periodo').modal('hide');
                loadData();
            }
        }).fail(function () {
            toast('Error al guardar', 'error');
        }).always(function () {
            $('#btn-guardar').prop('disabled', false);
        });
    });

    /* ── init ────────────────────────────────────────────────── */
    $(document).ready(function () { loadData(); });

}());
</script>
