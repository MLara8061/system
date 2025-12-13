<?php
/**
 * app/controllers/DepartmentController.php
 * Controller de negocio para Department
 * Modelo simple - CRUD básico
 */

require_once dirname(__DIR__) . '/models/Department.php';

class DepartmentController {
    
    private $departmentModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->departmentModel = new Department();
    }
    
    /**
     * Crear nuevo departamento
     * 
     * @param array $input Datos del departamento
     * @return array Respuesta estándar
     */
    public function create($input) {
        // Validar entrada
        $validation = $this->validateInput($input, false);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            // Verificar nombre único
            if ($this->departmentModel->nameExists($input['name'])) {
                return [
                    'success' => false,
                    'message' => 'El nombre ya existe',
                    'errors' => ['Nombre duplicado']
                ];
            }
            
            // Preparar datos
            $data = [
                'name' => trim($input['name']),
                'description' => trim($input['description'] ?? ''),
                'active' => 1,
                'created_by' => $_SESSION['login_id'] ?? null
            ];
            
            // Crear departamento
            $deptId = $this->departmentModel->save($data);
            
            return [
                'success' => true,
                'message' => 'Departamento creado exitosamente',
                'data' => ['id' => $deptId]
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::create - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear departamento'
            ];
        }
    }
    
    /**
     * Actualizar departamento
     * 
     * @param int $deptId ID del departamento
     * @param array $input Datos a actualizar
     * @return array Respuesta estándar
     */
    public function update($deptId, $input) {
        // Validar que existe
        if (!$this->departmentModel->getById($deptId)) {
            return [
                'success' => false,
                'message' => 'Departamento no encontrado'
            ];
        }
        
        // Validar entrada
        $validation = $this->validateInput($input, true);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            // Verificar nombre único (excluyendo este)
            if (!empty($input['name']) && $this->departmentModel->nameExists($input['name'], $deptId)) {
                return [
                    'success' => false,
                    'message' => 'El nombre ya existe',
                    'errors' => ['Nombre duplicado']
                ];
            }
            
            // Preparar datos
            $data = [];
            if (!empty($input['name'])) {
                $data['name'] = trim($input['name']);
            }
            if (isset($input['description'])) {
                $data['description'] = trim($input['description']);
            }
            
            $data['updated_by'] = $_SESSION['login_id'] ?? null;
            
            // Actualizar
            if (!empty($data)) {
                $this->departmentModel->update($deptId, $data);
            }
            
            return [
                'success' => true,
                'message' => 'Departamento actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::update - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar departamento'
            ];
        }
    }
    
    /**
     * Eliminar departamento
     * 
     * @param int $deptId ID del departamento
     * @return array Respuesta estándar
     */
    public function delete($deptId) {
        try {
            if (!$this->departmentModel->getById($deptId)) {
                return [
                    'success' => false,
                    'message' => 'Departamento no encontrado'
                ];
            }
            
            $this->departmentModel->delete($deptId);
            
            return [
                'success' => true,
                'message' => 'Departamento eliminado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::delete - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar departamento'
            ];
        }
    }
    
    /**
     * Obtener un departamento
     * 
     * @param int $deptId ID del departamento
     * @return array Respuesta estándar
     */
    public function get($deptId) {
        try {
            $dept = $this->departmentModel->getById($deptId);
            
            if (!$dept) {
                return [
                    'success' => false,
                    'message' => 'Departamento no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => $dept
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::get - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener departamento'
            ];
        }
    }
    
    /**
     * Listar departamentos
     * 
     * @param bool $activeOnly Solo activos
     * @return array Respuesta estándar
     */
    public function list($activeOnly = false) {
        try {
            $depts = $activeOnly 
                ? $this->departmentModel->getActive() 
                : $this->departmentModel->getAll('name ASC');
            
            return [
                'success' => true,
                'data' => $depts
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::list - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al listar departamentos'
            ];
        }
    }
    
    /**
     * Buscar departamentos
     * 
     * @param string $search Término de búsqueda
     * @return array Respuesta estándar
     */
    public function search($search) {
        $search = trim($search);
        if (strlen($search) < 2) {
            return [
                'success' => false,
                'message' => 'Búsqueda debe tener al menos 2 caracteres',
                'errors' => ['Término de búsqueda muy corto']
            ];
        }
        
        try {
            $results = $this->departmentModel->search($search);
            
            return [
                'success' => true,
                'data' => $results
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::search - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la búsqueda'
            ];
        }
    }
    
    /**
     * Cambiar activo/inactivo
     * 
     * @param int $deptId ID del departamento
     * @param int $active 1 o 0
     * @return array Respuesta estándar
     */
    public function toggleActive($deptId, $active = null) {
        try {
            if (!$this->departmentModel->getById($deptId)) {
                return [
                    'success' => false,
                    'message' => 'Departamento no encontrado'
                ];
            }
            
            $this->departmentModel->toggleActive($deptId, $active);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado'
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::toggleActive - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cambiar estado'
            ];
        }
    }
    
    /**
     * Obtener estadísticas
     * 
     * @return array Respuesta estándar
     */
    public function getStatistics() {
        try {
            $stats = $this->departmentModel->getStatistics();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("DepartmentController::getStatistics - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ];
        }
    }
    
    /**
     * Validar entrada
     * 
     * @param array $input Datos a validar
     * @param bool $isUpdate Si es actualización
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateInput($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            if (empty($input['name'])) {
                $errors[] = 'Nombre requerido';
            } elseif (strlen($input['name']) < 3) {
                $errors[] = 'Nombre debe tener al menos 3 caracteres';
            }
        } else {
            if (!empty($input['name']) && strlen($input['name']) < 3) {
                $errors[] = 'Nombre debe tener al menos 3 caracteres';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
