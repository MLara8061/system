<?php
/**
 * public/ajax/user.php - Endpoint AJAX para Usuario (Nuevo Patrón - Fase 4)
 * 
 * Usa UserController en lugar de Action class
 * Ejemplo de cómo integrar los nuevos Models/Controllers
 * 
 * USO:
 * POST /public/ajax/user.php?action=create
 * POST /public/ajax/user.php?action=update&id=42
 * POST /public/ajax/user.php?action=delete&id=42
 * GET  /public/ajax/user.php?action=get&id=42
 * GET  /public/ajax/user.php?action=list&role=admin
 * GET  /public/ajax/user.php?action=search&q=juan
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
require_once ROOT . '/app/controllers/UserController.php';

try {
    $userController = new UserController();
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
        // CREATE - Crear nuevo usuario
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            // Solo admin puede crear usuarios
            if ($_SESSION['login_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permiso denegado']);
                exit;
            }
            
            echo json_encode($userController->create($_POST));
            break;
        
        // UPDATE - Actualizar usuario existente
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
            
            // Solo admin o el mismo usuario puede actualizar
            if ($_SESSION['login_type'] !== 'admin' && $_SESSION['login_id'] != $id) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permiso denegado']);
                exit;
            }
            
            echo json_encode($userController->update($id, $_POST));
            break;
        
        // DELETE - Eliminar usuario
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
            
            // Solo admin puede eliminar
            if ($_SESSION['login_type'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permiso denegado']);
                exit;
            }
            
            echo json_encode($userController->delete($id));
            break;
        
        // GET - Obtener un usuario
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
            
            echo json_encode($userController->get($id));
            break;
        
        // LIST - Listar usuarios
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'GET requerido']);
                exit;
            }
            
            $role = $_GET['role'] ?? null;
            echo json_encode($userController->list($role));
            break;
        
        // SEARCH - Buscar usuarios
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
            
            echo json_encode($userController->search($q));
            break;
        
        // CHANGE PASSWORD - Cambiar contraseña
        case 'change_password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST requerido']);
                exit;
            }
            
            $id = $_POST['id'] ?? $_SESSION['login_id'];
            
            // Solo el usuario o admin puede cambiar contraseña
            if ($_SESSION['login_type'] !== 'admin' && $_SESSION['login_id'] != $id) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permiso denegado']);
                exit;
            }
            
            echo json_encode($userController->changePassword($id, $_POST));
            break;
        
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => "Acción '{$action}' no existe"
            ]);
    }
    
} catch (Exception $e) {
    error_log("USER AJAX ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
