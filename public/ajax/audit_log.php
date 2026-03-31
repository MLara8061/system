<?php
/**
 * public/ajax/audit_log.php - Endpoint AJAX para Registros de Auditoría
 *
 * GET  ?action=list&page=1&module=equipment&action_type=create&date_from=2026-01-01&date_to=2026-03-19
 * GET  ?action=get&id=123
 * GET  ?action=filter_options
 * GET  ?action=export&format=excel  (+ mismos filtros que list)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

require_once ROOT . '/config/session.php';
require_once ROOT . '/config/db.php';

// Validar sesión
if (!isset($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion expirada']);
    exit;
}

if (!validate_session()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion expirada por inactividad']);
    exit;
}

// Solo admins pueden acceder a auditoría
if (($_SESSION['login_type'] ?? 0) != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sin permisos para acceder a auditoria']);
    exit;
}

require_once ROOT . '/app/controllers/AuditLogController.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $controller = new AuditLogController();
    $action = isset($_GET['action']) ? preg_replace('/[^a-z_]/', '', strtolower($_GET['action'])) : '';

    if (!$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Accion requerida']);
        exit;
    }

    // Construir filtros desde GET
    $filters = [];
    if (!empty($_GET['module']))      $filters['module']     = $_GET['module'];
    if (!empty($_GET['action_type'])) $filters['action']     = $_GET['action_type'];
    if (!empty($_GET['user_id']))     $filters['user_id']    = $_GET['user_id'];
    if (!empty($_GET['table_name']))  $filters['table_name'] = $_GET['table_name'];
    if (!empty($_GET['branch_id']))   $filters['branch_id']  = $_GET['branch_id'];
    if (!empty($_GET['date_from']))   $filters['date_from']  = $_GET['date_from'];
    if (!empty($_GET['date_to']))     $filters['date_to']    = $_GET['date_to'];
    if (!empty($_GET['search']))      $filters['search']     = $_GET['search'];

    switch ($action) {
        case 'list':
            $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
            echo json_encode($controller->list($filters, $page, $perPage));
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                exit;
            }
            echo json_encode($controller->get($id));
            break;

        case 'filter_options':
            echo json_encode($controller->getFilterOptions());
            break;

        case 'export':
            $format = $_GET['format'] ?? 'json';

            if ($format === 'excel') {
                // Registrar acción de exportación
                require_once ROOT . '/app/helpers/AuditLogger.php';
                AuditLogger::log('audit_logs', 'export', 'audit_logs', null, null, ['filters' => $filters]);

                $result = $controller->export($filters);
                if (!$result['success']) {
                    echo json_encode($result);
                    exit;
                }

                // Generar Excel usando formato HTML (sin dependencias externas)
                while (ob_get_level()) ob_end_clean();

                $filename = 'auditoria_' . date('Y-m-d_His') . '.xls';
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Pragma: no-cache');
                header('Expires: 0');

                $cols = ['ID', 'Fecha', 'Usuario', 'Modulo', 'Accion', 'Tabla', 'Registro ID', 'IP', 'Sucursal ID'];

                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
                    . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
                    . 'xmlns="http://www.w3.org/TR/REC-html40">'
                    . '<head><meta charset="UTF-8"></head><body>';
                echo '<table border="1" style="border-collapse:collapse;">';

                // Encabezados
                echo '<tr>';
                foreach ($cols as $h) {
                    echo '<th style="background:#343a40;color:white;font-weight:bold;padding:4px 8px;">'
                        . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>';
                }
                echo '</tr>';

                // Filas de datos
                foreach ($result['data'] as $row) {
                    echo '<tr>';
                    foreach ($row as $val) {
                        echo '<td style="padding:3px 6px;">'
                            . htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                    }
                    echo '</tr>';
                }

                echo '</table></body></html>';
                exit;
            }

            // JSON por defecto
            echo json_encode($controller->export($filters));
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Accion '{$action}' no existe"]);
    }

} catch (Throwable $e) {
    error_log('AUDIT_LOG AJAX ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
