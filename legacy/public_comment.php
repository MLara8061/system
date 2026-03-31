<?php
// Endpoint publico: permite al reportante enviar un comentario usando su tracking_token
// No requiere sesion. Solo acepta POST con token valido y ticket abierto.
define('ACCESS', true);
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 0, 'msg' => 'Metodo no permitido']);
    exit;
}

$token   = trim($_POST['token'] ?? '');
$comment = trim($_POST['comment'] ?? '');

if (empty($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    echo json_encode(['status' => 0, 'msg' => 'Token invalido']);
    exit;
}

if (empty($comment) || mb_strlen($comment) > 2000) {
    echo json_encode(['status' => 0, 'msg' => 'El mensaje es requerido (max 2000 caracteres)']);
    exit;
}

$token_esc   = $conn->real_escape_string($token);
$qry = $conn->query("SELECT id, reporter_name, is_public, status FROM tickets
    WHERE tracking_token = '$token_esc' AND is_public = 1 LIMIT 1");

if (!$qry || $qry->num_rows === 0) {
    echo json_encode(['status' => 0, 'msg' => 'Ticket no encontrado']);
    exit;
}

$ticket = $qry->fetch_assoc();
$ticket_id = (int)$ticket['id'];

if ((int)$ticket['status'] >= 3) {
    echo json_encode(['status' => 0, 'msg' => 'El ticket esta cerrado y no acepta mas mensajes']);
    exit;
}

$comment_clean = $conn->real_escape_string(htmlentities($comment));
$reporter_name = $conn->real_escape_string($ticket['reporter_name'] ?? 'Reportante');
$now = date('Y-m-d H:i:s');

// user_type=3 = Customer/reportante publico, user_id=0 (sin cuenta)
$ins = $conn->query("INSERT INTO comments (ticket_id, comment, user_id, user_type, is_internal, date_created)
    VALUES ($ticket_id, '$comment_clean', 0, 3, 0, '$now')");

if (!$ins) {
    echo json_encode(['status' => 0, 'msg' => 'Error al guardar el mensaje']);
    exit;
}

// Notificar al equipo interno por email si hay MailerService disponible
$mail_sent = false;
$mailer_path = __DIR__ . '/../app/helpers/MailerService.php';
if (file_exists($mailer_path)) {
    require_once $mailer_path;
    // Obtener correos de admins/soporte asignados al ticket
    $notif_qry = $conn->query("SELECT u.email FROM users u
        INNER JOIN tickets t ON t.assigned_to = u.id
        WHERE t.id = $ticket_id AND u.email IS NOT NULL AND u.email != ''
        LIMIT 1");
    $to_email = '';
    if ($notif_qry && $notif_qry->num_rows > 0) {
        $to_email = $notif_qry->fetch_assoc()['email'];
    }
    if ($to_email && class_exists('MailerService')) {
        $preview = mb_substr(strip_tags($comment), 0, 300);
        $subject = 'Nueva respuesta en ticket ' . htmlspecialchars($ticket_id);
        $body = '<p>El reportante <strong>' . htmlspecialchars($reporter_name) . '</strong> ha respondido en el ticket.</p>'
              . '<blockquote style="border-left:3px solid #667eea;padding:8px 12px;color:#374151;">'
              . nl2br(htmlspecialchars($preview))
              . '</blockquote>';
        try {
            MailerService::send($to_email, '', $subject, $body);
            $mail_sent = true;
        } catch (Exception $e) {
            error_log('public_comment mail error: ' . $e->getMessage());
        }
    }
}

echo json_encode(['status' => 1, 'msg' => 'Mensaje enviado correctamente']);
