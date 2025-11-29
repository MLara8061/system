<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Asegurar que la sesión esté iniciada antes de incluir admin_class
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Iniciar buffer
ob_start();

try {
    include 'admin_class.php';
    $crud = new Action();
} catch (Exception $e) {
    ob_end_clean();
    error_log("ERROR initializing admin_class: " . $e->getMessage());
    http_response_code(500);
    die("ERROR");
} catch (Error $e) {
    ob_end_clean();
    error_log("FATAL ERROR initializing admin_class: " . $e->getMessage());
    http_response_code(500);
    die("ERROR");
}

// === OBTENER ACCIÓN DE FORMA SEGURA ===
$action = $_REQUEST['action'] ?? '';

// ===================================
// 1. LOGIN / LOGOUT
// ===================================
if ($action == 'login') {
    ob_end_clean(); // Limpiar cualquier output previo
    try {
        $result = $crud->login();
        echo $result;
    } catch (Exception $e) {
        error_log("LOGIN ERROR: " . $e->getMessage());
        echo "2";
    }
    exit;
}

if ($action == 'logout') {
    $crud->logout();
    exit;
}

// ===================================
// 2. USUARIOS
// ===================================
if ($action == 'save_user') {
    error_log("=== AJAX save_user ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("SESSION login_id: " . ($_SESSION['login_id'] ?? 'NOT SET'));
    error_log("SESSION login_type: " . ($_SESSION['login_type'] ?? 'NOT SET'));
    $result = $crud->save_user();
    error_log("Result: " . var_export($result, true));
    echo $result;
    exit;
}

if ($action == 'delete_user') {
    echo $crud->delete_user();
    exit;
}

if ($action == 'check_username') {
    echo $crud->check_username();
    exit;
}

if ($action == 'upload_avatar') {
    echo $crud->upload_avatar();
    exit;
}

// ===================================
// 3. IMAGEN PÁGINA
// ===================================
if ($action == 'save_page_img') {
    echo $crud->save_page_img();
    exit;
}

// ===================================
// 4. STAFF / DEPARTAMENTOS
// ===================================
if ($action == 'save_staff') {
    echo $crud->save_staff();
    exit;
}

if ($action == 'delete_staff') {
    echo $crud->delete_staff();
    exit;
}

if ($action == 'save_department') {
    echo $crud->save_department();
    exit;
}

if ($action == 'delete_department') {
    echo $crud->delete_department();
    exit;
}

// ===================================
// 5. TICKETS
// ===================================
if ($action == 'save_ticket') {
    echo $crud->save_ticket();
    exit;
}

if ($action == 'save_public_ticket') {
    echo $crud->save_public_ticket();
    exit;
}

if ($action == 'update_ticket') {
    echo $crud->update_ticket();
    exit;
}

if ($action == 'delete_ticket') {
    echo $crud->delete_ticket();
    exit;
}

if ($action == 'save_comment') {
    echo $crud->save_comment();
    exit;
}

if ($action == 'delete_comment') {
    echo $crud->delete_comment();
    exit;
}

// ===================================
// 6. EQUIPOS
// ===================================
if ($action == 'save_equipment') {
    echo $crud->save_equipment();
    exit;
}

// 
if ($action == 'delete_equipment_image') {
    echo $crud->delete_equipment_image();
    exit;
}

if ($action == 'delete_equipment') {
    echo $crud->delete_equipment();
    exit;
}

if ($action == 'save_equipment_unsubscribe') {
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->save_equipment_unsubscribe();
    exit;
}

if ($action == 'save_equipment_revision') {
    echo $crud->save_equipment_revision();
    exit;
}

if ($action == 'upload_excel_equipment') {
    error_log("AJAX upload_excel_equipment llamado");
    $result = $crud->upload_excel_equipment();
    error_log("Respuesta upload_excel_equipment: $result");
    echo $result;
    exit;
}

if ($action == 'download_template') {
    // Redirigir a archivo dedicado para la plantilla Excel
    header('Location: download_template.php');
    exit;
}

// ===================================
// 7. PROVEEDORES
// ===================================
if ($action == 'save_supplier') {
    echo $crud->save_supplier();
    exit;
}

if ($action == 'delete_supplier') {
    echo $crud->delete_supplier();
    exit;
}

// ===================================
// 8. HERRAMIENTAS
// ===================================
if ($action == 'save_tool') {
    error_log("AJAX save_tool llamado");
    $result = $crud->save_tool();
    error_log("Respuesta save_tool: $result");
    echo $result;
    exit;
}

if ($action == 'delete_tool') {
    $result = $crud->delete_tool();
    echo $result;
    exit;
}

// ===================================
// 9. ACCESORIOS
// ===================================
if ($action == 'save_accessory') {
    echo $crud->save_accessory();
    exit;
}

if ($action == 'delete_accessory') {
    echo $crud->delete_accessory();
    exit;
}

// ===================================
// 10. MANTENIMIENTOS
// ===================================
if ($action == 'get_mantenimientos') {
    $crud->get_mantenimientos(); // Ya tiene header + exit
    exit;
}

if ($action == 'save_maintenance') {
    echo $crud->save_maintenance();
    exit;
}

if ($action == 'complete_maintenance') {
    echo $crud->complete_maintenance();
    exit;
}

// ===================================
// 11. UBICACIONES
// ===================================
if ($action == 'save_equipment_location') {
    echo $crud->save_equipment_location();
    exit;
}

if ($action == 'delete_equipment_location') {
    echo $crud->delete_equipment_location();
    exit;
}

// ===================================
// 12. PUESTOS DE TRABAJO
// ===================================
if ($action == 'save_job_position') {
    echo $crud->save_job_position();
    exit;
}

if ($action == 'delete_job_position') {
    echo $crud->delete_job_position();
    exit;
}

if ($action == 'get_job_positions_by_location') {
    error_log("DEBUG: get_job_positions_by_location called");
    $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
    error_log("DEBUG: location_id = " . $location_id);
    
    if ($location_id > 0) {
        // Primero intentar con la nueva estructura (location_id directo en job_positions)
        $query = "SELECT j.id, j.name 
                  FROM job_positions j 
                  WHERE j.location_id = $location_id 
                  ORDER BY j.name ASC";
        error_log("DEBUG: Query (new structure) = " . $query);
        
        $qry = $conn->query($query);
        $positions = [];
        
        if($qry && $qry->num_rows > 0) {
            error_log("DEBUG: Using new structure, rows = " . $qry->num_rows);
            while ($row = $qry->fetch_assoc()) {
                $positions[] = $row;
            }
        } else {
            // Fallback a estructura antigua (tabla intermedia location_positions)
            $query = "SELECT j.id, j.name 
                      FROM job_positions j 
                      INNER JOIN location_positions lp ON lp.job_position_id = j.id 
                      WHERE lp.location_id = $location_id 
                      ORDER BY j.name ASC";
            error_log("DEBUG: Fallback query (old structure) = " . $query);
            
            $qry = $conn->query($query);
            if($qry) {
                error_log("DEBUG: Using old structure, rows = " . $qry->num_rows);
                while ($row = $qry->fetch_assoc()) {
                    $positions[] = $row;
                }
            } else {
                error_log("DEBUG: Query error = " . $conn->error);
            }
        }
        
        error_log("DEBUG: Returning " . count($positions) . " positions");
        echo json_encode($positions);
    } else {
        error_log("DEBUG: location_id is 0 or invalid");
        echo json_encode([]);
    }
    exit;
}

if ($action == 'get_locations_by_department') {
    error_log("DEBUG get_locations_by_department: Called");
    
    try {
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
        error_log("DEBUG: department_id = $department_id");
        
        if ($department_id > 0) {
            // Obtener ubicaciones que pertenecen al departamento
            $query = "SELECT l.id, l.name 
                     FROM locations l 
                     WHERE l.department_id = $department_id 
                     ORDER BY l.name ASC";
            error_log("DEBUG: Query = $query");
            
            $qry = $conn->query($query);
            $locations = [];
            
            if($qry) {
                while ($row = $qry->fetch_assoc()) {
                    $locations[] = $row;
                }
                error_log("DEBUG: Found " . count($locations) . " locations");
            } else {
                error_log("ERROR: Query failed: " . $conn->error);
            }
            
            echo json_encode($locations);
        } else {
            error_log("DEBUG: No department_id, returning all locations");
            // Si no hay departamento, devolver todas las ubicaciones
            $qry = $conn->query("SELECT id, name FROM locations ORDER BY name ASC");
            $locations = [];
            
            if($qry) {
                while ($row = $qry->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            
            echo json_encode($locations);
        }
    } catch (Exception $e) {
        error_log("EXCEPTION in get_locations_by_department: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_positions_by_department') {
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
    
    if ($department_id > 0) {
        // Obtener puestos que pertenecen al departamento
        $qry = $conn->query("SELECT j.id, j.name 
                             FROM job_positions j 
                             WHERE j.department_id = $department_id 
                             ORDER BY j.name ASC");
        $positions = [];
        
        if($qry) {
            while ($row = $qry->fetch_assoc()) {
                $positions[] = $row;
            }
        }
        
        echo json_encode($positions);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ===================================
// 13. INVENTARIO
// ===================================
if ($action == 'save_inventory') {
    error_log("AJAX save_inventory llamado");
    $result = $crud->save_inventory();
    error_log("Respuesta save_inventory: $result");
    echo $result;
    exit;
}

if ($action == 'delete_inventory') {
    error_log("AJAX delete_inventory llamado");
    $result = $crud->delete_inventory();
    error_log("Respuesta delete_inventory: $result");
    echo $result;
    exit;
}

if ($action == 'save_maintenance_report') {
    echo $crud->save_maintenance_report();
    exit;
}

if ($action == 'get_equipo_details') {
    echo $crud->get_equipo_details(); 
    exit;
}

if ($action == 'update_and_save_report') {
}

// ================== SERVICIOS Y CATEGORÍAS ==================
if ($action == 'save_category') {
    echo $crud->save_category();
    exit;
}

if ($action == 'delete_service_category') {
    echo $crud->delete_service_category();
    exit;
}

if ($action == 'load_service_category') {
    echo $crud->load_service_category();
    exit;
}

if ($action == 'save_service') {
    echo $crud->save_service();
    exit;
}

if ($action == 'delete_service') {
    echo $crud->delete_service();
    exit;
}

if ($action == 'load_service') {
    echo $crud->load_service();
    exit;
}

ob_end_flush();
?>