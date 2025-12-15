<?php
// Endpoint AJAX simple sin dependencias
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
@session_start();

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/admin_class.php';
    
    $action = $_REQUEST['action'] ?? '';
    error_log("SIMPLE AJAX: action = $action");

    // Diagnóstico (solo admin logueado): devuelve DB actual y usuario MySQL
    if ($action === 'diag_connection') {
        $login_type = isset($_SESSION['login_type']) ? (int)$_SESSION['login_type'] : 0;
        if ($login_type !== 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }

        $dbName = null;
        $dbUser = null;
        try {
            $r = @$conn->query('SELECT DATABASE() AS db, USER() AS usr');
            if ($r && $r->num_rows > 0) {
                $row = $r->fetch_assoc();
                $dbName = $row['db'] ?? null;
                $dbUser = $row['usr'] ?? null;
            }
        } catch (Throwable $e) {
            // ignore
        }

        echo json_encode([
            'success' => true,
            'db' => $dbName,
            'user' => $dbUser,
        ]);
        exit;
    }

    // Diagnóstico (solo admin): validar ubicaciones por departamento desde el contexto web
    if ($action === 'diag_locations') {
        $login_type = isset($_SESSION['login_type']) ? (int)$_SESSION['login_type'] : 0;
        if ($login_type !== 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }

        $department_id = isset($_REQUEST['department_id']) ? (int)$_REQUEST['department_id'] : 0;
        $info = [
            'success' => true,
            'received' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'department_id' => $department_id,
            ],
            'counts' => [
                'locations_total' => 0,
                'locations_in_department' => 0,
            ],
            'sample' => [],
        ];

        try {
            $r = @$conn->query('SELECT COUNT(*) AS c FROM locations');
            if ($r && ($row = $r->fetch_assoc())) {
                $info['counts']['locations_total'] = (int)($row['c'] ?? 0);
            }

            if ($department_id > 0) {
                $r = @$conn->query("SELECT COUNT(*) AS c FROM locations WHERE department_id = {$department_id}");
                if ($r && ($row = $r->fetch_assoc())) {
                    $info['counts']['locations_in_department'] = (int)($row['c'] ?? 0);
                }

                $r = @$conn->query("SELECT id, name, department_id FROM locations WHERE department_id = {$department_id} ORDER BY name ASC LIMIT 20");
                if ($r) {
                    while ($row = $r->fetch_assoc()) {
                        $info['sample'][] = $row;
                    }
                }
            }
        } catch (Throwable $e) {
            $info['success'] = false;
            $info['error'] = $e->getMessage();
        }

        echo json_encode($info);
        exit;
    }

    // Diagnóstico (solo admin): validar cargos por ubicación (y fallback por dept) desde el contexto web
    if ($action === 'diag_positions') {
        $login_type = isset($_SESSION['login_type']) ? (int)$_SESSION['login_type'] : 0;
        if ($login_type !== 1) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }

        $location_id = isset($_REQUEST['location_id']) ? (int)$_REQUEST['location_id'] : 0;
        $info = [
            'success' => true,
            'received' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'location_id' => $location_id,
            ],
            'resolved' => [
                'department_id' => 0,
            ],
            'counts' => [
                'positions_total' => 0,
                'positions_in_location' => 0,
                'positions_in_department' => 0,
            ],
            'sample' => [
                'by_location' => [],
                'by_department' => [],
            ],
        ];

        try {
            $r = @$conn->query('SELECT COUNT(*) AS c FROM job_positions');
            if ($r && ($row = $r->fetch_assoc())) {
                $info['counts']['positions_total'] = (int)($row['c'] ?? 0);
            }

            if ($location_id > 0) {
                $r = @$conn->query("SELECT COUNT(*) AS c FROM job_positions WHERE location_id = {$location_id}");
                if ($r && ($row = $r->fetch_assoc())) {
                    $info['counts']['positions_in_location'] = (int)($row['c'] ?? 0);
                }
                $r = @$conn->query("SELECT id, name, location_id, department_id FROM job_positions WHERE location_id = {$location_id} ORDER BY name ASC LIMIT 20");
                if ($r) {
                    while ($row = $r->fetch_assoc()) {
                        $info['sample']['by_location'][] = $row;
                    }
                }

                $dq = @$conn->query("SELECT department_id FROM locations WHERE id = {$location_id} LIMIT 1");
                if ($dq && $dq->num_rows > 0) {
                    $info['resolved']['department_id'] = (int)($dq->fetch_assoc()['department_id'] ?? 0);
                }

                $dep_id = (int)$info['resolved']['department_id'];
                if ($dep_id > 0) {
                    $r = @$conn->query("SELECT COUNT(*) AS c FROM job_positions WHERE department_id = {$dep_id}");
                    if ($r && ($row = $r->fetch_assoc())) {
                        $info['counts']['positions_in_department'] = (int)($row['c'] ?? 0);
                    }
                    $r = @$conn->query("SELECT id, name, location_id, department_id FROM job_positions WHERE department_id = {$dep_id} ORDER BY name ASC LIMIT 20");
                    if ($r) {
                        while ($row = $r->fetch_assoc()) {
                            $info['sample']['by_department'][] = $row;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $info['success'] = false;
            $info['error'] = $e->getMessage();
        }

        echo json_encode($info);
        exit;
    }
    
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
        
        if ($department_id <= 0) {
            echo json_encode([]);
            exit;
        }

        $locations = [];
        try {
            // Compatibilidad: algunas instalaciones no tienen locations.department_id
            $has_department_id = false;
            $col = @$conn->query("SHOW COLUMNS FROM locations LIKE 'department_id'");
            $has_department_id = $col && $col->num_rows > 0;

            if ($has_department_id) {
                $query = "SELECT l.id, l.name FROM locations l WHERE l.department_id = $department_id ORDER BY l.name ASC";
            } else {
                $query = "SELECT l.id, l.name FROM locations l ORDER BY l.name ASC";
            }

            $qry = $conn->query($query);
            if ($qry) {
                while ($row = $qry->fetch_assoc()) {
                    $locations[] = $row;
                }
            } else {
                error_log('SIMPLE AJAX get_locations_by_department query error: ' . $conn->error);
            }

            // Si el filtro por department_id devuelve vacío (datos incompletos), fallback a listar todo
            if ($has_department_id && empty($locations)) {
                $qry = $conn->query("SELECT l.id, l.name FROM locations l ORDER BY l.name ASC");
                if ($qry) {
                    while ($row = $qry->fetch_assoc()) {
                        $locations[] = $row;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('SIMPLE AJAX get_locations_by_department throwable: ' . $e->getMessage());
        }

        echo json_encode($locations);
    }
    else if ($action == 'get_job_positions_by_location') {
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        error_log("SIMPLE AJAX: location_id = $location_id");
        
        if ($location_id > 0) {
            $positions = [];

            try {
                // Intentar con nueva estructura (job_positions.location_id)
                $has_location_id = false;
                $col = @$conn->query("SHOW COLUMNS FROM job_positions LIKE 'location_id'");
                $has_location_id = $col && $col->num_rows > 0;

                if ($has_location_id) {
                    $query = "SELECT j.id, j.name FROM job_positions j WHERE j.location_id = $location_id ORDER BY j.name ASC";
                    $qry = $conn->query($query);
                    if ($qry && $qry->num_rows > 0) {
                        while ($row = $qry->fetch_assoc()) {
                            $positions[] = $row;
                        }
                    }
                }

                // Fallback a estructura antigua (tabla intermedia location_positions)
                if (empty($positions)) {
                    $tbl = @$conn->query("SHOW TABLES LIKE 'location_positions'");
                    $has_lp = $tbl && $tbl->num_rows > 0;
                    if ($has_lp) {
                        $query = "SELECT j.id, j.name FROM job_positions j 
                                  INNER JOIN location_positions lp ON lp.job_position_id = j.id 
                                  WHERE lp.location_id = $location_id ORDER BY j.name ASC";
                        $qry = $conn->query($query);
                        if ($qry) {
                            while ($row = $qry->fetch_assoc()) {
                                $positions[] = $row;
                            }
                        }
                    }
                }

                // Último fallback: cargos por departamento del location (job_positions.department_id)
                if (empty($positions)) {
                    $col = @$conn->query("SHOW COLUMNS FROM job_positions LIKE 'department_id'");
                    $has_dep = $col && $col->num_rows > 0;
                    if ($has_dep) {
                        $dep_id = 0;
                        $col2 = @$conn->query("SHOW COLUMNS FROM locations LIKE 'department_id'");
                        $loc_has_dep = $col2 && $col2->num_rows > 0;
                        if ($loc_has_dep) {
                            $dq = @$conn->query("SELECT department_id FROM locations WHERE id = $location_id LIMIT 1");
                            if ($dq && $dq->num_rows > 0) {
                                $dep_id = (int)($dq->fetch_assoc()['department_id'] ?? 0);
                            }
                        }
                        if ($dep_id > 0) {
                            $query = "SELECT j.id, j.name FROM job_positions j WHERE j.department_id = $dep_id ORDER BY j.name ASC";
                            $qry = $conn->query($query);
                            if ($qry) {
                                while ($row = $qry->fetch_assoc()) {
                                    $positions[] = $row;
                                }
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('SIMPLE AJAX get_job_positions_by_location throwable: ' . $e->getMessage());
            }
            
            echo json_encode($positions);
        } else {
            echo json_encode([]);
        }
    }
    else if ($action == 'get_next_inventory_number') {
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        $acquisition_type_id = isset($_POST['acquisition_type_id']) ? intval($_POST['acquisition_type_id']) : (isset($_POST['acquisition_type']) ? intval($_POST['acquisition_type']) : 0);
        $equipment_category_id = isset($_POST['equipment_category_id']) ? intval($_POST['equipment_category_id']) : 0;
        error_log("SIMPLE AJAX: branch_id = $branch_id");
        
        if ($branch_id > 0) {
            if ($acquisition_type_id <= 0 || $equipment_category_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Selecciona tipo de adquisición y categoría para generar el número'
                ]);
                exit;
            }

            // Validaciones rápidas de existencia (evita fallos silenciosos)
            try {
                $chk = @$conn->query("SELECT id FROM branches WHERE id = {$branch_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Sucursal inválida']);
                    exit;
                }
                $chk = @$conn->query("SELECT id FROM acquisition_type WHERE id = {$acquisition_type_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Tipo de adquisición inválido']);
                    exit;
                }
                $chk = @$conn->query("SELECT id FROM equipment_categories WHERE id = {$equipment_category_id} LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    echo json_encode(['success' => false, 'error' => 'Categoría inválida']);
                    exit;
                }
            } catch (Throwable $e) {
                // Si falla la validación, continuamos al generador (que tiene sus propios try/catch)
                error_log('SIMPLE AJAX validation throwable: ' . $e->getMessage());
            }

            try {
                $admin = new Action();
                $number = $admin->get_next_inventory_number(
                    $branch_id,
                    $acquisition_type_id,
                    $equipment_category_id
                );

                if (!$number) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'No se pudo generar el número de inventario (revisa que la sucursal tenga código y que la categoría tenga clave)'
                    ]);
                    exit;
                }

                echo json_encode(['success' => true, 'number' => $number]);
            } catch (Throwable $e) {
                error_log("SIMPLE AJAX get_next_inventory_number THROWABLE: " . $e->getMessage());
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'error' => 'Error interno al generar el número de inventario'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Branch ID requerido']);
        }
    }
    else {
        echo json_encode(['error' => 'Acción no válida']);
    }
    
} catch (Throwable $e) {
    error_log("SIMPLE AJAX THROWABLE: " . $e->getMessage());
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'Error interno']);
}
?>
