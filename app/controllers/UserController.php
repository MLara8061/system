<?php
/**
 * User Controller - Lógica de negocio para usuarios
 * 
 * Maneja validación, lógica de negocio y respuestas
 * No accede directamente a BD (usa User Model)
 */

require_once __DIR__ . '/../models/User.php';

class UserController {
    protected $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Crear nuevo usuario con validación
     * @param array $input Datos del formulario
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function create($input) {
        // Validar inputs
        $validation = $this->validateUserInput($input);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['errors'][0],
                'errors' => $validation['errors']
            ];
        }
        
        // Username único
        if ($this->userModel->existsUsername($input['username'])) {
            return [
                'success' => false,
                'message' => 'El nombre de usuario ya existe'
            ];
        }
        
        // Email único (si se proporciona)
        if (!empty($input['email'])) {
            if ($this->userModel->getByEmail($input['email'])) {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ];
            }
        }
        
        // Crear usuario
        $userId = $this->userModel->save([
            'username' => trim($input['username']),
            'password' => $input['password'],
            'firstname' => trim($input['firstname'] ?? ''),
            'lastname' => trim($input['lastname'] ?? ''),
            'email' => trim($input['email'] ?? ''),
            'contact' => trim($input['contact'] ?? ''),
            'address' => trim($input['address'] ?? ''),
            'role' => $input['role'] ?? 'user',
            'type' => $input['type'] ?? 'user'
        ]);
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Error al crear el usuario'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => ['id' => $userId]
        ];
    }
    
    /**
     * Actualizar usuario existente
     * @param int $id ID del usuario
     * @param array $input Datos a actualizar
     * @return array
     */
    public function update($id, $input) {
        $id = (int)$id;
        
        // Verificar que usuario existe
        $user = $this->userModel->getById($id);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        // Validar inputs (no tan estricto como create)
        $updateData = [];
        
        if (isset($input['firstname'])) {
            $updateData['firstname'] = trim($input['firstname']);
        }
        if (isset($input['lastname'])) {
            $updateData['lastname'] = trim($input['lastname']);
        }
        if (isset($input['email'])) {
            // Verificar email único (excepto el actual)
            $existingEmail = $this->userModel->getByEmail($input['email']);
            if ($existingEmail && $existingEmail['id'] !== $id) {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ];
            }
            $updateData['email'] = trim($input['email']);
        }
        if (isset($input['contact'])) {
            $updateData['contact'] = trim($input['contact']);
        }
        if (isset($input['address'])) {
            $updateData['address'] = trim($input['address']);
        }
        if (isset($input['role'])) {
            $updateData['role'] = $input['role'];
        }
        
        if (empty($updateData)) {
            return [
                'success' => false,
                'message' => 'No hay datos para actualizar'
            ];
        }
        
        $updated = $this->userModel->update($updateData, $id);
        
        if (!$updated) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el usuario'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => ['id' => $id]
        ];
    }
    
    /**
     * Eliminar usuario
     * @param int $id ID del usuario
     * @return array
     */
    public function delete($id) {
        $id = (int)$id;
        
        $user = $this->userModel->getById($id);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        $deleted = $this->userModel->delete($id);
        
        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el usuario'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Usuario eliminado exitosamente',
            'data' => ['id' => $id]
        ];
    }
    
    /**
     * Obtener usuario
     * @param int $id ID del usuario
     * @return array
     */
    public function get($id) {
        $id = (int)$id;
        
        $user = $this->userModel->getWithDetails($id);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        return [
            'success' => true,
            'data' => $user
        ];
    }
    
    /**
     * Listar todos los usuarios
     * @param string $role Filtrar por role (opcional)
     * @return array
     */
    public function list($role = null) {
        if ($role) {
            $users = $this->userModel->getByRole($role);
        } else {
            $users = $this->userModel->getAll('firstname ASC');
        }
        
        // No retornar passwords
        $users = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);
        
        return [
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ];
    }
    
    /**
     * Buscar usuarios
     * @param string $search Término de búsqueda
     * @return array
     */
    public function search($search) {
        if (strlen($search) < 2) {
            return [
                'success' => false,
                'message' => 'Búsqueda debe tener al menos 2 caracteres'
            ];
        }
        
        $users = $this->userModel->search($search);
        
        // No retornar passwords
        $users = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);
        
        return [
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ];
    }
    
    /**
     * Cambiar contraseña
     * @param int $id ID del usuario
     * @param array $input ['old_password' => string, 'new_password' => string]
     * @return array
     */
    public function changePassword($id, $input) {
        $id = (int)$id;
        
        if (empty($input['old_password']) || empty($input['new_password'])) {
            return [
                'success' => false,
                'message' => 'Las contraseñas son requeridas'
            ];
        }
        
        if (strlen($input['new_password']) < 6) {
            return [
                'success' => false,
                'message' => 'La nueva contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        $changed = $this->userModel->changePassword(
            $id,
            $input['old_password'],
            $input['new_password']
        );
        
        if (!$changed) {
            return [
                'success' => false,
                'message' => 'Error al cambiar la contraseña'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ];
    }
    
    /**
     * Validar entrada de usuario
     * @param array $input
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateUserInput($input) {
        $errors = [];
        
        // Username
        if (empty($input['username'])) {
            $errors[] = 'El nombre de usuario es requerido';
        } elseif (strlen($input['username']) < 3) {
            $errors[] = 'El nombre de usuario debe tener al menos 3 caracteres';
        }
        
        // Password
        if (empty($input['password'])) {
            $errors[] = 'La contraseña es requerida';
        } elseif (strlen($input['password']) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }
        
        // Firstname
        if (empty($input['firstname'])) {
            $errors[] = 'El nombre es requerido';
        }
        
        // Email (si se proporciona)
        if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es válido';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
