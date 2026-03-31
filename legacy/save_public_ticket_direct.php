<?php
// Endpoint público para crear tickets sin autenticación
// NO usar admin_class.php que requiere sesión

define('ACCESS', true);
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 0, 'message' => 'Método no permitido']);
    exit;
}

try {
    $equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    $reporter_name = isset($_POST['reporter_name']) ? $conn->real_escape_string(trim($_POST['reporter_name'])) : '';
    $reporter_email = isset($_POST['reporter_email']) ? $conn->real_escape_string(trim($_POST['reporter_email'])) : '';
    $reporter_phone = isset($_POST['reporter_phone']) ? $conn->real_escape_string(trim($_POST['reporter_phone'])) : '';
    $issue_type = isset($_POST['issue_type']) ? $conn->real_escape_string($_POST['issue_type']) : '';
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
    
    if ($equipment_id <= 0 || empty($reporter_name) || empty($description)) {
        echo json_encode(['status' => 0, 'message' => 'Datos incompletos']);
        exit;
    }
    
    // Obtener información del equipo
    $eq_query = $conn->query("SELECT name, number_inventory FROM equipments WHERE id = $equipment_id");
    if (!$eq_query || $eq_query->num_rows === 0) {
        echo json_encode(['status' => 0, 'message' => 'Equipo no encontrado']);
        exit;
    }
    $equipment = $eq_query->fetch_assoc();
    
    // Generar ticket_number único
    $ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Generar tracking token para seguimiento publico
    $tracking_token = bin2hex(random_bytes(16));
    
    // Crear el subject del ticket
    $subject = "Falla reportada: {$issue_type} - {$equipment['name']} (#{$equipment['number_inventory']})";
    $subject_escaped = $conn->real_escape_string($subject);
    
    // Solo guardar la descripcion libre del usuario (la metadata va en columnas dedicadas)
    $clean_desc = nl2br(htmlspecialchars($description));
    $description_escaped = $conn->real_escape_string($clean_desc);
    
    // Obtener department_id del equipo si existe
    $dept_query = $conn->query("SELECT department_id FROM equipment_delivery WHERE equipment_id = $equipment_id LIMIT 1");
    $department_id = 0;
    if ($dept_query && $dept_query->num_rows > 0) {
        $dept_row = $dept_query->fetch_assoc();
        $department_id = intval($dept_row['department_id'] ?? 0);
    }
    
    // Si no hay department_id, usar uno por defecto o NULL
    $dept_value = $department_id > 0 ? $department_id : 'NULL';
    
    // Insertar ticket
    $sql = "INSERT INTO tickets SET 
            subject = '$subject_escaped',
            description = '$description_escaped',
            status = 0,
            priority = 'medium',
            customer_id = 0,
            department_id = $dept_value,
            equipment_id = $equipment_id,
            reporter_name = '$reporter_name',
            reporter_email = '$reporter_email',
            reporter_phone = '$reporter_phone',
            issue_type = '$issue_type',
            ticket_number = '$ticket_number',
            is_public = 1,
            tracking_token = '$tracking_token',
            date_created = NOW()";
    
    $save = $conn->query($sql);
    
    if (!$save) {
        error_log("Error al guardar ticket público: " . $conn->error);
        echo json_encode(['status' => 0, 'message' => 'Error al guardar el ticket: ' . $conn->error]);
        exit;
    }
    
    $ticket_id = $conn->insert_id;

    // ============================================================
    // PROCESAR IMAGEN ADJUNTA (si viene)
    // ============================================================
    $attachment_path = null;
    if (isset($_FILES['issue_image']) && $_FILES['issue_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['issue_image'];
        $allowed_mime = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        // Validar MIME real con finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($file['tmp_name']);

        if (in_array($real_mime, $allowed_mime, true) && $file['size'] <= $max_size) {
            $upload_dir = defined('ROOT_PATH') ? ROOT_PATH . 'uploads/tickets/' . $ticket_id . '/' : __DIR__ . '/../uploads/tickets/' . $ticket_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext === 'jpg') $ext = 'jpeg';
            $safe_name = 'issue_' . $ticket_id . '_' . uniqid() . '.' . $ext;
            $dest = $upload_dir . $safe_name;
            $relative_path = 'uploads/tickets/' . $ticket_id . '/' . $safe_name;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $attachment_path = $relative_path;
                $orig_name_esc = $conn->real_escape_string($file['name']);
                $rel_esc       = $conn->real_escape_string($relative_path);
                $mime_esc      = $conn->real_escape_string($real_mime);
                $size_val      = (int)$file['size'];

                // Guardar en ticket_attachments
                $conn->query("INSERT INTO ticket_attachments 
                    (ticket_id, file_name, file_path, file_type, file_size, uploaded_by)
                    VALUES ({$ticket_id}, '{$orig_name_esc}', '{$rel_esc}', '{$mime_esc}', {$size_val}, 0)");

                // Agregar primer comentario al timeline con la imagen
                $img_comment = $conn->real_escape_string(
                    "Imagen del problema adjunta por {$_POST['reporter_name']}: " .
                    '<br><img src="' . htmlspecialchars($relative_path, ENT_QUOTES) . '" style="max-width:100%;border-radius:8px;" alt="Imagen del problema">'
                );
                $conn->query("INSERT INTO comments 
                    (ticket_id, user_id, user_type, comment, is_internal, date_created)
                    VALUES ({$ticket_id}, 0, 0, '{$img_comment}', 0, NOW())");
            }
        }
    }

    // ============================================================
    // ENVIAR EMAIL AL REPORTANTE (si proporcionó correo)
    // ============================================================
    $raw_reporter_email = trim($_POST['reporter_email'] ?? '');
    if (!empty($raw_reporter_email) && filter_var($raw_reporter_email, FILTER_VALIDATE_EMAIL)) {
        _sendTicketConfirmationEmail(
            $raw_reporter_email,
            htmlspecialchars($_POST['reporter_name'] ?? '', ENT_QUOTES),
            $ticket_number,
            $tracking_token,
            $subject,
            strip_tags($description)
        );
    }
    
    echo json_encode([
        'status' => 1,
        'ticket_id' => $ticket_id,
        'ticket_number' => $ticket_number,
        'tracking_token' => $tracking_token,
        'tracking_url' => 'track_ticket.php?token=' . $tracking_token,
        'message' => 'Ticket creado exitosamente'
    ]);
    
} catch (Exception $e) {
    error_log("Exception en save_public_ticket_direct: " . $e->getMessage());
    echo json_encode(['status' => 0, 'message' => 'Error inesperado: ' . $e->getMessage()]);
}

// ============================================================
// HELPER: Enviar email de confirmación al usuario
// ============================================================
function _sendTicketConfirmationEmail($to, $reporter_name, $ticket_number, $tracking_token, $subject_ticket, $description_text)
{
    try {
        // Cargar MailerService (usa PHPMailer SMTP)
        $mailerPath = dirname(__DIR__) . '/app/helpers/MailerService.php';
        if (file_exists($mailerPath) && !class_exists('MailerService')) {
            require_once $mailerPath;
        }

        $base_url     = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $tracking_url = $base_url . '/legacy/track_ticket.php?token=' . urlencode($tracking_token);
        $mail_subject = "Tu reporte ha sido recibido - Ticket $ticket_number";

        $body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px;">'
            . '<div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
            . '<div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:30px;text-align:center;">'
            . '<h2 style="color:#fff;margin:0;">Reporte Recibido</h2></div>'
            . '<div style="padding:30px;">'
            . '<p>Hola <strong>' . htmlspecialchars($reporter_name, ENT_QUOTES) . '</strong>,</p>'
            . '<p>Tu reporte de falla ha sido registrado correctamente en nuestro sistema.</p>'
            . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
            . '<tr><td style="padding:8px;background:#f8f9fa;font-weight:bold;width:40%;">N&uacute;mero de Ticket</td>'
            . '<td style="padding:8px;"><strong>' . htmlspecialchars($ticket_number, ENT_QUOTES) . '</strong></td></tr>'
            . '<tr><td style="padding:8px;font-weight:bold;">Asunto</td>'
            . '<td style="padding:8px;">' . htmlspecialchars($subject_ticket, ENT_QUOTES) . '</td></tr>'
            . '<tr><td style="padding:8px;background:#f8f9fa;font-weight:bold;">Descripci&oacute;n</td>'
            . '<td style="padding:8px;background:#f8f9fa;">' . nl2br(htmlspecialchars(mb_substr($description_text, 0, 300), ENT_QUOTES)) . (mb_strlen($description_text) > 300 ? '...' : '') . '</td></tr>'
            . '</table>'
            . '<p style="text-align:center;margin:24px 0;">'
            . '<a href="' . htmlspecialchars($tracking_url, ENT_QUOTES) . '" '
            . 'style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:12px 28px;border-radius:25px;text-decoration:none;font-size:15px;">'
            . 'Consultar Estado del Ticket</a></p>'
            . '<p style="color:#6b7280;font-size:13px;">Recibirás actualizaciones por este medio cuando cambien el estado de tu reporte.</p>'
            . '</div>'
            . '<div style="background:#f8f9fa;padding:16px;text-align:center;font-size:12px;color:#9ca3af;">'
            . 'Este correo fue generado automáticamente.</div>'
            . '</div></body></html>';

        if (class_exists('MailerService')) {
            MailerService::send($to, $reporter_name, $mail_subject, $body);
        }
    } catch (\Throwable $e) {
        error_log('_sendTicketConfirmationEmail error: ' . $e->getMessage());
    }
}
?>
