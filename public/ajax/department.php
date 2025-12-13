<?php
/**
 * public/ajax/department.php - Endpoint AJAX para Department
 * 
 * USO:
 * POST /public/ajax/department.php?action=create
 * POST /public/ajax/department.php?action=update&id=1
 * POST /public/ajax/department.php?action=delete&id=1
 * GET  /public/ajax/department.php?action=get&id=1
 * GET  /public/ajax/department.php?action=list
 * GET  /public/ajax/department.php?action=list_active
 * GET  /public/ajax/department.php?action=search&q=ventas
 * POST /public/ajax/department.php?action=toggle&id=1&active=1
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

require_once ROOT . '/app/controllers/DepartmentController.php';

try {
    $controller = new DepartmentController();
    $action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? $_POST['action'] ?? ''));
    
    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción requerida']);
        exit;
    }
    
    switch ($action) {
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            echo json_encode($controller->create($_POST));
            break;
        
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            $id = $_POST['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            echo json_encode($controller->update($id, $_POST));
            break;
        
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            $id = $_POST['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            echo json_encode($controller->delete($id));
            break;
        
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            echo json_encode($controller->get($id));
            break;
        
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            echo json_encode($controller->list(false));
            break;
        
        case 'list_active':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            echo json_encode($controller->list(true));
            break;
        
        case 'search':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            $q = $_GET['q'] ?? '';
            if (strlen($q) < 2) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Búsqueda debe tener al menos 2 caracteres']);
                exit;
            }
            echo json_encode($controller->search($q));
            break;
        
        case 'toggle':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            $id = $_POST['id'] ?? null;
            $active = $_POST['active'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            echo json_encode($controller->toggleActive($id, $active));
            break;
        
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$action}' no existe"]);
    }
    
} catch (Exception $e) {
    error_log("DEPARTMENT AJAX ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
