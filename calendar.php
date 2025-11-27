<?php require_once 'config/config.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Calendario de Mantenimientos</h4>
                    <div class="ml-auto">
                        <button class="btn btn-success btn-sm" id="btn-new">
                            <i class="fas fa-plus"></i> Nuevo
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div id="calendar" style="font-size: 0.9rem;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js"></script>

<!-- jQuery + Bootstrap JS (OBLIGATORIO) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
    #calendar {
        height: 600px !important;
    }

    .fc-button {
        font-size: 0.8rem !important;
        padding: 0.25rem 0.5rem !important;
    }

    .fc-daygrid-day-number {
        font-size: 0.8rem !important;
    }

    .fc-event {
        font-size: 0.75rem !important;
        cursor: pointer;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            locale: 'es',
            height: '100%',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: 'ajax.php?action=get_mantenimientos',
            dateClick: function(info) {
                openModal(info.dateStr);
            },
            eventClick: function(info) {
                showCompleteMaintenanceDialog(info, calendar);
            }
        });
        calendar.render();

        // BOTÓN NUEVO
        $('#btn-new').click(() => openModal());
    });

    function openModal(date = '') {
        $('#maintenanceModal').modal('show');
        $('#maintenance-form')[0].reset();
        $('#m_id').val('');
        $('#tipo_mantenimiento').val('Preventivo');
        $('#hora_programada').val('');
        if (date) $('[name="fecha_programada"]').val(date);
    }

    function showCompleteMaintenanceDialog(info, calendar) {
        const event = info.event;
        const type = event.extendedProps.tipo_mantenimiento || 'Mantenimiento';
        const hora = event.extendedProps.hora_programada || '';
        const fecha = event.startStr ? new Date(event.startStr).toLocaleDateString('es-MX') : '';

        const message = `¿Marcar como completado el ${type} del ${fecha}${hora ? ' a las ' + hora : ''}?`;

        const $modal = $('<div class="modal fade" tabindex="-1" role="dialog">\n' +
            '  <div class="modal-dialog" role="document">\n' +
            '    <div class="modal-content">\n' +
            '      <div class="modal-header bg-secondary text-white">\n' +
            '        <h5 class="modal-title">Confirmar</h5>\n' +
            '        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">\n' +
            '          <span aria-hidden="true">&times;</span>\n' +
            '        </button>\n' +
            '      </div>\n' +
            '      <div class="modal-body">\n' +
            `        <p>${message}</p>\n` +
            '      </div>\n' +
            '      <div class="modal-footer">\n' +
            '        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>\n' +
            '        <button type="button" class="btn btn-primary" id="confirm-complete">Marcar como completado</button>\n' +
            '      </div>\n' +
            '    </div>\n' +
            '  </div>\n' +
            '</div>');

        $modal.appendTo('body');
        $modal.modal('show');

        $modal.on('click', '#confirm-complete', function() {
            start_load();
            $.post('ajax.php?action=complete_maintenance', {
                id: event.id
            }, function(resp) {
                end_load();
                resp = (resp || '').trim();
                if (resp === '1') {
                    alert_toast('Mantenimiento marcado como completado', 'success');
                    calendar.refetchEvents();
                    window.location.href = `index.php?page=report_form&equipment_id=${encodeURIComponent(event.extendedProps.equipment_id || '')}`;
                } else {
                    alert_toast('No se pudo completar el mantenimiento', 'error');
                }
                $modal.modal('hide');
            }).fail(function() {
                end_load();
                alert_toast('Error de conexión', 'error');
                $modal.modal('hide');
            });
        });

        $modal.on('hidden.bs.modal', function() {
            $modal.remove();
        });
    }
</script>

<!-- Modal -->
<div class="modal fade" id="maintenanceModal">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <form id="maintenance-form">
                <div class="modal-header">
                    <h5 class="modal-title">Programar Mantenimiento</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="m_id">
                    <div class="form-group">
                        <label>Equipo</label>
                        <select name="equipo_id" class="form-control form-control-sm" required>
                            <option value="">Seleccionar equipo</option>
                            <?php
                            $eqs = $conn->query("SELECT id, name FROM equipments ORDER BY name ASC");
                            while ($r = $eqs->fetch_assoc()):
                            ?>
                                <option value="<?php echo $r['id']; ?>">
                                    <?php echo htmlspecialchars($r['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha_programada" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de mantenimiento</label>
                        <select name="tipo_mantenimiento" id="tipo_mantenimiento" class="form-control form-control-sm" required>
                            <option value="Predictivo">Predictivo</option>
                            <option value="Preventivo" selected>Preventivo</option>
                            <option value="Correctivo">Correctivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hora</label>
                        <input type="time" name="hora_programada" id="hora_programada" class="form-control form-control-sm">
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // === GUARDAR MANTENIMIENTO ===
    $('#maintenance-form').submit(function(e) {
        e.preventDefault();
        start_load();

        $.ajax({
            url: 'ajax.php?action=save_maintenance',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                end_load();
                resp = resp.trim();
                if (resp === '1') {
                    alert_toast('Mantenimiento guardado', 'success');
                    $('#maintenanceModal').modal('hide');
                    // RECARGAR PÁGINA PARA EVITAR ERRORES DE FULLCALENDAR
                    setTimeout(() => location.reload(), 800);
                } else {
                    console.error('Respuesta inesperada:', resp);
                    alert_toast('Error al guardar', 'error');
                }
            },
            error: function(xhr) {
                end_load();
                console.error('AJAX Error:', xhr.responseText);
                alert_toast('Error de conexión', 'error');
            }
        });
    });

    // === EVITAR ERROR ARIA-HIDDEN AL CANCELAR ===
    $(document).on('click', '[data-dismiss="modal"]', function() {
        var $modal = $(this).closest('.modal');
        $modal.removeClass('show').hide();
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        setTimeout(() => $modal.modal('hide'), 100);
    });

    // === EVITAR ERRORES DE FULLCALENDAR AL RECARGAR ===
    $(window).on('beforeunload', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl && calendarEl._fullCalendar) {
            calendarEl._fullCalendar.destroy();
        }
    });
</script>