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
    
    if ($action == 'get_locations_by_department') {
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
