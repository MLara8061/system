<?php
/**
 * public/ajax/inventory.php - Endpoint AJAX para Inventory
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

require_once ROOT . '/app/controllers/InventoryController.php';

try {
    $c = new InventoryController();
    $a = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? $_POST['action'] ?? ''));
    
    if (!$a) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción requerida']);
        exit;
    }
    
    switch ($a) {
        case 'create':
            ($_SERVER['REQUEST_METHOD'] === 'POST') ? echo json_encode($c->create($_POST)) : http_response_code(405);
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
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            echo json_encode($c->list($limit, $offset));
            break;
        case 'list_filtered':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); break; }
            $limit = (int)($_POST['limit'] ?? 50);
            $offset = (int)($_POST['offset'] ?? 0);
            echo json_encode($c->listWithFilters($_POST, $limit, $offset));
            break;
        case 'search':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); break; }
            $q = $_GET['q'] ?? '';
            echo json_encode($c->search($q));
            break;
        case 'by_equipment':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); break; }
            $id = $_GET['equipment_id'] ?? null;
            echo json_encode($id ? $c->getByEquipment($id) : ['success' => false, 'message' => 'Equipment ID requerido']);
            break;
        case 'by_location':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); break; }
            $id = $_GET['location_id'] ?? null;
            echo json_encode($id ? $c->getByLocation($id) : ['success' => false, 'message' => 'Location ID requerido']);
            break;
        case 'statistics':
            echo json_encode($c->getStatistics());
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Acción '{$a}' no existe"]);
    }
} catch (Exception $e) {
    error_log("INVENTORY AJAX: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
