<?php
// Cargar sesión hardened
if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}
$env = strtolower(trim((string)(getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: '')));
$is_debug = in_array($env, ['local', 'dev', 'development'], true);
ini_set('display_errors', $is_debug ? '1' : '0');
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $is_debug);
}

class Action {
    private $db; // mysqli legacy
    private $pdo; // PDO nuevo
    public int $lastInsertId = 0; // Expuesto para que action.php devuelva el ID al cliente

    public function __construct() {
        // No iniciar buffer aquí, ya que ajax.php lo maneja
        // Mantener compatibilidad con código existente (mysqli)
        require_once __DIR__ . '/../config/config.php';
        $this->db = isset($conn) ? $conn : null;
        // Nueva conexión segura con PDO
        require_once __DIR__ . '/../config/db.php';
        $this->pdo = isset($pdo) ? $pdo : null;
        // Cargar AuditLogger
        $auditPath = __DIR__ . '/../app/helpers/AuditLogger.php';
        if (file_exists($auditPath)) {
            require_once $auditPath;
        }
        // Cargar NotificationService
        $notifPath = __DIR__ . '/../app/helpers/NotificationService.php';
        if (file_exists($notifPath)) {
            require_once $notifPath;
        }
    }

    /**
     * Registrar accion de auditoria (wrapper seguro)
     */
    private function audit($module, $action, $table, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            if (class_exists('AuditLogger')) {
                AuditLogger::log($module, $action, $table, $recordId, $oldValues, $newValues);
            }
        } catch (\Throwable $e) {
            error_log('AUDIT ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Obtener datos de un registro antes de modificarlo (para auditoria)
     */
    private function getOldRecord($table, $id, $idCol = 'id') {
        try {
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE {$idCol} = ? LIMIT 1");
                $stmt->execute([(int)$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } elseif ($this->db) {
                $r = $this->db->query("SELECT * FROM `{$table}` WHERE `{$idCol}` = " . (int)$id . " LIMIT 1");
                return ($r && $r->num_rows > 0) ? $r->fetch_assoc() : null;
            }
        } catch (\Throwable $e) { }
        return null;
    }

    function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
        // No hacer flush automático, ajax.php maneja el buffer
    }

    function getDb() {
        return $this->db;
    }

    // ================== LOGIN / LOGOUT ==================
    function login()
    {
        try {
            extract($_POST);
            
            // Verificar que existan los campos
            if (empty($username) || empty($password)) {
                error_log("LOGIN ERROR: username o password vacío");
                return 2;
            }
            
            // Buscar usuario por username (PDO preparado)
            $stmt = $this->pdo->prepare("SELECT *, CONCAT(firstname,' ',lastname) as name FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                
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
                    error_log("LOGIN ERROR: Password inválido para usuario: $username");
                    return 3; // Contraseña incorrecta
                }
                
                // Establecer sesión sin validar type (detección automática)
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

                // Regenerar session ID después de autenticación (prevenir Session Fixation)
                regenerate_session_id();

                // Log activity solo si login_id existe
                if (isset($_SESSION['login_id'])) {
                    $this->log_activity("Inició sesión", 'users', $_SESSION['login_id']);
                    $this->audit('users', 'login', 'users', $_SESSION['login_id'], null, ['username' => $username]);
                }
                
                return 1;
            } else {
                error_log("LOGIN ERROR: Usuario no encontrado: $username");
                return 2; // Usuario no encontrado
            }
        } catch (Exception $e) {
            error_log("LOGIN EXCEPTION: " . $e->getMessage());
            return 2;
        }
    }

    function logout() {
        // Auditar antes de destruir sesion
        $this->audit('users', 'logout', 'users', $_SESSION['login_id'] ?? 0, null, ['username' => $_SESSION['login_username'] ?? '']);
        // Limpiar variables de sesión
        $_SESSION = array();
        
        // Si se desea destruir la sesión completamente, borrar también la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir sin usar exit(header()) para permitir que ajax.php maneje la respuesta
        header("location:login.php");
        return 1;
    }

    // ================== USUARIOS ==================
    function save_user()
    {
        extract($_POST);
        $id = $id ?? 0;
        $current_user = $_SESSION['login_id'] ?? 0;

        $login_type = $_SESSION['login_type'] ?? 0;

        if (APP_DEBUG) {
            error_log("=== SAVE_USER DEBUG ===");
            error_log("current_user: $current_user");
            error_log("login_type: $login_type");
            error_log("id (para crear/editar): $id");
        }

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

        // Soportar tanto 'role' (legacy) como 'role_id' (nuevo sistema)
        $role_id = (int)($role_id ?? $role ?? 2);
        if ($role_id < 1) $role_id = 2; // Default: Usuario estándar
        
        // Department ID opcional (NULL = sin departamento asignado)
        $department_id = !empty($department_id) ? (int)$department_id : null;
        
        // Flag de acceso multi-departamental (default 0)
        $can_view_all_departments = isset($can_view_all_departments) && $can_view_all_departments == 1 ? 1 : 0;

        $original = '';
        if ($id > 0) {
            $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = :id');
            $stmt->execute([':id' => (int)$id]);
            $original = $stmt->fetch()['username'] ?? '';
        }
        if ($username !== $original) {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id');
            $stmt->execute([':username' => $username, ':id' => (int)$id]);
            $chk = $stmt->rowCount();
            if ($chk > 0) {
                error_log("Username duplicado: $username");
                return 2;
            }
        }

        // Construir SQL con PDO
        // Email opcional pero validado si se proporciona
        $email = isset($email) && trim($email) !== '' ? trim($email) : null;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 5; // Email invalido
        }

        if ($id == 0) {
            if (empty($password)) {
                error_log("Contraseña requerida para nuevo usuario");
                return 4;
            }
            $sql = 'INSERT INTO users (firstname, middlename, lastname, username, email, role, role_id, department_id, can_view_all_departments, password, date_created) 
                    VALUES (:firstname, :middlename, :lastname, :username, :email, :role, :role_id, :department_id, :can_view_all_departments, :password, NOW())';
            $params = [
                ':firstname' => $firstname,
                ':middlename' => $middlename ?? '',
                ':lastname' => $lastname,
                ':username' => $username,
                ':email' => $email,
                ':role' => $role_id,
                ':role_id' => $role_id,
                ':department_id' => $department_id,
                ':can_view_all_departments' => $can_view_all_departments,
                ':password' => password_hash($password, PASSWORD_DEFAULT)
            ];
        } else {
            $base = 'UPDATE users SET firstname = :firstname, middlename = :middlename, lastname = :lastname, 
                     username = :username, email = :email, role = :role, role_id = :role_id, department_id = :department_id, can_view_all_departments = :can_view_all_departments';
            if (!empty($password)) {
                $base .= ', password = :password';
            }
            $sql = $base . ' WHERE id = :id';
            $params = [
                ':firstname' => $firstname,
                ':middlename' => $middlename ?? '',
                ':lastname' => $lastname,
                ':username' => $username,
                ':email' => $email,
                ':role' => $role_id,
                ':role_id' => $role_id,
                ':department_id' => $department_id,
                ':can_view_all_departments' => $can_view_all_departments,
                ':id' => (int)$id
            ];
            if (!empty($password)) {
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        // Capturar datos anteriores para auditoría antes de ejecutar el UPDATE
        $oldUserData = ($id > 0) ? $this->getOldRecord('users', $id) : null;

        $stmt = $this->pdo->prepare($sql);
        $save = $stmt->execute($params);

        if (!$save) {
            error_log("Error execute: " . implode(", ", $stmt->errorInfo()));
            return 0;
        }

        if ($save) {
            $new_id = $id == 0 ? (int)$this->pdo->lastInsertId() : (int)$id;
            $action = $id == 0 ? "Añadió usuario" : "Editó usuario ID: $new_id";
            $this->log_activity($action, 'users', $new_id);
            $auditData = ['firstname' => $firstname, 'lastname' => $lastname, 'username' => $username, 'role_id' => $role_id, 'department_id' => $department_id];
            $this->audit('users', $id == 0 ? 'create' : 'update', 'users', $new_id, $oldUserData, $auditData);
            error_log("Usuario guardado exitosamente: $new_id");
            return 1;
        }
        return 0;
    }

    function delete_user()
    {
        extract($_POST);
        try {
            $id = (int)$id;
            if ($id <= 0) return 0;
            $oldData = $this->getOldRecord('users', $id);
            
            // Usar PDO si está disponible, sino mysqli legacy
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $deleted = $stmt->rowCount() > 0;
            } else {
                $deleted = $this->db->query("DELETE FROM users WHERE id = $id");
            }
            
            if ($deleted) {
                $this->log_activity("Eliminó usuario ID: $id", 'users', $id);
                $this->audit('users', 'delete', 'users', $id, $oldData, null);
                return 1;
            }
            return 0;
        } catch (Exception $e) {
            error_log("DELETE_USER ERROR: " . $e->getMessage());
            return 0;
        }
    }

    function check_username()
    {
        extract($_POST);
        $id = $id ?? 0;
        $username = trim($username ?? '');

        if (empty($username)) return 0;
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id');
        $stmt->execute([':username' => $username, ':id' => (int)$id]);
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function upload_avatar()
    {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return '';
            
            // Verificar permisos
            if ($_SESSION['login_id'] != $id && $_SESSION['login_type'] != 1) return '';

            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] != 0) return '';

            $base = 'avatar_' . $id . '_' . time();
            $saved = $this->save_uploaded_image_optimized($_FILES['avatar'], 'assets/avatars', $base, 5 * 1024 * 1024, 512, true);
            if (!empty($saved['ok'])) {
                $fname = $saved['filename'];
                // Obtener avatar anterior
                $old_avatar = null;
                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $result = $stmt->fetch();
                    $old_avatar = $result['avatar'] ?? null;
                } else {
                    $result = $this->db->query("SELECT avatar FROM users WHERE id = $id")->fetch_assoc();
                    $old_avatar = $result['avatar'] ?? null;
                }
                
                // Eliminar avatar anterior si existe
                if ($old_avatar && $old_avatar != 'default-avatar.png' && file_exists('assets/avatars/' . $old_avatar)) {
                    @unlink('assets/avatars/' . $old_avatar);
                }

                // Actualizar avatar en BD
                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$fname, $id]);
                } else {
                    $this->db->query("UPDATE users SET avatar = '$fname' WHERE id = $id");
                }
                
                $_SESSION['login_avatar'] = $fname;
                return 'assets/avatars/' . $fname;
            }
            return '';
        } catch (Exception $e) {
            error_log("UPLOAD_AVATAR ERROR: " . $e->getMessage());
            return '';
        }
    }

    function delete_avatar()
    {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return 0;
            
            // Verificar permisos: solo el usuario o admin
            if ($_SESSION['login_id'] != $id && $_SESSION['login_type'] != 1) return 0;

            // Obtener avatar actual
            $old_avatar = null;
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $result = $stmt->fetch();
                $old_avatar = $result['avatar'] ?? null;
            } else {
                $result = $this->db->query("SELECT avatar FROM users WHERE id = $id")->fetch_assoc();
                $old_avatar = $result['avatar'] ?? null;
            }
            
            // Eliminar archivo físico si existe
            if ($old_avatar && $old_avatar != 'default-avatar.png') {
                $file_path = 'assets/avatars/' . $old_avatar;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Limpiar avatar en BD (poner NULL o vacío)
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                $this->db->query("UPDATE users SET avatar = NULL WHERE id = $id");
            }
            
            // Actualizar sesión
            unset($_SESSION['login_avatar']);
            
            return 1;
        } catch (Exception $e) {
            error_log("DELETE_AVATAR ERROR: " . $e->getMessage());
            return 0;
        }
    }

    // ================== PÁGINA (IMÁGENES) ==================
    function save_page_img() {
        extract($_POST);
        if ($_FILES['img']['tmp_name'] != '') {
            $nameBase = pathinfo((string)($_FILES['img']['name'] ?? ''), PATHINFO_FILENAME);
            $nameBase = preg_replace('/[^a-zA-Z0-9._-]/', '', (string)$nameBase);
            if (empty($nameBase)) $nameBase = 'img';
            $rand = function_exists('random_bytes') ? bin2hex(random_bytes(3)) : substr(md5(uniqid('', true)), 0, 6);
            $base = strtotime(date('y-m-d H:i')) . '_' . $nameBase . '_' . $rand;
            $saved = $this->save_uploaded_image_optimized($_FILES['img'], 'assets/uploads', $base, 5 * 1024 * 1024, 1600, true);
            if (!empty($saved['ok'])) {
                $fname = $saved['filename'];
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
        try {
            extract($_POST);
            $id = isset($id) ? (int)$id : 0;
            $email = trim($email ?? '');
            $isNewCustomer = ($id == 0);
            if (!$isNewCustomer) { $oldCustomerData = $this->getOldRecord('customers', $id); }
            
            if (empty($email)) return 0;
            
            // Construir datos seguros
            $allowed_fields = ['name', 'phone', 'email', 'address', 'city', 'country', 'website', 'password'];
            $data = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($_POST[$field]) && !is_numeric($field)) {
                    $value = trim($_POST[$field]);
                    if ($field === 'password' && !empty($value)) {
                        $value = md5($value);
                    }
                    $data[$field] = $value;
                }
            }
            
            if (empty($data)) return 0;
            
            // Verificar email duplicado
            if ($this->pdo) {
                $check_query = "SELECT id FROM customers WHERE email = ?";
                $check_params = [$email];
                if ($id > 0) {
                    $check_query .= " AND id != ?";
                    $check_params[] = $id;
                }
                $stmt = $this->pdo->prepare($check_query);
                $stmt->execute($check_params);
                if ($stmt->rowCount() > 0) return 2;
                
                // Insert o Update
                if ($id > 0) {
                    $fields = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                    $stmt = $this->pdo->prepare("UPDATE customers SET $fields WHERE id = ?");
                    $values = array_merge(array_values($data), [$id]);
                    $stmt->execute($values);
                    $this->audit('customers', 'update', 'customers', $id, $oldCustomerData ?? null, $data);
                } else {
                    $fields = implode(', ', array_keys($data));
                    $placeholders = implode(', ', array_fill(0, count($data), '?'));
                    $stmt = $this->pdo->prepare("INSERT INTO customers ($fields) VALUES ($placeholders)");
                    $stmt->execute(array_values($data));
                    $newCustId = (int)$this->pdo->lastInsertId();
                    $this->audit('customers', 'create', 'customers', $newCustId, null, $data);
                }
                return 1;
            } else {
                // Fallback a mysqli
                $check = $this->db->query("SELECT * FROM customers WHERE email='" . $this->db->real_escape_string($email) . "' " . ($id > 0 ? "AND id != $id" : ''))->num_rows;
                if ($check > 0) return 2;
                
                $set_parts = [];
                foreach ($data as $k => $v) {
                    $set_parts[] = "$k='" . $this->db->real_escape_string($v) . "'";
                }
                $set_sql = implode(', ', $set_parts);
                
                $save = $id > 0
                    ? $this->db->query("UPDATE customers SET $set_sql WHERE id = $id")
                    : $this->db->query("INSERT INTO customers SET $set_sql");
                return $save ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("SAVE_CUSTOMER ERROR: " . $e->getMessage());
            return 0;
        }
    }

    function delete_customer() {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return 0;
            $oldData = $this->getOldRecord('customers', $id);
            
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) { $this->audit('customers', 'delete', 'customers', $id, $oldData, null); return 1; }
                return 0;
            } else {
                return $this->db->query("DELETE FROM customers WHERE id = $id") ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("DELETE_CUSTOMER ERROR: " . $e->getMessage());
            return 0;
        }
    }

    function save_staff() {
        try {
            extract($_POST);
            $id = isset($id) ? (int)$id : 0;
            $email = trim($email ?? '');
            $isNewStaff = ($id == 0);
            if (!$isNewStaff) { $oldStaffData = $this->getOldRecord('staff', $id); }
            
            if (empty($email)) return 0;
            
            // Construir datos seguros
            $allowed_fields = ['firstname', 'lastname', 'phone', 'email', 'address', 'city', 'country', 'password', 'job_position'];
            $data = [];
            
            foreach ($allowed_fields as $field) {
                if (isset($_POST[$field]) && !is_numeric($field)) {
                    $value = trim($_POST[$field]);
                    if ($field === 'password' && !empty($value)) {
                        $value = md5($value);
                    }
                    $data[$field] = $value;
                }
            }
            
            if (empty($data)) return 0;
            
            // Verificar email duplicado
            if ($this->pdo) {
                $check_query = "SELECT id FROM staff WHERE email = ?";
                $check_params = [$email];
                if ($id > 0) {
                    $check_query .= " AND id != ?";
                    $check_params[] = $id;
                }
                $stmt = $this->pdo->prepare($check_query);
                $stmt->execute($check_params);
                if ($stmt->rowCount() > 0) return 2;
                
                // Insert o Update
                if ($id > 0) {
                    $fields = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                    $stmt = $this->pdo->prepare("UPDATE staff SET $fields WHERE id = ?");
                    $values = array_merge(array_values($data), [$id]);
                    $stmt->execute($values);
                    $this->audit('staff', 'update', 'staff', $id, $oldStaffData ?? null, $data);
                } else {
                    $fields = implode(', ', array_keys($data));
                    $placeholders = implode(', ', array_fill(0, count($data), '?'));
                    $stmt = $this->pdo->prepare("INSERT INTO staff ($fields) VALUES ($placeholders)");
                    $stmt->execute(array_values($data));
                    $newStaffId = (int)$this->pdo->lastInsertId();
                    $this->audit('staff', 'create', 'staff', $newStaffId, null, $data);
                }
                return 1;
            } else {
                // Fallback a mysqli
                $check = $this->db->query("SELECT * FROM staff WHERE email='" . $this->db->real_escape_string($email) . "' " . ($id > 0 ? "AND id != $id" : ''))->num_rows;
                if ($check > 0) return 2;
                
                $set_parts = [];
                foreach ($data as $k => $v) {
                    $set_parts[] = "$k='" . $this->db->real_escape_string($v) . "'";
                }
                $set_sql = implode(', ', $set_parts);
                
                $save = $id > 0
                    ? $this->db->query("UPDATE staff SET $set_sql WHERE id = $id")
                    : $this->db->query("INSERT INTO staff SET $set_sql");
                return $save ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("SAVE_STAFF ERROR: " . $e->getMessage());
            return 0;
        }
    }

    function delete_staff() {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return 0;
            $oldData = $this->getOldRecord('staff', $id);
            
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("DELETE FROM staff WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) { $this->audit('staff', 'delete', 'staff', $id, $oldData, null); return 1; }
                return 0;
            } else {
                return $this->db->query("DELETE FROM staff WHERE id = $id") ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("DELETE_STAFF ERROR: " . $e->getMessage());
            return 0;
        }
    }

    // ================== DEPARTAMENTOS ==================
    function save_department() {
        try {
            // No usar extract() para evitar conflictos
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $isNewDept = ($id == 0);
            $name = isset($_POST['name']) ? $this->db->real_escape_string($_POST['name']) : '';
            $locations = isset($_POST['locations']) ? $_POST['locations'] : [];
            $positions = isset($_POST['positions']) ? $_POST['positions'] : [];
            
            // Log para debugging
            error_log("DEBUG save_department: id=$id, name=$name");
            error_log("DEBUG save_department: locations = " . print_r($locations, true));
            error_log("DEBUG save_department: positions = " . print_r($positions, true));
            
            // Validar nombre
            if(empty($name)) {
                error_log("ERROR: Name is empty");
                return 0;
            }

            // Capturar datos anteriores para auditoría
            $oldDeptData = !$isNewDept ? $this->getOldRecord('departments', $id) : null;
            
            // Verificar si el nombre ya existe
            $check_query = "SELECT * FROM departments WHERE name='$name' ".($id > 0 ? "AND id != $id" : '');
            error_log("DEBUG: Check query = $check_query");
            $check_result = $this->db->query($check_query);
            if(!$check_result) {
                error_log("ERROR in check query: " . $this->db->error);
                return 0;
            }
            $check = $check_result->num_rows;
            if ($check > 0) {
                error_log("ERROR: Department name already exists");
                return 2;
            }

            // Guardar departamento
            if($id == 0) {
                $query = "INSERT INTO departments SET name='$name'";
                error_log("DEBUG: Executing INSERT: $query");
                $save = $this->db->query($query);
                if(!$save) {
                    error_log("ERROR save_department INSERT: " . $this->db->error);
                    return 0;
                }
                $id = $this->db->insert_id;
                error_log("DEBUG: New department ID = $id");
            } else {
                $query = "UPDATE departments SET name='$name' WHERE id = $id";
                error_log("DEBUG: Executing UPDATE: $query");
                $save = $this->db->query($query);
                if(!$save) {
                    error_log("ERROR save_department UPDATE: " . $this->db->error);
                    return 0;
                }
            }
            
            if($save && $id > 0) {
                // Actualizar relaciones con ubicaciones
                if(is_array($locations) && count($locations) > 0) {
                    error_log("DEBUG: Processing " . count($locations) . " locations");
                    
                    // Quitar el departamento de ubicaciones que ya no están seleccionadas
                    $query = "UPDATE locations SET department_id = NULL WHERE department_id = $id";
                    error_log("DEBUG: Clearing locations: $query");
                    $update = $this->db->query($query);
                    if(!$update) {
                        error_log("ERROR clearing locations: " . $this->db->error);
                    }
                    
                    // Asignar el departamento a las ubicaciones seleccionadas
                    foreach($locations as $location_id) {
                        $location_id = intval($location_id);
                        if($location_id > 0) {
                            $query = "UPDATE locations SET department_id = $id WHERE id = $location_id";
                            error_log("DEBUG: Assigning location $location_id: $query");
                            $update = $this->db->query($query);
                            if(!$update) {
                                error_log("ERROR updating location $location_id: " . $this->db->error);
                            }
                        }
                    }
                } else {
                    // Si no hay ubicaciones seleccionadas, quitar todas las asignaciones
                    error_log("DEBUG: No locations selected, clearing all");
                    $this->db->query("UPDATE locations SET department_id = NULL WHERE department_id = $id");
                }
                
                // Actualizar relaciones con puestos
                if(is_array($positions) && count($positions) > 0) {
                    error_log("DEBUG: Processing " . count($positions) . " positions");
                    
                    // Quitar el departamento de puestos que ya no están seleccionados
                    $query = "UPDATE job_positions SET department_id = NULL WHERE department_id = $id";
                    error_log("DEBUG: Clearing positions: $query");
                    $update = $this->db->query($query);
                    if(!$update) {
                        error_log("ERROR clearing positions: " . $this->db->error);
                    }
                    
                    // Asignar el departamento a los puestos seleccionados
                    foreach($positions as $position_id) {
                        $position_id = intval($position_id);
                        if($position_id > 0) {
                            $query = "UPDATE job_positions SET department_id = $id WHERE id = $position_id";
                            error_log("DEBUG: Assigning position $position_id: $query");
                            $update = $this->db->query($query);
                            if(!$update) {
                                error_log("ERROR updating position $position_id: " . $this->db->error);
                            }
                        }
                    }
                } else {
                    // Si no hay puestos seleccionados, quitar todas las asignaciones
                    error_log("DEBUG: No positions selected, clearing all");
                    $this->db->query("UPDATE job_positions SET department_id = NULL WHERE department_id = $id");
                }
            }
            
            error_log("DEBUG save_department: Returning success");
            $this->audit('departments', $isNewDept ? 'create' : 'update', 'departments', $id, $oldDeptData ?? null, ['name' => $name, 'locations' => $locations, 'positions' => $positions]);
            return 1;
            
        } catch (Exception $e) {
            error_log("EXCEPTION in save_department: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0;
        }
    }

    function delete_department() {
        extract($_POST);
        $oldData = $this->getOldRecord('departments', (int)$id);
        // Antes de eliminar, quitar las relaciones
        $this->db->query("UPDATE locations SET department_id = NULL WHERE department_id = $id");
        $this->db->query("UPDATE job_positions SET department_id = NULL WHERE department_id = $id");
        $result = $this->db->query("DELETE FROM departments WHERE id = $id");
        if ($result) { $this->audit('departments', 'delete', 'departments', (int)$id, $oldData, null); }
        return $result ? 1 : 0;
    }

    // ================== SUCURSALES ==================
    function save_branch() {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $code = isset($_POST['code']) ? trim($_POST['code']) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';

            if ($code === '' || $name === '') {
                return 0;
            }

            $code_esc = $this->db->real_escape_string($code);
            $name_esc = $this->db->real_escape_string($name);

            $has_active = false;
            $col = $this->db->query("SHOW COLUMNS FROM branches LIKE 'active'");
            if ($col && $col->num_rows > 0) $has_active = true;

            $active_val = 1;
            if ($has_active) {
                $active_val = isset($_POST['active']) ? 1 : 0;
            }

            // Unicidad por código
            $check_code = $this->db->query("SELECT id FROM branches WHERE code='$code_esc' " . ($id > 0 ? "AND id != $id" : '') . " LIMIT 1");
            if ($check_code && $check_code->num_rows > 0) {
                return 2;
            }

            // Unicidad por nombre
            $check_name = $this->db->query("SELECT id FROM branches WHERE name='$name_esc' " . ($id > 0 ? "AND id != $id" : '') . " LIMIT 1");
            if ($check_name && $check_name->num_rows > 0) {
                return 3;
            }

            if ($id > 0) {
                $oldBranchData = $this->getOldRecord('branches', $id);
                $set = "code='$code_esc', name='$name_esc'";
                if ($has_active) $set .= ", active=$active_val";
                $save = $this->db->query("UPDATE branches SET $set WHERE id = $id");
                if ($save) { $this->audit('branches', 'update', 'branches', $id, $oldBranchData, ['code' => $code, 'name' => $name, 'active' => $active_val]); }
                return $save ? 1 : 0;
            }

            $fields = "code, name";
            $values = "'$code_esc', '$name_esc'";
            if ($has_active) {
                $fields .= ", active";
                $values .= ", $active_val";
            }

            $save = $this->db->query("INSERT INTO branches ($fields) VALUES ($values)");
            if ($save) { $this->audit('branches', 'create', 'branches', $this->db->insert_id, null, ['code' => $code, 'name' => $name, 'active' => $active_val]); }
            return $save ? 1 : 0;
        } catch (Exception $e) {
            error_log("SAVE_BRANCH ERROR: " . $e->getMessage());
            return 0;
        }
    }

    function delete_branch() {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if ($id <= 0) return 0;

            // Bloquear eliminación si está referenciada
            $tables = [
                ['table' => 'equipments', 'col' => 'branch_id'],
                ['table' => 'tools', 'col' => 'branch_id'],
                ['table' => 'accessories', 'col' => 'branch_id'],
                ['table' => 'maintenance_reports', 'col' => 'branch_id'],
                ['table' => 'users', 'col' => 'active_branch_id'],
            ];

            foreach ($tables as $t) {
                $table = $t['table'];
                $col = $t['col'];
                $exists = $this->db->query("SHOW TABLES LIKE '$table'");
                if (!$exists || $exists->num_rows === 0) continue;

                $has_col = $this->db->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                if (!$has_col || $has_col->num_rows === 0) continue;

                $cnt_res = $this->db->query("SELECT COUNT(*) as cnt FROM `$table` WHERE `$col` = $id");
                if ($cnt_res && ($row = $cnt_res->fetch_assoc())) {
                    if (intval($row['cnt']) > 0) {
                        return 2;
                    }
                }
            }

            $oldBranchData = $this->getOldRecord('branches', $id);
            $del = $this->db->query("DELETE FROM branches WHERE id = $id");
            if ($del) { $this->audit('branches', 'delete', 'branches', $id, $oldBranchData, null); }
            return $del ? 1 : 0;
        } catch (Exception $e) {
            error_log("DELETE_BRANCH ERROR: " . $e->getMessage());
            return 0;
        }
    }

    // ================== TICKETS ==================
    function save_ticket() {
        extract($_POST);
        $isNewTicket = empty($id);
        if (!$isNewTicket) { $oldTicketData = $this->getOldRecord('tickets', (int)$id); }
        $data = "";
        $auditTicket = [];
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id']) && !is_numeric($k)) {
                if ($k == 'description') $v = htmlentities(str_replace("'", "&#x2019;", $v));
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
                $auditTicket[$k] = $v;
            }
        }
        if (!isset($customer_id)) $data .= ", customer_id={$_SESSION['login_id']} ";
        if ($_SESSION['login_type'] == 1) $data .= ", admin_id={$_SESSION['login_id']} ";

        $save = $isNewTicket
            ? $this->db->query("INSERT INTO tickets SET $data")
            : $this->db->query("UPDATE tickets SET $data WHERE id = $id");
        if ($save) {
            $ticketId = $isNewTicket ? $this->db->insert_id : (int)$id;
            $this->audit('tickets', $isNewTicket ? 'create' : 'update', 'tickets', $ticketId, $oldTicketData ?? null, $auditTicket);
        }
        return $save ? 1 : 0;
    }

    function update_ticket() {
        extract($_POST);
        $oldTicketData = $this->getOldRecord('tickets', (int)$id);
        $oldStatus = isset($oldTicketData['status']) ? (int)$oldTicketData['status'] : null;
        $data = " status=$status ";
        if ($_SESSION['login_type'] == 2) $data .= ", staff_id={$_SESSION['login_id']} ";
        $result = $this->db->query("UPDATE tickets SET $data WHERE id = $id");
        if ($result) {
            $this->audit('tickets', 'update', 'tickets', (int)$id, $oldTicketData, ['status' => $status]);
            // E2.3: Registrar cambio de estado en historial
            $comment_hist = isset($comment) ? $this->db->real_escape_string($comment) : '';
            $changedBy = (int)$_SESSION['login_id'];
            $this->db->query("INSERT INTO ticket_status_history (ticket_id, old_status, new_status, changed_by, comment) VALUES ({$id}, " . ($oldStatus !== null ? $oldStatus : 'NULL') . ", {$status}, {$changedBy}, '{$comment_hist}')");
            // E2.2: Notificar cambio de estado (in-app)
            if (class_exists('NotificationService')) {
                NotificationService::notifyTicketStatusChange((int)$id, $oldStatus, (int)$status, $changedBy);
            }
            // Enviar email al reportante si es ticket público con email registrado
            if (!empty($oldTicketData['is_public']) && !empty($oldTicketData['reporter_email'])) {
                $this->sendPublicTicketStatusEmail($oldTicketData, (int)$status);
            }
            // Enviar email al tecnico asignado sobre el cambio de estado
            $statusLabels = [0 => 'Abierto', 1 => 'En Proceso', 2 => 'Finalizado', 3 => 'Cerrado'];
            $newLabel = $statusLabels[(int)$status] ?? 'Actualizado';
            $ticketSubject = htmlspecialchars($oldTicketData['subject'] ?? 'Sin asunto', ENT_QUOTES);
            $content = "<p>El ticket <strong>#{$id}</strong> — <em>{$ticketSubject}</em> cambio de estado a:</p>";
            $content .= "<p style='font-size:18px;color:#007bff;font-weight:bold'>{$newLabel}</p>";
            $this->sendTicketEmailToTechnician((int)$id, "Cambio de estado en ticket #{$id}", $this->buildTechnicianEmailBody('Cambio de Estado', $content, (int)$id), $changedBy);
        }
        return $result ? 1 : 0;
    }

    function delete_ticket() {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return 0;
            $oldData = $this->getOldRecord('tickets', $id);
            
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("DELETE FROM tickets WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) { $this->audit('tickets', 'delete', 'tickets', $id, $oldData, null); return 1; }
                return 0;
            } else {
                $del = $this->db->query("DELETE FROM tickets WHERE id = $id");
                if ($del) { $this->audit('tickets', 'delete', 'tickets', $id, $oldData, null); }
                return $del ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("DELETE_TICKET ERROR: " . $e->getMessage());
            return 0;
        }
    }

    // Ticket público (sin autenticación) para reportes de QR
    function save_public_ticket() {
        try {
            $equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
            $reporter_name = isset($_POST['reporter_name']) ? $this->db->real_escape_string(trim($_POST['reporter_name'])) : '';
            $reporter_email = isset($_POST['reporter_email']) ? $this->db->real_escape_string(trim($_POST['reporter_email'])) : '';
            $reporter_phone = isset($_POST['reporter_phone']) ? $this->db->real_escape_string(trim($_POST['reporter_phone'])) : '';
            $issue_type = isset($_POST['issue_type']) ? $this->db->real_escape_string($_POST['issue_type']) : '';
            $description = isset($_POST['description']) ? $this->db->real_escape_string($_POST['description']) : '';
            
            if ($equipment_id <= 0 || empty($reporter_name) || empty($description)) {
                return json_encode(['status' => 0, 'message' => 'Datos incompletos']);
            }
            
            // Obtener información del equipo
            $eq_query = $this->db->query("SELECT name, number_inventory FROM equipments WHERE id = $equipment_id");
            if (!$eq_query || $eq_query->num_rows === 0) {
                return json_encode(['status' => 0, 'message' => 'Equipo no encontrado']);
            }
            $equipment = $eq_query->fetch_assoc();
            
            // Generar número de ticket
            $ticket_number = 'PUB-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
            
            // Generar tracking token para seguimiento publico
            $tracking_token = bin2hex(random_bytes(16));
            
            // Crear el subject del ticket
            $subject = "Falla reportada: {$issue_type} - {$equipment['name']} (#{$equipment['number_inventory']})";
            
            // Solo guardar la descripcion libre del usuario (la metadata va en columnas dedicadas)
            $full_description = $description;
            
            // Insertar ticket
            $sql = "INSERT INTO tickets SET 
                    subject = '$subject',
                    description = '" . htmlentities(str_replace("'", "&#x2019;", $full_description)) . "',
                    status = 0,
                    priority = 'medium',
                    equipment_id = $equipment_id,
                    reporter_name = '$reporter_name',
                    reporter_email = '$reporter_email',
                    reporter_phone = '$reporter_phone',
                    issue_type = '$issue_type',
                    ticket_number = '$ticket_number',
                    is_public = 1,
                    tracking_token = '$tracking_token',
                    date_created = NOW()";
            
            $save = $this->db->query($sql);
            
            if (!$save) {
                error_log("Error al guardar ticket público: " . $this->db->error);
                return json_encode(['status' => 0, 'message' => 'Error al guardar el ticket']);
            }
            
            $ticket_id = $this->db->insert_id;
            $this->audit('tickets', 'create', 'tickets', $ticket_id, null, ['subject' => $subject, 'equipment_id' => $equipment_id, 'is_public' => 1, 'ticket_number' => $ticket_number]);            
            return json_encode([
                'status' => 1,
                'ticket_id' => $ticket_id,
                'ticket_number' => $ticket_number,
                'tracking_token' => $tracking_token,
                'tracking_url' => 'public/track.php?token=' . $tracking_token,
                'message' => 'Ticket creado exitosamente'
            ]);
            
        } catch (Exception $e) {
            error_log("Exception en save_public_ticket: " . $e->getMessage());
            return json_encode(['status' => 0, 'message' => 'Error inesperado']);
        }
    }

    function save_comment() {
        extract($_POST);
        $isNewComment = empty($id);
        $data = "";
        $auditComment = [];
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ['id', 'is_internal']) && !is_numeric($k)) {
                if ($k == 'comment') $v = htmlentities(str_replace("'", "&#x2019;", $v));
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
                $auditComment[$k] = $v;
            }
        }
        $data .= ", user_type={$_SESSION['login_type']}, user_id={$_SESSION['login_id']} ";
        // E2.4: soporte para is_internal
        $is_internal = isset($_POST['is_internal']) ? (int)$_POST['is_internal'] : 0;
        $data .= ", is_internal={$is_internal} ";
        $save = $isNewComment
            ? $this->db->query("INSERT INTO comments SET $data")
            : $this->db->query("UPDATE comments SET $data WHERE id = $id");
        if ($save) {
            $commentId = $isNewComment ? $this->db->insert_id : (int)$id;
            $this->audit('comments', $isNewComment ? 'create' : 'update', 'comments', $commentId, null, $auditComment);
            // E2.2: Notificar nuevo comentario (solo si no es nota interna)
            if ($isNewComment && $is_internal == 0 && isset($ticket_id) && class_exists('NotificationService')) {
                NotificationService::notifyTicketComment((int)$ticket_id, (int)$_SESSION['login_id']);
            }
            // Email al reportante externo si es ticket público con email registrado
            if ($isNewComment && $is_internal == 0 && isset($ticket_id)) {
                $ticketRow = $this->getOldRecord('tickets', (int)$ticket_id);
                if (!empty($ticketRow['is_public']) && !empty($ticketRow['reporter_email'])) {
                    $commentText = mb_substr(strip_tags(html_entity_decode($auditComment['comment'] ?? '')), 0, 300);
                    $this->sendPublicTicketCommentEmail($ticketRow, $commentText);
                }
                // Email al tecnico asignado sobre el nuevo comentario
                $commentPreview = htmlspecialchars(mb_substr(strip_tags(html_entity_decode($auditComment['comment'] ?? '')), 0, 300), ENT_QUOTES);
                $ticketSubject  = htmlspecialchars(($ticketRow['subject'] ?? 'Sin asunto'), ENT_QUOTES);
                $content  = "<p>Se agrego un nuevo comentario al ticket <strong>#{$ticket_id}</strong> — <em>{$ticketSubject}</em>:</p>";
                $content .= "<blockquote style='border-left:4px solid #007bff;margin:15px 0;padding:10px 15px;background:#f0f7ff;color:#333'>{$commentPreview}</blockquote>";
                $this->sendTicketEmailToTechnician((int)$ticket_id, "Nuevo comentario en ticket #{$ticket_id}", $this->buildTechnicianEmailBody('Nuevo Comentario', $content, (int)$ticket_id), (int)$_SESSION['login_id']);
            }
        }
        return $save ? 1 : 0;
    }

    function delete_comment() {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return 0;
            $oldData = $this->getOldRecord('comments', $id);
            
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) { $this->audit('comments', 'delete', 'comments', $id, $oldData, null); return 1; }
                return 0;
            } else {
                $del = $this->db->query("DELETE FROM comments WHERE id = $id");
                if ($del) { $this->audit('comments', 'delete', 'comments', $id, $oldData, null); }
                return $del ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("DELETE_COMMENT ERROR: " . $e->getMessage());
            return 0;
        }
    }

    // ================== E2.1: ADJUNTOS DE TICKETS ==================
    function upload_ticket_attachment() {
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        if ($ticket_id <= 0) return json_encode(['status' => 0, 'msg' => 'Ticket inválido']);

        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            return json_encode(['status' => 0, 'msg' => 'No se recibió archivo']);
        }

        $file = $_FILES['attachment'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed_types)) {
            return json_encode(['status' => 0, 'msg' => 'Tipo de archivo no permitido. Solo: JPG, PNG, GIF, WebP, PDF']);
        }
        if ($file['size'] > $max_size) {
            return json_encode(['status' => 0, 'msg' => 'El archivo excede 5MB']);
        }

        $upload_dir = ROOT . '/uploads/tickets/' . $ticket_id;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $unique_name = $safe_name . '_' . uniqid() . '.' . $ext;
        $dest = $upload_dir . '/' . $unique_name;
        $relative_path = 'uploads/tickets/' . $ticket_id . '/' . $unique_name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return json_encode(['status' => 0, 'msg' => 'Error al mover archivo']);
        }

        $original_name = $this->db->real_escape_string($file['name']);
        $relative_path_esc = $this->db->real_escape_string($relative_path);
        $mime_esc = $this->db->real_escape_string($mime);
        $size = (int)$file['size'];
        $user_id = (int)$_SESSION['login_id'];

        $save = $this->db->query("INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_type, file_size, uploaded_by) 
            VALUES ({$ticket_id}, '{$original_name}', '{$relative_path_esc}', '{$mime_esc}', {$size}, {$user_id})");

        if ($save) {
            $att_id = $this->db->insert_id;
            $this->audit('ticket_attachments', 'create', 'ticket_attachments', $att_id, null, ['ticket_id' => $ticket_id, 'file_name' => $file['name']]);
            return json_encode(['status' => 1, 'msg' => 'Archivo subido', 'id' => $att_id, 'file_name' => $file['name'], 'file_path' => $relative_path, 'file_type' => $mime, 'file_size' => $size]);
        }
        @unlink($dest);
        return json_encode(['status' => 0, 'msg' => 'Error al guardar en base de datos']);
    }

    function delete_ticket_attachment() {
        $att_id = (int)($_POST['id'] ?? 0);
        if ($att_id <= 0) return json_encode(['status' => 0, 'msg' => 'ID inválido']);

        $row = $this->db->query("SELECT * FROM ticket_attachments WHERE id = {$att_id}")->fetch_assoc();
        if (!$row) return json_encode(['status' => 0, 'msg' => 'Adjunto no encontrado']);

        $file_path = ROOT . '/' . $row['file_path'];
        $this->db->query("DELETE FROM ticket_attachments WHERE id = {$att_id}");
        if (file_exists($file_path)) @unlink($file_path);
        $this->audit('ticket_attachments', 'delete', 'ticket_attachments', $att_id, $row, null);
        return json_encode(['status' => 1, 'msg' => 'Adjunto eliminado']);
    }

    function get_ticket_attachments() {
        $ticket_id = (int)($_GET['ticket_id'] ?? $_POST['ticket_id'] ?? 0);
        if ($ticket_id <= 0) return json_encode([]);
        $result = $this->db->query("SELECT ta.*, 
            COALESCE(CONCAT(u.lastname, ', ', u.firstname), 'Usuario') as uploaded_by_name
            FROM ticket_attachments ta
            LEFT JOIN users u ON u.id = ta.uploaded_by
            WHERE ta.ticket_id = {$ticket_id} ORDER BY ta.created_at ASC");
        $attachments = [];
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        return json_encode($attachments);
    }

    // ================== INVENTARIO CON PREFIJOS ==================
    private function table_exists_local($table) {
        if (!$this->db) return false;
        $table_esc = $this->db->real_escape_string($table);
        $res = $this->db->query("SHOW TABLES LIKE '{$table_esc}'");
        return $res && $res->num_rows > 0;
    }

    private function column_exists_local($table, $column) {
        if (!$this->db) return false;
        $table_esc = $this->db->real_escape_string($table);
        $col_esc = $this->db->real_escape_string($column);
        $res = $this->db->query("SHOW COLUMNS FROM `{$table_esc}` LIKE '{$col_esc}'");
        return $res && $res->num_rows > 0;
    }

    private function index_exists_local($table, $index_name) {
        if (!$this->db) return false;
        $table_esc = $this->db->real_escape_string($table);
        $idx_esc = $this->db->real_escape_string($index_name);
        try {
            $res = $this->db->query("SHOW INDEX FROM `{$table_esc}` WHERE Key_name = '{$idx_esc}'");
            return $res && $res->num_rows > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function ensure_equipment_categories_schema() {
        if (!$this->db) return;

        // Si no existe, crearla completa
        if (!$this->table_exists_local('equipment_categories')) {
            $sql = "CREATE TABLE IF NOT EXISTS `equipment_categories` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `clave` VARCHAR(3) NULL,
                `description` VARCHAR(255) NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_equipment_categories_clave` (`clave`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            @$this->db->query($sql);
            return;
        }

        // Si ya existe, asegurar columnas mínimas (para compatibilidad con instalaciones antiguas)
        if (!$this->column_exists_local('equipment_categories', 'description')) {
            // Algunas instalaciones antiguas podían usar `name`
            if ($this->column_exists_local('equipment_categories', 'name')) {
                // No renombrar automáticamente; solo aseguramos que al menos exista 'description'
                // sin borrar datos. Se puede poblar luego si se requiere.
                @$this->db->query("ALTER TABLE `equipment_categories` ADD COLUMN `description` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`");
                @$this->db->query("UPDATE `equipment_categories` SET `description` = COALESCE(NULLIF(`description`, ''), `name`)");
            } else {
                @$this->db->query("ALTER TABLE `equipment_categories` ADD COLUMN `description` VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }

        if (!$this->column_exists_local('equipment_categories', 'clave')) {
            // NULLable para no romper filas existentes; se puede completar desde configuración.
            @$this->db->query("ALTER TABLE `equipment_categories` ADD COLUMN `clave` VARCHAR(3) NULL AFTER `id`");
        }
        if (!$this->column_exists_local('equipment_categories', 'active')) {
            @$this->db->query("ALTER TABLE `equipment_categories` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `description`");
        }
        if ($this->column_exists_local('equipment_categories', 'clave') && !$this->index_exists_local('equipment_categories', 'uniq_equipment_categories_clave')) {
            try {
                @$this->db->query("ALTER TABLE `equipment_categories` ADD UNIQUE KEY `uniq_equipment_categories_clave` (`clave`)");
            } catch (Throwable $e) {
                // Ignorar (puede fallar si ya existe con otro nombre o hay datos duplicados)
            }
        }
    }

    private function ensure_inventory_config_schema() {
        if (!$this->db) return;
        if (!$this->table_exists_local('inventory_config')) {
            $sql = "CREATE TABLE IF NOT EXISTS `inventory_config` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `branch_id` INT NOT NULL,
                `acquisition_type_id` INT NULL,
                `equipment_category_id` INT NULL,
                `prefix` VARCHAR(64) NOT NULL,
                `current_number` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            @$this->db->query($sql);
            return;
        }

        if (!$this->column_exists_local('inventory_config', 'acquisition_type_id')) {
            @$this->db->query("ALTER TABLE `inventory_config` ADD COLUMN `acquisition_type_id` INT NULL AFTER `branch_id`");
        }
        if (!$this->column_exists_local('inventory_config', 'equipment_category_id')) {
            @$this->db->query("ALTER TABLE `inventory_config` ADD COLUMN `equipment_category_id` INT NULL AFTER `acquisition_type_id`");
        }
        // Asegurar índice único (solo si no existe; evita excepciones con MYSQLI_REPORT_STRICT)
        if (!$this->index_exists_local('inventory_config', 'uniq_inventory_cfg')) {
            try {
                $this->db->query("ALTER TABLE `inventory_config` ADD UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`)");
            } catch (Throwable $e) {
                // Ignorar: puede existir con otro nombre o haber datos duplicados.
            }
        }
    }

    private function ensure_acquisition_type_code_column() {
        if (!$this->db) return;
        if (!$this->table_exists_local('acquisition_type')) return;
        if (!$this->column_exists_local('acquisition_type', 'code')) {
            @$this->db->query("ALTER TABLE `acquisition_type` ADD COLUMN `code` VARCHAR(3) NULL AFTER `name`");
        }
    }

    private function ensure_acquisition_type_schema() {
        if (!$this->db) return;

        if (!$this->table_exists_local('acquisition_type')) {
            $sql = "CREATE TABLE IF NOT EXISTS `acquisition_type` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `code` VARCHAR(3) NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            @$this->db->query($sql);
        }

        $this->ensure_acquisition_type_code_column();

        if ($this->table_exists_local('acquisition_type') && !$this->column_exists_local('acquisition_type', 'active')) {
            @$this->db->query("ALTER TABLE `acquisition_type` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `code`");
        }
        // Índice único para code (solo si no existe; evita fatal por 'Duplicate key name')
        if (
            $this->table_exists_local('acquisition_type') &&
            $this->column_exists_local('acquisition_type', 'code') &&
            !$this->index_exists_local('acquisition_type', 'uniq_acquisition_type_code')
        ) {
            try {
                $this->db->query("ALTER TABLE `acquisition_type` ADD UNIQUE KEY `uniq_acquisition_type_code` (`code`)");
            } catch (Throwable $e) {
                // Ignorar errores de carrera/duplicado (p.ej. ya existe con otro nombre)
                error_log('ensure_acquisition_type_schema index create warning: ' . $e->getMessage());
            }
        }
    }

    private function derive_acquisition_code($acquisition_type_id) {
        $id = (int)$acquisition_type_id;
        if ($id <= 0) return null;

        // Preferir mysqli si está disponible (compatibilidad legacy)
        if ($this->db) {
            $this->ensure_acquisition_type_code_column();
            $has_code = $this->column_exists_local('acquisition_type', 'code');
            $cols = $has_code ? 'id, name, code' : 'id, name';
            $res = $this->db->query("SELECT {$cols} FROM acquisition_type WHERE id = {$id} LIMIT 1");
            if (!$res || $res->num_rows === 0) return null;
            $row = $res->fetch_assoc();

            $code = strtoupper(trim($row['code'] ?? ''));
            if ($code !== '') {
                $code = preg_replace('/[^A-Z0-9]/', '', $code);
                $code = substr($code, 0, 3);
                if ($code !== '') return $code;
            }

            $name = strtoupper(trim($row['name'] ?? ''));
            $name = preg_replace('/[^A-Z0-9]/', '', $name);
            $name = substr($name, 0, 3);
            if ($name !== '') return $name;

            return str_pad((string)($id % 1000), 3, '0', STR_PAD_LEFT);
        }

        // Fallback: si mysqli no existe en el entorno, usar PDO
        if ($this->pdo) {
            try {
                $has_code = false;
                try {
                    $c = $this->pdo->query("SHOW COLUMNS FROM acquisition_type LIKE 'code'");
                    $has_code = $c && $c->fetch(PDO::FETCH_ASSOC);
                } catch (Throwable $e) {
                    $has_code = false;
                }
                $cols = $has_code ? 'id, name, code' : 'id, name';
                $stmt = $this->pdo->prepare("SELECT {$cols} FROM acquisition_type WHERE id = ? LIMIT 1");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) return null;

                $code = strtoupper(trim($row['code'] ?? ''));
                if ($code !== '') {
                    $code = preg_replace('/[^A-Z0-9]/', '', $code);
                    $code = substr($code, 0, 3);
                    if ($code !== '') return $code;
                }

                $name = strtoupper(trim($row['name'] ?? ''));
                $name = preg_replace('/[^A-Z0-9]/', '', $name);
                $name = substr($name, 0, 3);
                if ($name !== '') return $name;

                return str_pad((string)($id % 1000), 3, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    function preview_inventory_number($branch_id, $acquisition_type_id = null, $equipment_category_id = null) {
        try {
            $branch_id = (int)$branch_id;
            $acquisition_type_id = $acquisition_type_id !== null ? (int)$acquisition_type_id : null;
            $equipment_category_id = $equipment_category_id !== null ? (int)$equipment_category_id : null;
            if ($branch_id <= 0) return false;

            // Prefijo base desde branches.code (3 caracteres)
            $branch_code = null;
            if ($this->db) {
                $bq = $this->db->query("SELECT code FROM branches WHERE id = {$branch_id} LIMIT 1");
                if ($bq && $bq->num_rows > 0) {
                    $branch_code = strtoupper(trim(($bq->fetch_assoc()['code'] ?? '')));
                    $branch_code = substr(preg_replace('/[^A-Z0-9]/', '', $branch_code), 0, 3);
                }
            } elseif ($this->pdo) {
                try {
                    $stmt = $this->pdo->prepare("SELECT code FROM branches WHERE id = ? LIMIT 1");
                    $stmt->execute([$branch_id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $branch_code = strtoupper(trim((string)($row['code'] ?? '')));
                        $branch_code = substr(preg_replace('/[^A-Z0-9]/', '', $branch_code), 0, 3);
                    }
                } catch (Throwable $e) {
                    $branch_code = null;
                }
            }
            if (!$branch_code) $branch_code = 'INV';

            $has_full_params = (!empty($acquisition_type_id) && !empty($equipment_category_id));
            if (!$has_full_params) {
                $prefix = $branch_code;
                $next = 1;

                if ($this->db && $this->table_exists_local('inventory_config')) {
                    $res = $this->db->query("SELECT prefix, current_number FROM inventory_config WHERE branch_id = {$branch_id} LIMIT 1");
                    if ($res && $res->num_rows > 0) {
                        $row = $res->fetch_assoc();
                        $prefix = !empty($row['prefix']) ? (string)$row['prefix'] : $prefix;
                        $next = ((int)($row['current_number'] ?? 0)) + 1;
                    }
                } elseif ($this->pdo) {
                    try {
                        $stmt = $this->pdo->prepare("SELECT prefix, current_number FROM inventory_config WHERE branch_id = ? LIMIT 1");
                        $stmt->execute([$branch_id]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $prefix = !empty($row['prefix']) ? (string)$row['prefix'] : $prefix;
                            $next = ((int)($row['current_number'] ?? 0)) + 1;
                        }
                    } catch (Throwable $e) {
                        // tabla no existe u otro error: usar 001
                    }
                }

                return $prefix . '-' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
            }

            $acq_code = $this->derive_acquisition_code($acquisition_type_id);
            if (!$acq_code) return false;

            // Derivar código de categoría (sin mutar DB)
            $cat_code = '';
            if ($this->db) {
                $has_clave = $this->column_exists_local('equipment_categories', 'clave');
                $has_desc = $this->column_exists_local('equipment_categories', 'description');
                $has_name = $this->column_exists_local('equipment_categories', 'name');
                $desc_col = $has_desc ? 'description' : ($has_name ? 'name' : null);
                $select_cols = $has_clave
                    ? ("clave" . ($desc_col ? ", {$desc_col} AS description" : ""))
                    : ($desc_col ? "{$desc_col} AS description" : "id");

                $cq = $this->db->query("SELECT {$select_cols} FROM equipment_categories WHERE id = {$equipment_category_id} LIMIT 1");
                if (!$cq || $cq->num_rows === 0) return false;
                $cat_row = $cq->fetch_assoc();

                $cat_code_raw = $has_clave ? ($cat_row['clave'] ?? '') : '';
                $cat_code = strtoupper(trim((string)$cat_code_raw));
                $cat_code = substr(preg_replace('/[^A-Z0-9]/', '', $cat_code), 0, 3);
                if ($cat_code === '') {
                    $desc_raw = (string)($cat_row['description'] ?? '');
                    $desc = strtoupper(trim($desc_raw));
                    $desc = substr(preg_replace('/[^A-Z0-9]/', '', $desc), 0, 3);
                    $cat_code = $desc !== '' ? $desc : str_pad((string)($equipment_category_id % 1000), 3, '0', STR_PAD_LEFT);
                }
            } elseif ($this->pdo) {
                // Detectar columnas para compatibilidad
                $has_clave_pdo = false;
                $has_desc_pdo = false;
                $has_name_pdo = false;
                try { $c = $this->pdo->query("SHOW COLUMNS FROM equipment_categories LIKE 'clave'"); $has_clave_pdo = $c && $c->fetch(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
                try { $c = $this->pdo->query("SHOW COLUMNS FROM equipment_categories LIKE 'description'"); $has_desc_pdo = $c && $c->fetch(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
                try { $c = $this->pdo->query("SHOW COLUMNS FROM equipment_categories LIKE 'name'"); $has_name_pdo = $c && $c->fetch(PDO::FETCH_ASSOC); } catch (Throwable $e) {}

                $desc_col = $has_desc_pdo ? 'description' : ($has_name_pdo ? 'name' : null);
                $select_cols = $has_clave_pdo
                    ? ("clave" . ($desc_col ? ", {$desc_col} AS description" : ""))
                    : ($desc_col ? "{$desc_col} AS description" : "id");

                $stmt = $this->pdo->prepare("SELECT {$select_cols} FROM equipment_categories WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$equipment_category_id]);
                $cat_row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$cat_row) return false;

                $cat_code_raw = $has_clave_pdo ? ($cat_row['clave'] ?? '') : '';
                $cat_code = strtoupper(trim((string)$cat_code_raw));
                $cat_code = substr(preg_replace('/[^A-Z0-9]/', '', $cat_code), 0, 3);
                if ($cat_code === '') {
                    $desc_raw = (string)($cat_row['description'] ?? '');
                    $desc = strtoupper(trim($desc_raw));
                    $desc = substr(preg_replace('/[^A-Z0-9]/', '', $desc), 0, 3);
                    $cat_code = $desc !== '' ? $desc : str_pad((string)($equipment_category_id % 1000), 3, '0', STR_PAD_LEFT);
                }
            }
            if ($cat_code === '') return false;

            $prefix = $branch_code . $acq_code . $cat_code;
            $next = 1;

            if ($this->db && $this->table_exists_local('inventory_config') && $this->column_exists_local('inventory_config', 'acquisition_type_id') && $this->column_exists_local('inventory_config', 'equipment_category_id')) {
                $b = (int)$branch_id;
                $a = (int)$acquisition_type_id;
                $c = (int)$equipment_category_id;
                $res = $this->db->query("SELECT current_number FROM inventory_config WHERE branch_id = {$b} AND acquisition_type_id = {$a} AND equipment_category_id = {$c} LIMIT 1");
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $next = ((int)($row['current_number'] ?? 0)) + 1;
                }
            } elseif ($this->pdo) {
                try {
                    $stmt = $this->pdo->prepare("SELECT current_number FROM inventory_config WHERE branch_id = ? AND acquisition_type_id = ? AND equipment_category_id = ? LIMIT 1");
                    $stmt->execute([(int)$branch_id, (int)$acquisition_type_id, (int)$equipment_category_id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $next = ((int)($row['current_number'] ?? 0)) + 1;
                    }
                } catch (Throwable $e) {
                    // tabla/columnas no existen: usar 001
                }
            }

            return $prefix . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return false;
        }
    }

    function get_next_inventory_number($branch_id, $acquisition_type_id = null, $equipment_category_id = null) {
        try {
            $branch_id = (int)$branch_id;
            $acquisition_type_id = $acquisition_type_id !== null ? (int)$acquisition_type_id : null;
            $equipment_category_id = $equipment_category_id !== null ? (int)$equipment_category_id : null;
            if ($branch_id <= 0) return false;

            // Prefijo base desde branches.code (3 caracteres)
            $branch_code = null;
            if ($this->db) {
                $bq = $this->db->query("SELECT code FROM branches WHERE id = {$branch_id} LIMIT 1");
                if ($bq && $bq->num_rows > 0) {
                    $branch_code = strtoupper(trim(($bq->fetch_assoc()['code'] ?? '')));
                    $branch_code = substr(preg_replace('/[^A-Z0-9]/', '', $branch_code), 0, 3);
                }
            } elseif ($this->pdo) {
                try {
                    $stmt = $this->pdo->prepare("SELECT code FROM branches WHERE id = ? LIMIT 1");
                    $stmt->execute([$branch_id]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $branch_code = strtoupper(trim((string)($row['code'] ?? '')));
                        $branch_code = substr(preg_replace('/[^A-Z0-9]/', '', $branch_code), 0, 3);
                    }
                } catch (Throwable $e) {
                    $branch_code = null;
                }
            }
            if (!$branch_code) {
                $branch_code = 'INV';
            }

            // Si faltan partes del nuevo esquema, usar esquema anterior por sucursal (PREFIX-001)
            if (empty($acquisition_type_id) || empty($equipment_category_id)) {
                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("SELECT prefix, current_number FROM inventory_config WHERE branch_id = ? LIMIT 1");
                    $stmt->execute([$branch_id]);
                    $config = $stmt->fetch();
                    if (!$config) {
                        $ins = $this->pdo->prepare("INSERT INTO inventory_config (branch_id, prefix, current_number) VALUES (?, ?, 0)");
                        $ins->execute([$branch_id, $branch_code]);
                        $config = ['prefix' => $branch_code, 'current_number' => 0];
                    }
                    $prefix = $config['prefix'] ?: $branch_code;
                    $current = ((int)$config['current_number']) + 1;
                    $update_stmt = $this->pdo->prepare("UPDATE inventory_config SET current_number = ? WHERE branch_id = ?");
                    $update_stmt->execute([$current, $branch_id]);
                    return $prefix . '-' . str_pad((string)$current, 3, '0', STR_PAD_LEFT);
                }

                if ($this->db) {
                    $prefix = $branch_code;
                    $res = $this->db->query("SELECT id, prefix, current_number FROM inventory_config WHERE branch_id = {$branch_id} LIMIT 1");
                    if (!$res || $res->num_rows === 0) {
                        $this->db->query("INSERT INTO inventory_config (branch_id, prefix, current_number) VALUES ({$branch_id}, '{$this->db->real_escape_string($prefix)}', 0)");
                        $current = 1;
                        $this->db->query("UPDATE inventory_config SET current_number = {$current} WHERE branch_id = {$branch_id}");
                        return $prefix . '-' . str_pad((string)$current, 3, '0', STR_PAD_LEFT);
                    }
                    $row = $res->fetch_assoc();
                    $current = ((int)($row['current_number'] ?? 0)) + 1;
                    $prefix = !empty($row['prefix']) ? $row['prefix'] : $prefix;
                    $this->db->query("UPDATE inventory_config SET current_number = {$current} WHERE id = " . (int)$row['id']);
                    return $prefix . '-' . str_pad((string)$current, 3, '0', STR_PAD_LEFT);
                }

                return false;
            }

            // Nuevo esquema: SUC(3)+ADQ(2/3)+CAT(2/3)+001 (consecutivo por combinación)
            $acq_code = $this->derive_acquisition_code($acquisition_type_id);
            if (!$acq_code) return false;

            // ====== Camino mysqli ======
            if ($this->db) {
                $this->ensure_inventory_config_schema();
                $this->ensure_equipment_categories_schema();
                $this->ensure_acquisition_type_schema();

                // Algunas instalaciones antiguas no tenían `clave` y/o usaban `name` en vez de `description`.
                $has_clave = $this->column_exists_local('equipment_categories', 'clave');
                $has_desc = $this->column_exists_local('equipment_categories', 'description');
                $has_name = $this->column_exists_local('equipment_categories', 'name');

                $desc_col = $has_desc ? 'description' : ($has_name ? 'name' : null);
                $select_cols = $has_clave
                    ? ("clave" . ($desc_col ? ", {$desc_col} AS description" : ""))
                    : ($desc_col ? "{$desc_col} AS description" : "id");

                $cq = $this->db->query("SELECT {$select_cols} FROM equipment_categories WHERE id = {$equipment_category_id} LIMIT 1");
                if (!$cq || $cq->num_rows === 0) {
                    error_log("ERROR: equipment_category_id {$equipment_category_id} no encontrada");
                    return false;
                }
                $cat_row = $cq->fetch_assoc();

                $cat_code_raw = $has_clave ? ($cat_row['clave'] ?? '') : '';
                $cat_code = strtoupper(trim($cat_code_raw));
                $cat_code = substr(preg_replace('/[^A-Z0-9]/', '', $cat_code), 0, 3);
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    error_log("Category ID {$equipment_category_id} - clave raw: '{$cat_code_raw}'");
                    error_log("Category code after processing: '{$cat_code}'");
                }
                if ($cat_code === '') {
                    $desc_raw = (string)($cat_row['description'] ?? '');
                    $desc = strtoupper(trim($desc_raw));
                    $desc = substr(preg_replace('/[^A-Z0-9]/', '', $desc), 0, 3);
                    if ($desc !== '') {
                        $cat_code = $desc;
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("WARN: cat_code vacío, usando description='{$desc_raw}' => '{$cat_code}'");
                        }
                    } else {
                        $cat_code = str_pad((string)(((int)$equipment_category_id) % 1000), 3, '0', STR_PAD_LEFT);
                        if (defined('APP_DEBUG') && APP_DEBUG) {
                            error_log("WARN: cat_code vacío, usando ID={$equipment_category_id} => '{$cat_code}'");
                        }
                    }
                }

                $prefix = $branch_code . $acq_code . $cat_code;
                $prefix_esc = $this->db->real_escape_string($prefix);
                $b = (int)$branch_id;
                $a = (int)$acquisition_type_id;
                $c = (int)$equipment_category_id;

                // Primero verificamos si existe el registro
                $sel = $this->db->query("SELECT id, current_number FROM inventory_config WHERE branch_id = {$b} AND acquisition_type_id = {$a} AND equipment_category_id = {$c} LIMIT 1");
                
                if ($sel && $sel->num_rows > 0) {
                    // Ya existe: incrementar current_number
                    $r = $sel->fetch_assoc();
                    $n = ((int)($r['current_number'] ?? 0)) + 1;
                    $this->db->query("UPDATE inventory_config SET prefix = '{$prefix_esc}', current_number = {$n} WHERE id = " . (int)$r['id']);
                } else {
                    // No existe: crear nuevo con current_number = 1
                    $this->db->query("INSERT INTO inventory_config (branch_id, acquisition_type_id, equipment_category_id, prefix, current_number) VALUES ({$b}, {$a}, {$c}, '{$prefix_esc}', 1)");
                    $n = 1;
                }

                $seq = str_pad((string)$n, 3, '0', STR_PAD_LEFT);
                return $prefix . $seq;
            }

            // ====== Camino PDO (cuando $conn/mysqli no existe en prod) ======
            if (!$this->pdo) return false;

            // Best-effort: asegurar tabla/columnas/índice necesarios para que el UPSERT funcione
            try {
                $this->pdo->exec(
                    "CREATE TABLE IF NOT EXISTS `inventory_config` (\n" .
                    "  `id` INT NOT NULL AUTO_INCREMENT,\n" .
                    "  `branch_id` INT NOT NULL,\n" .
                    "  `acquisition_type_id` INT NULL,\n" .
                    "  `equipment_category_id` INT NULL,\n" .
                    "  `prefix` VARCHAR(64) NOT NULL,\n" .
                    "  `current_number` INT NOT NULL DEFAULT 0,\n" .
                    "  PRIMARY KEY (`id`),\n" .
                    "  UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`)\n" .
                    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );
            } catch (Throwable $e) {
                // ignorar
            }
            try { $this->pdo->exec("ALTER TABLE `inventory_config` ADD COLUMN `acquisition_type_id` INT NULL AFTER `branch_id`"); } catch (Throwable $e) {}
            try { $this->pdo->exec("ALTER TABLE `inventory_config` ADD COLUMN `equipment_category_id` INT NULL AFTER `acquisition_type_id`"); } catch (Throwable $e) {}
            try { $this->pdo->exec("ALTER TABLE `inventory_config` ADD UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`)"); } catch (Throwable $e) {}

            // Detectar columnas en PDO para compatibilidad con tablas antiguas
            $has_clave_pdo = false;
            $has_desc_pdo = false;
            $has_name_pdo = false;
            try {
                $c = $this->pdo->query("SHOW COLUMNS FROM equipment_categories LIKE 'clave'");
                $has_clave_pdo = $c && $c->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}
            try {
                $c = $this->pdo->query("SHOW COLUMNS FROM equipment_categories LIKE 'description'");
                $has_desc_pdo = $c && $c->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}
            try {
                $c = $this->pdo->query("SHOW COLUMNS FROM equipment_categories LIKE 'name'");
                $has_name_pdo = $c && $c->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {}

            $desc_col = $has_desc_pdo ? 'description' : ($has_name_pdo ? 'name' : null);
            $select_cols = $has_clave_pdo
                ? ("clave" . ($desc_col ? ", {$desc_col} AS description" : ""))
                : ($desc_col ? "{$desc_col} AS description" : "id");

            $stmt = $this->pdo->prepare("SELECT {$select_cols} FROM equipment_categories WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$equipment_category_id]);
            $cat_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cat_row) {
                error_log("ERROR: equipment_category_id {$equipment_category_id} no encontrada (PDO)");
                return false;
            }

            $cat_code_raw = $has_clave_pdo ? ($cat_row['clave'] ?? '') : '';
            $cat_code = strtoupper(trim((string)$cat_code_raw));
            $cat_code = substr(preg_replace('/[^A-Z0-9]/', '', $cat_code), 0, 3);
            if ($cat_code === '') {
                $desc_raw = (string)($cat_row['description'] ?? '');
                $desc = strtoupper(trim($desc_raw));
                $desc = substr(preg_replace('/[^A-Z0-9]/', '', $desc), 0, 3);
                if ($desc !== '') {
                    $cat_code = $desc;
                } else {
                    $cat_code = str_pad((string)(((int)$equipment_category_id) % 1000), 3, '0', STR_PAD_LEFT);
                }
            }

            $prefix = $branch_code . $acq_code . $cat_code;
            $b = (int)$branch_id;
            $a = (int)$acquisition_type_id;
            $c = (int)$equipment_category_id;

            try {
                $this->pdo->beginTransaction();

                $up = $this->pdo->prepare(
                    "INSERT INTO inventory_config (branch_id, acquisition_type_id, equipment_category_id, prefix, current_number)\n" .
                    "VALUES (?, ?, ?, ?, 1)\n" .
                    "ON DUPLICATE KEY UPDATE\n" .
                    "  prefix = VALUES(prefix),\n" .
                    "  current_number = LAST_INSERT_ID(current_number + 1)"
                );
                $up->execute([$b, $a, $c, $prefix]);
                $n = (int)$this->pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();

                // Si por alguna razón no hay índice, intentar fallback con bloqueo
                if ($n <= 0) {
                    $sel = $this->pdo->prepare(
                        "SELECT id, current_number FROM inventory_config WHERE branch_id = ? AND acquisition_type_id = ? AND equipment_category_id = ? LIMIT 1 FOR UPDATE"
                    );
                    $sel->execute([$b, $a, $c]);
                    $row = $sel->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $n = ((int)($row['current_number'] ?? 0)) + 1;
                        $upd = $this->pdo->prepare("UPDATE inventory_config SET prefix = ?, current_number = ? WHERE id = ?");
                        $upd->execute([$prefix, $n, (int)$row['id']]);
                    } else {
                        $ins = $this->pdo->prepare(
                            "INSERT INTO inventory_config (branch_id, acquisition_type_id, equipment_category_id, prefix, current_number) VALUES (?, ?, ?, ?, 1)"
                        );
                        $ins->execute([$b, $a, $c, $prefix]);
                        $n = 1;
                    }
                }

                $this->pdo->commit();
                $seq = str_pad((string)$n, 3, '0', STR_PAD_LEFT);
                return $prefix . $seq;
            } catch (Throwable $e) {
                if ($this->pdo && $this->pdo->inTransaction()) {
                    try { $this->pdo->rollBack(); } catch (Throwable $e2) {}
                }
                error_log('GET_NEXT_INVENTORY_NUMBER PDO ERROR: ' . $e->getMessage());
                return false;
            }
        } catch (Throwable $e) {
            error_log("GET_NEXT_INVENTORY_NUMBER ERROR: " . $e->getMessage());
            return false;
        }
    }

    // ================== TIPOS DE ADQUISICIÓN (CONFIG) ==================
    function load_acquisition_type() {
        $this->ensure_acquisition_type_schema();
        $data = array();

        if (!$this->db || !$this->table_exists_local('acquisition_type')) {
            return json_encode(['status' => 'success', 'data' => $data]);
        }

        $has_code = $this->column_exists_local('acquisition_type', 'code');
        $has_active = $this->column_exists_local('acquisition_type', 'active');
        $cols = 'id, name' . ($has_code ? ', code' : '');
        $where = $has_active ? 'WHERE active = 1' : '';
        $order = $has_code ? 'ORDER BY code ASC, name ASC' : 'ORDER BY name ASC';
        $qry = $this->db->query("SELECT {$cols} FROM acquisition_type {$where} {$order}");
        if ($qry) {
            while ($row = $qry->fetch_assoc()) {
                $row['name'] = strip_tags(stripslashes($row['name'] ?? ''));
                $row['code'] = strtoupper(trim($row['code'] ?? ''));
                $data[] = $row;
            }
        }
        return json_encode(['status' => 'success', 'data' => $data]);
    }

    function save_acquisition_type() {
        $this->ensure_acquisition_type_schema();
        if (!$this->db || !$this->table_exists_local('acquisition_type')) {
            return json_encode(['status' => 'error']);
        }

        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $code = preg_replace('/[^A-Z0-9]/', '', $code);
        $name = trim($_POST['name'] ?? '');

        if ($name === '' || $code === '' || strlen($code) < 2 || strlen($code) > 3) {
            return json_encode(['status' => 'error']);
        }

        $has_code = $this->column_exists_local('acquisition_type', 'code');
        if (!$has_code) {
            $this->ensure_acquisition_type_code_column();
        }

        // Duplicados por code (para crear o editar)
        $code_esc = $this->db->real_escape_string($code);
        if ($id > 0) {
            $chk = $this->db->query("SELECT id FROM acquisition_type WHERE code = '{$code_esc}' AND id != {$id} LIMIT 1");
        } else {
            $chk = $this->db->query("SELECT id FROM acquisition_type WHERE code = '{$code_esc}' LIMIT 1");
        }
        if ($chk && $chk->num_rows > 0) {
            return json_encode(['status' => 'duplicate_code']);
        }

        // Si se intenta CAMBIAR la clave y el tipo está en uso, bloquear
        if ($id > 0) {
            $current = $this->db->query("SELECT code FROM acquisition_type WHERE id = {$id} LIMIT 1");
            if (!$current || $current->num_rows === 0) {
                return json_encode(['status' => 'error']);
            }
            $existing = strtoupper(trim($current->fetch_assoc()['code'] ?? ''));
            $isChangingCode = ($existing !== '' && $existing !== $code);
            $isAddingCode = ($existing === '' && $code !== '');
            if ($isChangingCode || $isAddingCode) {
                $inUse = false;
                if ($this->table_exists_local('inventory_config') && $this->column_exists_local('inventory_config', 'acquisition_type_id')) {
                    $u = $this->db->query("SELECT id FROM inventory_config WHERE acquisition_type_id = {$id} LIMIT 1");
                    if ($u && $u->num_rows > 0) $inUse = true;
                }
                if (!$inUse && $this->table_exists_local('equipment') && $this->column_exists_local('equipment', 'acquisition_type')) {
                    $u = $this->db->query("SELECT id FROM equipment WHERE acquisition_type = {$id} LIMIT 1");
                    if ($u && $u->num_rows > 0) $inUse = true;
                }
                if ($inUse && $isChangingCode) {
                    return json_encode(['status' => 'in_use']);
                }
            }
        }

        $name_esc = $this->db->real_escape_string($name);

        // Capturar datos anteriores para auditoría
        $oldAcqData = ($id > 0) ? $this->getOldRecord('acquisition_type', $id) : null;

        if ($id > 0) {
            $sql = "UPDATE acquisition_type SET name = '{$name_esc}', code = '{$code_esc}' WHERE id = {$id}";
        } else {
            $sql = "INSERT INTO acquisition_type (name, code, active) VALUES ('{$name_esc}', '{$code_esc}', 1)";
        }

        $save = $this->db->query($sql);
        if ($save) {
            $acqId = ($id > 0) ? $id : $this->db->insert_id;
            $this->audit('acquisition_types', ($id > 0) ? 'update' : 'create', 'acquisition_type', $acqId, ($id > 0) ? $oldAcqData : null, ['code' => $code, 'name' => $name]);
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'error']);
    }

    function delete_acquisition_type() {
        $this->ensure_acquisition_type_schema();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return json_encode(['status' => 'error']);
        $oldData = $this->getOldRecord('acquisition_type', $id);

        // Evitar eliminar si está en uso (equipos o inventario_config)
        if ($this->db) {
            if ($this->column_exists_local('equipment', 'acquisition_type') && $this->table_exists_local('equipment')) {
                $chk = $this->db->query("SELECT id FROM equipment WHERE acquisition_type = {$id} LIMIT 1");
                if ($chk && $chk->num_rows > 0) {
                    return json_encode(['status' => 'in_use']);
                }
            }
            if ($this->table_exists_local('inventory_config') && $this->column_exists_local('inventory_config', 'acquisition_type_id')) {
                $chk2 = $this->db->query("SELECT id FROM inventory_config WHERE acquisition_type_id = {$id} LIMIT 1");
                if ($chk2 && $chk2->num_rows > 0) {
                    return json_encode(['status' => 'in_use']);
                }
            }
        }

        $delete = $this->db ? $this->db->query("DELETE FROM acquisition_type WHERE id = {$id}") : false;
        if ($delete) {
            $this->audit('acquisition_types', 'delete', 'acquisition_type', $id, $oldData, null);
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'error', 'error' => $this->db ? $this->db->error : '']);
    }

    // ================== EQUIPOS (COMPLETO) ==================
    private function optimize_image_inplace($path, $ext, $maxDim = 1600) {
        if (empty($path) || !file_exists($path)) return;
        if (!function_exists('getimagesize')) return;

        $ext = strtolower((string)$ext);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) return;

        $info = @getimagesize($path);
        if (!$info || empty($info[0]) || empty($info[1])) return;
        $w = (int)$info[0];
        $h = (int)$info[1];

        $src = null;
        if (($ext === 'jpg' || $ext === 'jpeg') && function_exists('imagecreatefromjpeg')) {
            $src = @imagecreatefromjpeg($path);
            if ($src && function_exists('exif_read_data')) {
                $exif = @exif_read_data($path);
                $orientation = (int)($exif['Orientation'] ?? 0);
                if ($orientation === 3 && function_exists('imagerotate')) $src = imagerotate($src, 180, 0);
                if ($orientation === 6 && function_exists('imagerotate')) $src = imagerotate($src, -90, 0);
                if ($orientation === 8 && function_exists('imagerotate')) $src = imagerotate($src, 90, 0);
            }
        } elseif ($ext === 'png' && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($path);
        } elseif ($ext === 'gif' && function_exists('imagecreatefromgif')) {
            $src = @imagecreatefromgif($path);
        } elseif ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($path);
        }
        if (!$src) return;

        $ratio = 1.0;
        if ($w > $maxDim || $h > $maxDim) {
            $ratio = min($maxDim / max(1, $w), $maxDim / max(1, $h));
        }
        $newW = max(1, (int)round($w * $ratio));
        $newH = max(1, (int)round($h * $ratio));

        $dst = $src;
        if ($ratio < 1.0 && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
            $dst = imagecreatetruecolor($newW, $newH);
            if ($ext === 'png' || $ext === 'gif' || $ext === 'webp') {
                if (function_exists('imagealphablending')) imagealphablending($dst, false);
                if (function_exists('imagesavealpha')) imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        }

        if ($ext === 'jpg' || $ext === 'jpeg') {
            @imagejpeg($dst, $path, 82);
        } elseif ($ext === 'png') {
            @imagepng($dst, $path, 6);
        } elseif ($ext === 'gif') {
            @imagegif($dst, $path);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            @imagewebp($dst, $path, 82);
        }

        // @phpstan-ignore-next-line - imagedestroy deprecated PHP 8+ but still functional
        if ($dst && $dst !== $src) @imagedestroy($dst);
        // @phpstan-ignore-next-line - imagedestroy deprecated PHP 8+ but still functional
        if ($src) @imagedestroy($src);
    }

    private function create_image_thumb($srcPath, $thumbJpgPath, $thumbWebpPath = null, $size = 96) {
        if (empty($srcPath) || !file_exists($srcPath)) return;
        if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) return;

        $info = @getimagesize($srcPath);
        if (!$info || empty($info[0]) || empty($info[1])) return;
        $w = (int)$info[0];
        $h = (int)$info[1];
        if ($w <= 0 || $h <= 0) return;

        $type = (int)($info[2] ?? 0);
        $src = null;
        if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) $src = @imagecreatefromjpeg($srcPath);
        if ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) $src = @imagecreatefrompng($srcPath);
        if ($type === IMAGETYPE_GIF && function_exists('imagecreatefromgif')) $src = @imagecreatefromgif($srcPath);
        if (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($srcPath);
        if (!$src) return;

        // cover crop
        $srcRatio = $w / max(1, $h);
        $dstRatio = 1.0;
        if ($srcRatio > $dstRatio) {
            $cropH = $h;
            $cropW = (int)round($h * $dstRatio);
            $srcX = (int)round(($w - $cropW) / 2);
            $srcY = 0;
        } else {
            $cropW = $w;
            $cropH = (int)round($w / $dstRatio);
            $srcX = 0;
            $srcY = (int)round(($h - $cropH) / 2);
        }

        $dst = imagecreatetruecolor((int)$size, (int)$size);
        // fondo blanco (evita fondos negros al convertir a jpg)
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, (int)$size, (int)$size, $white);

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, (int)$size, (int)$size, $cropW, $cropH);

        $thumbDir = dirname($thumbJpgPath);
        if (!is_dir($thumbDir)) @mkdir($thumbDir, 0777, true);
        @imagejpeg($dst, $thumbJpgPath, 75);

        if (!empty($thumbWebpPath) && function_exists('imagewebp')) {
            $thumbWebpDir = dirname($thumbWebpPath);
            if (!is_dir($thumbWebpDir)) @mkdir($thumbWebpDir, 0777, true);
            @imagewebp($dst, $thumbWebpPath, 75);
        }

        // @phpstan-ignore-next-line - imagedestroy deprecated PHP 8+ but still functional
        if ($dst) @imagedestroy($dst);
        // @phpstan-ignore-next-line - imagedestroy deprecated PHP 8+ but still functional
        if ($src) @imagedestroy($src);
    }

    private function delete_equipment_image_thumbs($imgPath) {
        if (empty($imgPath)) return;
        $base = pathinfo($imgPath, PATHINFO_FILENAME);
        if (empty($base)) return;

        $jpg = 'uploads/thumbs/' . $base . '.jpg';
        $webp = 'uploads/thumbs/' . $base . '.webp';
        if (file_exists($jpg)) @unlink($jpg);
        if (file_exists($webp)) @unlink($webp);
    }

    private function save_uploaded_image_optimized($file, $destDirRel, $baseName, $maxBytes = 5242880, $maxDim = 1600, $preferWebp = true) {
        // Retorna: ['ok'=>bool,'filename'=>string,'path'=>string,'error'=>string]
        $result = ['ok' => false, 'filename' => '', 'path' => '', 'error' => ''];

        if (empty($file) || !is_array($file)) {
            $result['error'] = 'Archivo inválido';
            return $result;
        }
        if (empty($file['tmp_name']) || (int)($file['error'] ?? 0) !== 0) {
            $result['error'] = 'Sin archivo';
            return $result;
        }

        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            $result['error'] = 'Upload no válido';
            return $result;
        }
        $size = (int)($file['size'] ?? 0);
        if ($maxBytes > 0 && $size > $maxBytes) {
            $result['error'] = 'Archivo demasiado grande';
            return $result;
        }

        $destDirRel = trim((string)$destDirRel, '/\\');
        $destDirFs = defined('ROOT') ? rtrim(ROOT, '/\\') . '/' . $destDirRel : $destDirRel;
        if (!is_dir($destDirFs)) {
            @mkdir($destDirFs, 0777, true);
            @chmod($destDirFs, 0777);
        }

        if (!function_exists('getimagesize')) {
            $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $result['error'] = 'Formato no permitido';
                return $result;
            }
            if ($ext === 'jpeg') $ext = 'jpg';
            $filename = $baseName . '.' . $ext;
            $target = rtrim($destDirFs, '/\\') . '/' . $filename;
            if (move_uploaded_file($tmp, $target)) {
                $result['ok'] = true;
                $result['filename'] = $filename;
                $result['path'] = $destDirRel . '/' . $filename;
                return $result;
            }
            $result['error'] = 'No se pudo guardar';
            return $result;
        }

        $info = @getimagesize($tmp);
        if (!$info || empty($info[2])) {
            $result['error'] = 'No es una imagen válida';
            return $result;
        }

        $type = (int)$info[2];
        $srcW = (int)($info[0] ?? 0);
        $srcH = (int)($info[1] ?? 0);

        $inExt = '';
        if ($type === IMAGETYPE_JPEG) $inExt = 'jpg';
        if ($type === IMAGETYPE_PNG) $inExt = 'png';
        if ($type === IMAGETYPE_GIF) $inExt = 'gif';
        if (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP) $inExt = 'webp';
        if ($inExt === '') {
            $result['error'] = 'Formato no soportado';
            return $result;
        }

        $canWebp = $preferWebp && function_exists('imagewebp') && $type !== IMAGETYPE_GIF;
        $outExt = $canWebp ? 'webp' : $inExt;

        $filename = $baseName . '.' . $outExt;
        $target = rtrim($destDirFs, '/\\') . '/' . $filename;

        // GIF: no re-encode para evitar perder animación
        if ($type === IMAGETYPE_GIF && $outExt === 'gif') {
            if (move_uploaded_file($tmp, $target)) {
                $result['ok'] = true;
                $result['filename'] = $filename;
                $result['path'] = $destDirRel . '/' . $filename;
                return $result;
            }
            $result['error'] = 'No se pudo guardar';
            return $result;
        }

        $src = null;
        if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
            $src = @imagecreatefromjpeg($tmp);
            if ($src && function_exists('exif_read_data')) {
                $exif = @exif_read_data($tmp);
                $orientation = (int)($exif['Orientation'] ?? 0);
                if ($orientation === 3 && function_exists('imagerotate')) $src = imagerotate($src, 180, 0);
                if ($orientation === 6 && function_exists('imagerotate')) $src = imagerotate($src, -90, 0);
                if ($orientation === 8 && function_exists('imagerotate')) $src = imagerotate($src, 90, 0);
            }
        } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($tmp);
        } elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($tmp);
        }

        if (!$src) {
            // Fallback: mover con extensión de entrada
            $fallbackName = $baseName . '.' . $inExt;
            $fallbackTarget = rtrim($destDirFs, '/\\') . '/' . $fallbackName;
            if (move_uploaded_file($tmp, $fallbackTarget)) {
                $result['ok'] = true;
                $result['filename'] = $fallbackName;
                $result['path'] = $destDirRel . '/' . $fallbackName;
                return $result;
            }
            $result['error'] = 'No se pudo procesar';
            return $result;
        }

        $ratio = 1.0;
        if ($maxDim > 0 && ($srcW > $maxDim || $srcH > $maxDim)) {
            $ratio = min($maxDim / max(1, $srcW), $maxDim / max(1, $srcH));
        }
        $newW = max(1, (int)round($srcW * $ratio));
        $newH = max(1, (int)round($srcH * $ratio));

        $dst = $src;
        if ($ratio < 1.0 && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
            $dst = imagecreatetruecolor($newW, $newH);
            // Preservar alpha para PNG/WebP
            if ($type === IMAGETYPE_PNG || (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP) || $outExt === 'webp') {
                if (function_exists('imagealphablending')) imagealphablending($dst, false);
                if (function_exists('imagesavealpha')) imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        }

        $saved = false;
        if ($outExt === 'webp' && function_exists('imagewebp')) {
            $saved = @imagewebp($dst, $target, 82);
        } elseif ($outExt === 'jpg' && function_exists('imagejpeg')) {
            $saved = @imagejpeg($dst, $target, 82);
        } elseif ($outExt === 'png' && function_exists('imagepng')) {
            $saved = @imagepng($dst, $target, 6);
        }

        // @phpstan-ignore-next-line
        if ($dst && $dst !== $src) @imagedestroy($dst);
        // @phpstan-ignore-next-line
        if ($src) @imagedestroy($src);

        if ($saved) {
            @chmod($target, 0644);
            $result['ok'] = true;
            $result['filename'] = $filename;
            $result['path'] = $destDirRel . '/' . $filename;
            return $result;
        }

        // Último fallback: mover con extensión de entrada
        $fallbackName = $baseName . '.' . $inExt;
        $fallbackTarget = rtrim($destDirFs, '/\\') . '/' . $fallbackName;
        if (move_uploaded_file($tmp, $fallbackTarget)) {
            $result['ok'] = true;
            $result['filename'] = $fallbackName;
            $result['path'] = $destDirRel . '/' . $fallbackName;
            return $result;
        }

        $result['error'] = 'No se pudo guardar';
        return $result;
    }

    function save_equipment() {
        try {
            $login_type = (int)($_SESSION['login_type'] ?? 0);
            $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

            $is_new_request = empty($_POST['id'] ?? '');

            // No-admin: forzar sucursal activa
            if ($login_type !== 1) {
                if ($active_bid <= 0) {
                    return 2;
                }
                $_POST['branch_id'] = $active_bid;
            } else {
                // Admin: si está en una sucursal específica y no viene branch_id, usarla
                if (!isset($_POST['branch_id']) && $active_bid > 0) {
                    $_POST['branch_id'] = $active_bid;
                }

                // Admin en "todas": impedir crear sin branch_id explícito
                if ($is_new_request && $active_bid === 0 && empty($_POST['branch_id'])) {
                    return 2;
                }
            }

            extract($_POST);
            $id = isset($id) ? (int)$id : 0;
            $new = empty($id);
            $oldEquipData = !$new ? $this->getOldRecord('equipments', $id) : null;

            $this->ensure_equipment_delivery_fk();
            $this->ensure_equipment_delivery_position_fk();

            // Asegurar columnas adicionales (sin romper entornos viejos)
            if ($this->table_exists_local('equipments')) {
                if (!$this->column_exists_local('equipments', 'inventario_anterior')) {
                    @$this->db->query("ALTER TABLE `equipments` ADD COLUMN `inventario_anterior` VARCHAR(255) NULL AFTER `number_inventory`");
                }
                if (!$this->column_exists_local('equipments', 'numero_parte')) {
                    @$this->db->query("ALTER TABLE `equipments` ADD COLUMN `numero_parte` VARCHAR(255) NULL AFTER `inventario_anterior`");
                }
                if (!$this->column_exists_local('equipments', 'equipment_category_id')) {
                    @$this->db->query("ALTER TABLE `equipments` ADD COLUMN `equipment_category_id` INT NULL AFTER `discipline`");
                }
            }

            // === EQUIPOS ===
            $array_cols_equipment = ['serie','amount','date_created','name','brand','model','acquisition_type','mandate_period_id','characteristics','discipline','supplier_id','number_inventory','branch_id','inventario_anterior','numero_parte','equipment_category_id'];
            $equipment_data = [];
            foreach ($array_cols_equipment as $field) {
                if (isset($_POST[$field])) {
                    $equipment_data[$field] = $_POST[$field];
                }
            }

            if ((empty($equipment_data['discipline']) || trim((string)$equipment_data['discipline']) === '') && !empty($equipment_data['equipment_category_id']) && $this->db) {
                $cid = (int)$equipment_data['equipment_category_id'];
                if ($cid > 0) {
                    $this->ensure_equipment_categories_schema();
                    $cq = $this->db->query("SELECT description FROM equipment_categories WHERE id = {$cid} LIMIT 1");
                    if ($cq && $cq->num_rows > 0) {
                        $equipment_data['discipline'] = $cq->fetch_assoc()['description'] ?? '';
                    }
                }
            }

            // Generación de inventario:
            // - NO confiar en el número enviado por el cliente (puede ser solo preview).
            // - Para nuevos: siempre asignar el número definitivo en el guardado.
            // - Para ediciones: regenerar solo si cambia sucursal/tipo/categoría o si viene vacío.
            $must_generate = false;
            if (!empty($equipment_data['branch_id'])) {
                if ($new) {
                    $must_generate = true;
                } else {
                    $must_generate = empty($equipment_data['number_inventory']);
                    // Si hay ID, comparar con el registro actual para detectar cambios de combinación
                    if (!$must_generate && $id > 0) {
                        $current = null;
                        if ($this->pdo) {
                            try {
                                $st = $this->pdo->prepare('SELECT branch_id, acquisition_type, equipment_category_id, number_inventory, inventario_anterior FROM equipments WHERE id = ? LIMIT 1');
                                $st->execute([(int)$id]);
                                $current = $st->fetch(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) {
                                $current = null;
                            }
                        } elseif ($this->db) {
                            $rq = $this->db->query('SELECT branch_id, acquisition_type, equipment_category_id, number_inventory, inventario_anterior FROM equipments WHERE id = ' . (int)$id . ' LIMIT 1');
                            if ($rq && $rq->num_rows > 0) $current = $rq->fetch_assoc();
                        }
                        if ($current) {
                            $old_branch = (int)($current['branch_id'] ?? 0);
                            $old_acq = (int)($current['acquisition_type'] ?? 0);
                            $old_cat = (int)($current['equipment_category_id'] ?? 0);
                            $new_branch = (int)($equipment_data['branch_id'] ?? 0);
                            $new_acq = (int)($equipment_data['acquisition_type'] ?? 0);
                            $new_cat = (int)($equipment_data['equipment_category_id'] ?? 0);
                            if ($old_branch !== $new_branch || $old_acq !== $new_acq || $old_cat !== $new_cat) {
                                $must_generate = true;
                                // Guardar inventario anterior si aún no está
                                if (empty($equipment_data['inventario_anterior']) && empty($current['inventario_anterior']) && !empty($current['number_inventory'])) {
                                    $equipment_data['inventario_anterior'] = (string)$current['number_inventory'];
                                }
                            }
                        }
                    }
                }
            }

            if ($must_generate) {
                $generated_number = $this->get_next_inventory_number(
                    $equipment_data['branch_id'],
                    $equipment_data['acquisition_type'] ?? null,
                    $equipment_data['equipment_category_id'] ?? null
                );
                if ($generated_number) {
                    $equipment_data['number_inventory'] = $generated_number;
                } else {
                    return 2; // Error generando número
                }
            }

            if (empty($equipment_data)) return 2;

            // Validar serie única
            $serie_val = trim((string)($equipment_data['serie'] ?? ''));
            if ($serie_val !== '') {
                try {
                    $check_sql = "SELECT id FROM equipments WHERE serie = ? AND serie != ''";
                    $check_params = [$serie_val];
                    if (!$new && $id > 0) {
                        $check_sql .= " AND id != ?";
                        $check_params[] = $id;
                    }
                    if ($this->pdo) {
                        $chk_stmt = $this->pdo->prepare($check_sql);
                        $chk_stmt->execute($check_params);
                        if ($chk_stmt->fetch(PDO::FETCH_ASSOC)) {
                            return json_encode(['status' => 'error', 'message' => 'El numero de serie ya existe en otro equipo']);
                        }
                    }
                } catch (Throwable $e) {
                    // No bloquear si falla la validación
                }
            }

            // Insert o Update equipments con PDO
            if ($this->pdo) {
                if ($new) {
                    $fields = implode(', ', array_keys($equipment_data));
                    $placeholders = implode(', ', array_fill(0, count($equipment_data), '?'));
                    $stmt = $this->pdo->prepare("INSERT INTO equipments ($fields) VALUES ($placeholders)");
                    $stmt->execute(array_values($equipment_data));
                    $id = $this->pdo->lastInsertId();
                } else {
                    $set_parts = array_map(fn($k) => "$k = ?", array_keys($equipment_data));
                    $where = " WHERE id = ?";
                    $whereParams = [$id];
                    if ($login_type !== 1 && $active_bid > 0) {
                        $where .= " AND branch_id = ?";
                        $whereParams[] = $active_bid;
                    }
                    $stmt = $this->pdo->prepare("UPDATE equipments SET " . implode(', ', $set_parts) . $where);
                    $values = array_merge(array_values($equipment_data), $whereParams);
                    $stmt->execute($values);
                }
            } else {
                // Fallback a mysqli
                $data = "";
                foreach ($equipment_data as $k => $v) {
                    $v = $this->db->real_escape_string($v);
                    $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
                }
                $save = $new
                    ? $this->db->query("INSERT INTO equipments SET $data")
                    : $this->db->query("UPDATE equipments SET $data WHERE id = $id" . ($login_type !== 1 && $active_bid > 0 ? " AND branch_id = {$active_bid}" : ""));
                if (!$save) return 2;
                $id = $new ? $this->db->insert_id : $id;
            }

            $_POST['equipment_id'] = $id;

            // === RECEPTION, DELIVERY, SAFEGUARD ===
            foreach ([
                'equipment_reception' => ['state','comments'],
                'equipment_delivery' => ['department_id','location_id','responsible_name','responsible_position','date_training'],
                'equipment_safeguard' => ['warranty_time','date_adquisition']
            ] as $table => $fields) {
                $this->save_or_update_pdo($table, $_POST, $fields, $id, $new);
            }

            // === DOCUMENTOS ===
            $doc_fields = ['invoice','bailment_file','contract_file','usermanual_file','fast_guide_file','datasheet_file','servicemanual_file'];
            $doc_data = [];
            foreach ($doc_fields as $field) {
                if (isset($_POST[$field])) {
                    $doc_data[$field] = $_POST[$field];
                }
            }
            
            // Procesar uploads de documentos
            $upload_base_dir = dirname(__DIR__) . '/uploads/';
            if (!is_dir($upload_base_dir)) mkdir($upload_base_dir, 0775, true);
            
            foreach ($_FILES as $k => $file) {
                if (!empty($file['tmp_name']) && in_array($k, $doc_fields)) {
                    $filename = time() . '_' . basename($file['name']);
                    $dest = $upload_base_dir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $doc_data[$k] = 'uploads/' . $filename;
                    }
                }
            }

            // Verificar si existe documento y actualizar
            if (!empty($doc_data)) {
                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("SELECT id FROM equipment_control_documents WHERE equipment_id = ?");
                    $stmt->execute([$id]);
                    $exists = $stmt->rowCount() > 0;
                } else {
                    $exists = $this->db->query("SELECT id FROM equipment_control_documents WHERE equipment_id=$id")->num_rows > 0;
                }
                $this->save_or_update_pdo('equipment_control_documents', $doc_data, array_keys($doc_data), $id, !$exists);
            }

            // === ELIMINAR DOCUMENTOS ===
            foreach ($doc_fields as $field) {
                if (!empty($_POST["delete_$field"]) && $_POST["delete_$field"] == '1') {
                    if ($this->pdo) {
                        $stmt = $this->pdo->prepare("SELECT $field FROM equipment_control_documents WHERE equipment_id = ?");
                        $stmt->execute([$id]);
                        $result = $stmt->fetch();
                        $old = $result[$field] ?? '';
                    } else {
                        $qry = $this->db->query("SELECT $field FROM equipment_control_documents WHERE equipment_id = $id");
                        $old = $qry->fetch_array()[$field] ?? '';
                    }
                    if ($old && file_exists($old)) unlink($old);
                    
                    if ($this->pdo) {
                        $stmt = $this->pdo->prepare("UPDATE equipment_control_documents SET $field = NULL WHERE equipment_id = ?");
                        $stmt->execute([$id]);
                    } else {
                        $this->db->query("UPDATE equipment_control_documents SET $field = NULL WHERE equipment_id = $id");
                    }
                }
            }

            // === IMAGEN ===
            $upload_base_dir = dirname(__DIR__) . '/uploads/';
            $thumbs_dir = $upload_base_dir . 'thumbs/';
            if (!is_dir($upload_base_dir)) mkdir($upload_base_dir, 0775, true);
            if (!is_dir($thumbs_dir)) mkdir($thumbs_dir, 0775, true);
            
            if (!empty($_FILES['equipment_image']['tmp_name'])) {
                $file = $_FILES['equipment_image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $valid_ext = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $valid_ext)) return 3;
                if ($file['size'] > 5*1024*1024) return 4;

                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("SELECT image FROM equipments WHERE id = ?");
                    $stmt->execute([$id]);
                    $result = $stmt->fetch();
                    $old_img = $result['image'] ?? '';
                } else {
                    $old_img = $this->db->query("SELECT image FROM equipments WHERE id = $id")->fetch_array()['image'] ?? '';
                }
                
                if ($old_img) {
                    $old_img_full = dirname(__DIR__) . '/' . ltrim($old_img, '/');
                    if (file_exists($old_img_full)) {
                        @unlink($old_img_full);
                    }
                    $this->delete_equipment_image_thumbs($old_img);
                }

                $filename = $id.'_'.time().'.'.$ext;
                $dest = $upload_base_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // Optimiza imagen original y genera miniaturas para listados
                    $this->optimize_image_inplace($dest, $ext, 1600);
                    $base = pathinfo($dest, PATHINFO_FILENAME);
                    $thumbJpg = $thumbs_dir . $base . '.jpg';
                    $thumbWebp = $thumbs_dir . $base . '.webp';
                    $this->create_image_thumb($dest, $thumbJpg, $thumbWebp, 96);

                    $relative_path = 'uploads/' . $filename;
                    if ($this->pdo) {
                        $stmt = $this->pdo->prepare("UPDATE equipments SET image = ? WHERE id = ?");
                        $stmt->execute([$relative_path, $id]);
                    } else {
                        $this->db->query("UPDATE equipments SET image='$relative_path' WHERE id=$id");
                    }
                }
            }

            if (!empty($_POST['delete_image']) && $_POST['delete_image']=='1') {
                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("SELECT image FROM equipments WHERE id = ?");
                    $stmt->execute([$id]);
                    $result = $stmt->fetch();
                    $old_img = $result['image'] ?? '';
                } else {
                    $old_img = $this->db->query("SELECT image FROM equipments WHERE id = $id")->fetch_array()['image'] ?? '';
                }
                if ($old_img) {
                    $old_img_full = dirname(__DIR__) . '/' . ltrim($old_img, '/');
                    if (file_exists($old_img_full)) unlink($old_img_full);
                    $this->delete_equipment_image_thumbs($old_img);
                }
                
                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("UPDATE equipments SET image = NULL WHERE id = ?");
                    $stmt->execute([$id]);
                } else {
                    $this->db->query("UPDATE equipments SET image=NULL WHERE id=$id");
                }
            }

            // === CONSUMO ELÉCTRICO ===
            if (!empty($voltage) && !empty($amperage)) {
                $voltage = floatval($voltage);
                $amperage = floatval($amperage);
                $frequency_hz = !empty($frequency_hz) ? floatval($frequency_hz) : 60.00;
                $power_w = round($voltage * $amperage, 2);
                $notes = $new ? 'Registro inicial' : 'Actualización';

                if ($this->pdo) {
                    $stmt = $this->pdo->prepare("SELECT id FROM equipment_power_specs WHERE equipment_id = ?");
                    $stmt->execute([$id]);
                    $exists = $stmt->rowCount() > 0;
                } else {
                    $exists = $this->db->query("SELECT id FROM equipment_power_specs WHERE equipment_id = $id")->num_rows > 0;
                }
                
                if ($exists) {
                    if ($this->pdo) {
                        $stmt = $this->pdo->prepare("UPDATE equipment_power_specs SET voltage=?, amperage=?, frequency_hz=?, power_w=?, notes=? WHERE equipment_id=?");
                        $stmt->execute([$voltage, $amperage, $frequency_hz, $power_w, $notes, $id]);
                    } else {
                        $this->db->query("UPDATE equipment_power_specs SET voltage=$voltage, amperage=$amperage, frequency_hz=$frequency_hz, power_w=$power_w, notes='$notes' WHERE equipment_id=$id");
                    }
                } else {
                    if ($this->pdo) {
                        $stmt = $this->pdo->prepare("INSERT INTO equipment_power_specs (equipment_id, voltage, amperage, frequency_hz, power_w, notes) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$id, $voltage, $amperage, $frequency_hz, $power_w, $notes]);
                    } else {
                        $this->db->query("INSERT INTO equipment_power_specs (equipment_id, voltage, amperage, frequency_hz, power_w, notes) VALUES ($id, $voltage, $amperage, $frequency_hz, $power_w, '$notes')");
                    }
                }
            }

            // === MANTENIMIENTO AUTOMÁTICO ===
            if (!empty($mandate_period_id)) {
                $update_maintenance = $new;
                if (!$new) {
                    if ($this->pdo) {
                        $stmt = $this->pdo->prepare("SELECT date_created, mandate_period_id FROM equipments WHERE id = ?");
                        $stmt->execute([$id]);
                        $old = $stmt->fetch();
                    } else {
                        $old = $this->db->query("SELECT date_created, mandate_period_id FROM equipments WHERE id = $id")->fetch_assoc();
                    }
                    if ($old['date_created'] != $date_created || $old['mandate_period_id'] != $mandate_period_id) {
                        $update_maintenance = true;
                    }
                }
                if ($update_maintenance) {
                    $this->generate_automatic_maintenance($id, $date_created, $mandate_period_id, false);
                }
            }

            $this->lastInsertId = (int)$id;
            $this->audit('equipment', $new ? 'create' : 'update', 'equipments', (int)$id, $oldEquipData, $equipment_data);
            return 1;
        } catch (Exception $e) {
            error_log("SAVE_EQUIPMENT ERROR: " . $e->getMessage());
            return 2;
        }
    }
    
    // Helper para save_or_update con PDO
    private function save_or_update_pdo($table, $data, $allowed, $equipment_id, $is_new) {
        try {
            $filtered_data = [];
            foreach ($allowed as $field) {
                if (isset($data[$field]) && !is_numeric($field)) {
                    $filtered_data[$field] = $data[$field];
                }
            }
            
            if (empty($filtered_data)) return true;
            
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("SELECT id FROM $table WHERE equipment_id = ?");
                $stmt->execute([$equipment_id]);
                $exists = $stmt->rowCount() > 0;
                
                if ($exists) {
                    $set_parts = array_map(fn($k) => "$k = ?", array_keys($filtered_data));
                    $stmt = $this->pdo->prepare("UPDATE $table SET " . implode(', ', $set_parts) . " WHERE equipment_id = ?");
                    $values = array_merge(array_values($filtered_data), [$equipment_id]);
                    $stmt->execute($values);
                } else {
                    $filtered_data['equipment_id'] = $equipment_id;
                    $fields = implode(', ', array_keys($filtered_data));
                    $placeholders = implode(', ', array_fill(0, count($filtered_data), '?'));
                    $stmt = $this->pdo->prepare("INSERT INTO $table ($fields) VALUES ($placeholders)");
                    $stmt->execute(array_values($filtered_data));
                }
                return true;
            } else {
                // Fallback a mysqli
                $data_str = "";
                foreach ($filtered_data as $k => $v) {
                    $v = $this->db->real_escape_string($v);
                    $data_str .= empty($data_str) ? " $k='$v' " : ", $k='$v' ";
                }
                
                $exists = $this->db->query("SELECT id FROM $table WHERE equipment_id = $equipment_id LIMIT 1")->num_rows > 0;
                $sql = $exists
                    ? "UPDATE $table SET $data_str WHERE equipment_id = $equipment_id"
                    : "INSERT INTO $table SET $data_str, equipment_id = $equipment_id";
                
                $result = $this->db->query($sql);
                if (!$result) {
                    error_log("ERROR en $table: " . $this->db->error . " | SQL: $sql");
                    return false;
                }
                return true;
            }
        } catch (Exception $e) {
            error_log("SAVE_OR_UPDATE_PDO ERROR: " . $e->getMessage());
            return false;
        }
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
        try {
            extract($_POST);
            if (empty($id) || !is_numeric($id)) return 2;
            
            $id = (int)$id;
            $oldData = $this->getOldRecord('equipments', $id);
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

            // Usar PDO si disponible
            if ($this->pdo) {
                foreach ($tables as $table) {
                    if ($table === 'mantenimientos') {
                        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE equipo_id = ?");
                    } else {
                        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE equipment_id = ?");
                    }
                    $stmt->execute([$id]);
                }
                
                $stmt = $this->pdo->prepare("DELETE FROM equipments WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) { $this->audit('equipment', 'delete', 'equipments', $id, $oldData, null); return 1; }
                return 2;
            } else {
                // Fallback a mysqli
                foreach ($tables as $table) {
                    if ($table === 'mantenimientos') {
                        $this->db->query("DELETE FROM $table WHERE equipo_id = $id");
                    } else {
                        $this->db->query("DELETE FROM $table WHERE equipment_id = $id");
                    }
                }

                $delete = $this->db->query("DELETE FROM equipments WHERE id = $id");
                if ($delete) { $this->audit('equipment', 'delete', 'equipments', $id, $oldData, null); }
                return $delete ? 1 : 2;
            }
        } catch (Exception $e) {
            error_log("DELETE_EQUIPMENT ERROR: " . $e->getMessage());
            return 2;
        }
    }

    function save_equipment_unsubscribe()
    {
        $equipmentId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($equipmentId <= 0) {
            return json_encode(['status' => 0, 'message' => 'Equipo inválido.']);
        }

        $equipmentExists = $this->db->query("SELECT id, branch_id FROM equipments WHERE id = {$equipmentId} LIMIT 1");
        if (!$equipmentExists || $equipmentExists->num_rows === 0) {
            return json_encode(['status' => 0, 'message' => 'No se encontró el equipo.']);
        }
        $equipRow = $equipmentExists->fetch_assoc();
        $equipBranchId = (int)($equipRow['branch_id'] ?? 0);

        $dateInput = $_POST['date'] ?? date('Y-m-d');
        $dateObj = DateTime::createFromFormat('Y-m-d', $dateInput) ?: DateTime::createFromFormat('Y-m-d H:i:s', $dateInput);
        $dateValue = $dateObj ? $dateObj->format('Y-m-d') : date('Y-m-d');

        $timeInput = $_POST['time'] ?? date('H:i');
        $timeValue = null;
        foreach (['H:i', 'H:i:s'] as $timeFormat) {
            $timeObj = DateTime::createFromFormat($timeFormat, $timeInput);
            if ($timeObj instanceof DateTime) {
                $timeValue = $timeObj->format('H:i:s');
                break;
            }
        }
        if (!$timeValue) {
            $timeValue = date('H:i:s');
        }

        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
        $opinion = isset($_POST['opinion']) ? (int)$_POST['opinion'] : null;
        $destination = isset($_POST['destination']) ? (int)$_POST['destination'] : null;
        $responsible = isset($_POST['responsible']) ? (int)$_POST['responsible'] : null;

        $rawReasons = isset($_POST['withdrawal_reason']) ? $_POST['withdrawal_reason'] : [];
        if (!is_array($rawReasons)) {
            $rawReasons = [];
        }
        $reasonIds = array_values(array_filter(array_map('intval', $rawReasons), function ($value) {
            return $value > 0;
        }));
        $withdrawalJson = $this->db->real_escape_string(json_encode($reasonIds, JSON_UNESCAPED_UNICODE));

        $now = date('Y-m-d H:i:s');
        $sessionFirst = $_SESSION['login_firstname'] ?? '';
        $sessionMiddle = $_SESSION['login_middlename'] ?? '';
        $sessionLast = $_SESSION['login_lastname'] ?? '';
        $sessionUsername = $_SESSION['login_username'] ?? '';
        $processedName = trim(implode(' ', array_filter([$sessionFirst, $sessionMiddle, $sessionLast])));
        if ($processedName === '') {
            $processedName = $sessionUsername;
        }
        $processedName = $processedName ?: 'No registrado';
        $processedBy = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : null;

        $setParts = [
            "`date` = '" . $this->db->real_escape_string($dateValue) . "'",
            "`time` = '" . $this->db->real_escape_string($timeValue) . "'",
            "`description` = '" . $this->db->real_escape_string($description) . "'",
            "`comments` = '" . $this->db->real_escape_string($comments) . "'",
            "`opinion` = " . ($opinion === null ? "NULL" : (int)$opinion),
            "`destination` = " . ($destination === null ? "NULL" : (int)$destination),
            "`responsible` = " . ($responsible === null ? "NULL" : (int)$responsible),
            "`withdrawal_reason` = '" . $withdrawalJson . "'",
            "`processed_by` = " . ($processedBy === null ? "NULL" : $processedBy),
            "`processed_by_name` = '" . $this->db->real_escape_string($processedName) . "'",
            "`updated_at` = '" . $this->db->real_escape_string($now) . "'"
        ];

        $existing = $this->db->query("SELECT id, folio FROM equipment_unsubscribe WHERE equipment_id = {$equipmentId} LIMIT 1");
        $folio = '';
        $unsubscribeId = null;
        if ($existing && $existing->num_rows > 0) {
            $row = $existing->fetch_assoc();
            $unsubscribeId = (int)$row['id'];
            $folio = $row['folio'] ?? '';
            $sql = "UPDATE equipment_unsubscribe SET " . implode(', ', $setParts) . " WHERE equipment_id = {$equipmentId}";
            $save = $this->db->query($sql);
        } else {
            $insertParts = array_merge($setParts, [
                "`equipment_id` = {$equipmentId}",
                "`created_at` = '" . $this->db->real_escape_string($now) . "'"
            ]);
            $sql = "INSERT INTO equipment_unsubscribe SET " . implode(', ', $insertParts);
            $save = $this->db->query($sql);
            if ($save) {
                $unsubscribeId = $this->db->insert_id;
            }
        }

        if (!$save) {
            error_log('Error al guardar baja de equipo: ' . $this->db->error);
            return json_encode(['status' => 0, 'message' => 'No se pudo guardar la baja.']);
        }

        if ($unsubscribeId && empty($folio)) {
            $this->audit('equipment', ($existing && $existing->num_rows > 0) ? 'update' : 'create', 'equipment_unsubscribe', $unsubscribeId, null, ['equipment_id' => $equipmentId, 'date' => $dateValue, 'description' => $description]);
            // Generar folio con consecutivo mensual desde company_config
            $helperPath = realpath(__DIR__ . '/../app/helpers/company_config_helper.php');
            if ($helperPath && file_exists($helperPath)) {
                require_once $helperPath;
                $folio = generate_sequential_folio($this->db, $equipBranchId, 'unsubscribe');
            } else {
                $folio = sprintf('BAJA-%s-%02d-%03d', date('Y'), date('m'), $unsubscribeId);
            }
            $folioEscaped = $this->db->real_escape_string($folio);
            $this->db->query("UPDATE equipment_unsubscribe SET folio = '{$folioEscaped}' WHERE id = {$unsubscribeId}");
        }

        return json_encode([
            'status' => 1,
            'unsubscribe_id' => $unsubscribeId,
            'folio' => $folio,
            'processed_by_name' => $processedName,
            'equipment_id' => $equipmentId
        ]);
    }

    function save_equipment_revision()
    {
        extract($_POST);
        $data = $this->build_data($_POST, ["equipment_id", "date_revision", "frecuencia"]);

        if (empty($id)) return 2;
        if ($this->db->query("SELECT id FROM equipments WHERE id = $id")->num_rows == 0) return 2;

        $save = $this->db->query("INSERT INTO equipment_revision SET $data");
        if ($save) { $this->audit('equipment', 'create', 'equipment_revision', $this->db->insert_id, null, $_POST); }
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

    private function ensure_equipment_delivery_fk() {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $dbNameRes = $this->db->query("SELECT DATABASE() AS db_name");
            if (!$dbNameRes || $dbNameRes->num_rows === 0) {
                return;
            }
            $dbName = $dbNameRes->fetch_assoc()['db_name'] ?? '';
            if (empty($dbName)) {
                return;
            }
            $dbNameEsc = $this->db->real_escape_string($dbName);

            $kcuSql = "
                SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = '$dbNameEsc'
                  AND TABLE_NAME = 'equipment_delivery'
                  AND COLUMN_NAME = 'department_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ";
            $kcuRes = $this->db->query($kcuSql);
            if (!$kcuRes) {
                error_log('ensure_equipment_delivery_fk: KEY_COLUMN_USAGE query failed: ' . $this->db->error);
                return;
            }

            $constraintName = null;
            $referencedTable = null;
            if ($kcuRes->num_rows > 0) {
                $row = $kcuRes->fetch_assoc();
                $constraintName = $row['CONSTRAINT_NAME'] ?? null;
                $referencedTable = $row['REFERENCED_TABLE_NAME'] ?? null;
                if ($referencedTable === 'departments') {
                    return;
                }
            }

            $legacyTableExists = false;
            $legacyCheck = $this->db->query("SHOW TABLES LIKE 'equipment_deparments'");
            if ($legacyCheck && $legacyCheck->num_rows > 0) {
                $legacyTableExists = true;
            }

            $missingSql = "
                SELECT DISTINCT ed.department_id
                FROM equipment_delivery ed
                LEFT JOIN departments d ON d.id = ed.department_id
                WHERE ed.department_id IS NOT NULL
                  AND ed.department_id <> 0
                  AND d.id IS NULL
            ";
            $missingRes = $this->db->query($missingSql);
            if ($missingRes) {
                while ($miss = $missingRes->fetch_assoc()) {
                    $legacyId = (int)($miss['department_id'] ?? 0);
                    if ($legacyId <= 0) {
                        continue;
                    }

                    $newId = null;
                    if ($legacyTableExists) {
                        $legacyNameRes = $this->db->query("SELECT name FROM equipment_deparments WHERE id = $legacyId LIMIT 1");
                        if ($legacyNameRes && $legacyNameRes->num_rows > 0) {
                            $legacyName = trim($legacyNameRes->fetch_assoc()['name'] ?? '');
                            if ($legacyName !== '') {
                                $legacyNameEsc = $this->db->real_escape_string($legacyName);
                                $matchRes = $this->db->query("SELECT id FROM departments WHERE LOWER(TRIM(name)) = LOWER('$legacyNameEsc') LIMIT 1");
                                if ($matchRes && $matchRes->num_rows > 0) {
                                    $newId = (int)$matchRes->fetch_assoc()['id'];
                                }
                            }
                        }
                    }

                    if ($newId !== null) {
                        if (!$this->db->query("UPDATE equipment_delivery SET department_id = $newId WHERE department_id = $legacyId")) {
                            error_log('ensure_equipment_delivery_fk: failed to remap department_id ' . $legacyId . ': ' . $this->db->error);
                        }
                    } else {
                        if (!$this->db->query("UPDATE equipment_delivery SET department_id = NULL WHERE department_id = $legacyId")) {
                            error_log('ensure_equipment_delivery_fk: failed to nullify department_id ' . $legacyId . ': ' . $this->db->error);
                        }
                    }
                }
            }

            if ($constraintName) {
                try {
                    $this->db->query("ALTER TABLE equipment_delivery DROP FOREIGN KEY `$constraintName`");
                } catch (\mysqli_sql_exception $e) {
                    error_log('ensure_equipment_delivery_fk: drop constraint failed: ' . $e->getMessage());
                    return;
                }
            }

            try {
                $this->db->query("ALTER TABLE equipment_delivery ADD INDEX idx_equipment_delivery_department (department_id)");
            } catch (\mysqli_sql_exception $e) {
                if ($e->getCode() !== 1061) {
                    error_log('ensure_equipment_delivery_fk: add index failed: ' . $e->getMessage());
                }
            }

            try {
                $this->db->query("ALTER TABLE equipment_delivery ADD CONSTRAINT `equipment_delivery_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
            } catch (\mysqli_sql_exception $e) {
                error_log('ensure_equipment_delivery_fk: add constraint failed: ' . $e->getMessage());
            }
        } catch (\mysqli_sql_exception $e) {
            error_log('ensure_equipment_delivery_fk: unexpected error: ' . $e->getMessage());
        }
    }

    private function ensure_equipment_delivery_position_fk() {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        try {
            $dbNameRes = $this->db->query("SELECT DATABASE() AS db_name");
            if (!$dbNameRes || $dbNameRes->num_rows === 0) {
                return;
            }
            $dbName = $dbNameRes->fetch_assoc()['db_name'] ?? '';
            if (empty($dbName)) {
                return;
            }
            $dbNameEsc = $this->db->real_escape_string($dbName);

            $kcuSql = "
                SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = '$dbNameEsc'
                  AND TABLE_NAME = 'equipment_delivery'
                  AND COLUMN_NAME = 'responsible_position'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            ";
            $kcuRes = $this->db->query($kcuSql);
            if (!$kcuRes) {
                error_log('ensure_equipment_delivery_position_fk: KEY_COLUMN_USAGE query failed: ' . $this->db->error);
                return;
            }

            $constraintName = null;
            $referencedTable = null;
            if ($kcuRes->num_rows > 0) {
                $row = $kcuRes->fetch_assoc();
                $constraintName = $row['CONSTRAINT_NAME'] ?? null;
                $referencedTable = $row['REFERENCED_TABLE_NAME'] ?? null;
                if ($referencedTable === 'job_positions') {
                    return;
                }
            }

            $legacyTableExists = false;
            $legacyCheck = $this->db->query("SHOW TABLES LIKE 'responsible_positions'");
            if ($legacyCheck && $legacyCheck->num_rows > 0) {
                $legacyTableExists = true;
            }

            $missingSql = "
                SELECT DISTINCT ed.responsible_position
                FROM equipment_delivery ed
                LEFT JOIN job_positions jp ON jp.id = ed.responsible_position
                WHERE ed.responsible_position IS NOT NULL
                  AND ed.responsible_position <> 0
                  AND jp.id IS NULL
            ";
            $missingRes = $this->db->query($missingSql);
            if ($missingRes) {
                while ($miss = $missingRes->fetch_assoc()) {
                    $legacyId = (int)($miss['responsible_position'] ?? 0);
                    if ($legacyId <= 0) {
                        continue;
                    }

                    $newId = null;
                    if ($legacyTableExists) {
                        $legacyNameRes = $this->db->query("SELECT name FROM responsible_positions WHERE id = $legacyId LIMIT 1");
                        if ($legacyNameRes && $legacyNameRes->num_rows > 0) {
                            $legacyName = trim($legacyNameRes->fetch_assoc()['name'] ?? '');
                            if ($legacyName !== '') {
                                $legacyNameEsc = $this->db->real_escape_string($legacyName);
                                $matchRes = $this->db->query("SELECT id FROM job_positions WHERE LOWER(TRIM(name)) = LOWER('$legacyNameEsc') LIMIT 1");
                                if ($matchRes && $matchRes->num_rows > 0) {
                                    $newId = (int)$matchRes->fetch_assoc()['id'];
                                }
                            }
                        }
                    }

                    if ($newId !== null) {
                        if (!$this->db->query("UPDATE equipment_delivery SET responsible_position = $newId WHERE responsible_position = $legacyId")) {
                            error_log('ensure_equipment_delivery_position_fk: failed to remap responsible_position ' . $legacyId . ': ' . $this->db->error);
                        }
                    } else {
                        if (!$this->db->query("UPDATE equipment_delivery SET responsible_position = NULL WHERE responsible_position = $legacyId")) {
                            error_log('ensure_equipment_delivery_position_fk: failed to nullify responsible_position ' . $legacyId . ': ' . $this->db->error);
                        }
                    }
                }
            }

            if ($constraintName) {
                try {
                    $this->db->query("ALTER TABLE equipment_delivery DROP FOREIGN KEY `$constraintName`");
                } catch (\mysqli_sql_exception $e) {
                    error_log('ensure_equipment_delivery_position_fk: drop constraint failed: ' . $e->getMessage());
                    return;
                }
            }

            try {
                $this->db->query("ALTER TABLE equipment_delivery ADD INDEX idx_equipment_delivery_position (responsible_position)");
            } catch (\mysqli_sql_exception $e) {
                if ($e->getCode() !== 1061) {
                    error_log('ensure_equipment_delivery_position_fk: add index failed: ' . $e->getMessage());
                }
            }

            try {
                $this->db->query("ALTER TABLE equipment_delivery ADD CONSTRAINT `equipment_delivery_position_fk` FOREIGN KEY (`responsible_position`) REFERENCES `job_positions`(`id`) ON UPDATE CASCADE ON DELETE SET NULL");
            } catch (\mysqli_sql_exception $e) {
                error_log('ensure_equipment_delivery_position_fk: add constraint failed: ' . $e->getMessage());
            }
        } catch (\mysqli_sql_exception $e) {
            error_log('ensure_equipment_delivery_position_fk: unexpected error: ' . $e->getMessage());
        }
    }

    private function generate_automatic_maintenance($equipment_id, $start_date, $period_id, $is_new = true) {
        $period_id = (int)$period_id;
        $qry = $this->db->query("SELECT days_interval FROM maintenance_periods WHERE id = $period_id");
        if (!$qry || $qry->num_rows == 0) {
            return false;
        }

        if (!$is_new) {
            $this->db->query("DELETE FROM mantenimientos WHERE equipo_id = $equipment_id AND descripcion = 'Mantenimiento automático'");
        }

        $start = DateTime::createFromFormat('Y-m-d', $start_date) ?: DateTime::createFromFormat('Y-m-d H:i:s', $start_date);
        if (!$start) {
            $start = new DateTime();
        }
        $start->setTime(0, 0, 0);
        $end = (clone $start)->modify('+36 months');

        $this->ensure_maintenance_schedule($start, $end, (int)$equipment_id);
        return true;
    }

    private function ensure_maintenance_schedule(DateTime $start, DateTime $end, $equipmentId = null) {
        $periods = [];
        $periodRes = $this->db->query("SELECT id, days_interval FROM maintenance_periods");
        if (!$periodRes) {
            return;
        }
        while ($row = $periodRes->fetch_assoc()) {
            $periods[(int)$row['id']] = (int)$row['days_interval'];
        }
        if (empty($periods)) {
            return;
        }

        $statusColumn = $this->detect_equipment_status_column();
        $statusSelect = $statusColumn ? "e.`$statusColumn` AS status_value" : "NULL AS status_value";

        $where = "WHERE e.mandate_period_id IS NOT NULL";
        if ($equipmentId !== null) {
            $where .= " AND e.id = " . (int)$equipmentId;
        }

        $sql = "SELECT e.id, e.mandate_period_id, e.date_created, $statusSelect, u.date AS unsubscribe_date
                FROM equipments e
                LEFT JOIN equipment_unsubscribe u ON u.equipment_id = e.id
                $where";

        $equipments = $this->db->query($sql);
        if (!$equipments) {
            return;
        }

        $startStr = $start->format('Y-m-d');
        $endStr = $end->format('Y-m-d');

        while ($eq = $equipments->fetch_assoc()) {
            $periodId = (int)($eq['mandate_period_id'] ?? 0);
            $intervalDays = $periods[$periodId] ?? 0;
            if ($intervalDays <= 0) {
                continue;
            }

            $statusValue = $eq['status_value'];
            if ($statusValue !== null && strtoupper(trim($statusValue)) !== 'ACTIVO') {
                $this->db->query("DELETE FROM mantenimientos WHERE equipo_id = {$eq['id']} AND descripcion = 'Mantenimiento automático' AND fecha_programada >= '$startStr'");
                continue;
            }

            if (empty($eq['date_created'])) {
                continue;
            }

            $dateCreated = DateTime::createFromFormat('Y-m-d', $eq['date_created']) ?: DateTime::createFromFormat('Y-m-d H:i:s', $eq['date_created']);
            if (!$dateCreated) {
                continue;
            }
            $dateCreated->setTime(0, 0, 0);

            $unsubscribeDate = null;
            if (!empty($eq['unsubscribe_date'])) {
                $unsubscribeDate = DateTime::createFromFormat('Y-m-d', $eq['unsubscribe_date']) ?: DateTime::createFromFormat('Y-m-d H:i:s', $eq['unsubscribe_date']);
                if ($unsubscribeDate) {
                    $unsubscribeDate->setTime(0, 0, 0);
                    $cutoff = $unsubscribeDate->format('Y-m-d');
                    $this->db->query("DELETE FROM mantenimientos WHERE equipo_id = {$eq['id']} AND fecha_programada >= '$cutoff'");
                    if ($unsubscribeDate <= $start) {
                        continue;
                    }
                }
            }

            $limitDate = clone $end;
            if ($unsubscribeDate && $unsubscribeDate < $limitDate) {
                $limitDate = (clone $unsubscribeDate)->modify('-1 day');
            }

            if ($limitDate < $start) {
                continue;
            }

            $limitStr = $limitDate->format('Y-m-d');

            $lastRow = $this->db->query("SELECT MAX(fecha_programada) AS last_date FROM mantenimientos WHERE equipo_id = {$eq['id']}");
            $lastDate = $lastRow && $lastRow->num_rows ? $lastRow->fetch_assoc()['last_date'] : null;
            $cursor = $lastDate ? DateTime::createFromFormat('Y-m-d', $lastDate) : clone $dateCreated;
            if (!$cursor) {
                continue;
            }
            $cursor->setTime(0, 0, 0);

            while (true) {
                $cursor->modify("+{$intervalDays} days");
                $candidateStr = $cursor->format('Y-m-d');

                if ($candidateStr > $limitStr) {
                    break;
                }

                $exists = $this->db->query("SELECT 1 FROM mantenimientos WHERE equipo_id = {$eq['id']} AND fecha_programada = '$candidateStr' LIMIT 1");
                if ($exists && $exists->num_rows > 0) {
                    continue;
                }

                // Validar límite de eventos por día antes de insertar
                if (!$this->can_add_maintenance_on_date($candidateStr)) {
                    continue; // Saltar esta fecha si ya tiene demasiados eventos
                }

                $this->db->query("INSERT INTO mantenimientos (equipo_id, fecha_programada, hora_programada, tipo_mantenimiento, descripcion, estatus, created_at) VALUES ({$eq['id']}, '$candidateStr', NULL, 'Preventivo', 'Mantenimiento automático', 'pendiente', NOW())");
            }
        }
    }

    /**
     * Valida si se puede agregar un mantenimiento en la fecha especificada
     * Límite configurable para evitar sobrecarga del calendario
     */
    private function can_add_maintenance_on_date($fecha) {
        // Cargar límite desde configuración
        $config_file = __DIR__ . '/../config/maintenance_limits.php';
        $config = file_exists($config_file) ? include($config_file) : [];
        $max_events_per_day = $config['max_events_per_day'] ?? 20;
        
        $count_query = $this->db->query("SELECT COUNT(*) as total FROM mantenimientos WHERE fecha_programada = '" . $this->db->real_escape_string($fecha) . "'");
        
        if ($count_query && $row = $count_query->fetch_assoc()) {
            return $row['total'] < $max_events_per_day;
        }
        
        return true; // Si hay error, permitir (fail-safe)
    }

    private function detect_equipment_status_column() {
        static $statusColumn = false;
        if ($statusColumn !== false) {
            return $statusColumn ?: null;
        }

        foreach (['status', 'estatus', 'estado', 'state'] as $candidate) {
            $res = $this->db->query("SHOW COLUMNS FROM equipments LIKE '$candidate'");
            if ($res && $res->num_rows > 0) {
                $statusColumn = $candidate;
                return $statusColumn;
            }
        }

        $statusColumn = null;
        return null;
    }

    private function createDateFromParam($value) {
        if (!$value) {
            return null;
        }
        $value = substr($value, 0, 10);
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if ($date) {
            $date->setTime(0, 0, 0);
        }
        return $date ?: null;
    }

    // ================== HERRAMIENTAS ==================
    function save_tool() {
        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

        $is_new_request = empty($_POST['id'] ?? '');

        if ($login_type !== 1) {
            if ($active_bid <= 0) return 0;
            $_POST['branch_id'] = $active_bid;
        } else {
            if (!isset($_POST['branch_id']) && $active_bid > 0) {
                $_POST['branch_id'] = $active_bid;
            }

            if ($is_new_request && $active_bid === 0 && empty($_POST['branch_id'])) return 0;
        }

        extract($_POST);
        $data = "";
        $allowed = ['nombre','marca','costo','supplier_id','estatus','fecha_adquisicion','fecha_baja','caracteristicas','branch_id'];
        foreach ($allowed as $k) {
            if (isset($_POST[$k])) {
                $data .= empty($data) ? " `$k` = '".addslashes($_POST[$k])."' " : ", `$k` = '".addslashes($_POST[$k])."' ";
            }
        }

        if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != '') {
            // Eliminar imagen anterior si se está reemplazando
            if (!empty($id)) {
                $old = $this->db->query("SELECT imagen FROM tools WHERE id = " . (int)$id)->fetch_assoc();
                $oldImg = $old['imagen'] ?? '';
                if (!empty($oldImg) && file_exists('uploads/' . $oldImg)) {
                    @unlink('uploads/' . $oldImg);
                }
            }

            $rand = function_exists('random_bytes') ? bin2hex(random_bytes(3)) : substr(md5(uniqid('', true)), 0, 6);
            $base = 'tool_' . time() . '_' . $rand;
            $saved = $this->save_uploaded_image_optimized($_FILES['imagen'], 'uploads', $base, 5 * 1024 * 1024, 1600, true);
            if (empty($saved['ok'])) return 0;
            $fname = $saved['filename'];
            $data .= empty($data) ? " `imagen` = '$fname' " : ", `imagen` = '$fname' ";
        }

        if (empty($id)) {
            $sql = "INSERT INTO tools SET $data";
        } else {
            $id = (int)$id;
            $sql = "UPDATE tools SET $data WHERE id = $id";
            if ($login_type !== 1 && $active_bid > 0) {
                $sql .= " AND branch_id = {$active_bid}";
            }
        }

        $oldToolData = !empty($_POST['id'] ?? '') ? $this->getOldRecord('tools', (int)$_POST['id']) : null;
        $result = $this->db->query($sql);
        if ($result) {
            $toolId = empty($id) ? $this->db->insert_id : (int)$id;
            $this->lastInsertId = $toolId;
            $this->audit('tools', empty($id) ? 'create' : 'update', 'tools', $toolId, $oldToolData, $_POST);
        }
        return $result ? 1 : 0;
    }

    function delete_tool() {
        extract($_POST);
        $id = (int)$id;
        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
        $oldData = $this->getOldRecord('tools', $id);

        $sel = "SELECT imagen FROM tools WHERE id = $id";
        if ($login_type !== 1 && $active_bid > 0) {
            $sel .= " AND branch_id = {$active_bid}";
        }
        $qry = $this->db->query($sel);
        if (!$qry || $qry->num_rows === 0) {
            return 0;
        }
        $img = $qry->fetch_assoc()['imagen'];
        if (!empty($img) && file_exists('uploads/' . $img)) {
            unlink('uploads/' . $img);
        }
        $sql = "DELETE FROM tools WHERE id = $id";
        if ($login_type !== 1 && $active_bid > 0) {
            $sql .= " AND branch_id = {$active_bid}";
        }
        $result = $this->db->query($sql);
        if ($result) { $this->audit('tools', 'delete', 'tools', $id, $oldData, null); }
        return $result ? 1 : 0;
    }

    // ================== ACCESORIOS ==================
    function save_accessory() {
        if (APP_DEBUG) {
            error_log('=== INSIDE save_accessory ===');
        }
        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
        if (APP_DEBUG) {
            error_log("login_type: $login_type, active_bid: $active_bid");
        }

        $is_new_request = empty($_POST['id'] ?? '');
        if (APP_DEBUG) {
            error_log("is_new_request: " . ($is_new_request ? 'YES' : 'NO'));
        }

        if ($login_type !== 1) {
            if ($active_bid <= 0) {
                error_log('ERROR: Non-admin user with active_bid <= 0');
                return 0;
            }
            $_POST['branch_id'] = $active_bid;
        } else {
            if (!isset($_POST['branch_id']) && $active_bid > 0) {
                $_POST['branch_id'] = $active_bid;
            }

            if ($is_new_request && $active_bid === 0 && empty($_POST['branch_id'])) return 0;
        }

        // El número de inventario en accesorios lo asigna el sistema (no editable desde el cliente)
        if (isset($_POST['inventory_number'])) {
            unset($_POST['inventory_number']);
        }

        extract($_POST);
        $data = "";
        $allowed = ['name','type','brand','model','serial','cost','acquisition_date','acquisition_type_id','area_id','status','observations','branch_id','numero_parte'];
        foreach ($allowed as $k) {
            if (isset($_POST[$k])) {
                $data .= empty($data) ? " `$k` = '".addslashes($_POST[$k])."' " : ", `$k` = '".addslashes($_POST[$k])."' ";
            }
        }

        // Para accesorios, herramientas e insumos usamos el esquema simple (PREFIX-001)
        // Siempre autogenerado al crear un nuevo registro.
        if ($is_new_request && !empty($_POST['branch_id'])) {
            $generated_number = $this->get_next_inventory_number($_POST['branch_id'], null, null);
            if ($generated_number) {
                $data .= ", `inventory_number` = '$generated_number' ";
            } else {
                error_log('ERROR save_accessory: No se pudo generar inventory_number para branch_id=' . $_POST['branch_id']);
                return 0; // Error generando número
            }
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
            // Eliminar imagen anterior si se está reemplazando
            if (!empty($id)) {
                $qryOld = $this->db->query("SELECT image FROM accessories WHERE id = " . (int)$id);
                if ($qryOld && $qryOld->num_rows > 0) {
                    $oldImg = $qryOld->fetch_assoc()['image'] ?? '';
                    if (!empty($oldImg) && file_exists('uploads/' . $oldImg)) {
                        @unlink('uploads/' . $oldImg);
                    }
                }
            }

            $rand = function_exists('random_bytes') ? bin2hex(random_bytes(3)) : substr(md5(uniqid('', true)), 0, 6);
            $base = 'acc_' . time() . '_' . $rand;
            $saved = $this->save_uploaded_image_optimized($_FILES['imagen'], 'uploads', $base, 5 * 1024 * 1024, 1600, true);
            if (empty($saved['ok'])) return 0;
            $fname = $saved['filename'];
            $data .= ", image = '$fname' ";
        }

        if (empty($id)) {
            $sql = "INSERT INTO accessories SET $data";
        } else {
            $id = (int)$id;
            $sql = "UPDATE accessories SET $data WHERE id = $id";
            if ($login_type !== 1 && $active_bid > 0) {
                $sql .= " AND branch_id = {$active_bid}";
            }
        }

        $oldAccData = !empty($_POST['id'] ?? '') ? $this->getOldRecord('accessories', (int)$_POST['id']) : null;
        $result = $this->db->query($sql);
        if ($result) {
            $accId = empty($id) ? $this->db->insert_id : (int)$id;
            $this->lastInsertId = $accId;
            $this->audit('accessories', empty($id) ? 'create' : 'update', 'accessories', $accId, $oldAccData, $_POST);
        }
        return $result ? 1 : 0;
    }

    function delete_accessory() {
        extract($_POST);
        $id = (int)$id;
        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
        $oldData = $this->getOldRecord('accessories', $id);

        $sel = "SELECT image FROM accessories WHERE id = $id";
        if ($login_type !== 1 && $active_bid > 0) {
            $sel .= " AND branch_id = {$active_bid}";
        }
        $qry = $this->db->query($sel);
        if (!$qry || $qry->num_rows === 0) {
            return 0;
        }
        $img = $qry->fetch_assoc()['image'];
        if (!empty($img) && file_exists('uploads/' . $img)) unlink('uploads/' . $img);
        $sql = "DELETE FROM accessories WHERE id = $id";
        if ($login_type !== 1 && $active_bid > 0) {
            $sql .= " AND branch_id = {$active_bid}";
        }
        $result = $this->db->query($sql);
        if ($result) { $this->audit('accessories', 'delete', 'accessories', $id, $oldData, null); }
        return $result ? 1 : 0;
    }

    // ================== INVENTARIO ==================
    function save_inventory() {
        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

        $is_new_request = empty($_POST['id'] ?? '');

        if ($login_type !== 1) {
            if ($active_bid <= 0) return 0;
            $_POST['branch_id'] = $active_bid;
        } else {
            if (!isset($_POST['branch_id']) && $active_bid > 0) {
                $_POST['branch_id'] = $active_bid;
            }

            if ($is_new_request && $active_bid === 0 && empty($_POST['branch_id'])) return 0;
        }

        extract($_POST);
        $data = "";
        $allowed = ['name', 'category', 'price', 'cost', 'stock', 'min_stock', 'max_stock', 'status', 'branch_id', 'is_hazardous', 'hazard_class'];
        
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $value = $this->db->real_escape_string($_POST[$field]);
                $data .= empty($data) ? " `$field` = '$value' " : ", `$field` = '$value' ";
            }
        }
        // Si is_hazardous no viene (checkbox desmarcado), forzar a 0
        if (!isset($_POST['is_hazardous'])) {
            $data .= empty($data) ? " `is_hazardous` = '0' " : ", `is_hazardous` = '0' ";
        }

        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == 0 && !empty($_FILES['image_path']['tmp_name'])) {
            // Eliminar imagen anterior si se está reemplazando
            if (!empty($id)) {
                $qryOld = $this->db->query("SELECT image_path FROM inventory WHERE id = " . (int)$id);
                if ($qryOld && $qryOld->num_rows > 0) {
                    $oldImg = $qryOld->fetch_assoc()['image_path'] ?? '';
                    if (!empty($oldImg) && file_exists('uploads/' . $oldImg)) {
                        @unlink('uploads/' . $oldImg);
                    }
                }
            }

            $rand = function_exists('random_bytes') ? bin2hex(random_bytes(3)) : substr(md5(uniqid('', true)), 0, 6);
            $base = 'inv_' . time() . '_' . $rand;
            $saved = $this->save_uploaded_image_optimized($_FILES['image_path'], 'uploads', $base, 5 * 1024 * 1024, 1600, true);
            if (!empty($saved['ok'])) {
                $filename = $saved['filename'];
                $data .= ", `image_path` = '$filename' ";
            }
        }

        // Hoja de seguridad (PDF/imagen)
        if (isset($_FILES['safety_data_sheet']) && $_FILES['safety_data_sheet']['error'] == 0 && !empty($_FILES['safety_data_sheet']['tmp_name'])) {
            $sds     = $_FILES['safety_data_sheet'];
            $allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $sdsReal = $finfo->file($sds['tmp_name']);
            if (in_array($sdsReal, $allowedMime) && $sds['size'] <= 10 * 1024 * 1024) {
                $ext     = strtolower(pathinfo($sds['name'], PATHINFO_EXTENSION));
                $rand2   = function_exists('random_bytes') ? bin2hex(random_bytes(3)) : substr(md5(uniqid('', true)), 0, 6);
                $sdsDir  = 'uploads/inventory_sds/';
                if (!is_dir($sdsDir)) @mkdir($sdsDir, 0755, true);
                $sdsFile = 'sds_' . time() . '_' . $rand2 . '.' . $ext;
                if (move_uploaded_file($sds['tmp_name'], $sdsDir . $sdsFile)) {
                    $sdsEsc  = $this->db->real_escape_string($sdsDir . $sdsFile);
                    $data .= ", `safety_data_sheet` = '$sdsEsc' ";
                }
            }
        }

        if (empty($id)) {
            $sql = "INSERT INTO inventory SET $data";
        } else {
            $id = (int)$id;
            $sql = "UPDATE inventory SET $data WHERE id = $id";
            if ($login_type !== 1 && $active_bid > 0) {
                $sql .= " AND branch_id = {$active_bid}";
            }
        }

        $oldInvData = !$is_new_request ? $this->getOldRecord('inventory', (int)($id ?? 0)) : null;
        $result = $this->db->query($sql);
        if ($result) {
            $invId = empty($id) ? $this->db->insert_id : (int)$id;
            $this->lastInsertId = $invId;
            $this->audit('inventory', empty($id) ? 'create' : 'update', 'inventory', $invId, $oldInvData, $_POST);
        }
        return $result ? 1 : 0;
    }

    function delete_inventory() {
        extract($_POST);
        $id = (int)$id;
        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
        $oldData = $this->getOldRecord('inventory', $id);

        $sel = "SELECT image_path FROM inventory WHERE id = $id";
        if ($login_type !== 1 && $active_bid > 0) {
            $sel .= " AND branch_id = {$active_bid}";
        }
        $qry = $this->db->query($sel);
        if (!$qry || $qry->num_rows === 0) {
            return 0;
        }
        $row = $qry->fetch_assoc();
        if (!empty($row['image_path']) && file_exists('uploads/' . $row['image_path'])) {
            unlink('uploads/' . $row['image_path']);
        }
        $sql = "DELETE FROM inventory WHERE id = $id";
        if ($login_type !== 1 && $active_bid > 0) {
            $sql .= " AND branch_id = {$active_bid}";
        }
        $result = $this->db->query($sql);
        if ($result) { $this->audit('inventory', 'delete', 'inventory', $id, $oldData, null); }
        return $result ? 1 : 0;
    }

    // ================== MANTENIMIENTOS ==================
    function get_mantenimientos() {
        // Verificar que la tabla mantenimientos existe
        $check_table = $this->db->query("SHOW TABLES LIKE 'mantenimientos'");
        if (!$check_table || $check_table->num_rows == 0) {
            error_log("ERROR: Tabla 'mantenimientos' no existe");
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        
        $startParam = $_GET['start'] ?? date('Y-m-01');
        $endParam = $_GET['end'] ?? date('Y-m-d', strtotime('+12 months'));

        $startDate = $this->createDateFromParam($startParam) ?? new DateTime(date('Y-m-01'));
        $endDate = $this->createDateFromParam($endParam);

        if (!$endDate) {
            $endDate = (clone $startDate)->modify('+12 months');
        }

        if ($endDate <= $startDate) {
            $endDate = (clone $startDate)->modify('+12 months');
        }

        // DESACTIVADO: Regeneración automática causaba sobrecarga del calendario
        // Solo se debe ejecutar manualmente o al crear/editar equipos
        // $this->ensure_maintenance_schedule($startDate, $endDate);

        $statusColumn = $this->detect_equipment_status_column();
        $statusSelect = $statusColumn ? "e.`$statusColumn` AS status_value" : "NULL AS status_value";

        $startStr = $startDate->format('Y-m-d');
        $endStr = $endDate->format('Y-m-d');

        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
        $branch_and_e = '';
        if ($active_bid > 0 || $login_type !== 1) {
            // Para no-admin siempre aplica; para admin sólo si tiene sucursal específica
            $branch_and_e = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'e') : ($active_bid > 0 ? " AND e.branch_id = {$active_bid} " : '');
        }

        $sql = "SELECT m.id, m.equipo_id, m.fecha_programada, m.hora_programada, m.tipo_mantenimiento, m.descripcion, m.estatus, e.name, $statusSelect, u.date AS unsubscribe_date
                FROM mantenimientos m
                JOIN equipments e ON m.equipo_id = e.id
                LEFT JOIN equipment_unsubscribe u ON u.equipment_id = e.id
                WHERE m.fecha_programada BETWEEN '$startStr' AND '$endStr'";

        if ($branch_and_e) {
            $sql .= $branch_and_e;
        }

        if ($statusColumn) {
            $sql .= " AND (UPPER(e.`$statusColumn`) = 'ACTIVO')";
        }

        $sql .= " AND (u.date IS NULL OR m.fecha_programada < u.date)
                  ORDER BY m.fecha_programada";

        $events = [];
        $qry = $this->db->query($sql);
        
        if (!$qry) {
            error_log("ERROR en query mantenimientos: " . $this->db->error);
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
        
        if ($qry) {
            while ($row = $qry->fetch_assoc()) {
                $title = $row['name'];
                $description = trim((string)($row['descripcion'] ?? ''));
                if ($description !== '') {
                    if (function_exists('mb_substr')) {
                        $excerpt = mb_substr($description, 0, 40);
                        if (mb_strlen($description) > 40) {
                            $excerpt .= '...';
                        }
                    } else {
                        $excerpt = substr($description, 0, 40);
                        if (strlen($description) > 40) {
                            $excerpt .= '...';
                        }
                    }
                    $title .= ' - ' . $excerpt;
                }

                $typeLabel = trim((string)($row['tipo_mantenimiento'] ?? ''));
                if ($typeLabel !== '') {
                    $title = '[' . $typeLabel . '] ' . $title;
                }

                $status = strtolower((string)($row['estatus'] ?? ''));
                $tipo = strtolower((string)($row['tipo_mantenimiento'] ?? 'preventivo'));
                
                // Diferencia de color por tipo de mantenimiento, ajustado por estado
                if ($status === 'completado') {
                    $color = '#6c757d';  // Gris para completado (cualquier tipo)
                } elseif ($tipo === 'preventivo') {
                    $color = '#1565C0';  // Azul oscuro
                } elseif ($tipo === 'correctivo') {
                    $color = '#B71C1C';  // Rojo
                } elseif ($tipo === 'predictivo') {
                    $color = '#4A148C';  // Púrpura oscuro
                } else {
                    $color = '#dc3545';  // Rojo por defecto
                }

                $start = $row['fecha_programada'];
                $hora = trim((string)($row['hora_programada'] ?? ''));
                if ($hora !== '') {
                    if (strlen($hora) === 5) {
                        $hora .= ':00';
                    }
                    $start .= 'T' . $hora;
                }

                $events[] = [
                    'id' => $row['id'],
                    'title' => $title,
                    'start' => $start,
                    'color' => $color,
                    'extendedProps' => [
                        'equipment_id' => (int)$row['equipo_id'],
                        'hora_programada' => $hora,
                        'tipo_mantenimiento' => $typeLabel
                    ]
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($events);
        exit;
    }

    function save_maintenance() {
        $equipo_id = isset($_POST['equipo_id']) ? (int)$_POST['equipo_id'] : 0;
        $fecha_programada = trim($_POST['fecha_programada'] ?? '');
        $descripcion = $_POST['descripcion'] ?? '';
        $tipo_input = strtolower(trim($_POST['tipo_mantenimiento'] ?? ''));
        $hora_input = trim($_POST['hora_programada'] ?? '');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($equipo_id <= 0 || empty($fecha_programada)) {
            return 0;
        }

        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
        // En modo sucursal específica (admin con active_bid>0) o no-admin: validar que el equipo pertenezca a esa sucursal
        if ($login_type !== 1 || $active_bid > 0) {
            if ($active_bid <= 0) return 0;
            $checkEq = $this->db->query("SELECT id FROM equipments WHERE id = {$equipo_id} AND branch_id = {$active_bid} LIMIT 1");
            if (!$checkEq || $checkEq->num_rows === 0) {
                return 0;
            }
        }

        // Si es edición, validar que el mantenimiento pertenezca a un equipo permitido
        if ($id > 0 && ($login_type !== 1 || $active_bid > 0)) {
            $checkMaint = $this->db->query("SELECT m.id FROM mantenimientos m JOIN equipments e ON m.equipo_id = e.id WHERE m.id = {$id} AND e.branch_id = {$active_bid} LIMIT 1");
            if (!$checkMaint || $checkMaint->num_rows === 0) {
                return 0;
            }
        }

        // Validar límite de eventos por día (solo para nuevos registros)
        if ($id <= 0 && !$this->can_add_maintenance_on_date($fecha_programada)) {
            return -1; // Código especial para indicar límite excedido
        }

        $allowedTypes = [
            'predictivo' => 'Predictivo',
            'preventivo' => 'Preventivo',
            'correctivo' => 'Correctivo'
        ];
        $tipo_mantenimiento = $allowedTypes[$tipo_input] ?? 'Preventivo';

        $hora_sql = 'NULL';
        if ($hora_input !== '' && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora_input)) {
            if (strlen($hora_input) === 5) {
                $hora_input .= ':00';
            }
            $hora_sql = "'" . $this->db->real_escape_string($hora_input) . "'";
        }

        $data = [];
        $data[] = "equipo_id=" . $equipo_id;
        $data[] = "fecha_programada='" . $this->db->real_escape_string($fecha_programada) . "'";
        $data[] = "tipo_mantenimiento='" . $this->db->real_escape_string($tipo_mantenimiento) . "'";
        $data[] = "hora_programada=$hora_sql";
        if (!empty($descripcion)) {
            $data[] = "descripcion='" . addslashes($descripcion) . "'";
        }

        $setClause = implode(', ', $data);
        if ($id <= 0) {
            $sql = "INSERT INTO mantenimientos SET $setClause";
        } else {
            $sql = "UPDATE mantenimientos SET $setClause WHERE id=" . $id;
        }

        $result = $this->db->query($sql);
        if ($result) {
            $mntId = ($id <= 0) ? $this->db->insert_id : $id;
            $this->audit('maintenance', ($id <= 0) ? 'create' : 'update', 'mantenimientos', $mntId, null, ['equipo_id' => $equipo_id, 'fecha_programada' => $fecha_programada, 'tipo_mantenimiento' => $tipo_mantenimiento]);
        }
        return $result ? 1 : 0;
    }

    function complete_maintenance() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return 0;

        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

        $sql = "UPDATE mantenimientos m JOIN equipments e ON m.equipo_id = e.id SET m.estatus='completado' WHERE m.id={$id}";
        if ($login_type !== 1 || $active_bid > 0) {
            if ($active_bid <= 0) return 0;
            $sql .= " AND e.branch_id = {$active_bid}";
        }

        $result = $this->db->query($sql);
        if ($result) { $this->audit('maintenance', 'update', 'mantenimientos', $id, null, ['estatus' => 'completado']); }
        return $result ? 1 : 0;
    }

    // ================== UBICACIONES / PUESTOS ==================
    function save_equipment_location() {
        extract($_POST);
        $isNewLoc = empty($id);
        $data = "";
        $auditLoc = [];
        foreach ($_POST as $k => $v) {
            if ($k != 'id') {
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
                $auditLoc[$k] = $v;
            }
        }
        $oldLocData = !$isNewLoc ? $this->getOldRecord('locations', (int)$id) : null;
        $save = $isNewLoc
            ? $this->db->query("INSERT INTO locations SET $data")
            : $this->db->query("UPDATE locations SET $data WHERE id = $id");
        if ($save) {
            $locId = $isNewLoc ? $this->db->insert_id : (int)$id;
            $this->audit('locations', $isNewLoc ? 'create' : 'update', 'locations', $locId, $oldLocData, $auditLoc);
        }
        return $save ? ($isNewLoc ? 1 : 2) : 0;
    }

    function delete_equipment_location() {
        extract($_POST);
        $oldData = $this->getOldRecord('locations', (int)$id);
        $result = $this->db->query("DELETE FROM locations WHERE id = $id");
        if ($result) { $this->audit('locations', 'delete', 'locations', (int)$id, $oldData, null); }
        return $result ? 1 : 0;
    }

    function save_job_position() {
        try {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $name = isset($_POST['name']) ? $this->db->real_escape_string($_POST['name']) : '';
            $location_id = isset($_POST['location_id']) && !empty($_POST['location_id']) ? intval($_POST['location_id']) : 'NULL';
            $department_id = isset($_POST['department_id']) && !empty($_POST['department_id']) ? intval($_POST['department_id']) : 'NULL';
            
            error_log("DEBUG save_job_position: id=$id, name=$name, location_id=$location_id, department_id=$department_id");
            
            if (empty($name)) {
                error_log("ERROR: Job position name is empty");
                return 0;
            }
            
            if ($id == 0) {
                // Crear nuevo puesto
                $query = "INSERT INTO job_positions SET name='$name', location_id=$location_id, department_id=$department_id";
                error_log("DEBUG: INSERT query = $query");
                $save = $this->db->query($query);
                
                if(!$save) {
                    error_log("ERROR save_job_position INSERT: " . $this->db->error);
                    return 0;
                }
                
                $id = $this->db->insert_id;
                error_log("DEBUG: New job position ID = $id");
                $this->audit('job_positions', 'create', 'job_positions', $id, null, ['name' => $name, 'location_id' => $location_id, 'department_id' => $department_id]);
                
                // Mantener compatibilidad con tabla antigua location_positions
                if($location_id !== 'NULL') {
                    $compat_query = "INSERT IGNORE INTO location_positions SET job_position_id=$id, location_id=$location_id";
                    error_log("DEBUG: Compatibility INSERT = $compat_query");
                    $this->db->query($compat_query);
                }
                
                return 1;
            } else {
                // Actualizar puesto existente
                $query = "UPDATE job_positions SET name='$name', location_id=$location_id, department_id=$department_id WHERE id=$id";
                error_log("DEBUG: UPDATE query = $query");
                $oldJobData = $this->getOldRecord('job_positions', $id);
                $update = $this->db->query($query);
                
                if(!$update) {
                    error_log("ERROR save_job_position UPDATE: " . $this->db->error);
                    return 0;
                }
                $this->audit('job_positions', 'update', 'job_positions', $id, $oldJobData, ['name' => $name, 'location_id' => $location_id, 'department_id' => $department_id]);
                
                // Actualizar tabla antigua location_positions para compatibilidad
                $check = $this->db->query("SELECT id FROM location_positions WHERE job_position_id=$id");
                $exists = $check ? $check->num_rows : 0;
                
                if ($exists > 0) {
                    if($location_id !== 'NULL') {
                        $this->db->query("UPDATE location_positions SET location_id=$location_id WHERE job_position_id=$id");
                    } else {
                        $this->db->query("DELETE FROM location_positions WHERE job_position_id=$id");
                    }
                } else {
                    if($location_id !== 'NULL') {
                        $this->db->query("INSERT INTO location_positions SET job_position_id=$id, location_id=$location_id");
                    }
                }
                
                return 2;
            }
        } catch (Exception $e) {
            error_log("EXCEPTION in save_job_position: " . $e->getMessage());
            return 0;
        }
    }

    function delete_job_position() {
        extract($_POST);
        if (empty($id) || !is_numeric($id)) return 2;
        $oldData = $this->getOldRecord('job_positions', (int)$id);
        $this->db->query("DELETE FROM location_positions WHERE job_position_id=$id");
        $result = $this->db->query("DELETE FROM job_positions WHERE id=$id");
        if ($result) { $this->audit('job_positions', 'delete', 'job_positions', (int)$id, $oldData, null); }
        return $result ? 1 : 2;
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

        $oldSuppData = !empty($id) ? $this->getOldRecord('suppliers', (int)$id) : null;
        $save = empty($id)
            ? $this->db->query("INSERT INTO suppliers SET $set_clause")
            : $this->db->query("UPDATE suppliers SET $set_clause WHERE id = " . (int)$id);
        if ($save) {
            $suppId = empty($id) ? $this->db->insert_id : (int)$id;
            $this->audit('suppliers', empty($id) ? 'create' : 'update', 'suppliers', $suppId, $oldSuppData, $data);
        }
        return $save ? 1 : 0;
    }

    function delete_supplier() {
        try {
            extract($_POST);
            $id = (int)($id ?? 0);
            if ($id <= 0) return 0;
            $oldData = $this->getOldRecord('suppliers', $id);
            
            if ($this->pdo) {
                $stmt = $this->pdo->prepare("DELETE FROM suppliers WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) { $this->audit('suppliers', 'delete', 'suppliers', $id, $oldData, null); return 1; }
                return 0;
            } else {
                return $this->db->query("DELETE FROM suppliers WHERE id = $id") ? 1 : 0;
            }
        } catch (Exception $e) {
            error_log("DELETE_SUPPLIER ERROR: " . $e->getMessage());
            return 0;
        }
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

        $login_type = (int)($_SESSION['login_type'] ?? 0);
        $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

        $equipment_id_for_branch = isset($_POST['equipo_id_select']) ? (int)$_POST['equipo_id_select'] : 0;
        if ($equipment_id_for_branch <= 0) {
            return json_encode(['status' => 0, 'message' => 'Equipo inválido.']);
        }

        $eqBranchRow = $this->db->query("SELECT branch_id FROM equipments WHERE id = {$equipment_id_for_branch} LIMIT 1");
        if (!$eqBranchRow || $eqBranchRow->num_rows === 0) {
            return json_encode(['status' => 0, 'message' => 'Equipo no encontrado.']);
        }
        $report_branch_id = (int)($eqBranchRow->fetch_assoc()['branch_id'] ?? 0);
        if ($report_branch_id <= 0) {
            return json_encode(['status' => 0, 'message' => 'El equipo no tiene sucursal asignada.']);
        }

        // No-admin: el reporte debe ser de su sucursal activa. Admin: si tiene sucursal específica, también.
        if ($login_type !== 1 || $active_bid > 0) {
            if ($active_bid <= 0 || $report_branch_id !== $active_bid) {
                return json_encode(['status' => 0, 'message' => 'Sin permiso para generar reporte en esta sucursal.']);
            }
        }
        
        // Mapeo de campos POST a columnas de BD
        $field_mapping = [
            'orden_mto' => 'order_number',
            'fecha_reporte' => 'report_date',
            'cliente_nombre' => 'client_name',
            'equipo_id_select' => 'equipment_id',
            'tipo_servicio' => 'service_type',
            'descripcion' => 'description',
            'observaciones' => 'observations',
            'status_final' => 'final_status',
            'ingeniero_nombre' => 'engineer_name',
            'recibe_nombre' => 'received_by',
            'admin_name' => 'admin_name'
        ];
        
        foreach ($_POST as $k => $v) {
            if (isset($field_mapping[$k])) {
                $column_name = $field_mapping[$k];
                $escaped_value = $this->db->real_escape_string($v);
                $data_report .= empty($data_report) ? " $column_name='$escaped_value' " : ", $column_name='$escaped_value' ";
            }
        }

        // Asegurar branch_id consistente (derivado del equipo)
        $data_report .= empty($data_report) ? " branch_id='{$report_branch_id}' " : ", branch_id='{$report_branch_id}' ";
        
        $save_report = $this->db->query("INSERT INTO maintenance_reports SET $data_report");

        if (!$save_report) {
            return json_encode(['status' => 0, 'message' => 'Error al guardar el reporte principal.']); 
        }
        
        $report_id = $this->db->insert_id; 
        $this->audit('maintenance', 'create', 'maintenance_reports', $report_id, null, $_POST);
        
        if (isset($refaccion_item_id) && is_array($refaccion_item_id)) {
            for ($i = 0; $i < count($refaccion_item_id); $i++) {
                $item_id = $refaccion_item_id[$i];
                $qty = (int) $refaccion_qty[$i];
                
                if (!empty($item_id) && $qty > 0) {
                    $update_stock = $this->db->query("
                        UPDATE inventory SET stock = stock - {$qty} WHERE id = {$item_id} AND branch_id = {$report_branch_id}
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
        // Evitar timeouts en cargas grandes (según hosting puede no aplicar)
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        // Usar SimpleXLSX (librería ligera sin dependencias)
        require_once 'lib/simplexlsx-master/src/SimpleXLSX.php';
        
        $this->ensure_equipment_delivery_fk();
        $this->ensure_equipment_delivery_position_fk();

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
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file['tmp_name']);
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
                    $pos_query = $this->db->query("SELECT id FROM job_positions WHERE name LIKE '%$pos_escaped%' LIMIT 1");
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

    // ================== SERVICIOS Y CATEGORÍAS ==================
    function save_category()
    {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $allowed_fields = ['category', 'clave', 'description'];
        $data_parts = [];
        foreach ($allowed_fields as $k) {
            if (!array_key_exists($k, $_POST)) {
                continue;
            }
            $v = (string)$_POST[$k];
            $v_esc = $this->db->real_escape_string($v);
            $data_parts[] = "`{$k}` = '{$v_esc}'";
        }
        $data = implode(' , ', $data_parts);

        $category_esc = $this->db->real_escape_string(trim((string)($_POST['category'] ?? '')));
        $chk = $this->db->query("SELECT 1 FROM `services_category` where category = '{$category_esc}' " . ($id > 0 ? " and id != {$id}" : ""));
        if ($chk->num_rows > 0) {
            return json_encode(['status' => 'duplicate_category']);
        }
        if (!empty($_POST['clave'])) {
            $clave_esc = $this->db->real_escape_string(trim((string)$_POST['clave']));
            $chk_clave = $this->db->query("SELECT 1 FROM `services_category` where clave = '{$clave_esc}' " . ($id > 0 ? " and id != {$id}" : ""));
            if ($chk_clave->num_rows > 0) {
                return json_encode(['status' => 'duplicate_clave']);
            }
        }
        if ($data === '') {
            return json_encode(['status' => 'error', 'msg' => 'Datos inválidos']);
        }
        if ($id <= 0) {
            $sql = "INSERT INTO `services_category` set $data ";
        } else {
            $sql = "UPDATE `services_category` set $data where id = {$id}";
        }
        $oldCatData = ($id > 0) ? $this->getOldRecord('services_category', $id) : null;
        $save = $this->db->query($sql);
        if ($save) {
            $catId = ($id > 0) ? $id : $this->db->insert_id;
            $this->audit('services', ($id > 0) ? 'update' : 'create', 'services_category', $catId, $oldCatData, $_POST);
            return json_encode(['status' => 'success']);
        } else {
            return json_encode(['status' => 'error', 'data' => $sql]);
        }
    }

    function delete_service_category()
    {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            return json_encode(['status' => 'error', 'error' => 'ID inválido']);
        }
        $oldData = $this->getOldRecord('services_category', $id);
        $delete = $this->db->query("DELETE FROM `services_category` where id = {$id}");
        $delete2 = $this->db->query("DELETE FROM `services` where category_id = {$id}");
        if ($delete && $delete2) {
            $this->audit('services', 'delete', 'services_category', $id, $oldData, null);
            return json_encode(['status' => 'success']);
        } else {
            return json_encode(['status' => 'error', 'error' => $this->db->error]);
        }
    }

    function load_service_category()
    {
        $qry = $this->db->query("SELECT * FROM `services_category` order by `category` asc");
        $data = array();
        while ($row = $qry->fetch_assoc()) {
            $row['description'] = strip_tags(stripslashes($row['description']));
            $data[] = $row;
        }
        return json_encode(['status' => 'success', 'data' => $data]);
    }

    function save_service()
    {
        extract($_POST);
        $data = "";
        foreach ($_POST as $k => $v) {
            if (!in_array($k, array('id'))) {
                if ($k == 'description') $v = addslashes($v);
                if (!empty($data)) $data .= " , ";
                $data .= " {$k} = '{$v}' ";
            }
        }
        $chk = $this->db->query("SELECT * FROM `services` where service = '{$service}' " . (!empty($id) ? " and id != {$id}" : "")) or die($this->db->error);
        if ($chk->num_rows > 0) {
            return json_encode(['status' => 'duplicate']);
        }

        if (empty($id)) {
            $sql = "INSERT INTO `services` set $data ";
        } else {
            $sql = "UPDATE `services` set $data where id = {$id}";
        }
        $save = $this->db->query($sql);
        if ($save) {
            $svcId = !empty($id) ? $id : $this->db->insert_id;
            $this->audit('services', empty($id) ? 'create' : 'update', 'services', $svcId, null, ['service' => $service ?? '']);
            $id = !empty($id) ? $id : $this->db->insert_id;
            $upload_dir_rel = 'uploads/services';
            $upload_dir = (defined('ROOT') ? rtrim(ROOT, '/\\') . '/' . $upload_dir_rel : $upload_dir_rel);

            // Verificar y crear directorio
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
                @chmod($upload_dir, 0777);
            }

            if (!empty($_FILES['img']['tmp_name'])) {
                $file = pathinfo($_FILES['img']['name'] ?? '');
                $extension = strtolower($file['extension'] ?? '');

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $base = $id . '_img';

                    // Eliminar imagen anterior si existe
                    $old_images = glob($upload_dir . '/' . $base . '.*');
                    foreach ($old_images as $old_img) {
                        if (is_file($old_img)) {
                            @unlink($old_img);
                        }
                    }

                    $saved = $this->save_uploaded_image_optimized($_FILES['img'], $upload_dir_rel, $base, 5 * 1024 * 1024, 1200, true);
                    if (!empty($saved['ok'])) {
                        $fname = $saved['filename'];
                        $data = " img_path = '{$upload_dir_rel}/{$fname}' ";
                        $this->db->query("UPDATE `services` set {$data} where id = $id ");
                    }
                }
            }
            return json_encode(['status' => 'success']);
        } else {
            return json_encode(['status' => 'error', 'data' => $sql]);
        }
    }

    function delete_service()
    {
        extract($_POST);
        $oldData = $this->getOldRecord('services', (int)$id);
        $delete = $this->db->query("DELETE FROM `services` where `id` ='$id' ");
        if ($delete) {
            $this->audit('services', 'delete', 'services', (int)$id, $oldData, null);
            return json_encode(['status' => 'success']);
        } else {
            return json_encode(['status' => 'error', 'error' => $this->db->error]);
        }
    }

    function load_service()
    {
        $qry = $this->db->query("SELECT s.*,c.category FROM `services` s inner join `services_category` c on c.id = s.category_id order by s.`service` asc");
        $data = array();
        while ($row = $qry->fetch_assoc()) {
            $row['description'] = strip_tags(stripslashes($row['description']));

            $default_rel = 'uploads/default.png';
            $img = trim((string)($row['img_path'] ?? ''));
            $img_rel = '';

            if ($img !== '') {
                // URLs públicas se devuelven tal cual
                if (preg_match('/^https?:\/\//i', $img)) {
                    $img_rel = $img;
                } else {
                    // Normalizar separadores / limpiar bytes nulos
                    $img_norm = str_replace("\0", '', $img);
                    $img_norm = str_replace('\\', '/', $img_norm);

                    // Si viene ruta absoluta Windows (C:/...), intentar convertir a relativa a ROOT
                    if (preg_match('/^[A-Za-z]:\//', $img_norm) && defined('ROOT')) {
                        $root_norm = str_replace('\\', '/', (string)ROOT);
                        $root_norm = rtrim($root_norm, '/');
                        if (stripos($img_norm, $root_norm . '/') === 0) {
                            $img_norm = substr($img_norm, strlen($root_norm) + 1);
                        } else {
                            $img_norm = basename($img_norm);
                        }
                    }

                    $candidate_rel = ltrim($img_norm, '/');
                    if (defined('ROOT')) {
                        $candidate_fs = rtrim(ROOT, '/\\') . '/' . $candidate_rel;
                        if (is_file($candidate_fs)) {
                            $img_rel = $candidate_rel;
                        }
                    } else {
                        if (is_file($candidate_rel)) {
                            $img_rel = $candidate_rel;
                        }
                    }
                }
            }

            $row['img_path'] = $img_rel !== '' ? $img_rel : $default_rel;
            $data[] = $row;
        }
        return json_encode(['status' => 'success', 'data' => $data]);
    }

    // ================== CATEGORÍAS DE EQUIPOS ==================
    function load_equipment_category()
    {
        $this->ensure_equipment_categories_schema();
        $data = array();
        $qry = $this->db->query("SELECT id, clave, description FROM equipment_categories ORDER BY clave ASC");
        if ($qry) {
            while ($row = $qry->fetch_assoc()) {
                $row['description'] = strip_tags(stripslashes($row['description']));
                $data[] = $row;
            }
        }
        return json_encode(['status' => 'success', 'data' => $data]);
    }

    function save_equipment_category()
    {
        $this->ensure_equipment_categories_schema();
        $id = (int)($_POST['id'] ?? 0);
        $clave = strtoupper(trim($_POST['clave'] ?? ''));
        $clave = preg_replace('/[^A-Z0-9]/', '', $clave);
        $description = trim($_POST['description'] ?? '');

        if ($clave === '' || (strlen($clave) < 2 || strlen($clave) > 3) || $description === '') {
            return json_encode(['status' => 'error']);
        }

        if ($id > 0) {
            $current = $this->db->query("SELECT clave FROM equipment_categories WHERE id = {$id} LIMIT 1");
            if (!$current || $current->num_rows === 0) {
                return json_encode(['status' => 'error']);
            }
            $existing = $current->fetch_assoc()['clave'] ?? '';
            if (strtoupper($existing) !== $clave) {
                // CLAVE inmutable
                return json_encode(['status' => 'error']);
            }
        } else {
            $chk = $this->db->query("SELECT id FROM equipment_categories WHERE clave = '{$this->db->real_escape_string($clave)}' LIMIT 1");
            if ($chk && $chk->num_rows > 0) {
                return json_encode(['status' => 'duplicate_clave']);
            }
        }

        $desc_esc = $this->db->real_escape_string($description);
        $clave_esc = $this->db->real_escape_string($clave);

        if ($id > 0) {
            $sql = "UPDATE equipment_categories SET description = '{$desc_esc}' WHERE id = {$id}";
        } else {
            $sql = "INSERT INTO equipment_categories (clave, description) VALUES ('{$clave_esc}', '{$desc_esc}')";
        }

        $save = $this->db->query($sql);
        if ($save) {
            $catId = ($id > 0) ? $id : $this->db->insert_id;
            $this->audit('equipment_categories', ($id > 0) ? 'update' : 'create', 'equipment_categories', $catId, null, ['clave' => $clave, 'description' => $description]);
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'error']);
    }

    function delete_equipment_category()
    {
        $this->ensure_equipment_categories_schema();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) return json_encode(['status' => 'error']);
        $oldData = $this->getOldRecord('equipment_categories', $id);
        $delete = $this->db->query("DELETE FROM equipment_categories WHERE id = {$id}");
        if ($delete) {
            $this->audit('equipment_categories', 'delete', 'equipment_categories', $id, $oldData, null);
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'error', 'error' => $this->db->error]);
    }

    // ================== EMAIL HELPERS PARA TICKETS ==================

    /**
     * Carga MailerService (PHPMailer SMTP) si no está disponible.
     */
    private function loadMailerService(): void {
        if (!class_exists('MailerService')) {
            $path = dirname(__DIR__) . '/app/helpers/MailerService.php';
            if (file_exists($path)) require_once $path;
        }
    }

    /**
     * Envía email al reportante externo cuando el estado de su ticket cambia.
     */
    private function sendPublicTicketStatusEmail(array $ticket, int $newStatus): void {
        $statusLabels = [0 => 'Abierto', 1 => 'En Proceso', 2 => 'Finalizado', 3 => 'Cerrado'];
        $newLabel = $statusLabels[$newStatus] ?? 'Actualizado';
        $to = filter_var($ticket['reporter_email'], FILTER_VALIDATE_EMAIL);
        if (!$to) return;
        $name      = htmlspecialchars($ticket['reporter_name'] ?? 'Estimado usuario', ENT_QUOTES);
        $ticketN   = htmlspecialchars($ticket['ticket_number'] ?? $ticket['id'], ENT_QUOTES);
        $subject   = $ticket['subject'] ?? 'Sin asunto';
        $token     = $ticket['tracking_token'] ?? '';
        $baseUrl   = rtrim(defined('BASE_URL') ? BASE_URL : (getenv('BASE_URL') ?: ''), '/');
        $trackLink = htmlspecialchars($baseUrl . '/public/track.php?token=' . urlencode($token), ENT_QUOTES);
        $mailSubject = "Actualización de Tu Ticket #{$ticketN}";
        $body  = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px'>";
        $body .= "<div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:30px'>";
        $body .= "<h2 style='color:#343a40'>Actualización de Tu Ticket</h2>";
        $body .= "<p>Hola <strong>{$name}</strong>,</p>";
        $body .= "<p>El estado de tu ticket <strong>#{$ticketN}</strong> — <em>" . htmlspecialchars($subject, ENT_QUOTES) . "</em> ha cambiado a:</p>";
        $body .= "<p style='font-size:18px;color:#007bff;font-weight:bold'>{$newLabel}</p>";
        if (!empty($token)) {
            $body .= "<p style='margin-top:25px'><a href='{$trackLink}' style='background:#007bff;color:#fff;padding:12px 24px;border-radius:5px;text-decoration:none;font-size:15px'>Ver estado del ticket</a></p>";
        }
        $body .= "<hr style='margin-top:30px;border:none;border-top:1px solid #eee'>";
        $body .= "<p style='font-size:12px;color:#999'>Este mensaje fue generado automáticamente.</p>";
        $body .= "</div></body></html>";
        $this->loadMailerService();
        if (class_exists('MailerService')) {
            MailerService::send($to, $ticket['reporter_name'] ?? '', $mailSubject, $body);
        }
    }

    /**
     * Envía email al reportante externo cuando se agrega un comentario público a su ticket.
     */
    private function sendPublicTicketCommentEmail(array $ticket, string $commentPreview): void {
        $to = filter_var($ticket['reporter_email'], FILTER_VALIDATE_EMAIL);
        if (!$to) return;
        $name      = htmlspecialchars($ticket['reporter_name'] ?? 'Estimado usuario', ENT_QUOTES);
        $ticketN   = htmlspecialchars($ticket['ticket_number'] ?? $ticket['id'], ENT_QUOTES);
        $subject   = $ticket['subject'] ?? 'Sin asunto';
        $token     = $ticket['tracking_token'] ?? '';
        $baseUrl   = rtrim(defined('BASE_URL') ? BASE_URL : (getenv('BASE_URL') ?: ''), '/');
        $trackLink = htmlspecialchars($baseUrl . '/public/track.php?token=' . urlencode($token), ENT_QUOTES);
        $mailSubject = "Nueva respuesta en Tu Ticket #{$ticketN}";
        $body  = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px'>";
        $body .= "<div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:30px'>";
        $body .= "<h2 style='color:#343a40'>Nueva Respuesta en Tu Ticket</h2>";
        $body .= "<p>Hola <strong>{$name}</strong>,</p>";
        $body .= "<p>Se ha enviado una nueva respuesta en tu ticket <strong>#{$ticketN}</strong> — <em>" . htmlspecialchars($subject, ENT_QUOTES) . "</em>:</p>";
        if (!empty($commentPreview)) {
            $body .= "<blockquote style='border-left:4px solid #007bff;margin:15px 0;padding:10px 15px;background:#f0f7ff;color:#333'>" . htmlspecialchars($commentPreview, ENT_QUOTES) . "</blockquote>";
        }
        if (!empty($token)) {
            $body .= "<p style='margin-top:25px'><a href='{$trackLink}' style='background:#007bff;color:#fff;padding:12px 24px;border-radius:5px;text-decoration:none;font-size:15px'>Ver ticket completo</a></p>";
        }
        $body .= "<hr style='margin-top:30px;border:none;border-top:1px solid #eee'>";
        $body .= "<p style='font-size:12px;color:#999'>Este mensaje fue generado automáticamente.</p>";
        $body .= "</div></body></html>";
        $this->loadMailerService();
        if (class_exists('MailerService')) {
            MailerService::send($to, $ticket['reporter_name'] ?? '', $mailSubject, $body);
        }
    }

    /**
     * Envia email al tecnico asignado al ticket.
     * $skipUserId: no enviar si el tecnico es quien genero la accion.
     */
    private function sendTicketEmailToTechnician(int $ticketId, string $subject, string $bodyHtml, int $skipUserId = 0): void {
        try {
            $q = $this->db->query("SELECT u.id, u.email, CONCAT(u.firstname,' ',u.lastname) AS name
                FROM users u INNER JOIN tickets t ON t.assigned_to = u.id
                WHERE t.id = {$ticketId} AND u.email IS NOT NULL AND u.email != '' LIMIT 1");
            if (!$q) return;
            $tech = $q->fetch_assoc();
            if (!$tech || (int)$tech['id'] === $skipUserId) return;
            $to = filter_var($tech['email'], FILTER_VALIDATE_EMAIL);
            if (!$to) return;
            $this->loadMailerService();
            if (class_exists('MailerService')) {
                MailerService::send($to, $tech['name'] ?? '', $subject, $bodyHtml);
            }
        } catch (\Throwable $e) {
            error_log('sendTicketEmailToTechnician ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Genera el cuerpo HTML del email para notificaciones a tecnicos.
     */
    private function buildTechnicianEmailBody(string $heading, string $content, int $ticketId): string {
        $baseUrl = rtrim(defined('BASE_URL') ? BASE_URL : (getenv('BASE_URL') ?: ''), '/');
        $link = htmlspecialchars($baseUrl . '/index.php?page=view_ticket&id=' . $ticketId, ENT_QUOTES);
        $body  = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f5f5f5;padding:20px'>";
        $body .= "<div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:30px'>";
        $body .= "<h2 style='color:#343a40'>{$heading}</h2>";
        $body .= $content;
        $body .= "<p style='margin-top:25px'><a href='{$link}' style='background:#007bff;color:#fff;padding:12px 24px;border-radius:5px;text-decoration:none;font-size:15px'>Ver ticket</a></p>";
        $body .= "<hr style='margin-top:30px;border:none;border-top:1px solid #eee'>";
        $body .= "<p style='font-size:12px;color:#999'>Este mensaje fue generado automaticamente.</p>";
        $body .= "</div></body></html>";
        return $body;
    }

}
?>
