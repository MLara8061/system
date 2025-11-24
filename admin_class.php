<?php
session_start();
ini_set('display_errors', 1);

class Action {
    private $db;

    public function __construct() {
        ob_start();
        require_once 'config/config.php';
        $this->db = $conn;
    }

    function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
        ob_end_flush();
    }

    function getDb() {
        return $this->db;
    }

    // ================== LOGIN / LOGOUT ==================
    function login()
    {
        extract($_POST);
        
        // Buscar usuario por username solamente
        $qry = $this->db->query("SELECT *, CONCAT(firstname,' ',lastname) as name FROM users WHERE username = '" . $username . "'");

        if ($qry->num_rows > 0) {
            $user = $qry->fetch_array();
            
            // Verificar contraseña (soportar MD5 legacy y bcrypt moderno)
            $password_valid = false;
            
            if (strpos($user['password'], '$2y$') === 0) {
                // Password con bcrypt
                $password_valid = password_verify($password, $user['password']);
            } else {
                // Password con MD5 (legacy)
                $password_valid = ($user['password'] === md5($password));
            }
            
            if (!$password_valid) {
                return 2; // Contraseña incorrecta
            }
            
            // Validar que el role coincida con el type solicitado
            if (!isset($type) || $user['role'] != $type) {
                return 2; // Tipo de usuario incorrecto
            }

            foreach ($user as $key => $value) {
                if ($key != 'password' && !is_numeric($key)) {
                    // Renombrar 'role' a 'type' para la sesión
                    if ($key === 'role') {
                        $_SESSION['login_type'] = $value;
                    } else {
                        $_SESSION['login_' . $key] = $value;
                    }
                }
            }

            $_SESSION['login_avatar'] = $user['avatar'] ?? 'default-avatar.png';

            $this->log_activity("Inició sesión", 'users', $_SESSION['login_id']);
            return 1;
        } else {
            return 2; // Usuario no encontrado
        }
    }

    function logout() {
        session_destroy();
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        header("location:login.php");
    }

    // ================== USUARIOS ==================
    function save_user()
    {
        extract($_POST);
        $id = $id ?? 0;
        $current_user = $_SESSION['login_id'] ?? 0;

        $login_type = $_SESSION['login_type'] ?? 0;

        error_log("=== SAVE_USER DEBUG ===");
        error_log("current_user: $current_user");
        error_log("login_type: $login_type");
        error_log("id (para crear/editar): $id");
        error_log("SESSION completa: " . json_encode($_SESSION));

        // Solo admin puede crear usuarios nuevos (id=0) o editar a otros usuarios
        // Usuarios normales solo pueden editar su propio perfil
        if ($login_type != 1) {
            if ($id == 0 || $id != $current_user) {
                error_log("Acceso denegado: login_type=$login_type, id=$id, current_user=$current_user");
                return 0; // Acceso denegado
            }
        }

        if (empty($username) || empty($firstname) || empty($lastname)) {
            error_log("Campos vacíos: username=$username, firstname=$firstname, lastname=$lastname");
            return 3;
        }

        $role = (int)($role ?? 2);
        if (!in_array($role, [1, 2])) $role = 2;

        $original = $id > 0 ? $this->db->query("SELECT username FROM users WHERE id = $id")->fetch_assoc()['username'] ?? '' : '';
        if ($username !== $original) {
            $chk = $this->db->query("SELECT id FROM users WHERE username = '$username' AND id != $id")->num_rows;
            if ($chk > 0) {
                error_log("Username duplicado: $username");
                return 2;
            }
        }

        $data = "firstname = ?, middlename = ?, lastname = ?, username = ?, role = ?";
        $params = [$firstname, $middlename ?? '', $lastname, $username, $role];
        $types = "ssssi";

        if (!empty($password)) {
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $data .= ", password = ?";
            $types .= "s";
        }

        if ($id == 0) {
            if (empty($password)) {
                error_log("Contraseña requerida para nuevo usuario");
                return 4;
            }
            $sql = "INSERT INTO users SET $data, date_created = NOW()";
        } else {
            $sql = "UPDATE users SET $data WHERE id = ?";
            $params[] = $id;
            $types .= "i";
        }

        error_log("Preparando SQL: $sql con types: $types");
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Error prepare: " . $this->db->error);
            return 0;
        }
        
        $stmt->bind_param($types, ...$params);
        $save = $stmt->execute();

        if (!$save) {
            error_log("Error execute: " . $stmt->error);
            return 0;
        }

        if ($save) {
            $new_id = $id == 0 ? $this->db->insert_id : $id;
            $action = $id == 0 ? "Añadió usuario" : "Editó usuario ID: $new_id";
            $this->log_activity($action, 'users', $new_id);
            error_log("Usuario guardado exitosamente: $new_id");
            return 1;
        }
        return 0;
    }

    function delete_user()
    {
        extract($_POST);
        $delete = $this->db->query("DELETE FROM users WHERE id = $id");
        if ($delete) {
            $this->log_activity("Eliminó usuario ID: $id", 'users', $id);
            return 1;
        }
        return 0;
    }

    function check_username()
    {
        extract($_POST);
        $id = $id ?? 0;
        $username = trim($username ?? '');

        if (empty($username)) return 0;

        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0 ? 1 : 0;
    }

    function upload_avatar()
    {
        extract($_POST);
        $id = $id ?? 0;
        if ($_SESSION['login_id'] != $id && $_SESSION['login_type'] != 1) return;

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] != 0) return;

        $fname = 'avatar_' . $id . '_' . time() . '.jpg';
        $path = 'assets/avatars/' . $fname;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
            $old = $this->db->query("SELECT avatar FROM users WHERE id = $id")->fetch_assoc()['avatar'];
            if ($old && $old != 'default-avatar.png' && file_exists('assets/avatars/' . $old)) {
                unlink('assets/avatars/' . $old);
            }

            $this->db->query("UPDATE users SET avatar = '$fname' WHERE id = $id");
            $_SESSION['login_avatar'] = $fname;

            return 'assets/avatars/' . $fname;
        }
        return '';
    }

    // ================== PÁGINA (IMÁGENES) ==================
    function save_page_img() {
        extract($_POST);
        if ($_FILES['img']['tmp_name'] != '') {
            $fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'], 'assets/uploads/'.$fname);
            if ($move) {
                $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
                $hostName = $_SERVER['HTTP_HOST'];
                $path = explode('/', $_SERVER['PHP_SELF']);
                $currentPath = '/'.$path[1];
                return json_encode(['link' => $protocol.'://'.$hostName.$currentPath.'/admin/assets/uploads/'.$fname]);
            }
        }
    }

    // ================== CLIENTES / STAFF ==================
    function save_customer() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id','cpass']) && !is_numeric($k)) {
                if ($k == 'password') $v = md5($v);
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        $check = $this->db->query("SELECT * FROM customers WHERE email='$email' ".(!empty($id) ? "AND id != $id" : ''))->num_rows;
        if ($check > 0) return 2;

        $save = empty($id)
            ? $this->db->query("INSERT INTO customers SET $data")
            : $this->db->query("UPDATE customers SET $data WHERE id = $id");
        return $save ? 1 : 0;
    }

    function delete_customer() {
        extract($_POST);
        return $this->db->query("DELETE FROM customers WHERE id = $id") ? 1 : 0;
    }

    function save_staff() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id','cpass']) && !is_numeric($k)) {
                if ($k == 'password') $v = md5($v);
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        $check = $this->db->query("SELECT * FROM staff WHERE email='$email' ".(!empty($id) ? "AND id != $id" : ''))->num_rows;
        if ($check > 0) return 2;

        $save = empty($id)
            ? $this->db->query("INSERT INTO staff SET $data")
            : $this->db->query("UPDATE staff SET $data WHERE id = $id");
        return $save ? 1 : 0;
    }

    function delete_staff() {
        extract($_POST);
        return $this->db->query("DELETE FROM staff WHERE id = $id") ? 1 : 0;
    }

    // ================== DEPARTAMENTOS ==================
    function save_department() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id']) && !is_numeric($k)) {
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        $check = $this->db->query("SELECT * FROM departments WHERE name='$name' ".(!empty($id) ? "AND id != $id" : ''))->num_rows;
        if ($check > 0) return 2;

        $save = empty($id)
            ? $this->db->query("INSERT INTO departments SET $data")
            : $this->db->query("UPDATE departments SET $data WHERE id = $id");
        return $save ? 1 : 0;
    }

    function delete_department() {
        extract($_POST);
        return $this->db->query("DELETE FROM departments WHERE id = $id") ? 1 : 0;
    }

    // ================== TICKETS ==================
    function save_ticket() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id']) && !is_numeric($k)) {
                if ($k == 'description') $v = htmlentities(str_replace("'", "&#x2019;", $v));
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        if (!isset($customer_id)) $data .= ", customer_id={$_SESSION['login_id']} ";
        if ($_SESSION['login_type'] == 1) $data .= ", admin_id={$_SESSION['login_id']} ";

        $save = empty($id)
            ? $this->db->query("INSERT INTO tickets SET $data")
            : $this->db->query("UPDATE tickets SET $data WHERE id = $id");
        return $save ? 1 : 0;
    }

    function update_ticket() {
        extract($_POST);
        $data = " status=$status ";
        if ($_SESSION['login_type'] == 2) $data .= ", staff_id={$_SESSION['login_id']} ";
        return $this->db->query("UPDATE tickets SET $data WHERE id = $id") ? 1 : 0;
    }

    function delete_ticket() {
        extract($_POST);
        return $this->db->query("DELETE FROM tickets WHERE id = $id") ? 1 : 0;
    }

    function save_comment() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id']) && !is_numeric($k)) {
                if ($k == 'comment') $v = htmlentities(str_replace("'", "&#x2019;", $v));
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        $data .= ", user_type={$_SESSION['login_type']}, user_id={$_SESSION['login_id']} ";
        $save = empty($id)
            ? $this->db->query("INSERT INTO comments SET $data")
            : $this->db->query("UPDATE comments SET $data WHERE id = $id");
        return $save ? 1 : 0;
    }

    function delete_comment() {
        extract($_POST);
        return $this->db->query("DELETE FROM comments WHERE id = $id") ? 1 : 0;
    }

    // ================== EQUIPOS (COMPLETO) ==================
    function save_equipment() {
        extract($_POST);
        $data = "";
        $new = empty($id);

        $array_cols_equipment = ['serie','amount','date_created','name','brand','model','acquisition_type','mandate_period_id','characteristics','discipline','supplier_id','number_inventory'];
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id']) && !is_numeric($k) && in_array($k, $array_cols_equipment)) {
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }

        $save = $new
            ? $this->db->query("INSERT INTO equipments SET $data")
            : $this->db->query("UPDATE equipments SET $data WHERE id = $id");
        if (!$save) return 2;

        $id = $new ? $this->db->insert_id : $id;
        $_POST['equipment_id'] = $id;

        // === RECEPTION, DELIVERY, SAFEGUARD ===
        foreach ([
            'equipment_reception' => ['state','comments'],
            'equipment_delivery' => ['department_id','location_id','responsible_name','responsible_position','date_training'],
            'equipment_safeguard' => ['warranty_time','date_adquisition']
        ] as $table => $fields) {
            $data = $this->build_data($_POST, $fields);
            $this->save_or_update($table, $data, $id, $new);
        }

        // === DOCUMENTOS ===
        $doc_fields = ['invoice','bailment_file','contract_file','usermanual_file','fast_guide_file','datasheet_file','servicemanual_file'];
        $data = $this->build_data($_POST, $doc_fields);
        foreach ($_FILES as $k => $file) {
            if (!empty($file['tmp_name']) && in_array($k, $doc_fields)) {
                $dest = 'uploads/' . $file['name'];
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $data .= ", $k='$dest' ";
                }
            }
        }
        $exists = $this->db->query("SELECT id FROM equipment_control_documents WHERE equipment_id=$id")->num_rows > 0;
        $this->save_or_update('equipment_control_documents', $data, $id, !$exists);

        // === ELIMINAR DOCUMENTOS ===
        foreach ($doc_fields as $field) {
            if (!empty($_POST["delete_$field"]) && $_POST["delete_$field"] == '1') {
                $qry = $this->db->query("SELECT $field FROM equipment_control_documents WHERE equipment_id = $id");
                $old = $qry->fetch_array()[$field] ?? '';
                if ($old && file_exists($old)) unlink($old);
                $this->db->query("UPDATE equipment_control_documents SET $field = NULL WHERE equipment_id = $id");
            }
        }

        // === IMAGEN ===
        $upload_dir = "uploads/equipment/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        if (!empty($_FILES['equipment_image']['tmp_name'])) {
            $file = $_FILES['equipment_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $valid_ext = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $valid_ext)) return 3;
            if ($file['size'] > 5*1024*1024) return 4;

            $old_img = $this->db->query("SELECT image FROM equipments WHERE id = $id")->fetch_array()['image'] ?? '';
            if ($old_img && file_exists($old_img)) unlink($old_img);

            $filename = $id.'_'.time().'.'.$ext;
            $dest = $upload_dir.$filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $this->db->query("UPDATE equipments SET image='$dest' WHERE id=$id");
            }
        }

        if (!empty($_POST['delete_image']) && $_POST['delete_image']=='1') {
            $old_img = $this->db->query("SELECT image FROM equipments WHERE id = $id")->fetch_array()['image'] ?? '';
            if ($old_img && file_exists($old_img)) unlink($old_img);
            $this->db->query("UPDATE equipments SET image=NULL WHERE id=$id");
        }

        // === CONSUMO ELÉCTRICO ===
        if (!empty($voltage) && !empty($amperage)) {
            $voltage = floatval($voltage);
            $amperage = floatval($amperage);
            $frequency_hz = !empty($frequency_hz) ? floatval($frequency_hz) : 60.00;
            $power_w = round($voltage * $amperage, 2);
            $notes = $new ? 'Registro inicial' : 'Actualización';

            $exists = $this->db->query("SELECT id FROM equipment_power_specs WHERE equipment_id = $id")->num_rows > 0;
            if ($exists) {
                $this->db->query("UPDATE equipment_power_specs SET voltage=$voltage, amperage=$amperage, frequency_hz=$frequency_hz, power_w=$power_w, notes='$notes' WHERE equipment_id=$id");
            } else {
                $this->db->query("INSERT INTO equipment_power_specs (equipment_id, voltage, amperage, frequency_hz, power_w, notes) VALUES ($id, $voltage, $amperage, $frequency_hz, $power_w, '$notes')");
            }
        }

        // === MANTENIMIENTO AUTOMÁTICO ===
        if (!empty($mandate_period_id)) {
            $update_maintenance = $new;
            if (!$new) {
                $old = $this->db->query("SELECT date_created, mandate_period_id FROM equipments WHERE id = $id")->fetch_assoc();
                if ($old['date_created'] != $date_created || $old['mandate_period_id'] != $mandate_period_id) {
                    $update_maintenance = true;
                }
            }
            if ($update_maintenance) {
                $this->generate_automatic_maintenance($id, $date_created, $mandate_period_id, false);
            }
        }

        return 1;
    }

    function delete_equipment_image()
    {
        extract($_POST);
        $id = (int)$id;
        $qry = $this->db->query("SELECT image FROM equipments WHERE id = $id");
        $img = $qry->fetch_array()['image'] ?? '';
        if ($img && file_exists($img)) unlink($img);
        $this->db->query("UPDATE equipments SET image = NULL WHERE id = $id");
        return 1;
    }

    function delete_equipment()
    {
        extract($_POST);
        if (empty($id) || !is_numeric($id)) return 2;

        $tables = [
            'equipment_control_documents',
            'equipment_reception',
            'equipment_delivery',
            'equipment_safeguard',
            'equipment_revision',
            'equipment_unsubscribe',
            'equipment_power_specs',
            'mantenimientos'
        ];

        foreach ($tables as $table) {
            if ($table === 'mantenimientos') {
                $this->db->query("DELETE FROM $table WHERE equipo_id = $id");
            } else {
                $this->db->query("DELETE FROM $table WHERE equipment_id = $id");
            }
        }

        $delete = $this->db->query("DELETE FROM equipments WHERE id = $id");
        return $delete ? 1 : 2;
    }

    function save_equipment_unsubscribe()
    {
        extract($_POST);
        $data = "";
        $array_cols = ['date', 'equipment_id', 'withdrawal_reason', 'description', 'comments', 'opinion', 'destination', 'responsible'];
        $_POST['equipment_id'] = $id;

        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id']) && !is_numeric($k)) {
                if ($k == 'withdrawal_reason') {
                    $reasons = json_encode($_POST['withdrawal_reason']);
                    $data .= empty($data) ? " $k='$reasons' " : ", $k='$reasons' ";
                } elseif (in_array($k, $array_cols)) {
                    $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
                }
            }
        }

        $exists = $this->db->query("SELECT id FROM equipment_unsubscribe WHERE equipment_id = $id")->num_rows > 0;
        $save = $exists
            ? $this->db->query("UPDATE equipment_unsubscribe SET $data WHERE equipment_id = $id")
            : $this->db->query("INSERT INTO equipment_unsubscribe SET $data");

        return $save ? 1 : 2;
    }

    function save_equipment_revision()
    {
        extract($_POST);
        $data = $this->build_data($_POST, ["equipment_id", "date_revision", "frecuencia"]);

        if (empty($id)) return 2;
        if ($this->db->query("SELECT id FROM equipments WHERE id = $id")->num_rows == 0) return 2;

        $save = $this->db->query("INSERT INTO equipment_revision SET $data");
        return $save ? 1 : 2;
    }

    // Métodos privados para equipos
    private function build_data($post, $allowed) {
        $data = "";
        foreach ($post as $k => $v) {
            if (!in_array($k, ['id','equipment_id']) && !is_numeric($k) && in_array($k, $allowed)) {
                $v = $this->db->real_escape_string($v);
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        return $data;
    }

    private function save_or_update($table, $data, $equipment_id, $is_new = false) {
        $data = preg_replace("/,? *equipment_id *= *['\"][^'\"]+['\"] */i", "", $data);
        $data = trim($data, " ,");
        $exists = $this->db->query("SELECT id FROM $table WHERE equipment_id = $equipment_id LIMIT 1")->num_rows > 0;

        $sql = $exists
            ? "UPDATE $table SET $data WHERE equipment_id = $equipment_id"
            : "INSERT INTO $table SET $data, equipment_id = $equipment_id";

        $result = $this->db->query($sql);
        if (!$result) {
            error_log("ERROR en $table: " . $this->db->error . " | SQL: $sql");
            return false;
        }
        return true;
    }

    private function generate_automatic_maintenance($equipment_id, $start_date, $period_id, $is_new = true) {
        $qry = $this->db->query("SELECT days_interval FROM maintenance_periods WHERE id = $period_id");
        if ($qry->num_rows == 0) return false;
        $interval = $qry->fetch_array()['days_interval'];

        if (!$is_new) {
            $this->db->query("DELETE FROM mantenimientos WHERE equipo_id = $equipment_id AND descripcion = 'Mantenimiento automático'");
        }

        $fecha = date('Y-m-d', strtotime("+$interval days", strtotime($start_date)));
        $this->db->query("INSERT INTO mantenimientos (equipo_id, fecha_programada, descripcion, estatus, created_at) VALUES ('$equipment_id', '$fecha', 'Mantenimiento automático', 'pendiente', NOW())");
    }

    // ================== HERRAMIENTAS ==================
    function save_tool() {
        extract($_POST);
        $data = "";
        $allowed = ['nombre','marca','costo','supplier_id','estatus','fecha_adquisicion','fecha_baja','caracteristicas'];
        foreach ($allowed as $k) {
            if (isset($_POST[$k])) {
                $data .= empty($data) ? " `$k` = '".addslashes($_POST[$k])."' " : ", `$k` = '".addslashes($_POST[$k])."' ";
            }
        }

        if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != '') {
            $fname = time().'_'.$_FILES['imagen']['name'];
            move_uploaded_file($_FILES['imagen']['tmp_name'], 'uploads/'.$fname);
            $data .= empty($data) ? " `imagen` = '$fname' " : ", `imagen` = '$fname' ";
        }

        $sql = empty($id)
            ? "INSERT INTO tools SET $data"
            : "UPDATE tools SET $data WHERE id = $id";

        return $this->db->query($sql) ? 1 : 0;
    }

    function delete_tool() {
        extract($_POST);
        $id = (int)$id;
        $qry = $this->db->query("SELECT imagen FROM tools WHERE id = $id");
        if ($qry && $qry->num_rows > 0) {
            $img = $qry->fetch_assoc()['imagen'];
            if (!empty($img) && file_exists('uploads/' . $img)) {
                unlink('uploads/' . $img);
            }
        }
        return $this->db->query("DELETE FROM tools WHERE id = $id") ? 1 : 0;
    }

    // ================== ACCESORIOS ==================
    function save_accessory() {
        extract($_POST);
        $data = "";
        $allowed = ['name','type','brand','model','serial','cost','acquisition_date','acquisition_type_id','area_id','status','observations','inventory_number'];
        foreach ($allowed as $k) {
            if (isset($_POST[$k])) {
                $data .= empty($data) ? " `$k` = '".addslashes($_POST[$k])."' " : ", `$k` = '".addslashes($_POST[$k])."' ";
            }
        }

        if (isset($inventory_number)) {
            $inventory_number = addslashes($inventory_number);
            $check = empty($id)
                ? $this->db->query("SELECT id FROM accessories WHERE inventory_number = '$inventory_number'")
                : $this->db->query("SELECT id FROM accessories WHERE inventory_number = '$inventory_number' AND id != $id");
            if ($check && $check->num_rows > 0) return 0;
        }

        if (isset($keep_image) && $keep_image == '0' && !empty($id)) {
            $qry = $this->db->query("SELECT image FROM accessories WHERE id = $id");
            if ($qry && $qry->num_rows > 0) {
                $img = $qry->fetch_assoc()['image'];
                if (!empty($img) && file_exists('uploads/' . $img)) unlink('uploads/' . $img);
            }
            $data .= ", image = '' ";
        }

        if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != '') {
            $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['imagen']['name']);
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], 'uploads/' . $fname)) {
                $data .= ", image = '$fname' ";
            } else {
                return 0;
            }
        }

        $sql = empty($id)
            ? "INSERT INTO accessories SET $data"
            : "UPDATE accessories SET $data WHERE id = $id";

        return $this->db->query($sql) ? 1 : 0;
    }

    function delete_accessory() {
        extract($_POST);
        $id = (int)$id;
        $qry = $this->db->query("SELECT image FROM accessories WHERE id = $id");
        if ($qry && $qry->num_rows > 0) {
            $img = $qry->fetch_assoc()['image'];
            if (!empty($img) && file_exists('uploads/' . $img)) unlink('uploads/' . $img);
        }
        return $this->db->query("DELETE FROM accessories WHERE id = $id") ? 1 : 0;
    }

    // ================== INVENTARIO ==================
    function save_inventory() {
        extract($_POST);
        $data = "";
        $allowed = ['name', 'category', 'price', 'cost', 'stock', 'min_stock', 'max_stock', 'status'];
        
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $value = $this->db->real_escape_string($_POST[$field]);
                $data .= empty($data) ? " `$field` = '$value' " : ", `$field` = '$value' ";
            }
        }

        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == 0) {
            $upload_dir = 'uploads/';
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image_path']['name']);
            $filepath = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $filepath)) {
                $data .= ", `image_path` = '$filename' ";
            }
        }

        $sql = empty($id)
            ? "INSERT INTO inventory SET $data"
            : "UPDATE inventory SET $data WHERE id = " . (int)$id;

        return $this->db->query($sql) ? 1 : 0;
    }

    function delete_inventory() {
        extract($_POST);
        $id = (int)$id;
        $qry = $this->db->query("SELECT image_path FROM inventory WHERE id = $id");
        if ($qry && $row = $qry->fetch_assoc()) {
            if (!empty($row['image_path']) && file_exists('uploads/' . $row['image_path'])) {
                unlink('uploads/' . $row['image_path']);
            }
        }
        return $this->db->query("DELETE FROM inventory WHERE id = $id") ? 1 : 0;
    }

    // ================== MANTENIMIENTOS ==================
    function get_mantenimientos() {
        $events = [];
        $sql = "SELECT m.id, m.fecha_programada, m.descripcion, m.estatus, e.name 
                FROM mantenimientos m 
                JOIN equipments e ON m.equipo_id = e.id 
                ORDER BY m.fecha_programada";
        $qry = $this->db->query($sql);
        if (!$qry) return json_encode([]);

        while ($row = $qry->fetch_assoc()) {
            $title = $row['name'];
            if (!empty($row['descripcion'])) $title .= ' - ' . substr($row['descripcion'], 0, 25);
            $color = $row['estatus'] == 'completado' ? '#28a745' : '#dc3545';
            $events[] = [
                'id' => $row['id'],
                'title' => $title,
                'start' => $row['fecha_programada'],
                'color' => $color
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($events);
        exit;
    }

    function save_maintenance() {
        extract($_POST);
        $data = "equipo_id='$equipo_id', fecha_programada='$fecha_programada'";
        if (!empty($descripcion)) $data .= ", descripcion='".addslashes($descripcion)."'";
        $sql = empty($id) ? "INSERT INTO mantenimientos SET $data" : "UPDATE mantenimientos SET $data WHERE id=$id";
        return $this->db->query($sql) ? 1 : 0;
    }

    function complete_maintenance() {
        $id = $_POST['id'];
        return $this->db->query("UPDATE mantenimientos SET estatus='completado' WHERE id=$id") ? 1 : 0;
    }

    // ================== UBICACIONES / PUESTOS ==================
    function save_equipment_location() {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if ($k != 'id') $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
        }
        $save = empty($id)
            ? $this->db->query("INSERT INTO equipment_locations SET $data")
            : $this->db->query("UPDATE equipment_locations SET $data WHERE id = $id");
        return $save ? (empty($id) ? 1 : 2) : 0;
    }

    function delete_equipment_location() {
        extract($_POST);
        return $this->db->query("DELETE FROM equipment_locations WHERE id = $id") ? 1 : 0;
    }

    function save_job_position() {
        extract($_POST);
        $data = "name='$name'";
        if (empty($id)) {
            $save = $this->db->query("INSERT INTO job_positions SET $data");
            $id = $this->db->insert_id;
            $this->db->query("INSERT INTO equipment_location_positions SET job_position_id=$id, location_id=$location_id");
            return 1;
        } else {
            $this->db->query("UPDATE job_positions SET $data WHERE id=$id");
            $exists = $this->db->query("SELECT id FROM equipment_location_positions WHERE job_position_id=$id")->num_rows;
            if ($exists > 0) {
                $this->db->query("UPDATE equipment_location_positions SET location_id=$location_id WHERE job_position_id=$id");
            } else {
                $this->db->query("INSERT INTO equipment_location_positions SET job_position_id=$id, location_id=$location_id");
            }
            return 2;
        }
    }

    function delete_job_position() {
        extract($_POST);
        if (empty($id) || !is_numeric($id)) return 2;
        $this->db->query("DELETE FROM equipment_location_positions WHERE job_position_id=$id");
        return $this->db->query("DELETE FROM job_positions WHERE id=$id") ? 1 : 2;
    }

    // ================== PROVEEDORES ==================
    function save_supplier() {
        extract($_POST);
        if (empty(trim($empresa ?? ''))) return 2;
        $sitio_web = $sitio_web ?? '';

        if (!empty(trim($rfc ?? ''))) {
            $rfc_clean = $this->db->real_escape_string(trim($rfc));
            $check_sql = "SELECT id FROM suppliers WHERE UPPER(rfc) = UPPER('$rfc_clean')";
            if (!empty($id)) $check_sql .= " AND id != " . (int)$id;
            if ($this->db->query($check_sql)->num_rows > 0) return 5;
        }

        $data = [
            'empresa' => $this->db->real_escape_string(trim($empresa)),
            'rfc' => $this->db->real_escape_string(trim($rfc ?? '')),
            'representante' => $this->db->real_escape_string(trim($representante ?? '')),
            'telefono' => $this->db->real_escape_string(trim($telefono ?? '')),
            'correo' => $this->db->real_escape_string(trim($correo ?? '')),
            'sector' => $this->db->real_escape_string(trim($sector ?? '')),
            'estado' => (int)($estado ?? 1),
            'sitio_web' => $this->db->real_escape_string(trim($sitio_web)),
            'notas' => $this->db->real_escape_string(trim($notas ?? ''))
        ];
        $set = [];
        foreach ($data as $key => $value) $set[] = "$key = '$value'";
        $set_clause = implode(', ', $set);

        $save = empty($id)
            ? $this->db->query("INSERT INTO suppliers SET $set_clause")
            : $this->db->query("UPDATE suppliers SET $set_clause WHERE id = " . (int)$id);
        return $save ? 1 : 0;
    }

    function delete_supplier() {
        extract($_POST);
        $id = (int)$id;
        if (empty($id)) return 0;
        return $this->db->query("DELETE FROM suppliers WHERE id = $id") ? 1 : 0;
    }

    // ================== UTILIDADES ==================
    function log_activity($action, $table_name, $record_id = null) {
        $user_id = $_SESSION['login_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $action = $this->db->real_escape_string($action);
        $table_name = $this->db->real_escape_string($table_name);
        $record_id = $record_id ? (int)$record_id : 'NULL';

        $sql = "INSERT INTO activity_log 
                (user_id, action, table_name, record_id, ip_address) 
                VALUES ($user_id, '$action', '$table_name', $record_id, '$ip')";

        return $this->db->query($sql);
    }

    function get_equipo_details() {
        header('Content-Type: application/json');

        $equipo_id = $_POST['id'] ?? null; 
        
        if (empty($equipo_id)) {
            return json_encode(['status' => 'error', 'message' => 'ID no proporcionado']);
        }
        
        $equipo_id = $this->db->real_escape_string($equipo_id);
        
        $qry = $this->db->query("
            SELECT 
                name, brand, model, serie, number_inventory, 
                discipline AS location_name
            FROM 
                equipments 
            WHERE 
                id = '{$equipo_id}'
        ");
        
        if ($qry) {
            if ($qry->num_rows > 0) {
                $data = $qry->fetch_assoc();
                $data['location_id'] = ''; 
                
                return json_encode(['status' => 1, 'data' => $data]); 
            } else {
                return json_encode(['status' => 0, 'message' => 'Equipo no encontrado']); 
            }
        } else {
            return json_encode(['status' => 3, 'message' => 'Error de consulta: ' . $this->db->error]); 
        }
    }
    
    function save_maintenance_report() {
        extract($_POST); 
        $data_report = "";
        
        $fields_to_save = ['orden_mto', 'fecha_reporte', 'cliente_nombre', 'equipo_id_select', 'tipo_servicio', 'descripcion', 'observaciones', 'status_final', 'ingeniero_nombre', 'recibe_nombre']; 
        
        foreach ($_POST as $k => $v) {
            if (in_array($k, $fields_to_save)) {
                $data_report .= empty($data_report) ? " $k='$v' " : ", $k='$v' ";
            }
        }
        
        $save_report = $this->db->query("INSERT INTO maintenance_reports SET $data_report");

        if (!$save_report) {
            return json_encode(['status' => 0, 'message' => 'Error al guardar el reporte principal.']); 
        }
        
        $report_id = $this->db->insert_id; 
        
        if (isset($refaccion_item_id) && is_array($refaccion_item_id)) {
            for ($i = 0; $i < count($refaccion_item_id); $i++) {
                $item_id = $refaccion_item_id[$i];
                $qty = (int) $refaccion_qty[$i];
                
                if (!empty($item_id) && $qty > 0) {
                    $update_stock = $this->db->query("
                        UPDATE inventory SET stock = stock - {$qty} WHERE id = {$item_id}
                    ");
                    
                    $save_item = $this->db->query("
                        INSERT INTO report_items (report_id, item_id, quantity) 
                        VALUES ({$report_id}, {$item_id}, {$qty})
                    ");
                    
                    if (!$update_stock || !$save_item) {
                        return json_encode(['status' => 3, 'message' => 'Error al guardar un item o descontar stock.']); 
                    }
                }
            }
        }
        
        return json_encode(['status' => 1, 'report_id' => $report_id, 'message' => 'Reporte guardado exitosamente.']);
    }

    //======== CARGA MASIVA DE EQUIPOS DESDE EXCEL
    function upload_excel_equipment() {
        // Usar SimpleXLSX (librería ligera sin dependencias)
        require_once 'lib/simplexlsx-master/src/SimpleXLSX.php';
        
        if (!isset($_FILES['excel_file'])) {
            return json_encode(['status' => 0, 'msg' => 'No se recibió ningún archivo']);
        }
        
        $file = $_FILES['excel_file'];
        
        // Validar extensión
        $allowed = ['xlsx', 'xls'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            return json_encode(['status' => 0, 'msg' => 'Solo se permiten archivos Excel (.xlsx, .xls)']);
        }
        
        // Cargar PHPSpreadsheet
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } else {
            return json_encode(['status' => 0, 'msg' => 'Error: PHPSpreadsheet no está instalado']);
        }
        
        // Verificar si se debe actualizar equipos existentes
        $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] == '1';
        
        // Procesar archivo Excel con PHPSpreadsheet
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            $success = 0;
            $updated = 0;
            $errors = [];
            $skipped = 0;
            
            // Saltar encabezados (fila 0) y filas de ejemplo (2-4 si existen)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Saltar filas de ejemplo (las que tienen EQ-001-2024, EQ-002-2024, etc.)
                if (isset($row[0]) && strpos($row[0], 'EQ-') === 0 && strpos($row[0], '-2024') !== false) {
                    $skipped++;
                    continue;
                }
                
                // Validar que tenga datos en columnas obligatorias (Serie, Nombre, Modelo, Valor)
                if (empty($row[0]) || trim($row[0]) == '' || 
                    empty($row[1]) || trim($row[1]) == '' ||
                    empty($row[3]) || trim($row[3]) == '') {
                    $skipped++;
                    continue;
                }
                
                // Mapeo de columnas del Excel (21 columnas: A-U)
                // A(0)=Serie*, B(1)=Nombre*, C(2)=Marca, D(3)=Modelo*, E(4)=Valor*
                // F(5)=Tipo Adquisición*, G(6)=Disciplina*, H(7)=Proveedor*, I(8)=Cantidad
                // J(9)=Características, K(10)=Voltaje, L(11)=Amperaje, M(12)=Frecuencia
                // N(13)=Departamento*, O(14)=Ubicación*, P(15)=Responsable*, Q(16)=Cargo
                // R(17)=Fecha Capacitación, S(18)=Factura, T(19)=Garantía, U(20)=Fecha Adquisición
                
                $serie = $this->db->real_escape_string(trim($row[0]));
                $name = $this->db->real_escape_string(trim($row[1]));
                $brand = isset($row[2]) && trim($row[2]) != '' ? $this->db->real_escape_string(trim($row[2])) : '';
                $model = $this->db->real_escape_string(trim($row[3]));
                $amount = isset($row[4]) && trim($row[4]) != '' ? floatval($row[4]) : 0;
                $acquisition_type = isset($row[5]) && trim($row[5]) != '' ? $this->db->real_escape_string(trim($row[5])) : '';
                $discipline = isset($row[6]) && trim($row[6]) != '' ? $this->db->real_escape_string(trim($row[6])) : '';
                $supplier_name = isset($row[7]) && trim($row[7]) != '' ? trim($row[7]) : '';
                $quantity = isset($row[8]) && trim($row[8]) != '' && intval($row[8]) > 0 ? intval($row[8]) : 1;
                $characteristics = isset($row[9]) && trim($row[9]) != '' ? $this->db->real_escape_string(trim($row[9])) : '';
                // Columnas K(10)=Voltaje, L(11)=Amperaje, M(12)=Frecuencia para equipment_power_specs
                $voltage = isset($row[10]) && trim($row[10]) != '' ? floatval($row[10]) : 0;
                $amperage = isset($row[11]) && trim($row[11]) != '' ? floatval($row[11]) : 0;
                $frequency = isset($row[12]) && trim($row[12]) != '' ? floatval($row[12]) : 60;
                $department_name = isset($row[13]) && trim($row[13]) != '' ? trim($row[13]) : '';
                $location_name = isset($row[14]) && trim($row[14]) != '' ? trim($row[14]) : '';
                $responsible_name = isset($row[15]) && trim($row[15]) != '' ? $this->db->real_escape_string(trim($row[15])) : '';
                $position_name = isset($row[16]) && trim($row[16]) != '' ? trim($row[16]) : '';
                $date_training = isset($row[17]) && trim($row[17]) != '' ? trim($row[17]) : date('Y-m-d');
                $invoice = isset($row[18]) && trim($row[18]) != '' ? $this->db->real_escape_string(trim($row[18])) : '';
                $warranty_time = isset($row[19]) && trim($row[19]) != '' ? intval($row[19]) : 1;
                $date_adquisition = isset($row[20]) && trim($row[20]) != '' ? trim($row[20]) : date('Y-m-d');
                
                // Buscar IDs en base de datos
                
                // Proveedor
                $supplier_id = 'NULL';
                if (!empty($supplier_name)) {
                    $supplier_escaped = $this->db->real_escape_string($supplier_name);
                    $supplier_query = $this->db->query("SELECT id FROM suppliers WHERE empresa LIKE '%$supplier_escaped%' LIMIT 1");
                    if ($supplier_query && $supplier_query->num_rows > 0) {
                        $supplier_id = $supplier_query->fetch_assoc()['id'];
                    }
                }
                
                // Tipo de adquisición
                $acquisition_type_id = 'NULL';
                if (!empty($acquisition_type)) {
                    $acq_escaped = $this->db->real_escape_string($acquisition_type);
                    $acq_query = $this->db->query("SELECT id FROM acquisition_type WHERE name LIKE '%$acq_escaped%' LIMIT 1");
                    if ($acq_query && $acq_query->num_rows > 0) {
                        $acquisition_type_id = $acq_query->fetch_assoc()['id'];
                    }
                }
                
                // Departamento
                $department_id = 'NULL';
                if (!empty($department_name)) {
                    $dept_escaped = $this->db->real_escape_string($department_name);
                    $dept_query = $this->db->query("SELECT id FROM departments WHERE name LIKE '%$dept_escaped%' LIMIT 1");
                    if ($dept_query && $dept_query->num_rows > 0) {
                        $department_id = $dept_query->fetch_assoc()['id'];
                    }
                }
                
                // Ubicación
                $location_id = 'NULL';
                if (!empty($location_name)) {
                    $loc_escaped = $this->db->real_escape_string($location_name);
                    $loc_query = $this->db->query("SELECT id FROM locations WHERE name LIKE '%$loc_escaped%' LIMIT 1");
                    if ($loc_query && $loc_query->num_rows > 0) {
                        $location_id = $loc_query->fetch_assoc()['id'];
                    }
                }
                
                // Cargo responsable
                $position_id = 'NULL';
                if (!empty($position_name)) {
                    $pos_escaped = $this->db->real_escape_string($position_name);
                    $pos_query = $this->db->query("SELECT id FROM responsible_positions WHERE name LIKE '%$pos_escaped%' LIMIT 1");
                    if ($pos_query && $pos_query->num_rows > 0) {
                        $position_id = $pos_query->fetch_assoc()['id'];
                    }
                }
                
                // Verificar si el equipo ya existe
                $check = $this->db->query("SELECT id, name, model FROM equipments WHERE serie = '$serie'");
                if ($check && $check->num_rows > 0) {
                    $existing = $check->fetch_assoc();
                    $equipment_id = $existing['id'];
                    
                    if ($update_existing) {
                        // ACTUALIZAR equipo existente
                        $sql = "UPDATE equipments SET 
                                name = '$name',
                                brand = '$brand',
                                model = '$model',
                                amount = $amount,
                                acquisition_type = $acquisition_type_id,
                                characteristics = '$characteristics',
                                discipline = '$discipline',
                                supplier_id = $supplier_id
                                WHERE id = $equipment_id";
                        
                        if ($this->db->query($sql)) {
                            // Actualizar recepción
                            $this->db->query("UPDATE equipment_reception SET state=1, comments='Actualizado desde Excel' WHERE equipment_id=$equipment_id");
                            
                            // Actualizar entrega
                            $delivery_check = $this->db->query("SELECT id FROM equipment_delivery WHERE equipment_id=$equipment_id");
                            if ($delivery_check && $delivery_check->num_rows > 0) {
                                $this->db->query("UPDATE equipment_delivery SET 
                                                 department_id=$department_id, location_id=$location_id, 
                                                 responsible_name='$responsible_name', responsible_position=$position_id, 
                                                 date_training='$date_training' 
                                                 WHERE equipment_id=$equipment_id");
                            } else {
                                $this->db->query("INSERT INTO equipment_delivery 
                                                 (equipment_id, department_id, location_id, responsible_name, responsible_position, date_training) 
                                                 VALUES ($equipment_id, $department_id, $location_id, '$responsible_name', $position_id, '$date_training')");
                            }
                            
                            // Actualizar resguardo
                            $safeguard_check = $this->db->query("SELECT id FROM equipment_safeguard WHERE equipment_id=$equipment_id");
                            if ($safeguard_check && $safeguard_check->num_rows > 0) {
                                $this->db->query("UPDATE equipment_safeguard SET 
                                                 warranty_time=$warranty_time, date_adquisition='$date_adquisition' 
                                                 WHERE equipment_id=$equipment_id");
                            } else {
                                $this->db->query("INSERT INTO equipment_safeguard 
                                                 (equipment_id, warranty_time, date_adquisition) 
                                                 VALUES ($equipment_id, $warranty_time, '$date_adquisition')");
                            }
                            
                            // Actualizar documentos
                            $docs_check = $this->db->query("SELECT id FROM equipment_control_documents WHERE equipment_id=$equipment_id");
                            if ($docs_check && $docs_check->num_rows > 0) {
                                $this->db->query("UPDATE equipment_control_documents SET invoice='$invoice' WHERE equipment_id=$equipment_id");
                            } else {
                                $this->db->query("INSERT INTO equipment_control_documents (equipment_id, invoice) VALUES ($equipment_id, '$invoice')");
                            }
                            
                            // Actualizar especificaciones eléctricas
                            if (!empty($voltage) && !empty($amperage)) {
                                $power_w = round($voltage * $amperage, 2);
                                $power_check = $this->db->query("SELECT id FROM equipment_power_specs WHERE equipment_id=$equipment_id");
                                if ($power_check && $power_check->num_rows > 0) {
                                    $this->db->query("UPDATE equipment_power_specs SET 
                                                     voltage=$voltage, amperage=$amperage, frequency_hz=$frequency, 
                                                     power_w=$power_w, notes='Actualizado desde Excel' 
                                                     WHERE equipment_id=$equipment_id");
                                } else {
                                    $this->db->query("INSERT INTO equipment_power_specs 
                                                     (equipment_id, voltage, amperage, frequency_hz, power_w, notes) 
                                                     VALUES ($equipment_id, $voltage, $amperage, $frequency, $power_w, 'Importado desde Excel')");
                                }
                            }
                            
                            $updated++;
                        } else {
                            $errors[] = "Fila " . ($i + 1) . ": Error al actualizar '{$existing['name']}' - " . $this->db->error;
                        }
                    } else {
                        // NO actualizar, reportar como error
                        $errors[] = "Fila " . ($i + 1) . ": El equipo con serie '$serie' ya existe ('{$existing['name']}' - {$existing['model']})";
                    }
                    continue;
                }
                
                // Obtener próximo número de inventario
                $result = $this->db->query("SHOW TABLE STATUS LIKE 'equipments'");
                $row_status = $result->fetch_assoc();
                $number_inventory = $row_status['Auto_increment'];
                
                // Insertar equipo (sin voltage, amperage, frequency_hz que no existen en la tabla)
                // mandate_period_id: 1=Preventivo, 2=Correctivo (por defecto 1 si no se especifica)
                $sql = "INSERT INTO equipments 
                        (number_inventory, serie, name, brand, model, amount, acquisition_type, characteristics, 
                         discipline, supplier_id, mandate_period_id, date_created) 
                        VALUES 
                        ($number_inventory, '$serie', '$name', '$brand', '$model', $amount, $acquisition_type_id, 
                         '$characteristics', '$discipline', $supplier_id, 1, NOW())";
                
                if ($this->db->query($sql)) {
                    $equipment_id = $this->db->insert_id;
                    
                    // Insertar recepción
                    $this->db->query("INSERT INTO equipment_reception (equipment_id, state, comments) 
                                     VALUES ($equipment_id, 1, 'Importado desde Excel')");
                    
                    // Insertar entrega
                    $this->db->query("INSERT INTO equipment_delivery 
                                     (equipment_id, department_id, location_id, responsible_name, responsible_position, date_training) 
                                     VALUES ($equipment_id, $department_id, $location_id, '$responsible_name', $position_id, '$date_training')");
                    
                    // Insertar resguardo
                    $this->db->query("INSERT INTO equipment_safeguard 
                                     (equipment_id, warranty_time, date_adquisition) 
                                     VALUES ($equipment_id, $warranty_time, '$date_adquisition')");
                    
                    // Insertar documentos de control
                    $this->db->query("INSERT INTO equipment_control_documents 
                                     (equipment_id, invoice) 
                                     VALUES ($equipment_id, '$invoice')");
                    
                    // Insertar especificaciones de consumo eléctrico (si tiene datos)
                    if (!empty($voltage) && !empty($amperage)) {
                        $power_w = round($voltage * $amperage, 2);
                        $this->db->query("INSERT INTO equipment_power_specs 
                                         (equipment_id, voltage, amperage, frequency_hz, power_w, notes) 
                                         VALUES ($equipment_id, $voltage, $amperage, $frequency, $power_w, 'Importado desde Excel')");
                    }
                    
                    $success++;
                } else {
                    $errors[] = "Fila " . ($i + 1) . ": " . $this->db->error;
                }
            }
            
            $msg = "Carga completada: $success equipos nuevos insertados";
            if ($updated > 0) $msg .= ", $updated equipos actualizados";
            if ($skipped > 0) $msg .= ", $skipped filas omitidas";
            if (count($errors) > 0) $msg .= ", " . count($errors) . " errores";
            
            return json_encode([
                'status' => 1,
                'msg' => $msg,
                'success' => $success,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            return json_encode(['status' => 0, 'msg' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }

}
?>
