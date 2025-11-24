<?php require_once 'config/config.php'; ?>

<!-- Calendario de Mantenimientos -->
<div class="col-lg-12">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Calendario de Mantenimientos</h3>
        </div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS (ya incluido en AdminLTE 3 si usas la plantilla completa) -->
<link rel="stylesheet" href="plugins/fullcalendar/main.min.css">

<!-- FullCalendar JS -->
<script src="plugins/fullcalendar/main.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        themeSystem: 'bootstrap',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: 'ajax.php?action=get_maintenance_events', // Carga dinámica
        eventClick: function(info) {
            alert('Mantenimiento: ' + info.event.title);
            // Puedes abrir un modal aquí si lo deseas
        }
    });

    calendar.render();
});
</script>
