<?php
/**
 * public/ajax/custom_field.php — Endpoint AJAX para Campos Personalizados
 *
 * GET  ?action=list[&entity_type=equipment]
 * GET  ?action=get&id=N
 * POST ?action=create
 * POST ?action=update&id=N
 * POST ?action=delete
 * POST ?action=save_values   → guarda valores para una entidad específica
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

require_once ROOT . '/app/controllers/CustomFieldController.php';

header('Content-Type: application/json');

$action   = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? $_POST['action'] ?? ''));
$isAdmin  = (int)($_SESSION['login_type'] ?? 0) === 1;
$branchId = function_exists('active_branch_id')
    ? (int)active_branch_id()
    : (int)($_SESSION['login_active_branch_id'] ?? 0);

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción requerida']);
    exit;
}

// save_values puede ser llamado por cualquier usuario autenticado (saving form values)
$adminOnly = !in_array($action, ['list', 'get', 'save_values'], true);

if ($adminOnly && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    $ctrl = new CustomFieldController();

    switch ($action) {

        case 'list':
            $entityType = preg_replace('/[^a-z]/', '', strtolower($_GET['entity_type'] ?? ''));
            $bid = $isAdmin ? ($branchId ?: null) : ($branchId ?: null);
            echo json_encode(['success' => true, 'data' => $ctrl->list($entityType, $bid)]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }
            echo json_encode($ctrl->get($id));
            break;

        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                break;
            }
            echo json_encode($ctrl->create($_POST));
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                break;
            }
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }
            echo json_encode($ctrl->update($id, $_POST));
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                break;
            }
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }
            echo json_encode($ctrl->delete($id));
            break;

        case 'save_values':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                break;
            }
            $entityType = preg_replace('/[^a-z]/', '', strtolower($_POST['entity_type'] ?? ''));
            $entityId   = (int)($_POST['entity_id'] ?? 0);
            $values     = $_POST['cf'] ?? [];

            if (!in_array($entityType, ['equipment', 'tool', 'accessory', 'inventory'], true)) {
                echo json_encode(['success' => false, 'message' => 'Tipo de entidad inválido']);
                break;
            }

            echo json_encode($ctrl->saveValues($entityType, $entityId, is_array($values) ? $values : []));
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$action}' no existe"]);
    }

} catch (PDOException $e) {
    error_log('CUSTOM_FIELD AJAX PDO ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    error_log('CUSTOM_FIELD AJAX ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
