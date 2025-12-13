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

// Cargar Model y Controller
require_once ROOT . '/app/models/Equipment.php';

try {
    $equipmentModel = new Equipment();
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
            
            // Validar entrada
            $required = ['name', 'category_id', 'purchase_price'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Campo requerido: {$field}"
                    ]);
                    exit;
                }
            }
            
            // Crear equipo
            $data = [
                'name' => $_POST['name'],
                'serial_number' => $_POST['serial_number'] ?? null,
                'category_id' => (int)$_POST['category_id'],
                'supplier_id' => $_POST['supplier_id'] ?? null,
                'location_id' => $_POST['location_id'] ?? null,
                'purchase_price' => (float)$_POST['purchase_price'],
                'purchase_date' => $_POST['purchase_date'] ?? date('Y-m-d'),
                'warranty_expiry' => $_POST['warranty_expiry'] ?? null,
                'status' => $_POST['status'] ?? 'active',
                'notes' => $_POST['notes'] ?? null,
                'created_by' => $_SESSION['login_id']
            ];
            
            $id = $equipmentModel->save($data);
            echo json_encode([
                'success' => true,
                'message' => 'Equipo creado exitosamente',
                'data' => ['id' => $id, 'asset_tag' => $equipmentModel->getById($id)['asset_tag'] ?? null]
            ]);
            break;
        
        // UPDATE - Actualizar equipo
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id || !$equipmentModel->getById($id)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
                exit;
            }
            
            $data = [
                'name' => $_POST['name'] ?? null,
                'serial_number' => $_POST['serial_number'] ?? null,
                'category_id' => $_POST['category_id'] ?? null,
                'supplier_id' => $_POST['supplier_id'] ?? null,
                'location_id' => $_POST['location_id'] ?? null,
                'purchase_price' => $_POST['purchase_price'] ?? null,
                'warranty_expiry' => $_POST['warranty_expiry'] ?? null,
                'status' => $_POST['status'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'updated_by' => $_SESSION['login_id']
            ];
            
            // Remover campos nulos
            $data = array_filter($data, fn($v) => $v !== null);
            
            $equipmentModel->update($id, $data);
            echo json_encode([
                'success' => true,
                'message' => 'Equipo actualizado exitosamente'
            ]);
            break;
        
        // DELETE - Eliminar equipo
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            $id = $_POST['id'] ?? null;
            if (!$id || !$equipmentModel->getById($id)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
                exit;
            }
            
            $equipmentModel->delete($id);
            echo json_encode([
                'success' => true,
                'message' => 'Equipo eliminado exitosamente'
            ]);
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
            
            $equipment = $equipmentModel->getWithRelations($id);
            if (!$equipment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $equipment
            ]);
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
            
            $equipment = $equipmentModel->listWithFilters($filters);
            echo json_encode([
                'success' => true,
                'data' => $equipment
            ]);
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
            
            $results = $equipmentModel->search($q);
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
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
            
            $results = $equipmentModel->getByCategory($categoryId);
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;
        
        // STATISTICS - Estadísticas de equipos
        case 'statistics':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            $stats = $equipmentModel->getStatistics();
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
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
            
            if (!$equipmentModel->getById($id)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
                exit;
            }
            
            $equipmentModel->changeStatus($id, $status);
            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado'
            ]);
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
            
            if (!$equipmentModel->getById($id)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
                exit;
            }
            
            $equipmentModel->assignToUser($id, $userId);
            echo json_encode([
                'success' => true,
                'message' => 'Equipo asignado'
            ]);
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
