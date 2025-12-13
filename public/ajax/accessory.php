<?php
/**
 * public/ajax/accessory.php - Endpoint AJAX para Accessories
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!defined('ROOT')) define('ROOT', dirname(dirname(dirname(__FILE__))));
require_once ROOT . '/config/session.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

require_once ROOT . '/app/controllers/AccessoryController.php';

try {
    $c = new AccessoryController();
    $a = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? $_POST['action'] ?? ''));
    
    if (!$a) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción requerida']);
        exit;
    }
    
    switch ($a) {
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode($c->create($_POST));
            } else {
                http_response_code(405);
            }
            break;
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
            $id = $_POST['id'] ?? null;
            echo json_encode($id ? $c->update($id, $_POST) : ['success' => false, 'message' => 'ID requerido']);
            break;
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
            $id = $_POST['id'] ?? null;
            echo json_encode($id ? $c->delete($id) : ['success' => false, 'message' => 'ID requerido']);
            break;
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); break; }
            $id = $_GET['id'] ?? null;
            echo json_encode($id ? $c->get($id) : ['success' => false, 'message' => 'ID requerido']);
            break;
        case 'list':
            echo json_encode($c->list());
            break;
        case 'list_active':
            echo json_encode($c->listActive());
            break;
        case 'search':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); break; }
            $q = $_GET['q'] ?? '';
            echo json_encode($c->search($q));
            break;
        case 'low_stock':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); break; }
            $th = (int)($_GET['threshold'] ?? 5);
            echo json_encode($c->getLowStock($th));
            break;
        case 'statistics':
            echo json_encode($c->getStatistics());
            break;
        case 'toggle':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
            $id = $_POST['id'] ?? null;
            echo json_encode($id ? $c->toggle($id) : ['success' => false, 'message' => 'ID requerido']);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$a}' no existe"]);
    }
} catch (Exception $e) {
    error_log("ACCESSORY AJAX: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
