<?php
/**
 * app/controllers/TicketController.php
 * Controller de negocio para Tickets
 * 
 * Modelo avanzado con validaciones complejas
 */

require_once dirname(__DIR__) . '/models/Ticket.php';

class TicketController {
    
    private $ticketModel;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ticketModel = new Ticket();
    }
    
    /**
     * Crear nuevo ticket
     * 
     * @param array $input Datos del ticket
     * @return array Respuesta estándar
     */
    public function create($input) {
        $validation = $this->validateInput($input, false);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            $data = [
                'title' => trim($input['title']),
                'description' => trim($input['description'] ?? ''),
                'priority' => $input['priority'] ?? 'medium',
                'status' => 'open',
                'category_id' => $input['category_id'] ?? null,
                'equipment_id' => $input['equipment_id'] ?? null,
                'customer_id' => $input['customer_id'] ?? null,
                'assigned_to' => $input['assigned_to'] ?? null,
                'created_by' => $_SESSION['login_id'] ?? null
            ];
            
            $ticketId = $this->ticketModel->save($data);
            $ticket = $this->ticketModel->getWithRelations($ticketId);
            
            return [
                'success' => true,
                'message' => 'Ticket creado exitosamente',
                'data' => [
                    'id' => $ticketId,
                    'ticket_number' => $ticket['ticket_number'] ?? null
                ]
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::create - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear ticket'
            ];
        }
    }
    
    /**
     * Actualizar ticket
     * 
     * @param int $ticketId ID del ticket
     * @param array $input Datos a actualizar
     * @return array Respuesta estándar
     */
    public function update($ticketId, $input) {
        if (!$this->ticketModel->getById($ticketId)) {
            return [
                'success' => false,
                'message' => 'Ticket no encontrado'
            ];
        }
        
        $validation = $this->validateInput($input, true);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validation['errors']
            ];
        }
        
        try {
            $data = [];
            
            if (!empty($input['title'])) {
                $data['title'] = trim($input['title']);
            }
            if (isset($input['description'])) {
                $data['description'] = trim($input['description']);
            }
            if (!empty($input['priority'])) {
                $data['priority'] = $input['priority'];
            }
            if (isset($input['category_id'])) {
                $data['category_id'] = $input['category_id'] ?: null;
            }
            if (isset($input['equipment_id'])) {
                $data['equipment_id'] = $input['equipment_id'] ?: null;
            }
            if (isset($input['customer_id'])) {
                $data['customer_id'] = $input['customer_id'] ?: null;
            }
            if (isset($input['assigned_to'])) {
                $data['assigned_to'] = $input['assigned_to'] ?: null;
            }
            
            if (!empty($data)) {
                $this->ticketModel->update($ticketId, $data);
            }
            
            return [
                'success' => true,
                'message' => 'Ticket actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::update - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar ticket'
            ];
        }
    }
    
    /**
     * Eliminar ticket
     * 
     * @param int $ticketId ID del ticket
     * @return array Respuesta estándar
     */
    public function delete($ticketId) {
        try {
            if (!$this->ticketModel->getById($ticketId)) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }
            
            $this->ticketModel->delete($ticketId);
            
            return [
                'success' => true,
                'message' => 'Ticket eliminado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::delete - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar ticket'
            ];
        }
    }
    
    /**
     * Obtener ticket con relaciones
     * 
     * @param int $ticketId ID del ticket
     * @return array Respuesta estándar
     */
    public function get($ticketId) {
        try {
            $ticket = $this->ticketModel->getWithRelations($ticketId);
            
            if (!$ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => $ticket
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::get - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener ticket'
            ];
        }
    }
    
    /**
     * Listar tickets con filtros
     * 
     * @param array $filters Filtros
     * @return array Respuesta estándar
     */
    public function list($filters = []) {
        try {
            $tickets = $this->ticketModel->listWithFilters($filters);
            
            return [
                'success' => true,
                'data' => $tickets
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::list - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al listar tickets'
            ];
        }
    }
    
    /**
     * Buscar tickets
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
            $results = $this->ticketModel->search($search);
            
            return [
                'success' => true,
                'data' => $results
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::search - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en la búsqueda'
            ];
        }
    }
    
    /**
     * Cambiar estado del ticket
     * 
     * @param int $ticketId ID del ticket
     * @param string $newStatus Nuevo estado
     * @return array Respuesta estándar
     */
    public function changeStatus($ticketId, $newStatus) {
        $validStates = ['open', 'in_progress', 'resolved', 'closed', 'on_hold'];
        if (!in_array($newStatus, $validStates)) {
            return [
                'success' => false,
                'message' => 'Estado inválido',
                'errors' => ["Estados válidos: " . implode(', ', $validStates)]
            ];
        }
        
        try {
            if (!$this->ticketModel->getById($ticketId)) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }
            
            $this->ticketModel->changeStatus($ticketId, $newStatus);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::changeStatus - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cambiar estado'
            ];
        }
    }
    
    /**
     * Asignar ticket a usuario
     * 
     * @param int $ticketId ID del ticket
     * @param int|null $userId ID del usuario (null para desasignar)
     * @return array Respuesta estándar
     */
    public function assignTo($ticketId, $userId = null) {
        try {
            if (!$this->ticketModel->getById($ticketId)) {
                return [
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ];
            }
            
            $this->ticketModel->assignTo($ticketId, $userId);
            
            return [
                'success' => true,
                'message' => $userId ? 'Ticket asignado' : 'Ticket desasignado'
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::assignTo - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al asignar ticket'
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
            $stats = $this->ticketModel->getStatistics();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::getStatistics - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ];
        }
    }
    
    /**
     * Obtener estadísticas de resolución
     * 
     * @return array Respuesta estándar
     */
    public function getResolutionStats() {
        try {
            $stats = $this->ticketModel->getResolutionStats();
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            error_log("TicketController::getResolutionStats - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ];
        }
    }
    
    /**
     * Validar entrada de ticket
     * 
     * @param array $input Datos a validar
     * @param bool $isUpdate Si es actualización
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateInput($input, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate) {
            if (empty($input['title'])) {
                $errors[] = 'Título requerido';
            } elseif (strlen($input['title']) < 5) {
                $errors[] = 'Título debe tener al menos 5 caracteres';
            }
            
            if (empty($input['description'])) {
                $errors[] = 'Descripción requerida';
            } elseif (strlen($input['description']) < 10) {
                $errors[] = 'Descripción debe tener al menos 10 caracteres';
            }
        } else {
            if (!empty($input['title']) && strlen($input['title']) < 5) {
                $errors[] = 'Título debe tener al menos 5 caracteres';
            }
            
            if (!empty($input['description']) && strlen($input['description']) < 10) {
                $errors[] = 'Descripción debe tener al menos 10 caracteres';
            }
        }
        
        // Validar prioridad si se envía
        if (!empty($input['priority'])) {
            $validPriorities = ['low', 'medium', 'high', 'critical'];
            if (!in_array($input['priority'], $validPriorities)) {
                $errors[] = 'Prioridad inválida';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
