<?php
/**
 * public/ajax/report_attachment.php - Endpoint AJAX para adjuntos de reportes
 *
 * POST /public/ajax/report_attachment.php?action=upload   → sube foto temporal
 * POST /public/ajax/report_attachment.php?action=delete   → elimina adjunto
 * GET  /public/ajax/report_attachment.php?action=list     → lista por report_id
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log errors to a file we can access
ini_set('error_log', dirname(dirname(dirname(__FILE__))) . '/logs/report_attachment.log');

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

require_once ROOT . '/config/session.php';

if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

if (!validate_session()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada por inactividad']);
    exit;
}

require_once ROOT . '/config/db.php';

header('Content-Type: application/json');

$action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? $_POST['action'] ?? ''));

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción requerida']);
    exit;
}

// -----------------------------------------------------------------------
// CONSTANTES DE CONFIGURACIÓN
// -----------------------------------------------------------------------
define('RA_UPLOAD_DIR',   ROOT . '/uploads/reports/');
define('RA_MAX_FILES',    10);
define('RA_MAX_BYTES',    5 * 1024 * 1024);   // 5 MB por foto
define('RA_ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Crear directorio si no existe (CRÍTICO para evitar errores de upload)
if (!is_dir(RA_UPLOAD_DIR)) {
    @mkdir(RA_UPLOAD_DIR, 0755, true);
    @chmod(RA_UPLOAD_DIR, 0755);
}

// -----------------------------------------------------------------------
try {
    $pdo = get_pdo();

    switch ($action) {

        // ----------------------------------------------------------------
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }

            if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
                echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
                exit;
            }

            $file  = $_FILES['photo'];
            $error = $file['error'];

            if ($error !== UPLOAD_ERR_OK) {
                $msg = match ($error) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Archivo demasiado grande',
                    UPLOAD_ERR_PARTIAL   => 'Carga incompleta',
                    default              => "Error de carga ($error)",
                };
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }

            // Validar tamaño
            if ($file['size'] > RA_MAX_BYTES) {
                echo json_encode(['success' => false, 'message' => 'El archivo supera 5 MB']);
                exit;
            }

            // Validar MIME real (no la extensión del cliente)
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            if (!in_array($mimeType, RA_ALLOWED_MIME, true)) {
                echo json_encode(['success' => false, 'message' => 'Solo se permiten imágenes JPG, PNG, GIF o WEBP']);
                exit;
            }

            // Validar que no supere el límite de fotos temporales de esta sesión
            $userId    = (int)$_SESSION['login_id'];
            $countStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM report_attachments WHERE report_id = 0 AND created_at > (NOW() - INTERVAL 2 HOUR)"
            );
            $countStmt->execute();
            if ((int)$countStmt->fetchColumn() >= RA_MAX_FILES) {
                echo json_encode(['success' => false, 'message' => 'Límite de ' . RA_MAX_FILES . ' fotos alcanzado']);
                exit;
            }

            // Crear directorio si no existe
            if (!is_dir(RA_UPLOAD_DIR)) {
                mkdir(RA_UPLOAD_DIR, 0755, true);
            }

            // Generar nombre único con extensión validada
            $ext      = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath = RA_UPLOAD_DIR . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo']);
                exit;
            }

            // Insertar registro temporal (report_id = 0)
            $ins = $pdo->prepare(
                "INSERT INTO report_attachments (report_id, file_name, file_path, sort_order)
                 VALUES (0, :name, :path, 0)"
            );
            $ins->execute([':name' => $fileName, ':path' => 'uploads/reports/' . $fileName]);
            $attachId = (int)$pdo->lastInsertId();

            echo json_encode([
                'success'   => true,
                'id'        => $attachId,
                'file_name' => $fileName,
                'file_path' => 'uploads/reports/' . $fileName,
            ]);
            break;

        // ----------------------------------------------------------------
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }

            $sel = $pdo->prepare("SELECT file_path FROM report_attachments WHERE id = :id");
            $sel->execute([':id' => $id]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Adjunto no encontrado']);
                exit;
            }

            // Eliminar archivo físico
            $fullPath = ROOT . '/' . ltrim($row['file_path'], '/');
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $del = $pdo->prepare("DELETE FROM report_attachments WHERE id = :id");
            $del->execute([':id' => $id]);

            echo json_encode(['success' => true]);
            break;

        // ----------------------------------------------------------------
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }

            $reportId = (int)($_GET['report_id'] ?? 0);
            if ($reportId <= 0) {
                echo json_encode(['success' => false, 'data' => []]);
                exit;
            }

            $lst = $pdo->prepare(
                "SELECT id, file_name, file_path, sort_order
                   FROM report_attachments
                  WHERE report_id = :rid
                  ORDER BY sort_order ASC, id ASC"
            );
            $lst->execute([':rid' => $reportId]);
            $rows = $lst->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        // ----------------------------------------------------------------
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$action}' no existe"]);
    }

} catch (PDOException $e) {
    $errorMsg = 'REPORT_ATTACHMENT AJAX ERROR: ' . $e->getMessage() . ' | Code: ' . $e->getCode() . ' | Line: ' . $e->getLine();
    error_log($errorMsg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor', 'debug' => $errorMsg]);
} catch (Exception $e) {
    $errorMsg = 'REPORT_ATTACHMENT UNEXPEC ERROR: ' . $e->getMessage();
    error_log($errorMsg);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error inesperado', 'debug' => $errorMsg]);
}
