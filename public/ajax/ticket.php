<?php
/**
 * public/ajax/ticket.php - Endpoint AJAX para Tickets
 * 
 * USO:
 * POST /public/ajax/ticket.php?action=create
 * POST /public/ajax/ticket.php?action=update&id=42
 * POST /public/ajax/ticket.php?action=delete&id=42
 * GET  /public/ajax/ticket.php?action=get&id=42
 * GET  /public/ajax/ticket.php?action=list
 * GET  /public/ajax/ticket.php?action=search&q=problema
 * GET  /public/ajax/ticket.php?action=my_tickets
 * GET  /public/ajax/ticket.php?action=statistics
 * POST /public/ajax/ticket.php?action=change_status&id=42
 * POST /public/ajax/ticket.php?action=assign&id=42&user_id=5
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

require_once ROOT . '/app/controllers/TicketController.php';

try {
    $controller = new TicketController();
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
            $filters = [
                'status' => $_GET['status'] ?? null,
                'priority' => $_GET['priority'] ?? null,
                'assigned_to' => $_GET['assigned_to'] ?? null,
                'category_id' => $_GET['category_id'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];
            $filters = array_filter($filters, fn($v) => $v !== null);
            echo json_encode($controller->list($filters));
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
        
        case 'my_tickets':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            $status = $_GET['status'] ?? null;
            $filters = ['assigned_to' => $_SESSION['login_id']];
            if ($status) {
                $filters['status'] = $status;
            }
            echo json_encode($controller->list($filters));
            break;
        
        case 'statistics':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            echo json_encode($controller->getStatistics());
            break;
        
        case 'resolution_stats':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            echo json_encode($controller->getResolutionStats());
            break;
        
        case 'change_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            $id = $_POST['id'] ?? null;
            $status = $_POST['status'] ?? null;
            if (!$id || !$status) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID y status requeridos']);
                exit;
            }
            echo json_encode($controller->changeStatus($id, $status));
            break;
        
        case 'assign':
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
            $userId = $_POST['user_id'] ?? null;
            echo json_encode($controller->assignTo($id, $userId));
            break;
        
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$action}' no existe"]);
    }
    
} catch (Exception $e) {
    error_log("TICKET AJAX ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
