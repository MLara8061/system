<?php
/**
 * app/controllers/CustomerController.php
 * Controller de negocio para Customer
 * 
 * Responsabilidades:
 * - Validación de entrada
 * - Lógica de negocio
 * - Delegación a Model para acceso a datos
 */

require_once dirname(__DIR__) . '/models/Customer.php';

class CustomerController {
    
    private $customerModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->customerModel = new Customer();
    }
    
    /**
     * Crear nuevo cliente
     * 
     * @param array $input Datos del cliente
     * @return array Respuesta estándar
     */
    public function create($input) {
        // Validar entrada
        $validation = $this->validateCustomerInput($input, false);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            // Verificar email único
            if ($this->customerModel->emailExists($input['email'])) {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado',
                    'errors' => ['Email duplicado']
                ];
            }
            
            // Preparar datos
            $data = [
                'company_name' => trim($input['company_name']),
                'contact_name' => trim($input['contact_name'] ?? ''),
                'email' => strtolower(trim($input['email'])),
                'phone' => trim($input['phone'] ?? ''),
                'address' => trim($input['address'] ?? ''),
                'city' => trim($input['city'] ?? ''),
                'country' => trim($input['country'] ?? ''),
                'status' => $input['status'] ?? 'active',
                'notes' => trim($input['notes'] ?? ''),
                'created_by' => $_SESSION['login_id'] ?? null
            ];
            
            // Crear cliente
            $customerId = $this->customerModel->save($data);
            
            return [
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => ['id' => $customerId]
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::create - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear cliente'
            ];
        }
    }
    
    /**
     * Actualizar cliente existente
     * 
     * @param int $customerId ID del cliente
     * @param array $input Datos a actualizar
     * @return array Respuesta estándar
     */
    public function update($customerId, $input) {
        // Validar que existe
        $existing = $this->customerModel->getById($customerId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => 'Cliente no encontrado'
            ];
        }
        
        // Validar entrada
        $validation = $this->validateCustomerInput($input, true);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            // Verificar email único (excluyendo este cliente)
            if (!empty($input['email']) && $input['email'] !== $existing['email']) {
                if ($this->customerModel->emailExists($input['email'], $customerId)) {
                    return [
                        'success' => false,
                        'message' => 'El email ya está registrado',
                        'errors' => ['Email duplicado']
                    ];
                }
            }
            
            // Preparar datos (solo campos no vacíos)
            $data = [];
            
            if (!empty($input['company_name'])) {
                $data['company_name'] = trim($input['company_name']);
            }
            if (isset($input['contact_name'])) {
                $data['contact_name'] = trim($input['contact_name']);
            }
            if (!empty($input['email'])) {
                $data['email'] = strtolower(trim($input['email']));
            }
            if (isset($input['phone'])) {
                $data['phone'] = trim($input['phone']);
            }
            if (isset($input['address'])) {
                $data['address'] = trim($input['address']);
            }
            if (isset($input['city'])) {
                $data['city'] = trim($input['city']);
            }
            if (isset($input['country'])) {
                $data['country'] = trim($input['country']);
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
                $this->customerModel->update($customerId, $data);
            }
            
            return [
                'success' => true,
                'message' => 'Cliente actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::update - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar cliente'
            ];
        }
    }
    
    /**
     * Eliminar cliente
     * 
     * @param int $customerId ID del cliente
     * @return array Respuesta estándar
     */
    public function delete($customerId) {
        try {
            // Validar que existe
            $customer = $this->customerModel->getById($customerId);
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ];
            }
            
            // Eliminar
            $this->customerModel->delete($customerId);
            
            return [
                'success' => true,
                'message' => 'Cliente eliminado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::delete - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar cliente'
            ];
        }
    }
    
    /**
     * Obtener un cliente
     * 
     * @param int $customerId ID del cliente
     * @return array Respuesta estándar
     */
    public function get($customerId) {
        try {
            $customer = $this->customerModel->getWithDetails($customerId);
            
            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => $customer
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::get - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener cliente'
            ];
        }
    }
    
    /**
     * Listar clientes con filtros opcionales
     * 
     * @param array $filters Filtros (status, city, country, limit, offset)
     * @return array Respuesta estándar
     */
    public function list($filters = []) {
        try {
            $customers = $this->customerModel->listWithFilters($filters);
            
            return [
                'success' => true,
                'data' => $customers
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::list - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al listar clientes'
            ];
        }
    }
    
    /**
     * Buscar clientes
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
            $results = $this->customerModel->search($search);
            
            return [
                'success' => true,
                'data' => $results
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::search - " . $e->getMessage());
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
            $stats = $this->customerModel->getStatistics();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::getStatistics - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ];
        }
    }
    
    /**
     * Cambiar estado del cliente
     * 
     * @param int $customerId ID del cliente
     * @param string $newStatus Nuevo estado
     * @return array Respuesta estándar
     */
    public function changeStatus($customerId, $newStatus) {
        // Validar estado
        $validStates = ['active', 'inactive', 'suspended'];
        if (!in_array($newStatus, $validStates)) {
            return [
                'success' => false,
                'message' => 'Estado inválido',
                'errors' => ["Estados válidos: " . implode(', ', $validStates)]
            ];
        }
        
        try {
            // Validar que existe
            if (!$this->customerModel->getById($customerId)) {
                return [
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ];
            }
            
            // Cambiar estado
            $this->customerModel->changeStatus($customerId, $newStatus);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("CustomerController::changeStatus - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cambiar estado'
            ];
        }
    }
    
    /**
     * Validar entrada de cliente
     * 
     * @param array $input Datos a validar
     * @param bool $isUpdate Si es actualización (campos opcionales)
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateCustomerInput($input, $isUpdate = false) {
        $errors = [];
        
        // En creación, estos campos son requeridos
        if (!$isUpdate) {
            if (empty($input['company_name'])) {
                $errors[] = 'Nombre de empresa requerido';
            } elseif (strlen($input['company_name']) < 3) {
                $errors[] = 'Nombre de empresa debe tener al menos 3 caracteres';
            }
            
            if (empty($input['email'])) {
                $errors[] = 'Email requerido';
            } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email inválido';
            }
        } else {
            // En actualización, campos opcionales pero si se envían deben ser válidos
            if (!empty($input['company_name']) && strlen($input['company_name']) < 3) {
                $errors[] = 'Nombre de empresa debe tener al menos 3 caracteres';
            }
            
            if (!empty($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email inválido';
                }
            }
        }
        
        // Validar contact_name si se envía
        if (!empty($input['contact_name']) && strlen($input['contact_name']) < 2) {
            $errors[] = 'Nombre de contacto debe tener al menos 2 caracteres';
        }
        
        // Validar estado si se envía
        if (!empty($input['status'])) {
            $validStates = ['active', 'inactive', 'suspended'];
            if (!in_array($input['status'], $validStates)) {
                $errors[] = 'Estado inválido. Estados válidos: ' . implode(', ', $validStates);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
