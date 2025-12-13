<?php
/**
 * User Model - Gestión de usuarios del sistema
 * 
 * Hereda de DataStore para CRUD básico y agrega lógica específica de usuarios
 */

require_once __DIR__ . '/DataStore.php';

class User extends DataStore {
    
    public function __construct() {
        parent::__construct('users');
    }
    
    /**
     * Guardar usuario (crear o actualizar)
     * @param array $data Datos del usuario
     * @return int|bool ID si creado, true si actualizado, false si error
     */
    public function save($data) {
        // Validar campos permitidos
        $allowed_fields = ['username', 'password', 'firstname', 'lastname', 'email', 'contact', 'address', 'avatar', 'role', 'type'];
        
        $filtered_data = array_intersect_key($data, array_flip($allowed_fields));
        
        if (empty($filtered_data)) {
            error_log("USER SAVE: No valid fields provided");
            return false;
        }
        
        // Si hay ID, es actualización
        if (isset($data['id'])) {
            $id = (int)$data['id'];
            
            // No permitir cambiar password por aquí (usar changePassword)
            unset($filtered_data['password']);
            
            return $this->update($filtered_data, $id);
        }
        
        // Crear nuevo usuario
        if (!isset($data['username']) || !isset($data['password'])) {
            error_log("USER SAVE: Username and password required");
            return false;
        }
        
        // Verificar username único
        if ($this->existsUsername($data['username'])) {
            error_log("USER SAVE: Username already exists");
            return false;
        }
        
        // Hash password si es nuevo
        if (isset($filtered_data['password'])) {
            $filtered_data['password'] = password_hash($filtered_data['password'], PASSWORD_BCRYPT);
        }
        
        return $this->insert($filtered_data);
    }
    
    /**
     * Verificar si username existe
     * @param string $username
     * @return bool
     */
    public function existsUsername($username) {
        return $this->findBy('username', $username, true) !== null;
    }
    
    /**
     * Obtener usuario por username
     * @param string $username
     * @return array|null
     */
    public function getByUsername($username) {
        return $this->findBy('username', $username, true);
    }
    
    /**
     * Obtener usuario por email
     * @param string $email
     * @return array|null
     */
    public function getByEmail($email) {
        return $this->findBy('email', $email, true);
    }
    
    /**
     * Cambiar contraseña
     * @param int $userId ID del usuario
     * @param string $oldPassword Contraseña actual
     * @param string $newPassword Nueva contraseña
     * @return bool
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->getById($userId);
        
        if (!$user) {
            error_log("USER: User not found");
            return false;
        }
        
        // Verificar contraseña actual
        $isValid = false;
        
        if (strpos($user['password'], '$2y$') === 0) {
            // bcrypt
            $isValid = password_verify($oldPassword, $user['password']);
        } else {
            // MD5 legacy
            $isValid = ($user['password'] === md5($oldPassword));
        }
        
        if (!$isValid) {
            error_log("USER: Old password incorrect");
            return false;
        }
        
        // Hash nueva contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        return $this->update(['password' => $hashedPassword], $userId);
    }
    
    /**
     * Listar usuarios por role/type
     * @param string $role Role a filtrar
     * @return array
     */
    public function getByRole($role) {
        $sql = "SELECT * FROM users WHERE role = :role OR type = :role ORDER BY firstname ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Contar usuarios por role
     * @param string $role
     * @return int
     */
    public function countByRole($role) {
        return $this->count("role = '{$role}' OR type = '{$role}'");
    }
    
    /**
     * Obtener usuario con info completa (incluyendo avatar)
     * @param int $id
     * @return array|null
     */
    public function getWithDetails($id) {
        $user = $this->getById($id);
        
        if ($user) {
            // No retornar password
            unset($user['password']);
        }
        
        return $user;
    }
    
    /**
     * Actualizar avatar
     * @param int $userId ID del usuario
     * @param string $avatar Nombre del archivo
     * @return bool
     */
    public function updateAvatar($userId, $avatar) {
        return $this->update(['avatar' => $avatar], $userId);
    }
    
    /**
     * Buscar usuarios (búsqueda general)
     * @param string $search Término de búsqueda
     * @return array
     */
    public function search($search) {
        $search = "%{$search}%";
        
        $sql = "SELECT * FROM users 
                WHERE username LIKE :search 
                   OR firstname LIKE :search 
                   OR lastname LIKE :search 
                   OR email LIKE :search
                ORDER BY firstname ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':search' => $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener último usuario creado
     * @return array|null
     */
    public function getLatest() {
        $sql = "SELECT * FROM users ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Validar login
     * @param string $username
     * @param string $password
     * @return array|false Array con datos usuario si válido, false si no
     */
    public function validateLogin($username, $password) {
        $user = $this->getByUsername($username);
        
        if (!$user) {
            error_log("LOGIN: User not found: {$username}");
            return false;
        }
        
        $passwordValid = false;
        
        if (strpos($user['password'], '$2y$') === 0) {
            // bcrypt
            $passwordValid = password_verify($password, $user['password']);
        } else {
            // MD5 legacy
            $passwordValid = ($user['password'] === md5($password));
        }
        
        if (!$passwordValid) {
            error_log("LOGIN: Invalid password for user: {$username}");
            return false;
        }
        
        // No retornar password
        unset($user['password']);
        return $user;
    }
}
