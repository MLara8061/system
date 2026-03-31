<?php
/**
 * public/ajax/maintenance_period.php - Endpoint AJAX para Periodos de Mantenimiento
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!defined('ROOT')) define('ROOT', dirname(dirname(dirname(__FILE__))));
require_once ROOT . '/config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

// Solo administradores pueden gestionar periodos de mantenimiento
if (($_SESSION['login_type'] ?? 0) != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

require_once ROOT . '/app/controllers/MaintenancePeriodController.php';

try {
    $ctrl   = new MaintenancePeriodController();
    $action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? $_POST['action'] ?? ''));
    $method = $_SERVER['REQUEST_METHOD'];

    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción requerida']);
        exit;
    }

    switch ($action) {
        case 'list':
            echo json_encode($ctrl->list());
            break;

        case 'get':
            if ($method !== 'GET') { http_response_code(405); break; }
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            echo json_encode($id ? $ctrl->get($id) : ['success' => false, 'message' => 'ID requerido']);
            break;

        case 'create':
            if ($method !== 'POST') { http_response_code(405); break; }
            echo json_encode($ctrl->create($_POST));
            break;

        case 'update':
            if ($method !== 'POST') { http_response_code(405); break; }
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            echo json_encode($id ? $ctrl->update($id, $_POST) : ['success' => false, 'message' => 'ID requerido']);
            break;

        case 'delete':
            if ($method !== 'POST') { http_response_code(405); break; }
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            echo json_encode($id ? $ctrl->delete($id) : ['success' => false, 'message' => 'ID requerido']);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$action}' no existe"]);
    }
} catch (Exception $e) {
    error_log("MAINTENANCE_PERIOD AJAX: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
