<?php
// Pagina publica para seguimiento de tickets via token
define('ACCESS', true);
require_once __DIR__ . '/../config/config.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$ticket = null;
$history = [];
$comments = [];
$error = '';

if (empty($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    $error = 'Enlace de seguimiento invalido.';
} else {
    $token_esc = $conn->real_escape_string($token);
    $qry = $conn->query("SELECT t.id, t.subject, t.ticket_number, t.status, t.priority, t.date_created, t.issue_type,
        t.reporter_name, t.equipment_id,
        e.name as equipment_name, e.number_inventory,
        COALESCE(d.name, 'Sin Departamento') as dname,
        COALESCE(CONCAT(ua.firstname,' ',ua.lastname), '') as assigned_name
        FROM tickets t
        LEFT JOIN equipments e ON e.id = t.equipment_id
        LEFT JOIN departments d ON d.id = t.department_id
        LEFT JOIN users ua ON ua.id = t.assigned_to
        WHERE t.tracking_token = '$token_esc' AND t.is_public = 1
        LIMIT 1");

    if ($qry && $qry->num_rows > 0) {
        $ticket = $qry->fetch_assoc();
        $tid = (int)$ticket['id'];

        $h = $conn->query("SELECT h.new_status, h.created_at,
            COALESCE(CONCAT(u.firstname,' ',u.lastname), 'Sistema') as changed_by
            FROM ticket_status_history h
            LEFT JOIN users u ON u.id = h.changed_by
            WHERE h.ticket_id = $tid ORDER BY h.created_at ASC");
        if ($h) { while ($row = $h->fetch_assoc()) $history[] = $row; }

        $cq = $conn->query("SELECT c.comment, c.date_created, c.user_type,
            CASE
                WHEN c.user_type = 1 THEN COALESCE((SELECT CONCAT(firstname,' ',lastname) FROM users WHERE id = c.user_id), 'Soporte')
                WHEN c.user_type = 2 THEN COALESCE((SELECT CONCAT(firstname,' ',lastname) FROM staff WHERE id = c.user_id), 'Soporte')
                WHEN c.user_type = 3 AND c.user_id = 0 THEN t.reporter_name
                ELSE 'Soporte'
            END as user_name
            FROM comments c
            JOIN tickets t ON t.id = c.ticket_id
            WHERE c.ticket_id = $tid AND COALESCE(c.is_internal,0) = 0 ORDER BY c.date_created ASC");
        if ($cq) { while ($row = $cq->fetch_assoc()) $comments[] = $row; }
    } else {
        $error = 'No se encontro el ticket o el enlace ha expirado.';
    }
}

$statusLabels = [0 => 'Abierto/Pendiente', 1 => 'En Proceso', 2 => 'Finalizado', 3 => 'Cerrado'];
$statusClasses = [0 => 'primary', 1 => 'info', 2 => 'success', 3 => 'secondary'];
$priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'];
$priorityClasses = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'critical' => 'danger'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Ticket<?php echo $ticket ? ' - ' . htmlspecialchars($ticket['ticket_number']) : ''; ?></title>
    <link rel="stylesheet" href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/plugins/fontawesome/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .track-card { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .track-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 25px; text-align: center; }
        .track-header h4 { margin: 0 0 5px; }
        .track-header .ticket-num { opacity: 0.85; font-size: 0.9rem; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6b7280; font-weight: 600; }
        .info-value { color: #1f2937; }
        .timeline { position: relative; padding-left: 24px; margin-top: 15px; }
        .timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 2px; background: #e5e7eb; }
        .tl-item { position: relative; margin-bottom: 12px; }
        .tl-dot { position: absolute; left: -20px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #667eea; border: 2px solid #fff; box-shadow: 0 0 0 2px #e5e7eb; }
        .tl-dot.done { background: #10b981; }
        .tl-date { font-size: 0.75rem; color: #9ca3af; }
        .tl-text { font-size: 0.85rem; color: #374151; }
        .comment-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; margin-top: 4px; font-size: 0.85rem; }
        .comment-box.from-reporter { background: #eef2ff; border-color: #c7d2fe; }
        .comment-author { font-weight: 600; color: #374151; }
        .comment-author.reporter { color: #4f46e5; }
        .comment-author.support { color: #059669; }
        .status-progress { display: flex; justify-content: space-between; margin: 20px 0 10px; }
        .progress-step { text-align: center; flex: 1; position: relative; }
        .progress-step .step-dot { width: 28px; height: 28px; border-radius: 50%; background: #e5e7eb; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; color: #fff; margin-bottom: 4px; }
        .progress-step.active .step-dot { background: #667eea; }
        .progress-step.done .step-dot { background: #10b981; }
        .progress-step .step-label { font-size: 0.7rem; color: #6b7280; }
        .progress-step::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 2px; background: #e5e7eb; z-index: 0; }
        .progress-step:last-child::after { display: none; }
        .progress-step.done::after { background: #10b981; }
        .progress-step .step-dot { position: relative; z-index: 1; }
    </style>
</head>
<body>
<div class="track-card">
<?php if ($error): ?>
    <div class="track-header"><h4>Seguimiento de Ticket</h4></div>
    <div class="p-4">
        <div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
    </div>
<?php else: ?>
    <div class="track-header">
        <h4><?php echo htmlspecialchars($ticket['subject']); ?></h4>
        <div class="ticket-num"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
    </div>
    <div class="p-4">
        <?php $st = (int)$ticket['status']; ?>
        <div class="status-progress">
            <div class="progress-step done">
                <div class="step-dot"><i class="fas fa-folder-open"></i></div>
                <div class="step-label">Abierto</div>
            </div>
            <div class="progress-step <?php echo $st >= 1 ? 'done' : ''; ?> <?php echo $st == 1 ? 'active' : ''; ?>">
                <div class="step-dot"><i class="fas fa-cog"></i></div>
                <div class="step-label">En Proceso</div>
            </div>
            <div class="progress-step <?php echo $st >= 2 ? 'done' : ''; ?> <?php echo $st == 2 ? 'active' : ''; ?>">
                <div class="step-dot"><i class="fas fa-check"></i></div>
                <div class="step-label">Finalizado</div>
            </div>
            <div class="progress-step <?php echo $st >= 3 ? 'done' : ''; ?> <?php echo $st == 3 ? 'active' : ''; ?>">
                <div class="step-dot"><i class="fas fa-times-circle"></i></div>
                <div class="step-label">Cerrado</div>
            </div>
        </div>

        <div class="mt-3">
            <div class="info-row">
                <span class="info-label">Estado</span>
                <span class="info-value"><span class="badge badge-<?php echo $statusClasses[$st] ?? 'primary'; ?>"><?php echo $statusLabels[$st] ?? 'Desconocido'; ?></span></span>
            </div>
            <div class="info-row">
                <span class="info-label">Prioridad</span>
                <span class="info-value"><span class="badge badge-<?php echo $priorityClasses[$ticket['priority'] ?? 'medium']; ?>"><?php echo $priorityLabels[$ticket['priority'] ?? 'medium']; ?></span></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de Creacion</span>
                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['date_created'])); ?></span>
            </div>
            <?php if (!empty($ticket['equipment_name'])): ?>
            <div class="info-row">
                <span class="info-label">Equipo</span>
                <span class="info-value"><?php echo htmlspecialchars($ticket['equipment_name']); ?><?php if (!empty($ticket['number_inventory'])) echo ' #'.htmlspecialchars($ticket['number_inventory']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($ticket['issue_type'])): ?>
            <div class="info-row">
                <span class="info-label">Tipo de Falla</span>
                <span class="info-value"><?php echo htmlspecialchars($ticket['issue_type']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($ticket['assigned_name'])): ?>
            <div class="info-row">
                <span class="info-label">Tecnico Asignado</span>
                <span class="info-value"><?php echo htmlspecialchars($ticket['assigned_name']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($history) || !empty($comments)): ?>
        <h6 class="mt-4 mb-2" style="color:#667eea; font-weight:600;">Actividad</h6>
        <div class="timeline">
            <div class="tl-item">
                <div class="tl-dot done"></div>
                <div class="tl-date"><?php echo date('d/m/Y H:i', strtotime($ticket['date_created'])); ?></div>
                <div class="tl-text">Ticket creado</div>
            </div>
            <?php
            $merged = [];
            foreach ($history as $h) $merged[] = ['type' => 'status', 'date' => $h['created_at'], 'data' => $h];
            foreach ($comments as $c) $merged[] = ['type' => 'comment', 'date' => $c['date_created'], 'data' => $c];
            usort($merged, function($a, $b) { return strtotime($a['date']) - strtotime($b['date']); });
            foreach ($merged as $m): ?>
                <?php if ($m['type'] === 'status'): ?>
                <div class="tl-item">
                    <div class="tl-dot <?php echo $m['data']['new_status'] >= 2 ? 'done' : ''; ?>"></div>
                    <div class="tl-date"><?php echo date('d/m/Y H:i', strtotime($m['data']['created_at'])); ?></div>
                    <div class="tl-text">Estado cambiado a: <strong><?php echo $statusLabels[$m['data']['new_status']] ?? '?'; ?></strong></div>
                </div>
                <?php else: ?>
                <?php $isReporter = ((int)$m['data']['user_type'] === 3); ?>
                <div class="tl-item">
                    <div class="tl-dot"></div>
                    <div class="tl-date"><?php echo date('d/m/Y H:i', strtotime($m['data']['date_created'])); ?></div>
                    <div class="tl-text">
                        <span class="comment-author <?php echo $isReporter ? 'reporter' : 'support'; ?>">
                            <i class="fas <?php echo $isReporter ? 'fa-user' : 'fa-headset'; ?>" style="font-size:0.75rem;"></i>
                            <?php echo htmlspecialchars($m['data']['user_name']); ?>
                        </span>
                    </div>
                    <div class="comment-box <?php echo $isReporter ? 'from-reporter' : ''; ?>"><?php echo html_entity_decode($m['data']['comment']); ?></div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ((int)$ticket['status'] < 3): ?>
        <div class="mt-4 px-1 pb-3">
            <h6 style="color:#667eea; font-weight:600;">Enviar mensaje al equipo de soporte</h6>
            <div id="public-comment-alert" class="alert" style="display:none;"></div>
            <textarea id="public-comment-text" class="form-control" rows="3" maxlength="2000"
                placeholder="Escribe tu mensaje aqui..." style="border-radius:8px; resize:vertical;"></textarea>
            <div class="mt-2 text-right">
                <button id="public-comment-btn" type="button"
                    style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:8px;padding:8px 22px;font-size:0.9rem;cursor:pointer;">
                    <i class="fas fa-paper-plane"></i> Enviar mensaje
                </button>
            </div>
        </div>
        <?php endif; ?>

    </div>
<?php endif; ?>
</div>
<script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/plugins/jquery/jquery.min.js"></script>
<script>
$(function() {
    $('#public-comment-btn').on('click', function() {
        var msg = $('#public-comment-text').val().trim();
        if (!msg) { showAlert('warning', 'Por favor escribe un mensaje antes de enviar.'); return; }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
        $.ajax({
            url: 'public_comment.php',
            method: 'POST',
            data: { token: '<?php echo isset($token) ? htmlspecialchars($token, ENT_QUOTES) : ''; ?>', comment: msg },
            success: function(r) {
                try { r = typeof r === 'string' ? JSON.parse(r) : r; } catch(e) {}
                if (r.status == 1) {
                    showAlert('success', 'Tu mensaje fue enviado. El equipo lo revisara pronto.');
                    $('#public-comment-text').val('');
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    showAlert('danger', r.msg || 'Error al enviar. Intenta de nuevo.');
                }
            },
            error: function() { showAlert('danger', 'Error de conexion. Intenta de nuevo.'); },
            complete: function() { $btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar mensaje'); }
        });
    });
    function showAlert(type, msg) {
        $('#public-comment-alert').removeClass('alert-success alert-warning alert-danger')
            .addClass('alert-' + type).html(msg).show();
    }
});
</script>
</body>
</html>
