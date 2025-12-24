<?php
/**
 * AJAX endpoint para gestión de usuarios
 */

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../app/helpers/permissions.php';

// Verificar que sea administrador
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'update_department':
        update_user_department();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Accion no valida']);
}

/**
 * Actualizar departamento y acceso multi-departamental de un usuario
 */
function update_user_department() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
        return;
    }
    
    $user_id = (int)($_POST['user_id'] ?? 0);
    $department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;
    $can_view_all = (int)($_POST['can_view_all_departments'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'User ID invalido']);
        return;
    }
    
    // Preparar query
    if ($department_id === null) {
        $stmt = $conn->prepare("UPDATE users SET department_id = NULL, can_view_all_departments = ? WHERE id = ?");
        $stmt->bind_param('ii', $can_view_all, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET department_id = ?, can_view_all_departments = ? WHERE id = ?");
        $stmt->bind_param('iii', $department_id, $can_view_all, $user_id);
    }
    
    if ($stmt->execute()) {
        // Registrar en log
        try {
            $admin_id = (int)$_SESSION['login_id'];
            $activity = "Actualizo departamento del usuario ID: $user_id";
            $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity, table_name, created_at) VALUES (?, ?, 'users', NOW())");
            $log_stmt->bind_param("is", $admin_id, $activity);
            $log_stmt->execute();
            $log_stmt->close();
        } catch (Exception $e) {
            // Ignorar errores de log
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Departamento actualizado correctamente',
            'user_id' => $user_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al actualizar: ' . $conn->error
        ]);
    }
    
    $stmt->close();
}
