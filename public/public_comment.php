<?php
// Endpoint publico: permite al reportante enviar un comentario usando su tracking_token
// Autonomo: no usa config.php para evitar side-effects (session, headers, output)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json; charset=utf-8');

function pc_json($data) {
    ob_end_clean();
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    pc_json(['status' => 0, 'msg' => 'Metodo no permitido']);
}

$token   = trim($_POST['token'] ?? '');
$comment = trim($_POST['comment'] ?? '');

if (empty($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    pc_json(['status' => 0, 'msg' => 'Token invalido']);
}

if (empty($comment) || mb_strlen($comment) > 2000) {
    pc_json(['status' => 0, 'msg' => 'El mensaje es requerido (max 2000 caracteres)']);
}

// Cargar .env manualmente
$env_file = __DIR__ . '/../config/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (preg_match('/^["\'](.*)["\']\z/', $v, $m)) $v = $m[1];
        if (getenv($k) === false) { putenv("$k=$v"); $_ENV[$k] = $v; }
    }
}

// Detectar credenciales (produccion vs local segun DB_HOST_PROD)
$db_host = getenv('DB_HOST_PROD') ?: getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER_PROD') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS_PROD') ?: getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME_PROD') ?: getenv('DB_NAME') ?: '';

if (empty($db_host) || empty($db_user) || empty($db_name)) {
    error_log('public_comment.php: credenciales de BD no configuradas');
    pc_json(['status' => 0, 'msg' => 'Error de configuracion']);
}

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) {
    error_log('public_comment.php DB error: ' . $e->getMessage());
    pc_json(['status' => 0, 'msg' => 'Error de conexion a BD']);
}

$stmt = $pdo->prepare("SELECT id, reporter_name, is_public, status FROM tickets
    WHERE tracking_token = ? AND is_public = 1 LIMIT 1");
$stmt->execute([$token]);
$ticket = $stmt->fetch();

if (!$ticket) {
    pc_json(['status' => 0, 'msg' => 'Ticket no encontrado']);
}

$ticket_id = (int)$ticket['id'];

if ((int)$ticket['status'] >= 3) {
    pc_json(['status' => 0, 'msg' => 'El ticket esta cerrado y no acepta mas mensajes']);
}

$now = date('Y-m-d H:i:s');
$ins = $pdo->prepare("INSERT INTO comments (ticket_id, comment, user_id, user_type, is_internal, date_created)
    VALUES (?, ?, 0, 3, 0, ?)");
$ins->execute([$ticket_id, htmlentities($comment), $now]);

if ($ins->rowCount() === 0) {
    pc_json(['status' => 0, 'msg' => 'Error al guardar el mensaje']);
}

// Notificar al tecnico asignado si hay MailerService disponible (ignorar cualquier error)
$mailer_path = __DIR__ . '/../app/helpers/MailerService.php';
if (file_exists($mailer_path)) {
    try {
        if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
        if (!defined('ACCESS')) define('ACCESS', true);
        require_once $mailer_path;
        $nq = $pdo->prepare("SELECT u.email FROM users u
            INNER JOIN tickets t ON t.assigned_to = u.id
            WHERE t.id = ? AND u.email IS NOT NULL AND u.email != '' LIMIT 1");
        $nq->execute([$ticket_id]);
        $row = $nq->fetch();
        if ($row && class_exists('MailerService')) {
            $reporter = htmlspecialchars($ticket['reporter_name'] ?? 'Reportante');
            $preview  = nl2br(htmlspecialchars(mb_substr(strip_tags($comment), 0, 300)));
            $body = "<p>El reportante <strong>{$reporter}</strong> respondio en el ticket #{$ticket_id}.</p>"
                  . "<blockquote style='border-left:3px solid #667eea;padding:8px 12px'>{$preview}</blockquote>";
            MailerService::send($row['email'], '', "Nueva respuesta en ticket #{$ticket_id}", $body);
        }
    } catch (Throwable $e) {
        error_log('public_comment mail error: ' . $e->getMessage());
    }
}

pc_json(['status' => 1]);
