<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir ROOT
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

// Cargar sesión hardened
require_once ROOT . '/config/session.php';
require_once ROOT . '/app/helpers/permissions.php';

// Determinar acción antes de validar sesión para permitir públicas específicas
$requestedAction = $_REQUEST['action'] ?? '';

// Para el calendario, permitir obtener eventos sin sesión
$isPublicAction = in_array($requestedAction, ['get_mantenimientos'], true);

if (!$isPublicAction) {
    // Validar sesión activa
    if (!isset($_SESSION['login_id'])) {
        ob_start();
        http_response_code(401);
        echo json_encode(['status' => 0, 'msg' => 'Sesión expirada']);
        ob_end_flush();
        exit;
    }

    // Validar timeout
    if (!validate_session()) {
        ob_start();
        http_response_code(401);
        echo json_encode(['status' => 0, 'msg' => 'Sesión expirada por inactividad']);
        ob_end_flush();
        exit;
    }
}

// Iniciar buffer
ob_start();

$__env = strtolower(trim((string)(getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: '')));
$__is_debug = in_array($__env, ['local', 'dev', 'development'], true);

try {
    include ROOT . '/legacy/admin_class.php';
    $crud = new Action();
    // Mantener respuestas AJAX limpias (admin_class puede activar display_errors=1)
    ini_set('display_errors', 0);
} catch (Exception $e) {
    ob_end_clean();
    error_log("ERROR initializing admin_class: " . $e->getMessage());
    http_response_code(500);
    die("ERROR");
} catch (Error $e) {
    ob_end_clean();
    error_log("FATAL ERROR initializing admin_class: " . $e->getMessage());
    http_response_code(500);
    die("ERROR");
}

// === OBTENER ACCIÓN DE FORMA SEGURA ===
$action = $requestedAction;

// ===================================
// 1. LOGIN / LOGOUT
// ===================================
if ($action == 'login') {
    ob_end_clean(); // Limpiar cualquier output previo
    try {
        $result = $crud->login();
        echo $result;
    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        echo "2";
    }
    exit;
}

if ($action == 'logout') {
    $crud->logout();
    exit;
}

// ===================================
// 2. USUARIOS
// ===================================
if ($action == 'update_user_branch') {
    header('Content-Type: application/json; charset=utf-8');

    $loginType = (int)($_SESSION['login_type'] ?? 0);
    if ($loginType !== 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'msg' => 'Sin permisos']);
        exit;
    }

    $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
    if ($branch_id === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'msg' => 'branch_id requerido']);
        exit;
    }

    // 0 = Todas (solo admin)
    if ($branch_id !== 0) {
        if ($branch_id < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => 'branch_id inválido']);
            exit;
        }

        $db = $crud->getDb();
        if (!($db instanceof mysqli)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'msg' => 'DB no disponible']);
            exit;
        }

        $has_active = false;
        $col = @$db->query("SHOW COLUMNS FROM branches LIKE 'active'");
        if ($col && $col->num_rows > 0) {
            $has_active = true;
        }

        $sql = 'SELECT id' . ($has_active ? ', active' : '') . ' FROM branches WHERE id = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['success' => false, 'msg' => 'Error preparando consulta']);
            exit;
        }
        $stmt->bind_param('i', $branch_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'msg' => 'Sucursal no existe']);
            exit;
        }
        if ($has_active && (int)($row['active'] ?? 1) !== 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => 'Sucursal inactiva']);
            exit;
        }
    }

    $_SESSION['login_active_branch_id'] = $branch_id;

    // Persistir preferencia si la columna existe (no bloquea el flujo si falla)
    try {
        $db = $crud->getDb();
        $user_id = (int)($_SESSION['login_id'] ?? 0);
        if (($db instanceof mysqli) && $user_id > 0) {
            $col = @$db->query("SHOW COLUMNS FROM users LIKE 'active_branch_id'");
            if ($col && $col->num_rows > 0) {
                $stmt = $db->prepare('UPDATE users SET active_branch_id = ? WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('ii', $branch_id, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    } catch (Throwable $e) {
        error_log('update_user_branch persistence error: ' . $e->getMessage());
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action == 'save_user') {
    $env = strtolower(trim((string)(getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: '')));
    $is_debug = in_array($env, ['local', 'dev', 'development'], true);
    if ($is_debug) {
        error_log("=== AJAX save_user (debug) ===");
        error_log("user_id=" . (int)($_SESSION['login_id'] ?? 0) . ", login_type=" . (int)($_SESSION['login_type'] ?? 0));
    }
    $result = $crud->save_user();
    if ($is_debug) {
        error_log("Result: " . var_export($result, true));
    }
    echo $result;
    exit;
}

if ($action == 'delete_user') {
    echo $crud->delete_user();
    exit;
}

if ($action == 'check_username') {
    echo $crud->check_username();
    exit;
}

if ($action == 'upload_avatar') {
    echo $crud->upload_avatar();
    exit;
}

if ($action == 'delete_avatar') {
    echo $crud->delete_avatar();
    exit;
}

// ===================================
// 3. IMAGEN PÁGINA
// ===================================
if ($action == 'save_page_img') {
    echo $crud->save_page_img();
    exit;
}

// ===================================
// 4. STAFF / DEPARTAMENTOS
// ===================================
if ($action == 'save_staff') {
    echo $crud->save_staff();
    exit;
}

if ($action == 'delete_staff') {
    echo $crud->delete_staff();
    exit;
}

if ($action == 'save_department') {
    echo $crud->save_department();
    exit;
}

if ($action == 'delete_department') {
    echo $crud->delete_department();
    exit;
}

// ===================================
// 4.1 SUCURSALES
// ===================================
if ($action == 'save_branch') {
    echo $crud->save_branch();
    exit;
}

if ($action == 'delete_branch') {
    echo $crud->delete_branch();
    exit;
}

// ===================================
// 5. TICKETS
// ===================================
if ($action == 'save_ticket') {
    echo $crud->save_ticket();
    exit;
}

if ($action == 'save_public_ticket') {
    echo $crud->save_public_ticket();
    exit;
}

if ($action == 'update_ticket') {
    echo $crud->update_ticket();
    exit;
}

if ($action == 'delete_ticket') {
    echo $crud->delete_ticket();
    exit;
}

if ($action == 'save_comment') {
    echo $crud->save_comment();
    exit;
}

if ($action == 'delete_comment') {
    echo $crud->delete_comment();
    exit;
}

// ===================================
// 5b. ADJUNTOS DE TICKETS (E2.1)
// ===================================
if ($action == 'upload_ticket_attachment') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->upload_ticket_attachment();
    exit;
}

if ($action == 'delete_ticket_attachment') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->delete_ticket_attachment();
    exit;
}

if ($action == 'get_ticket_attachments') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->get_ticket_attachments();
    exit;
}

// ===================================
// 5c. NOTIFICACIONES (E2.2)
// ===================================
if ($action == 'get_notifications') {
    header('Content-Type: application/json; charset=utf-8');
    require_once ROOT . '/config/db.php';
    require_once ROOT . '/app/helpers/NotificationService.php';
    $userId = (int)$_SESSION['login_id'];
    $unread = NotificationService::getUnread($userId, 15);
    $count = NotificationService::countUnread($userId);
    echo json_encode(['status' => 1, 'count' => $count, 'notifications' => $unread]);
    exit;
}

if ($action == 'mark_notification_read') {
    header('Content-Type: application/json; charset=utf-8');
    require_once ROOT . '/config/db.php';
    require_once ROOT . '/app/helpers/NotificationService.php';
    $nid = (int)($_POST['id'] ?? 0);
    if ($nid > 0) NotificationService::markRead($nid);
    echo json_encode(['status' => 1]);
    exit;
}

if ($action == 'mark_all_notifications_read') {
    header('Content-Type: application/json; charset=utf-8');
    require_once ROOT . '/config/db.php';
    require_once ROOT . '/app/helpers/NotificationService.php';
    NotificationService::markAllRead((int)$_SESSION['login_id']);
    echo json_encode(['status' => 1]);
    exit;
}

// ===================================
// 5d. HISTORIAL DE ESTADOS (E2.3)
// ===================================
if ($action == 'get_ticket_timeline') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        require_once ROOT . '/config/db.php';
        $ticket_id = (int)($_GET['ticket_id'] ?? 0);
        if ($ticket_id <= 0) { echo json_encode([]); exit; }
        $pdo = get_pdo();
        // Verificar que la tabla existe
        $check = $pdo->query("SHOW TABLES LIKE 'ticket_status_history'");
        if ($check->rowCount() === 0) { echo json_encode([]); exit; }
        $stmt = $pdo->prepare("SELECT h.*, COALESCE(CONCAT(u.lastname, ', ', u.firstname), CONCAT(s.lastname, ', ', s.firstname), 'Sistema') as changed_by_name 
            FROM ticket_status_history h 
            LEFT JOIN users u ON u.id = h.changed_by 
            LEFT JOIN staff s ON s.id = h.changed_by 
            WHERE h.ticket_id = ? ORDER BY h.created_at ASC");
        $stmt->execute([$ticket_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {
        error_log('Timeline error: ' . $e->getMessage());
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 5e. COMENTARIOS JSON (Activity Stream)
// ===================================
if ($action == 'get_comments') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        require_once ROOT . '/config/db.php';
        $ticket_id = (int)($_GET['ticket_id'] ?? 0);
        if ($ticket_id <= 0) { echo json_encode([]); exit; }
        $pdo = get_pdo();
        $stmt = $pdo->prepare("SELECT c.id, c.user_id, c.user_type, c.ticket_id, c.comment, c.date_created,
            COALESCE(c.is_internal, 0) as is_internal
            FROM comments c WHERE c.ticket_id = ? ORDER BY c.date_created ASC");
        $stmt->execute([$ticket_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Resolve user names
        foreach ($comments as &$c) {
            $name = 'Usuario';
            if ($c['user_type'] == 1) {
                $u = $pdo->prepare("SELECT CONCAT(lastname,', ',firstname,' ',middlename) as name FROM users WHERE id = ?");
                $u->execute([$c['user_id']]);
                $r = $u->fetch(PDO::FETCH_ASSOC);
                if ($r) $name = trim($r['name']);
            } elseif ($c['user_type'] == 2) {
                $u = $pdo->prepare("SELECT CONCAT(lastname,', ',firstname,' ',middlename) as name FROM staff WHERE id = ?");
                $u->execute([$c['user_id']]);
                $r = $u->fetch(PDO::FETCH_ASSOC);
                if ($r) $name = trim($r['name']);
            } elseif ($c['user_type'] == 3) {
                $u = $pdo->prepare("SELECT CONCAT(lastname,', ',firstname,' ',middlename) as name FROM customers WHERE id = ?");
                $u->execute([$c['user_id']]);
                $r = $u->fetch(PDO::FETCH_ASSOC);
                if ($r) $name = trim($r['name']);
            }
            $c['user_name'] = $name;
            $c['comment_html'] = html_entity_decode($c['comment']);
        }
        unset($c);
        echo json_encode($comments);
    } catch (\Throwable $e) {
        error_log('Get comments error: ' . $e->getMessage());
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 5f. PRIORIDAD DE TICKET
// ===================================
if ($action == 'change_priority') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SESSION['login_type'] == 3) { echo json_encode(['status' => 0, 'msg' => 'Sin permisos']); exit; }
    try {
        require_once ROOT . '/config/db.php';
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $priority = $_POST['priority'] ?? 'medium';
        $allowed = ['low', 'medium', 'high', 'critical'];
        if (!in_array($priority, $allowed)) { echo json_encode(['status' => 0, 'msg' => 'Prioridad invalida']); exit; }
        if ($ticket_id <= 0) { echo json_encode(['status' => 0, 'msg' => 'ID invalido']); exit; }
        $pdo = get_pdo();
        $stmt = $pdo->prepare("UPDATE tickets SET priority = ? WHERE id = ?");
        $stmt->execute([$priority, $ticket_id]);
        echo json_encode(['status' => 1]);
    } catch (\Throwable $e) {
        error_log('change_priority error: ' . $e->getMessage());
        echo json_encode(['status' => 0, 'msg' => 'Error interno']);
    }
    exit;
}

// ===================================
// 5g. ASIGNACION DE TECNICO
// ===================================
if ($action == 'assign_ticket') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SESSION['login_type'] == 3) { echo json_encode(['status' => 0, 'msg' => 'Sin permisos']); exit; }
    try {
        require_once ROOT . '/config/db.php';
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        if ($ticket_id <= 0) { echo json_encode(['status' => 0, 'msg' => 'ID invalido']); exit; }
        $pdo = get_pdo();
        $val = $assigned_to > 0 ? $assigned_to : null;
        $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ? WHERE id = ?");
        $stmt->execute([$val, $ticket_id]);
        // Registrar en historial como evento
        $changedBy = (int)$_SESSION['login_id'];
        if ($assigned_to > 0) {
            $u = $pdo->prepare("SELECT CONCAT(firstname,' ',lastname) as name FROM users WHERE id = ?");
            $u->execute([$assigned_to]);
            $tech = $u->fetch(PDO::FETCH_ASSOC);
            $techName = $tech ? $tech['name'] : 'Tecnico #'.$assigned_to;
            $comment = 'Tecnico asignado: ' . $techName;
        } else {
            $comment = 'Tecnico desasignado';
        }
        $pdo->prepare("INSERT INTO ticket_status_history (ticket_id, old_status, new_status, changed_by, comment) 
            SELECT id, status, status, ?, ? FROM tickets WHERE id = ?")
            ->execute([$changedBy, $comment, $ticket_id]);
        // Enviar email al tecnico recien asignado
        if ($assigned_to > 0 && $assigned_to !== $changedBy) {
            try {
                $eq = $pdo->prepare("SELECT email, CONCAT(firstname,' ',lastname) AS name FROM users WHERE id = ? AND email IS NOT NULL AND email != '' LIMIT 1");
                $eq->execute([$assigned_to]);
                $techRow = $eq->fetch(PDO::FETCH_ASSOC);
                if ($techRow && filter_var($techRow['email'], FILTER_VALIDATE_EMAIL)) {
                    $tq = $pdo->prepare("SELECT subject FROM tickets WHERE id = ?");
                    $tq->execute([$ticket_id]);
                    $ticketData = $tq->fetch(PDO::FETCH_ASSOC);
                    $ticketSubject = htmlspecialchars($ticketData['subject'] ?? 'Sin asunto', ENT_QUOTES);
                    $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : (getenv('BASE_URL') ?: ''), '/');
                    $link = htmlspecialchars($baseUrl . '/index.php?page=view_ticket&id=' . $ticket_id, ENT_QUOTES);
                    $body  = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px'>";
                    $body .= "<div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:30px'>";
                    $body .= "<h2 style='color:#343a40'>Ticket Asignado</h2>";
                    $body .= "<p>Se te ha asignado el ticket <strong>#{$ticket_id}</strong> — <em>{$ticketSubject}</em>.</p>";
                    $body .= "<p style='margin-top:25px'><a href='{$link}' style='background:#007bff;color:#fff;padding:12px 24px;border-radius:5px;text-decoration:none;font-size:15px'>Ver ticket</a></p>";
                    $body .= "<hr style='margin-top:30px;border:none;border-top:1px solid #eee'>";
                    $body .= "<p style='font-size:12px;color:#999'>Este mensaje fue generado automaticamente.</p>";
                    $body .= "</div></body></html>";
                    $mailerPath = ROOT . '/app/helpers/MailerService.php';
                    if (file_exists($mailerPath)) {
                        require_once $mailerPath;
                        if (class_exists('MailerService')) {
                            MailerService::send($techRow['email'], $techRow['name'] ?? '', "Ticket #{$ticket_id} asignado", $body);
                        }
                    }
                }
            } catch (\Throwable $me) {
                error_log('assign_ticket mail error: ' . $me->getMessage());
            }
        }
        echo json_encode(['status' => 1]);
    } catch (\Throwable $e) {
        error_log('assign_ticket error: ' . $e->getMessage());
        echo json_encode(['status' => 0, 'msg' => 'Error interno']);
    }
    exit;
}

if ($action == 'get_technicians') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SESSION['login_type'] == 3) { echo json_encode([]); exit; }
    try {
        require_once ROOT . '/config/db.php';
        $pdo = get_pdo();
        $stmt = $pdo->query("SELECT id, CONCAT(firstname,' ',lastname) as name FROM users WHERE role IN (1,2) ORDER BY firstname ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 5h. RESPUESTAS RAPIDAS
// ===================================
if ($action == 'get_quick_replies') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SESSION['login_type'] == 3) { echo json_encode([]); exit; }
    try {
        require_once ROOT . '/config/db.php';
        $pdo = get_pdo();
        $check = $pdo->query("SHOW TABLES LIKE 'quick_replies'");
        if ($check->rowCount() === 0) { echo json_encode([]); exit; }
        $stmt = $pdo->query("SELECT id, title, content, category FROM quick_replies WHERE is_active = 1 ORDER BY sort_order ASC, title ASC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 5i. BUSQUEDA DE USUARIOS (para @menciones)
// ===================================
if ($action == 'search_users') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SESSION['login_type'] == 3) { echo json_encode([]); exit; }
    try {
        require_once ROOT . '/config/db.php';
        $pdo = get_pdo();
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = $pdo->prepare("SELECT id, CONCAT(firstname,' ',lastname) as name, 'admin' as type FROM users WHERE CONCAT(firstname,' ',lastname) LIKE ? 
            UNION SELECT id, CONCAT(firstname,' ',lastname) as name, 'staff' as type FROM staff WHERE CONCAT(firstname,' ',lastname) LIKE ?
            ORDER BY name ASC LIMIT 10");
        $stmt->execute([$q, $q]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (\Throwable $e) {
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 5j. GENERAR PDF DEL TICKET
// ===================================
if ($action == 'generate_ticket_pdf') {
    if ($_SESSION['login_type'] == 3) { header('HTTP/1.1 403 Forbidden'); exit; }
    $canTicketPdf = function_exists('can')
        ? (
            can('export', 'reports') || can('view', 'reports') ||
            can('export', 'tickets') || can('view', 'tickets')
        )
        : ((int)($_SESSION['login_type'] ?? 0) === 1);
    if (!$canTicketPdf && (int)($_SESSION['login_type'] ?? 0) !== 1) {
        header('HTTP/1.1 403 Forbidden');
        exit;
    }
    try {
        require_once ROOT . '/config/db.php';
        $ticket_id = (int)($_GET['ticket_id'] ?? 0);
        if ($ticket_id <= 0) { header('HTTP/1.1 400 Bad Request'); exit; }
        $pdo = get_pdo();
        
        // Ticket data
        $stmt = $pdo->prepare("SELECT t.*, 
            COALESCE(CONCAT(c.lastname,', ',c.firstname,' ',c.middlename), t.reporter_name, 'Cliente Publico') as cname,
            COALESCE(d.name, 'Sin Departamento') as dname,
            e.name as equipment_name, e.number_inventory,
            COALESCE(CONCAT(u.firstname,' ',u.lastname), 'Sin asignar') as assigned_name
            FROM tickets t
            LEFT JOIN customers c ON c.id = t.customer_id
            LEFT JOIN departments d ON d.id = t.department_id
            LEFT JOIN equipments e ON e.id = t.equipment_id
            LEFT JOIN users u ON u.id = t.assigned_to
            WHERE t.id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) { header('HTTP/1.1 404 Not Found'); exit; }
        
        // Comments
        $stmt = $pdo->prepare("SELECT c.*, 
            CASE c.user_type 
                WHEN 1 THEN (SELECT CONCAT(firstname,' ',lastname) FROM users WHERE id = c.user_id)
                WHEN 2 THEN (SELECT CONCAT(firstname,' ',lastname) FROM staff WHERE id = c.user_id)
                WHEN 3 THEN (SELECT CONCAT(firstname,' ',lastname) FROM customers WHERE id = c.user_id)
                ELSE 'Usuario'
            END as user_name
            FROM comments c WHERE c.ticket_id = ? AND COALESCE(c.is_internal,0) = 0 ORDER BY c.date_created ASC");
        $stmt->execute([$ticket_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Status history
        $stmt = $pdo->prepare("SELECT h.*, COALESCE(CONCAT(u.firstname,' ',u.lastname), 'Sistema') as changed_by_name 
            FROM ticket_status_history h LEFT JOIN users u ON u.id = h.changed_by 
            WHERE h.ticket_id = ? ORDER BY h.created_at ASC");
        $stmt->execute([$ticket_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $statusLabels = [0 => 'Abierto/Pendiente', 1 => 'En Proceso', 2 => 'Finalizado', 3 => 'Cerrado'];
        $priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'];
        
        // Generate HTML for PDF
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Ticket #<?php echo htmlspecialchars($ticket['ticket_number'] ?? $ticket['id']); ?></title>
<style>
    @media print { body { margin: 0; } .no-print { display: none !important; } }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #333; margin: 20px; line-height: 1.5; }
    .print-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #667eea; padding-bottom: 10px; margin-bottom: 15px; }
    .print-header h1 { font-size: 18px; color: #667eea; margin: 0; }
    .print-header .ticket-num { font-size: 14px; color: #666; }
    .section { margin-bottom: 15px; }
    .section-title { font-size: 13px; font-weight: bold; color: #667eea; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 8px; }
    .info-table { width: 100%; border-collapse: collapse; }
    .info-table td { padding: 4px 8px; border: 1px solid #e5e7eb; }
    .info-table .label { background: #f3f4f6; font-weight: 600; width: 30%; color: #555; }
    .description-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; border-radius: 4px; }
    .timeline-entry { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
    .timeline-entry:last-child { border-bottom: none; }
    .timeline-date { color: #999; font-size: 11px; }
    .timeline-author { font-weight: 600; }
    .comment-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; margin-top: 4px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
    .badge-priority-low { background: #dbeafe; color: #1e40af; }
    .badge-priority-medium { background: #fef3c7; color: #92400e; }
    .badge-priority-high { background: #fed7aa; color: #c2410c; }
    .badge-priority-critical { background: #fecaca; color: #991b1b; }
    .btn-print { position: fixed; top: 10px; right: 10px; background: #667eea; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; }
    .btn-print:hover { background: #5a67d8; }
    .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
<button class="btn-print no-print" onclick="window.print()">Imprimir</button>
<div class="print-header">
    <div>
        <h1><?php echo htmlspecialchars($ticket['subject']); ?></h1>
        <span class="ticket-num"><?php echo htmlspecialchars($ticket['ticket_number'] ?? 'TKT-'.$ticket['id']); ?></span>
    </div>
    <div style="text-align:right;">
        <div><strong>Estado:</strong> <?php echo $statusLabels[$ticket['status']] ?? 'Desconocido'; ?></div>
        <div><strong>Prioridad:</strong> <span class="badge badge-priority-<?php echo $ticket['priority'] ?? 'medium'; ?>"><?php echo $priorityLabels[$ticket['priority'] ?? 'medium']; ?></span></div>
        <div style="font-size:11px;color:#999;"><?php echo date('d/m/Y H:i', strtotime($ticket['date_created'])); ?></div>
    </div>
</div>

<div class="section">
    <div class="section-title">Informacion del Ticket</div>
    <table class="info-table">
        <tr><td class="label">Reportado por</td><td><?php echo htmlspecialchars($ticket['cname']); ?></td></tr>
        <tr><td class="label">Departamento</td><td><?php echo htmlspecialchars($ticket['dname']); ?></td></tr>
        <?php if (!empty($ticket['equipment_name'])): ?>
        <tr><td class="label">Equipo</td><td><?php echo htmlspecialchars($ticket['equipment_name']); ?> <?php if (!empty($ticket['number_inventory'])) echo '#'.htmlspecialchars($ticket['number_inventory']); ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($ticket['issue_type'])): ?>
        <tr><td class="label">Tipo de Falla</td><td><?php echo htmlspecialchars($ticket['issue_type']); ?></td></tr>
        <?php endif; ?>
        <tr><td class="label">Tecnico Asignado</td><td><?php echo htmlspecialchars($ticket['assigned_name']); ?></td></tr>
        <?php if (!empty($ticket['reporter_email'])): ?>
        <tr><td class="label">Email Reportante</td><td><?php echo htmlspecialchars($ticket['reporter_email']); ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($ticket['reporter_phone'])): ?>
        <tr><td class="label">Telefono</td><td><?php echo htmlspecialchars($ticket['reporter_phone']); ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="section">
    <div class="section-title">Descripcion</div>
    <div class="description-box"><?php echo html_entity_decode($ticket['description']); ?></div>
</div>

<?php if (!empty($history)): ?>
<div class="section">
    <div class="section-title">Historial de Estados</div>
    <?php foreach ($history as $h): ?>
    <div class="timeline-entry">
        <span class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></span> - 
        <span class="timeline-author"><?php echo htmlspecialchars($h['changed_by_name']); ?></span>: 
        <?php echo ($statusLabels[$h['old_status']] ?? 'Creado'); ?> &rarr; <?php echo ($statusLabels[$h['new_status']] ?? '?'); ?>
        <?php if (!empty($h['comment'])): ?><br><small><?php echo htmlspecialchars($h['comment']); ?></small><?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($comments)): ?>
<div class="section">
    <div class="section-title">Comentarios (<?php echo count($comments); ?>)</div>
    <?php foreach ($comments as $c): ?>
    <div class="timeline-entry">
        <span class="timeline-author"><?php echo htmlspecialchars($c['user_name'] ?? 'Usuario'); ?></span>
        <span class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($c['date_created'])); ?></span>
        <div class="comment-box"><?php echo html_entity_decode($c['comment']); ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="footer">
    Documento generado el <?php echo date('d/m/Y H:i:s'); ?> - Sistema de Gestion de Tickets
</div>
</body>
</html>
        <?php
    } catch (\Throwable $e) {
        error_log('generate_ticket_pdf error: ' . $e->getMessage());
        echo 'Error al generar el documento';
    }
    exit;
}

// ===================================
// 6. EQUIPOS
// ===================================
if ($action == 'check_serie') {
    header('Content-Type: application/json; charset=utf-8');
    $serie = trim($_GET['serie'] ?? $_POST['serie'] ?? '');
    $excludeId = (int)($_GET['exclude_id'] ?? $_POST['exclude_id'] ?? 0);
    
    if ($serie === '') {
        echo json_encode(['available' => true]);
        exit;
    }
    
    try {
        $pdo = get_pdo();
        if (!$pdo) {
            echo json_encode(['available' => true, 'error' => true]);
            exit;
        }
        $sql = "SELECT id FROM equipments WHERE serie = ? AND serie != ''";
        $params = [$serie];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'Este numero de serie ya existe' : 'Disponible'
        ]);
    } catch (Exception $e) {
        echo json_encode(['available' => true, 'error' => true]);
    }
    exit;
}

if ($action == 'save_equipment') {
    $result = $crud->save_equipment();
    if ($result === 1) {
        $entityId = $crud->lastInsertId ?: (int)($_POST['id'] ?? 0);
        if (!empty($_POST['cf']) && is_array($_POST['cf']) && $entityId > 0) {
            try {
                require_once ROOT . '/app/models/CustomField.php';
                (new CustomField())->saveValues('equipment', $entityId, $_POST['cf']);
            } catch (Exception $__cfEx) { error_log('CF save equipment: ' . $__cfEx->getMessage()); }
        }
        echo json_encode(['s' => 1, 'id' => $entityId]);
    } elseif (is_string($result) && isset($result[0]) && $result[0] === '{') {
        echo $result; // JSON de error de serie
    } else {
        echo json_encode(['s' => 0, 'msg' => 'Error ' . $result]);
    }
    exit;
}

// 
if ($action == 'delete_equipment_image') {
    echo $crud->delete_equipment_image();
    exit;
}

if ($action == 'delete_equipment') {
    echo $crud->delete_equipment();
    exit;
}

if ($action == 'save_equipment_unsubscribe') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->save_equipment_unsubscribe();
    exit;
}

if ($action == 'save_equipment_revision') {
    echo $crud->save_equipment_revision();
    exit;
}

if ($action == 'upload_excel_equipment') {
    error_log("AJAX upload_excel_equipment llamado");
    $result = $crud->upload_excel_equipment();
    error_log("Respuesta upload_excel_equipment: $result");
    header('Content-Type: application/json; charset=utf-8');
    echo $result;
    exit;
}

if ($action == 'download_template') {
    // Redirigir a archivo dedicado para la plantilla Excel
    header('Location: download_template.php');
    exit;
}

// ===================================
// 7. PROVEEDORES
// ===================================
if ($action == 'save_supplier') {
    echo $crud->save_supplier();
    exit;
}

if ($action == 'delete_supplier') {
    echo $crud->delete_supplier();
    exit;
}

// ===================================
// 8. HERRAMIENTAS
// ===================================
if ($action == 'save_tool') {
    error_log("AJAX save_tool llamado");
    $result = $crud->save_tool();
    error_log("Respuesta save_tool: $result");
    if ($result == 1) {
        $entityId = $crud->lastInsertId ?: (int)($_POST['id'] ?? 0);
        if (!empty($_POST['cf']) && is_array($_POST['cf']) && $entityId > 0) {
            try {
                require_once ROOT . '/app/models/CustomField.php';
                (new CustomField())->saveValues('tool', $entityId, $_POST['cf']);
            } catch (Exception $__cfEx) { error_log('CF save tool: ' . $__cfEx->getMessage()); }
        }
        echo json_encode(['s' => 1, 'id' => $entityId]);
    } else {
        echo json_encode(['s' => 0, 'msg' => (string)$result]);
    }
    exit;
}

if ($action == 'delete_tool') {
    $result = $crud->delete_tool();
    echo $result;
    exit;
}

// ===================================
// 9. ACCESORIOS
// ===================================
if ($action == 'save_accessory') {
    if ($__is_debug) {
        error_log('=== SAVE_ACCESSORY ACTION (debug) ===');
        error_log('POST keys: ' . implode(',', array_keys($_POST)));
        error_log('FILES keys: ' . implode(',', array_keys($_FILES)));
        error_log('Session login_id: ' . (int)($_SESSION['login_id'] ?? 0));
        error_log('Session login_type: ' . (int)($_SESSION['login_type'] ?? 0));
        error_log('Session active_branch_id: ' . (int)($_SESSION['login_active_branch_id'] ?? 0));
    }
    $result = $crud->save_accessory();
    if ($__is_debug) {
        error_log('save_accessory result: ' . $result);
    }
    if ($result == 1) {
        $entityId = $crud->lastInsertId ?: (int)($_POST['id'] ?? 0);
        if (!empty($_POST['cf']) && is_array($_POST['cf']) && $entityId > 0) {
            try {
                require_once ROOT . '/app/models/CustomField.php';
                (new CustomField())->saveValues('accessory', $entityId, $_POST['cf']);
            } catch (Exception $__cfEx) { error_log('CF save accessory: ' . $__cfEx->getMessage()); }
        }
        echo json_encode(['s' => 1, 'id' => $entityId]);
    } else {
        echo json_encode(['s' => 0, 'msg' => (string)$result]);
    }
    exit;
}

if ($action == 'delete_accessory') {
    echo $crud->delete_accessory();
    exit;
}

// ===================================
// 10. MANTENIMIENTOS
// ===================================
if ($action == 'get_mantenimientos') {
    $crud->get_mantenimientos(); // Ya tiene header + exit
    exit;
}

if ($action == 'save_maintenance') {
    echo $crud->save_maintenance();
    exit;
}

if ($action == 'complete_maintenance') {
    echo $crud->complete_maintenance();
    exit;
}

// ===================================
// 11. UBICACIONES
// ===================================
if ($action == 'save_equipment_location') {
    echo $crud->save_equipment_location();
    exit;
}

if ($action == 'delete_equipment_location') {
    echo $crud->delete_equipment_location();
    exit;
}

// ===================================
// 12. PUESTOS DE TRABAJO
// ===================================
if ($action == 'save_job_position') {
    echo $crud->save_job_position();
    exit;
}

if ($action == 'delete_job_position') {
    echo $crud->delete_job_position();
    exit;
}

if ($action == 'get_job_positions_by_location') {
    // Limpiar cualquier salida previa y establecer header JSON
    if (ob_get_level()) ob_end_clean();
    ob_start();
    header('Content-Type: application/json');
    
    // Incluir conexión a BD (usar ROOT para evitar rutas relativas rotas en hosting)
    require_once ROOT . '/config/config.php';
    global $conn;
    if (!$conn) {
        if (isset($crud) && method_exists($crud, 'getDb')) {
            $conn = $crud->getDb();
        }
        if (!$conn && function_exists('db')) {
            $conn = db();
        }
    }
    
    error_log("DEBUG: get_job_positions_by_location called");
    $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
    error_log("DEBUG: location_id = " . $location_id);
    
    if ($location_id > 0) {
        // Primero intentar con la nueva estructura (location_id directo en job_positions)
        $query = "SELECT j.id, j.name 
                  FROM job_positions j 
                  WHERE j.location_id = $location_id 
                  ORDER BY j.name ASC";
        error_log("DEBUG: Query (new structure) = " . $query);
        
        $qry = $conn->query($query);
        $positions = [];
        
        if($qry && $qry->num_rows > 0) {
            error_log("DEBUG: Using new structure, rows = " . $qry->num_rows);
            while ($row = $qry->fetch_assoc()) {
                $positions[] = $row;
            }
        } else {
            // Fallback a estructura antigua (tabla intermedia location_positions)
            $query = "SELECT j.id, j.name 
                      FROM job_positions j 
                      INNER JOIN location_positions lp ON lp.job_position_id = j.id 
                      WHERE lp.location_id = $location_id 
                      ORDER BY j.name ASC";
            error_log("DEBUG: Fallback query (old structure) = " . $query);
            
            $qry = $conn->query($query);
            if($qry) {
                error_log("DEBUG: Using old structure, rows = " . $qry->num_rows);
                while ($row = $qry->fetch_assoc()) {
                    $positions[] = $row;
                }
            } else {
                error_log("DEBUG: Query error = " . $conn->error);
            }
        }
        
        error_log("DEBUG: Returning " . count($positions) . " positions");
        echo json_encode($positions);
    } else {
        error_log("DEBUG: location_id is 0 or invalid");
        echo json_encode([]);
    }
    exit;
}

if ($action == 'get_locations_by_department') {
    // Limpiar cualquier salida previa y establecer header JSON
    if (ob_get_level()) ob_end_clean();
    ob_start();
    header('Content-Type: application/json');
    
    // Incluir conexión a BD (usar ROOT para evitar rutas relativas rotas en hosting)
    require_once ROOT . '/config/config.php';
    global $conn;
    if (!$conn) {
        if (isset($crud) && method_exists($crud, 'getDb')) {
            $conn = $crud->getDb();
        }
        if (!$conn && function_exists('db')) {
            $conn = db();
        }
    }
    
    error_log("DEBUG get_locations_by_department: Called");
    
    try {
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
        error_log("DEBUG: department_id = $department_id");
        
        if ($department_id > 0) {
            // Obtener ubicaciones que pertenecen al departamento
            $query = "SELECT l.id, l.name 
                     FROM locations l 
                     WHERE l.department_id = $department_id 
                     ORDER BY l.name ASC";
            error_log("DEBUG: Query = $query");
            
            $qry = $conn->query($query);
            $locations = [];
            
            if($qry) {
                while ($row = $qry->fetch_assoc()) {
                    $locations[] = $row;
                }
                error_log("DEBUG: Found " . count($locations) . " locations");
            } else {
                error_log("ERROR: Query failed: " . $conn->error);
            }
            
            echo json_encode($locations);
        } else {
            error_log("DEBUG: No department_id, returning all locations");
            // Si no hay departamento, devolver todas las ubicaciones
            $qry = $conn->query("SELECT id, name FROM locations ORDER BY name ASC");
            $locations = [];
            
            if($qry) {
                while ($row = $qry->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            
            echo json_encode($locations);
        }
    } catch (Exception $e) {
        error_log("EXCEPTION in get_locations_by_department: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_next_inventory_number') {
    // Asegurar que no haya basura antes del JSON (warnings/notice/espacios)
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    $acquisition_type_id = isset($_POST['acquisition_type_id']) ? (int)$_POST['acquisition_type_id'] : (isset($_POST['acquisition_type']) ? (int)$_POST['acquisition_type'] : 0);
    $equipment_category_id = isset($_POST['equipment_category_id']) ? (int)$_POST['equipment_category_id'] : 0;

    if ($branch_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Branch ID requerido']);
        exit;
    }

    // Si no vienen adquisición/categoría, generar con el esquema simple por sucursal.
    // Esto mantiene compatibilidad con formularios como "Nuevo Accesorio".
    $has_full_params = ($acquisition_type_id > 0 && $equipment_category_id > 0);

    try {
        // Validaciones rápidas de existencia (evita fallos silenciosos)
        if (isset($conn) && $conn) {
            $chk = @$conn->query("SELECT id FROM branches WHERE id = {$branch_id} LIMIT 1");
            if (!$chk || $chk->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'Sucursal inválida']);
                exit;
            }

            if ($acquisition_type_id > 0) {
                $chk = @$conn->query("SELECT id FROM acquisition_type WHERE id = {$acquisition_type_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de adquisición inválido']);
                    exit;
                }
            }
            if ($equipment_category_id > 0) {
                $chk = @$conn->query("SELECT id FROM equipment_categories WHERE id = {$equipment_category_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Categoría inválida']);
                    exit;
                }
            }
        }

        $number = $has_full_params
            ? $crud->get_next_inventory_number($branch_id, $acquisition_type_id, $equipment_category_id)
            : $crud->get_next_inventory_number($branch_id, null, null);
        if (!$number) {
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo generar el número de inventario (revisa configuración de sucursal)'
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'number' => $number]);
        exit;
    } catch (Throwable $e) {
        error_log('ACTION get_next_inventory_number THROWABLE: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error interno al generar el número de inventario']);
        exit;
    }
}

if ($action == 'preview_inventory_number') {
    // Asegurar que no haya basura antes del JSON (warnings/notice/espacios)
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    $acquisition_type_id = isset($_POST['acquisition_type_id']) ? (int)$_POST['acquisition_type_id'] : (isset($_POST['acquisition_type']) ? (int)$_POST['acquisition_type'] : 0);
    $equipment_category_id = isset($_POST['equipment_category_id']) ? (int)$_POST['equipment_category_id'] : 0;

    if ($branch_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Branch ID requerido']);
        exit;
    }

    // Si no vienen adquisición/categoría, generar con el esquema simple por sucursal.
    $has_full_params = ($acquisition_type_id > 0 && $equipment_category_id > 0);

    try {
        // Validaciones rápidas de existencia (evita fallos silenciosos)
        if (isset($conn) && $conn) {
            $chk = @$conn->query("SELECT id FROM branches WHERE id = {$branch_id} LIMIT 1");
            if (!$chk || $chk->num_rows === 0) {
                echo json_encode(['success' => false, 'error' => 'Sucursal inválida']);
                exit;
            }

            if ($acquisition_type_id > 0) {
                $chk = @$conn->query("SELECT id FROM acquisition_type WHERE id = {$acquisition_type_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de adquisición inválido']);
                    exit;
                }
            }
            if ($equipment_category_id > 0) {
                $chk = @$conn->query("SELECT id FROM equipment_categories WHERE id = {$equipment_category_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Categoría inválida']);
                    exit;
                }
            }
        }

        $number = $has_full_params
            ? $crud->preview_inventory_number($branch_id, $acquisition_type_id, $equipment_category_id)
            : $crud->preview_inventory_number($branch_id, null, null);

        if (!$number) {
            echo json_encode([
                'success' => false,
                'error' => 'No se pudo previsualizar el número de inventario'
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'number' => $number]);
        exit;
    } catch (Throwable $e) {
        error_log('ACTION preview_inventory_number THROWABLE: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error interno al previsualizar el número de inventario']);
        exit;
    }
}

if ($action == 'get_positions_by_department') {
    // Limpiar cualquier salida previa y establecer header JSON
    if (ob_get_level()) ob_end_clean();
    ob_start();
    header('Content-Type: application/json');
    
    // Incluir conexión a BD (usar ROOT para evitar rutas relativas rotas en hosting)
    require_once ROOT . '/config/config.php';
    global $conn;
    if (!$conn) {
        if (isset($crud) && method_exists($crud, 'getDb')) {
            $conn = $crud->getDb();
        }
        if (!$conn && function_exists('db')) {
            $conn = db();
        }
    }
    
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
    
    if ($department_id > 0) {
        // Obtener puestos que pertenecen al departamento
        $qry = $conn->query("SELECT j.id, j.name 
                             FROM job_positions j 
                             WHERE j.department_id = $department_id 
                             ORDER BY j.name ASC");
        $positions = [];
        
        if($qry) {
            while ($row = $qry->fetch_assoc()) {
                $positions[] = $row;
            }
        }
        
        echo json_encode($positions);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 13. INVENTARIO
// ===================================
if ($action == 'save_inventory') {
    error_log("AJAX save_inventory llamado");
    $result = $crud->save_inventory();
    error_log("Respuesta save_inventory: $result");
    if ($result == 1) {
        $entityId = $crud->lastInsertId ?: (int)($_POST['id'] ?? 0);
        if (!empty($_POST['cf']) && is_array($_POST['cf']) && $entityId > 0) {
            try {
                require_once ROOT . '/app/models/CustomField.php';
                (new CustomField())->saveValues('inventory', $entityId, $_POST['cf']);
            } catch (Exception $__cfEx) { error_log('CF save inventory: ' . $__cfEx->getMessage()); }
        }
        echo json_encode(['s' => 1, 'id' => $entityId]);
    } else {
        echo json_encode(['s' => 0, 'msg' => (string)$result]);
    }
    exit;
}

if ($action == 'delete_inventory') {
    error_log("AJAX delete_inventory llamado");
    $result = $crud->delete_inventory();
    error_log("Respuesta delete_inventory: $result");
    echo $result;
    exit;
}

if ($action == 'save_maintenance_report') {
    echo $crud->save_maintenance_report();
    exit;
}

// ===================================
// CONFIGURACIÓN: Tipos de adquisición y categorías de equipos
// ===================================
if ($action == 'load_acquisition_type') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->load_acquisition_type();
    exit;
}

if ($action == 'save_acquisition_type') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->save_acquisition_type();
    exit;
}

if ($action == 'delete_acquisition_type') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->delete_acquisition_type();
    exit;
}

if ($action == 'load_equipment_category') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->load_equipment_category();
    exit;
}

if ($action == 'save_equipment_category') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->save_equipment_category();
    exit;
}

if ($action == 'delete_equipment_category') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->delete_equipment_category();
    exit;
}

if ($action == 'get_equipo_details') {
    echo $crud->get_equipo_details(); 
    exit;
}

if ($action == 'update_and_save_report') {
}

// ================== SERVICIOS Y CATEGORÍAS ==================
if ($action == 'save_category') {
    echo $crud->save_category();
    exit;
}

if ($action == 'delete_service_category') {
    echo $crud->delete_service_category();
    exit;
}

if ($action == 'load_service_category') {
    echo $crud->load_service_category();
    exit;
}

if ($action == 'save_service') {
    echo $crud->save_service();
    exit;
}

if ($action == 'delete_service') {
    echo $crud->delete_service();
    exit;
}

if ($action == 'load_service') {
    echo $crud->load_service();
    exit;
}

// ===================================
// 14. CONFIGURACION DE EMPRESA
// ===================================
if ($action == 'save_company_config') {
    header('Content-Type: application/json; charset=utf-8');
    // Solo admin puede modificar
    if (((int)($_SESSION['login_type'] ?? 0)) !== 1) {
        echo json_encode(['status' => 0, 'message' => 'Sin permisos.']);
        exit;
    }

    $branch_id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0;
    if ($branch_id <= 0) {
        echo json_encode(['status' => 0, 'message' => 'Sucursal invalida.']);
        exit;
    }

    $db = $crud->getDb();

    // Verificar/crear tabla
    $tableCheck = $db->query("SHOW TABLES LIKE 'company_config'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        // Crear tabla en caliente
        $createSql = "CREATE TABLE IF NOT EXISTS `company_config` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `branch_id` INT UNSIGNED NOT NULL,
            `company_name` VARCHAR(255) NOT NULL DEFAULT '',
            `address_line_1` VARCHAR(255) NOT NULL DEFAULT '',
            `address_line_2` VARCHAR(255) NOT NULL DEFAULT '',
            `city_state_zip` VARCHAR(255) NOT NULL DEFAULT '',
            `phone_number` VARCHAR(255) NOT NULL DEFAULT '',
            `company_description` VARCHAR(500) NOT NULL DEFAULT '',
            `logo_path` VARCHAR(500) NOT NULL DEFAULT '',
            `report_prefix` VARCHAR(20) NOT NULL DEFAULT 'O.T',
            `unsubscribe_prefix` VARCHAR(20) NOT NULL DEFAULT 'BAJA',
            `report_current_number` INT UNSIGNED NOT NULL DEFAULT 0,
            `report_current_year` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `report_current_month` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `unsubscribe_current_number` INT UNSIGNED NOT NULL DEFAULT 0,
            `unsubscribe_current_year` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `unsubscribe_current_month` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_branch` (`branch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->query($createSql);
    }

    // Asegurar columna logo_path en instalaciones previas
    $colLogo = $db->query("SHOW COLUMNS FROM company_config LIKE 'logo_path'");
    if (!$colLogo || $colLogo->num_rows === 0) {
        $db->query("ALTER TABLE company_config ADD COLUMN logo_path VARCHAR(500) NOT NULL DEFAULT '' AFTER company_description");
    }

    $fields = ['company_name', 'address_line_1', 'address_line_2', 'city_state_zip', 'phone_number', 'company_description', 'report_prefix', 'unsubscribe_prefix'];
    $setValues = [];
    foreach ($fields as $field) {
        $val = isset($_POST[$field]) ? trim($_POST[$field]) : '';
        $setValues[] = "`{$field}` = '" . $db->real_escape_string($val) . "'";
    }

    // Upload de logo (opcional)
    if (isset($_FILES['logo_file']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $logo = $_FILES['logo_file'];
        if ((int)$logo['size'] > 2 * 1024 * 1024) {
            echo json_encode(['status' => 0, 'message' => 'El logo supera los 2 MB permitidos.']);
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($logo['tmp_name']);
        $allowed = ['image/jpeg', 'image/png'];
        if (!in_array($mime, $allowed, true)) {
            echo json_encode(['status' => 0, 'message' => 'Formato de logo no permitido. Usa JPG o PNG.']);
            exit;
        }

        $ext = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $ext = ($mime === 'image/png') ? 'png' : 'jpg';
        }

        $targetDir = 'uploads/logos/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $filename = 'branch_' . $branch_id . '_' . time() . '_' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ext;
        $dest = $targetDir . $filename;

        if (!move_uploaded_file($logo['tmp_name'], $dest)) {
            echo json_encode(['status' => 0, 'message' => 'No se pudo guardar el logo.']);
            exit;
        }

        $setValues[] = "`logo_path` = '" . $db->real_escape_string($dest) . "'";
    }

    // Verificar si ya existe
    $existing = $db->query("SELECT id FROM company_config WHERE branch_id = {$branch_id} LIMIT 1");
    if ($existing && $existing->num_rows > 0) {
        $sql = "UPDATE company_config SET " . implode(', ', $setValues) . " WHERE branch_id = {$branch_id}";
    } else {
        $sql = "INSERT INTO company_config SET branch_id = {$branch_id}, " . implode(', ', $setValues);
    }

    $result = $db->query($sql);
    if ($result) {
        echo json_encode(['status' => 1, 'message' => 'Guardado correctamente.']);
    } else {
        error_log('save_company_config error: ' . $db->error);
        echo json_encode(['status' => 0, 'message' => 'Error al guardar.']);
    }
    exit;
}

ob_end_flush();
?>