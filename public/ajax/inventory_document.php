<?php
/**
 * Endpoint AJAX para gestión de documentos de inventario
 * Sprint 4 - E5.1
 */
if (!defined('ROOT')) {
    define('ROOT', realpath(dirname(__DIR__, 2)));
}
require_once ROOT . '/config/session.php';
require_once ROOT . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!validate_session()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => 'No autenticado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo    = get_pdo();

// ─── UPLOAD ────────────────────────────────────────────────
if ($action === 'upload') {
    $inventory_id   = (int)($_POST['inventory_id'] ?? 0);
    $document_type  = $_POST['document_type'] ?? 'other';
    $allowed_types  = ['safety_data_sheet', 'certificate', 'photo', 'other'];
    $user_id        = (int)($_SESSION['login_id'] ?? 0);

    if ($inventory_id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID de inventario inválido']);
        exit;
    }

    if (!in_array($document_type, $allowed_types)) {
        $document_type = 'other';
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => 'No se recibió archivo o hubo un error en la subida']);
        exit;
    }

    $file     = $_FILES['file'];
    $maxSize  = 10 * 1024 * 1024; // 10 MB
    $allowed  = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    $extMap   = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'msg' => 'Archivo demasiado grande (máx. 10 MB)']);
        exit;
    }

    // Validar MIME real
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);
    if (!in_array($mimeReal, $allowed)) {
        echo json_encode(['success' => false, 'msg' => 'Tipo de archivo no permitido']);
        exit;
    }

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $dir     = ROOT_PATH . 'uploads/inventory/' . $inventory_id . '/docs/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $basename = 'doc_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest     = $dir . $basename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'msg' => 'Error al guardar el archivo']);
        exit;
    }

    $relPath = 'inventory/' . $inventory_id . '/docs/' . $basename;

    $stmt = $pdo->prepare("
        INSERT INTO inventory_documents (inventory_id, document_type, file_name, file_path, file_type, uploaded_by)
        VALUES (:inv, :type, :name, :path, :mime, :user)
    ");
    $stmt->execute([
        ':inv'  => $inventory_id,
        ':type' => $document_type,
        ':name' => $file['name'],
        ':path' => $relPath,
        ':mime' => $mimeReal,
        ':user' => $user_id,
    ]);
    $docId = (int)$pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $docId, 'path' => $relPath]);
    exit;
}

// ─── LIST ───────────────────────────────────────────────────
if ($action === 'list') {
    $inventory_id = (int)($_GET['inventory_id'] ?? 0);
    if ($inventory_id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM inventory_documents WHERE inventory_id = :id ORDER BY created_at DESC");
    $stmt->execute([':id' => $inventory_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ─── DELETE ─────────────────────────────────────────────────
if ($action === 'delete') {
    $doc_id = (int)($_POST['id'] ?? 0);
    if ($doc_id <= 0) {
        echo json_encode(['success' => false, 'msg' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT file_path FROM inventory_documents WHERE id = :id");
    $stmt->execute([':id' => $doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        echo json_encode(['success' => false, 'msg' => 'Documento no encontrado']);
        exit;
    }
    $fullPath = ROOT_PATH . 'uploads/' . $doc['file_path'];
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
    $pdo->prepare("DELETE FROM inventory_documents WHERE id = :id")->execute([':id' => $doc_id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Acción no reconocida']);
