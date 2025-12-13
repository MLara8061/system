<?php
/**
 * public/ajax/equipment.php - Endpoint AJAX para Equipment (Patrón Fase 4)
 * 
 * Ejemplo de integración de EquipmentController con validaciones y permisos
 * 
 * USO:
 * POST /public/ajax/equipment.php?action=create
 * POST /public/ajax/equipment.php?action=update&id=42
 * POST /public/ajax/equipment.php?action=delete&id=42
 * GET  /public/ajax/equipment.php?action=get&id=42
 * GET  /public/ajax/equipment.php?action=list
 * GET  /public/ajax/equipment.php?action=search&q=laptop
 * GET  /public/ajax/equipment.php?action=list_by_category&category_id=5
 * GET  /public/ajax/equipment.php?action=statistics
 * POST /public/ajax/equipment.php?action=change_status&id=42
 * POST /public/ajax/equipment.php?action=assign_to_user&id=42
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
require_once ROOT . '/app/controllers/EquipmentController.php';

try {
    $equipmentController = new EquipmentController();
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
        // CREATE - Crear nuevo equipo
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            echo json_encode($equipmentController->create($_POST));
            break;
        
        // UPDATE - Actualizar equipo
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
            
            echo json_encode($equipmentController->update($id, $_POST));
            break;
        
        // DELETE - Eliminar equipo
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
            
            echo json_encode($equipmentController->delete($id));
            break;
        
        // GET - Obtener un equipo con relaciones
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
            
            echo json_encode($equipmentController->get($id));
            break;
        
        // LIST - Listar equipos con filtros opcionales
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'category_id' => $_GET['category_id'] ?? null,
                'location_id' => $_GET['location_id'] ?? null,
                'assigned_to' => $_GET['assigned_to'] ?? null
            ];
            
            $filters = array_filter($filters, fn($v) => $v !== null);
            
            echo json_encode($equipmentController->list($filters));
            break;
        
        // SEARCH - Buscar equipos
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
            
            echo json_encode($equipmentController->search($q));
            break;
        
        // LIST BY CATEGORY - Equipos por categoría
        case 'list_by_category':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            $categoryId = $_GET['category_id'] ?? null;
            if (!$categoryId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'category_id requerido']);
                exit;
            }
            
            echo json_encode($equipmentController->list(['category_id' => $categoryId]));
            break;
        
        // STATISTICS - Estadísticas de equipos
        case 'statistics':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            echo json_encode($equipmentController->getStatistics());
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
            
            echo json_encode($equipmentController->changeStatus($id, $status));
            break;
        
        // ASSIGN TO USER - Asignar a usuario
        case 'assign_to_user':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            $id = $_POST['id'] ?? null;
            $userId = $_POST['user_id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            
            echo json_encode($equipmentController->assignToUser($id, $userId));
            break;
        
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Acción '{$action}' no existe"
            ]);
    }
    
} catch (Exception $e) {
    error_log("EQUIPMENT AJAX ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
