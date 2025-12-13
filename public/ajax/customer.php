<?php
/**
 * public/ajax/customer.php - Endpoint AJAX para Customer (Patrón Fase 4)
 * 
 * USO:
 * POST /public/ajax/customer.php?action=create
 * POST /public/ajax/customer.php?action=update&id=42
 * POST /public/ajax/customer.php?action=delete&id=42
 * GET  /public/ajax/customer.php?action=get&id=42
 * GET  /public/ajax/customer.php?action=list
 * GET  /public/ajax/customer.php?action=search&q=juan
 * GET  /public/ajax/customer.php?action=statistics
 * POST /public/ajax/customer.php?action=change_status&id=42
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Definir ROOT si no existe
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

// Cargar sesión hardened
require_once ROOT . '/config/session.php';

// Validar sesión activa
if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesión expirada'
    ]);
    exit;
}

// Validar timeout
if (!validate_session()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Sesión expirada por inactividad'
    ]);
    exit;
}

// Cargar Controller
require_once ROOT . '/app/controllers/CustomerController.php';

try {
    $customerController = new CustomerController();
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    // Sanitar acción
    $action = preg_replace('/[^a-z_]/', '', strtolower($action));
    
    if (!$action) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Acción requerida'
        ]);
        exit;
    }
    
    switch ($action) {
        // CREATE - Crear nuevo cliente
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            echo json_encode($customerController->create($_POST));
            break;
        
        // UPDATE - Actualizar cliente
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
            
            echo json_encode($customerController->update($id, $_POST));
            break;
        
        // DELETE - Eliminar cliente
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
            
            echo json_encode($customerController->delete($id));
            break;
        
        // GET - Obtener cliente
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
            
            echo json_encode($customerController->get($id));
            break;
        
        // LIST - Listar clientes con filtros opcionales
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'city' => $_GET['city'] ?? null,
                'country' => $_GET['country'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            $filters = array_filter($filters, fn($v) => $v !== null);
            
            echo json_encode($customerController->list($filters));
            break;
        
        // SEARCH - Buscar clientes
        case 'search':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            $q = $_GET['q'] ?? '';
            if (strlen($q) < 2) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Búsqueda debe tener al menos 2 caracteres'
                ]);
                exit;
            }
            
            echo json_encode($customerController->search($q));
            break;
        
        // STATISTICS - Estadísticas de clientes
        case 'statistics':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            echo json_encode($customerController->getStatistics());
            break;
        
        // CHANGE STATUS - Cambiar estado
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
                echo json_encode([
                    'success' => false,
                    'message' => 'ID y status requeridos'
                ]);
                exit;
            }
            
            echo json_encode($customerController->changeStatus($id, $status));
            break;
        
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Acción '{$action}' no existe"
            ]);
    }
    
} catch (Exception $e) {
    error_log("CUSTOMER AJAX ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
