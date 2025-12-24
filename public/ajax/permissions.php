<?php
/**
 * AJAX endpoint para gestión de permisos
 */

// Activar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error fatal en el servidor',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    require_once '../../config/config.php';
    require_once '../../app/helpers/permissions.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar archivos de configuración',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

// Verificar sesión activa
if (!isset($_SESSION['login_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Debug: obtener información del usuario
$user_id = $_SESSION['login_id'];
$debug_query = "SELECT u.id, u.username, u.role_id, u.role as old_role, r.name as role_name, r.is_admin 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                WHERE u.id = $user_id";
$debug_result = $conn->query($debug_query);
$user_info = $debug_result ? $debug_result->fetch_assoc() : null;

// Verificar que sea administrador o tenga permiso para gestionar roles
$is_admin_check = is_admin();
$can_edit_users = can('edit', 'users');

if (!$is_admin_check && !$can_edit_users) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'No tiene permisos para gestionar roles y permisos',
        'debug' => [
            'user_id' => $user_id,
            'username' => $user_info['username'] ?? 'N/A',
            'role_id' => $user_info['role_id'] ?? 'NULL',
            'old_role' => $user_info['old_role'] ?? 'NULL',
            'role_name' => $user_info['role_name'] ?? 'N/A',
            'is_admin_db' => $user_info['is_admin'] ?? 'NULL',
            'is_admin_check' => $is_admin_check ? 'true' : 'false',
            'can_edit_users' => $can_edit_users ? 'true' : 'false'
        ]
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'save':
        save_permissions();
        break;
    
    case 'get':
        get_permissions();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

/**
 * Guardar permisos de un rol
 */
function save_permissions() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $role_id = (int)($_POST['role_id'] ?? 0);
        $permissions = $_POST['permissions'] ?? [];
        
        if ($role_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Role ID inválido']);
            return;
        }
        
        // Verificar que el rol no sea admin (no se pueden modificar permisos de admin)
        $role_check = $conn->query("SELECT is_admin FROM roles WHERE id = $role_id");
        if ($role_check && $role_check->num_rows > 0) {
            $role = $role_check->fetch_assoc();
            if ($role['is_admin'] == 1) {
                echo json_encode(['success' => false, 'message' => 'No se pueden modificar permisos de administradores globales']);
                return;
            }
        }
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Eliminar permisos existentes
        $conn->query("DELETE FROM role_permissions WHERE role_id = $role_id");
        
        // Insertar nuevos permisos
        $stmt = $conn->prepare("
            INSERT INTO role_permissions 
            (role_id, module_code, can_view, can_create, can_edit, can_delete, can_export) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($permissions as $module_code => $perms) {
            $can_view = isset($perms['can_view']) ? 1 : 0;
            $can_create = isset($perms['can_create']) ? 1 : 0;
            $can_edit = isset($perms['can_edit']) ? 1 : 0;
            $can_delete = isset($perms['can_delete']) ? 1 : 0;
            $can_export = isset($perms['can_export']) ? 1 : 0;
            
            $stmt->bind_param('isiiiii', 
                $role_id, 
                $module_code, 
                $can_view, 
                $can_create, 
                $can_edit, 
                $can_delete, 
                $can_export
            );
            
            $stmt->execute();
        }
        
        $stmt->close();
        
        // Si se envió user_id, actualizar también su rol, departamento y acceso
        $user_id_to_update = (int)($_POST['user_id'] ?? 0);
        if ($user_id_to_update > 0) {
            $user_dept_id = !empty($_POST['user_department_id']) ? (int)$_POST['user_department_id'] : null;
            $user_can_view_all = isset($_POST['user_can_view_all_departments']) ? 1 : 0;
            
            // Actualizar role_id, department_id y can_view_all_departments
            if ($user_dept_id !== null) {
                $update_user = $conn->prepare("
                    UPDATE users 
                    SET role_id = ?, department_id = ?, can_view_all_departments = ? 
                    WHERE id = ?
                ");
                $update_user->bind_param('iiii', $role_id, $user_dept_id, $user_can_view_all, $user_id_to_update);
            } else {
                $update_user = $conn->prepare("
                    UPDATE users 
                    SET role_id = ?, department_id = NULL, can_view_all_departments = ? 
                    WHERE id = ?
                ");
                $update_user->bind_param('iii', $role_id, $user_can_view_all, $user_id_to_update);
            }
            
            $update_user->execute();
            $update_user->close();
        }
        
        // Commit
        $conn->commit();
        
        // Registrar en log
        try {
            $user_id = (int)$_SESSION['login_id'];
            $activity = "Actualizó permisos del rol ID: $role_id";
            if ($user_id_to_update > 0) {
                $activity .= " y departamento del usuario ID: $user_id_to_update";
            }
            $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity, table_name, created_at) VALUES (?, ?, 'role_permissions', NOW())");
            $log_stmt->bind_param("is", $user_id, $activity);
            $log_stmt->execute();
            $log_stmt->close();
        } catch (Exception $e) {
            // Ignorar errores de log
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Permisos actualizados correctamente',
            'role_id' => $role_id
        ]);
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error al guardar',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
}

/**
 * Obtener permisos de un rol
 */
function get_permissions() {
    global $conn;
    
    $role_id = (int)($_GET['role_id'] ?? 0);
    
    if ($role_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Role ID inválido']);
        return;
    }
    
    $query = "SELECT * FROM role_permissions WHERE role_id = $role_id";
    $result = $conn->query($query);
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[$row['module_code']] = [
            'can_view' => (bool)$row['can_view'],
            'can_create' => (bool)$row['can_create'],
            'can_edit' => (bool)$row['can_edit'],
            'can_delete' => (bool)$row['can_delete'],
            'can_export' => (bool)$row['can_export']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'role_id' => $role_id,
        'permissions' => $permissions
    ]);
}
