<?php
// Endpoint AJAX simple sin dependencias
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once 'config/config.php';
    
    $action = $_REQUEST['action'] ?? '';
    error_log("SIMPLE AJAX: action = $action");
    
    if ($action == 'save_department') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? $conn->real_escape_string($_POST['name']) : '';
        $locations = isset($_POST['locations']) ? $_POST['locations'] : [];
        $positions = isset($_POST['positions']) ? $_POST['positions'] : [];
        
        error_log("SIMPLE AJAX save_department: id=$id, name=$name");
        error_log("SIMPLE AJAX locations: " . print_r($locations, true));
        error_log("SIMPLE AJAX positions: " . print_r($positions, true));
        
        if(empty($name)) {
            echo json_encode(['error' => 'Nombre vacío']);
            exit;
        }
        
        // Verificar si el nombre ya existe
        $check = $conn->query("SELECT * FROM departments WHERE name='$name' ".($id > 0 ? "AND id != $id" : ''));
        if($check && $check->num_rows > 0) {
            echo "2"; // Ya existe
            exit;
        }
        
        // Guardar departamento
        if($id == 0) {
            $conn->query("INSERT INTO departments SET name='$name'");
            $id = $conn->insert_id;
        } else {
            $conn->query("UPDATE departments SET name='$name' WHERE id = $id");
        }
        
        if($id > 0) {
            // Actualizar ubicaciones
            $conn->query("UPDATE locations SET department_id = NULL WHERE department_id = $id");
            if(is_array($locations) && count($locations) > 0) {
                foreach($locations as $loc_id) {
                    $loc_id = intval($loc_id);
                    if($loc_id > 0) {
                        $conn->query("UPDATE locations SET department_id = $id WHERE id = $loc_id");
                    }
                }
            }
            
            // Actualizar puestos
            $conn->query("UPDATE job_positions SET department_id = NULL WHERE department_id = $id");
            if(is_array($positions) && count($positions) > 0) {
                foreach($positions as $pos_id) {
                    $pos_id = intval($pos_id);
                    if($pos_id > 0) {
                        $conn->query("UPDATE job_positions SET department_id = $id WHERE id = $pos_id");
                    }
                }
            }
        }
        
        echo "1"; // Éxito
        exit;
    }
    else if ($action == 'get_locations_by_department') {
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
        error_log("SIMPLE AJAX: department_id = $department_id");
        
        if ($department_id > 0) {
            $query = "SELECT l.id, l.name FROM locations l WHERE l.department_id = $department_id ORDER BY l.name ASC";
            $qry = $conn->query($query);
            $locations = [];
            
            if($qry) {
                while ($row = $qry->fetch_assoc()) {
                    $locations[] = $row;
                }
            }
            
            echo json_encode($locations);
        } else {
            echo json_encode([]);
        }
    }
    else if ($action == 'get_job_positions_by_location') {
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        error_log("SIMPLE AJAX: location_id = $location_id");
        
        if ($location_id > 0) {
            // Intentar con nueva estructura
            $query = "SELECT j.id, j.name FROM job_positions j WHERE j.location_id = $location_id ORDER BY j.name ASC";
            $qry = $conn->query($query);
            $positions = [];
            
            if($qry && $qry->num_rows > 0) {
                while ($row = $qry->fetch_assoc()) {
                    $positions[] = $row;
                }
            } else {
                // Fallback a estructura antigua
                $query = "SELECT j.id, j.name FROM job_positions j 
                          INNER JOIN location_positions lp ON lp.job_position_id = j.id 
                          WHERE lp.location_id = $location_id ORDER BY j.name ASC";
                $qry = $conn->query($query);
                if($qry) {
                    while ($row = $qry->fetch_assoc()) {
                        $positions[] = $row;
                    }
                }
            }
            
            echo json_encode($positions);
        } else {
            echo json_encode([]);
        }
    }
    else {
        echo json_encode(['error' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    error_log("SIMPLE AJAX ERROR: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
