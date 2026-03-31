<?php
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    die('Acceso denegado.');
}
if (($_SESSION['login_type'] ?? 0) != 1) {
    echo '<div class="col-lg-12"><div class="alert alert-danger">Sin permisos para acceder a esta seccion.</div></div>';
    return;
}
?>

<div class="col-lg-12">

    <!-- Tarjetas resumen -->
    <div class="row mb-4" id="audit-summary-cards">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-shield-alt fa-2x text-primary mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Total Registros</h6>
                        <h4 class="mb-0" id="audit-total">--</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-plus-circle fa-2x text-success mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Creaciones</h6>
                        <h4 class="mb-0" id="audit-creates">--</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-edit fa-2x text-warning mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Actualizaciones</h6>
                        <h4 class="mb-0" id="audit-updates">--</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-trash-alt fa-2x text-danger mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Eliminaciones</h6>
                        <h4 class="mb-0" id="audit-deletes">--</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card card-outline card-dark mb-3">
        <div class="card-header py-2">
            <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filtros</h5>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body py-2">
            <form id="audit-filters">
                <div class="row mb-2">
                    <div class="col-md-3 col-sm-6 mb-1">
                        <label class="mb-1 small font-weight-bold">Modulo</label>
                        <select id="filter-module" class="form-control form-control-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-1">
                        <label class="mb-1 small font-weight-bold">Accion</label>
                        <select id="filter-action" class="form-control form-control-sm">
                            <option value="">Todas</option>
                            <option value="create">Registro</option>
                            <option value="update">Actualizacion</option>
                            <option value="delete">Eliminacion</option>
                            <option value="move">Movimiento</option>
                            <option value="login">Inicio sesion</option>
                            <option value="logout">Cierre sesion</option>
                            <option value="export">Exportacion</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-1">
                        <label class="mb-1 small font-weight-bold">Usuario</label>
                        <select id="filter-user" class="form-control form-control-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-1">
                        <label class="mb-1 small font-weight-bold">Desde</label>
                        <input type="date" id="filter-date-from" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row align-items-end">
                    <div class="col-md-3 col-sm-6 mb-1">
                        <label class="mb-1 small font-weight-bold">Hasta</label>
                        <input type="date" id="filter-date-to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-9 col-sm-6 mb-1 text-right">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Buscar</button>
                        <button type="button" id="btn-clear-filters" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros"><i class="fas fa-times"></i> Limpiar</button>
                        <button type="button" id="btn-export-excel" class="btn btn-sm btn-success" title="Exportar a Excel"><i class="fas fa-file-excel"></i> Excel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de auditoría -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-shield-alt"></i> Registro de Auditoria</h5>
            <div class="card-tools">
                <span class="badge badge-dark" id="audit-showing">--</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm mb-0" id="audit-table">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:140px">Fecha</th>
                            <th>Usuario</th>
                            <th style="width:100px">Modulo</th>
                            <th style="width:120px">Accion</th>
                            <th>Tabla</th>
                            <th style="width:60px" class="text-center">ID</th>
                            <th style="width:50px" class="text-center">Detalle</th>
                        </tr>
                    </thead>
                    <tbody id="audit-tbody">
                        <tr><td colspan="7" class="text-center py-4 text-muted">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            <div>
                <small class="text-muted">Pagina <span id="audit-current-page">1</span> de <span id="audit-total-pages">1</span></small>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary" id="btn-prev-page" disabled><i class="fas fa-chevron-left"></i></button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-next-page" disabled><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalle -->
<div class="modal fade" id="auditDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Detalle de Auditoria</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="audit-detail-body">
                <p class="text-muted">Cargando...</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var currentPage = 1;
    var totalPages = 1;
    var AJAX_URL = 'public/ajax/audit_log.php';

    // Mapeo de acciones a badges
    var actionBadges = {
        'create': '<span class="badge badge-success">Registro</span>',
        'update': '<span class="badge badge-warning text-dark">Actualizacion</span>',
        'delete': '<span class="badge badge-danger">Eliminacion</span>',
        'move':   '<span class="badge badge-info">Movimiento</span>',
        'login':  '<span class="badge badge-primary">Inicio sesion</span>',
        'logout': '<span class="badge badge-secondary">Cierre sesion</span>',
        'export': '<span class="badge badge-dark">Exportacion</span>'
    };

    function getFilters() {
        return {
            module:      $('#filter-module').val(),
            action_type: $('#filter-action').val(),
            user_id:     $('#filter-user').val(),
            date_from:   $('#filter-date-from').val(),
            date_to:     $('#filter-date-to').val()
        };
    }

    function buildQuery(params) {
        var q = [];
        for (var k in params) {
            if (params[k]) q.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
        }
        return q.join('&');
    }

    function loadData(page) {
        page = page || 1;
        currentPage = page;
        var filters = getFilters();
        filters.page = page;
        filters.per_page = 50;
        filters.action = 'list';

        $('#audit-tbody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');

        $.getJSON(AJAX_URL + '?' + buildQuery(filters), function(resp) {
            if (!resp.success) {
                $('#audit-tbody').html('<tr><td colspan="7" class="text-center text-danger">' + (resp.message || 'Error') + '</td></tr>');
                return;
            }

            totalPages = resp.pages || 1;
            $('#audit-total').text(resp.total);
            $('#audit-current-page').text(resp.page);
            $('#audit-total-pages').text(totalPages);
            $('#audit-showing').text(resp.total + ' registros encontrados');

            $('#btn-prev-page').prop('disabled', resp.page <= 1);
            $('#btn-next-page').prop('disabled', resp.page >= totalPages);

            if (!resp.data || resp.data.length === 0) {
                $('#audit-tbody').html('<tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron registros</td></tr>');
                return;
            }

            var html = '';
            var creates = 0, updates = 0, deletes = 0;
            resp.data.forEach(function(r) {
                if (r.action === 'create') creates++;
                if (r.action === 'update') updates++;
                if (r.action === 'delete') deletes++;

                html += '<tr>' +
                    '<td><small>' + formatDate(r.created_at) + '</small></td>' +
                    '<td><b>' + escHtml(r.user_name || ('ID: ' + r.user_id)) + '</b></td>' +
                    '<td><small>' + escHtml(r.module) + '</small></td>' +
                    '<td>' + (actionBadges[r.action] || escHtml(r.action)) + '</td>' +
                    '<td><code>' + escHtml(r.table_name) + '</code></td>' +
                    '<td class="text-center">' + (r.record_id || '-') + '</td>' +
                    '<td class="text-center"><button class="btn btn-xs btn-outline-info btn-detail" data-id="' + r.id + '" title="Ver detalle"><i class="fas fa-eye"></i></button></td>' +
                    '</tr>';
            });

            $('#audit-tbody').html(html);
            // Solo actualizar conteos si estamos en la primera página sin filtros
            if (page === 1) {
                $('#audit-creates').text(creates);
                $('#audit-updates').text(updates);
                $('#audit-deletes').text(deletes);
            }
        }).fail(function() {
            $('#audit-tbody').html('<tr><td colspan="7" class="text-center text-danger">Error de conexion</td></tr>');
        });
    }

    function loadFilterOptions() {
        $.getJSON(AJAX_URL + '?action=filter_options', function(resp) {
            if (!resp.success) return;
            var d = resp.data;

            if (d.modules) {
                d.modules.forEach(function(m) {
                    $('#filter-module').append('<option value="' + escHtml(m) + '">' + escHtml(m) + '</option>');
                });
            }
            if (d.users) {
                d.users.forEach(function(u) {
                    $('#filter-user').append('<option value="' + u.user_id + '">' + escHtml(u.user_name || ('ID: ' + u.user_id)) + '</option>');
                });
            }
        });
    }

    function showDetail(id) {
        $('#audit-detail-body').html('<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>');
        $('#auditDetailModal').modal('show');

        $.getJSON(AJAX_URL + '?action=get&id=' + id, function(resp) {
            if (!resp.success) {
                $('#audit-detail-body').html('<p class="text-danger">' + (resp.message || 'No encontrado') + '</p>');
                return;
            }

            var r = resp.data;
            var html = '<table class="table table-sm table-bordered mb-3">' +
                '<tr><th width="150">ID</th><td>' + r.id + '</td></tr>' +
                '<tr><th>Fecha</th><td>' + formatDate(r.created_at) + '</td></tr>' +
                '<tr><th>Usuario</th><td>' + escHtml(r.user_name || ('ID: ' + r.user_id)) + '</td></tr>' +
                '<tr><th>Modulo</th><td>' + escHtml(r.module) + '</td></tr>' +
                '<tr><th>Accion</th><td>' + (actionBadges[r.action] || escHtml(r.action)) + '</td></tr>' +
                '<tr><th>Tabla</th><td><code>' + escHtml(r.table_name) + '</code></td></tr>' +
                '<tr><th>Registro ID</th><td>' + (r.record_id || '-') + '</td></tr>' +
                '<tr><th>IP</th><td>' + escHtml(r.ip_address || '-') + '</td></tr>' +
                '<tr><th>Sucursal</th><td>' + (r.branch_id || '-') + '</td></tr>' +
                '</table>';

            if (r.old_values || r.new_values) {
                html += '<h6 class="font-weight-bold mt-3">Cambios realizados:</h6>';
                html += '<table class="table table-sm table-bordered"><thead><tr><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody>';

                var allKeys = {};
                if (r.old_values) Object.keys(r.old_values).forEach(function(k) { allKeys[k] = true; });
                if (r.new_values) Object.keys(r.new_values).forEach(function(k) { allKeys[k] = true; });

                Object.keys(allKeys).forEach(function(key) {
                    var oldVal = r.old_values ? (r.old_values[key] != null ? r.old_values[key] : '-') : '-';
                    var newVal = r.new_values ? (r.new_values[key] != null ? r.new_values[key] : '-') : '-';
                    var changed = String(oldVal) !== String(newVal);
                    html += '<tr' + (changed ? ' class="table-warning"' : '') + '>' +
                        '<td><b>' + escHtml(key) + '</b></td>' +
                        '<td>' + escHtml(String(oldVal)) + '</td>' +
                        '<td>' + escHtml(String(newVal)) + '</td>' +
                        '</tr>';
                });

                html += '</tbody></table>';
            }

            $('#audit-detail-body').html(html);
        }).fail(function() {
            $('#audit-detail-body').html('<p class="text-danger">Error de conexion</p>');
        });
    }

    function formatDate(dt) {
        if (!dt) return '-';
        var d = new Date(dt);
        var pad = function(n) { return n < 10 ? '0' + n : n; };
        return pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function escHtml(s) {
        if (s == null) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(s)));
        return div.innerHTML;
    }

    // Eventos
    $('#audit-filters').on('submit', function(e) {
        e.preventDefault();
        loadData(1);
    });

    $('#btn-clear-filters').on('click', function() {
        $('#filter-module, #filter-action, #filter-user').val('');
        $('#filter-date-from, #filter-date-to').val('');
        loadData(1);
    });

    $('#btn-prev-page').on('click', function() {
        if (currentPage > 1) loadData(currentPage - 1);
    });

    $('#btn-next-page').on('click', function() {
        if (currentPage < totalPages) loadData(currentPage + 1);
    });

    $(document).on('click', '.btn-detail', function() {
        showDetail($(this).data('id'));
    });

    $('#btn-export-excel').on('click', function() {
        var filters = getFilters();
        filters.action = 'export';
        filters.format = 'excel';
        window.location.href = AJAX_URL + '?' + buildQuery(filters);
    });

    // Init
    loadFilterOptions();
    loadData(1);
})();
</script>
