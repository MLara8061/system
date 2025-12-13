<?php
/**
 * app/controllers/EquipmentController.php
 * Controller de negocio para Equipment
 * 
 * Responsabilidades:
 * - Validación de entrada
 * - Lógica de negocio
 * - Autorización de acciones
 * - Delegación a Model para acceso a datos
 * 
 * Patrón: Controller valida → Model ejecuta → Respuesta estándar
 */

require_once dirname(__DIR__) . '/models/Equipment.php';

class EquipmentController {
    
    private $equipmentModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->equipmentModel = new Equipment();
    }
    
    /**
     * Crear nuevo equipo
     * 
     * @param array $input Datos del equipo
     * @return array Respuesta estándar
     */
    public function create($input) {
        // Validar entrada
        $validation = $this->validateEquipmentInput($input, false);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            // Preparar datos
            $data = [
                'name' => trim($input['name']),
                'serial_number' => trim($input['serial_number'] ?? ''),
                'category_id' => (int)$input['category_id'],
                'supplier_id' => (int)($input['supplier_id'] ?? 0) ?: null,
                'location_id' => (int)($input['location_id'] ?? 0) ?: null,
                'purchase_price' => (float)$input['purchase_price'],
                'purchase_date' => $input['purchase_date'] ?? date('Y-m-d'),
                'warranty_expiry' => $input['warranty_expiry'] ?? null,
                'status' => $input['status'] ?? 'active',
                'notes' => trim($input['notes'] ?? ''),
                'created_by' => $_SESSION['login_id'] ?? null
            ];
            
            // Crear equipo
            $equipmentId = $this->equipmentModel->save($data);
            
            // Obtener asset_tag auto-generado
            $equipment = $this->equipmentModel->getById($equipmentId);
            
            return [
                'success' => true,
                'message' => 'Equipo creado exitosamente',
                'data' => [
                    'id' => $equipmentId,
                    'asset_tag' => $equipment['asset_tag'] ?? null
                ]
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::create - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear equipo'
            ];
        }
    }
    
    /**
     * Actualizar equipo existente
     * 
     * @param int $equipmentId ID del equipo
     * @param array $input Datos a actualizar
     * @return array Respuesta estándar
     */
    public function update($equipmentId, $input) {
        // Validar que existe
        $existing = $this->equipmentModel->getById($equipmentId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => 'Equipo no encontrado'
            ];
        }
        
        // Validar entrada
        $validation = $this->validateEquipmentInput($input, true); // true = actualización
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            // Preparar datos (solo campos no vacíos)
            $data = [];
            
            if (!empty($input['name'])) {
                $data['name'] = trim($input['name']);
            }
            if (isset($input['serial_number'])) {
                $data['serial_number'] = trim($input['serial_number']);
            }
            if (!empty($input['category_id'])) {
                $data['category_id'] = (int)$input['category_id'];
            }
            if (isset($input['supplier_id'])) {
                $data['supplier_id'] = (int)($input['supplier_id'] ?? 0) ?: null;
            }
            if (isset($input['location_id'])) {
                $data['location_id'] = (int)($input['location_id'] ?? 0) ?: null;
            }
            if (!empty($input['purchase_price'])) {
                $data['purchase_price'] = (float)$input['purchase_price'];
            }
            if (!empty($input['purchase_date'])) {
                $data['purchase_date'] = $input['purchase_date'];
            }
            if (isset($input['warranty_expiry'])) {
                $data['warranty_expiry'] = $input['warranty_expiry'] ?: null;
            }
            if (isset($input['status'])) {
                $data['status'] = $input['status'];
            }
            if (isset($input['notes'])) {
                $data['notes'] = trim($input['notes']);
            }
            
            $data['updated_by'] = $_SESSION['login_id'] ?? null;
            
            // Actualizar
            if (!empty($data)) {
                $this->equipmentModel->update($equipmentId, $data);
            }
            
            return [
                'success' => true,
                'message' => 'Equipo actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::update - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar equipo'
            ];
        }
    }
    
    /**
     * Eliminar equipo
     * 
     * @param int $equipmentId ID del equipo
     * @return array Respuesta estándar
     */
    public function delete($equipmentId) {
        try {
            // Validar que existe
            $equipment = $this->equipmentModel->getById($equipmentId);
            if (!$equipment) {
                return [
                    'success' => false,
                    'message' => 'Equipo no encontrado'
                ];
            }
            
            // Eliminar
            $this->equipmentModel->delete($equipmentId);
            
            return [
                'success' => true,
                'message' => 'Equipo eliminado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::delete - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar equipo'
            ];
        }
    }
    
    /**
     * Obtener un equipo con relaciones
     * 
     * @param int $equipmentId ID del equipo
     * @return array Respuesta estándar
     */
    public function get($equipmentId) {
        try {
            $equipment = $this->equipmentModel->getWithRelations($equipmentId);
            
            if (!$equipment) {
                return [
                    'success' => false,
                    'message' => 'Equipo no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => $equipment
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::get - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener equipo'
            ];
        }
    }
    
    /**
     * Listar equipos con filtros opcionales
     * 
     * @param array $filters Filtros (status, category_id, location_id, assigned_to)
     * @return array Respuesta estándar
     */
    public function list($filters = []) {
        try {
            $equipment = $this->equipmentModel->listWithFilters($filters);
            
            return [
                'success' => true,
                'data' => $equipment
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::list - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al listar equipos'
            ];
        }
    }
    
    /**
     * Buscar equipos
     * 
     * @param string $search Término de búsqueda
     * @return array Respuesta estándar
     */
    public function search($search) {
        // Validar entrada
        $search = trim($search);
        if (strlen($search) < 2) {
            return [
                'success' => false,
                'message' => 'Búsqueda debe tener al menos 2 caracteres',
                'errors' => ['Término de búsqueda muy corto']
            ];
        }
        
        try {
            $results = $this->equipmentModel->search($search);
            
            return [
                'success' => true,
                'data' => $results
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::search - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la búsqueda'
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
            $stats = $this->equipmentModel->getStatistics();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::getStatistics - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ];
        }
    }
    
    /**
     * Cambiar estado del equipo
     * 
     * @param int $equipmentId ID del equipo
     * @param string $newStatus Nuevo estado
     * @return array Respuesta estándar
     */
    public function changeStatus($equipmentId, $newStatus) {
        // Validar estado
        $validStates = ['active', 'inactive', 'maintenance', 'retired'];
        if (!in_array($newStatus, $validStates)) {
            return [
                'success' => false,
                'message' => 'Estado inválido',
                'errors' => ["Estados válidos: " . implode(', ', $validStates)]
            ];
        }
        
        try {
            // Validar que existe
            if (!$this->equipmentModel->getById($equipmentId)) {
                return [
                    'success' => false,
                    'message' => 'Equipo no encontrado'
                ];
            }
            
            // Cambiar estado
            $this->equipmentModel->changeStatus($equipmentId, $newStatus);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::changeStatus - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cambiar estado'
            ];
        }
    }
    
    /**
     * Asignar equipo a usuario
     * 
     * @param int $equipmentId ID del equipo
     * @param int|null $userId ID del usuario (null para desasignar)
     * @return array Respuesta estándar
     */
    public function assignToUser($equipmentId, $userId = null) {
        try {
            // Validar que existe equipo
            if (!$this->equipmentModel->getById($equipmentId)) {
                return [
                    'success' => false,
                    'message' => 'Equipo no encontrado'
                ];
            }
            
            // Asignar
            $this->equipmentModel->assignToUser($equipmentId, $userId);
            
            return [
                'success' => true,
                'message' => $userId ? 'Equipo asignado' : 'Equipo desasignado'
            ];
            
        } catch (Exception $e) {
            error_log("EquipmentController::assignToUser - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al asignar equipo'
            ];
        }
    }
    
    /**
     * Validar entrada de equipo
     * 
     * @param array $input Datos a validar
     * @param bool $isUpdate Si es actualización (campos opcionales)
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateEquipmentInput($input, $isUpdate = false) {
        $errors = [];
        
        // En creación, estos campos son requeridos
        if (!$isUpdate) {
            if (empty($input['name'])) {
                $errors[] = 'Nombre de equipo requerido';
            } elseif (strlen($input['name']) < 3) {
                $errors[] = 'Nombre debe tener al menos 3 caracteres';
            }
            
            if (empty($input['category_id'])) {
                $errors[] = 'Categoría requerida';
            }
            
            if (empty($input['purchase_price'])) {
                $errors[] = 'Precio de compra requerido';
            } elseif (!is_numeric($input['purchase_price']) || $input['purchase_price'] < 0) {
                $errors[] = 'Precio debe ser un número positivo';
            }
        } else {
            // En actualización, campos opcionales pero si se envían deben ser válidos
            if (!empty($input['name']) && strlen($input['name']) < 3) {
                $errors[] = 'Nombre debe tener al menos 3 caracteres';
            }
            
            if (!empty($input['category_id']) && !is_numeric($input['category_id'])) {
                $errors[] = 'Category ID debe ser numérico';
            }
            
            if (!empty($input['purchase_price'])) {
                if (!is_numeric($input['purchase_price']) || $input['purchase_price'] < 0) {
                    $errors[] = 'Precio debe ser un número positivo';
                }
            }
        }
        
        // Validaciones de fecha
        if (!empty($input['purchase_date'])) {
            if (!$this->isValidDate($input['purchase_date'])) {
                $errors[] = 'Fecha de compra inválida (formato: YYYY-MM-DD)';
            }
        }
        
        if (!empty($input['warranty_expiry'])) {
            if (!$this->isValidDate($input['warranty_expiry'])) {
                $errors[] = 'Fecha de garantía inválida (formato: YYYY-MM-DD)';
            }
        }
        
        // Validar estado si se envía
        if (!empty($input['status'])) {
            $validStates = ['active', 'inactive', 'maintenance', 'retired'];
            if (!in_array($input['status'], $validStates)) {
                $errors[] = 'Estado inválido. Estados válidos: ' . implode(', ', $validStates);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validar formato de fecha
     * 
     * @param string $date Fecha a validar
     * @return bool
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
