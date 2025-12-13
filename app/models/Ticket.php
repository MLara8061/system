<?php
/**
 * app/models/Ticket.php
 * Model de dominio para Tickets (Soporte/Reportes de Equipos)
 * 
 * Modelo avanzado con relaciones, filtrado y estadísticas
 */

require_once dirname(__FILE__) . '/DataStore.php';

class Ticket extends DataStore {
    
    /**
     * Constructor - Inicializar con tabla 'tickets'
     */
    public function __construct() {
        parent::__construct('tickets');
    }
    
    /**
     * Guardar ticket (crear o actualizar)
     * 
     * @param array $data Datos del ticket
     * @return int ID del ticket
     */
    public function save($data) {
        try {
            if (empty($data['title'])) {
                throw new Exception('Título del ticket requerido');
            }
            
            // Auto-generar ticket_number si no existe
            if (!isset($data['ticket_number']) && empty($data['id'])) {
                $data['ticket_number'] = $this->generateTicketNumber();
            }
            
            if (!empty($data['id'])) {
                $this->update($data['id'], $data);
                return $data['id'];
            }
            
            return $this->insert($data);
            
        } catch (Exception $e) {
            error_log("Ticket::save - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generar número único de ticket
     * 
     * @return string Ticket number (TKT-YYYYMMDD-XXXX)
     */
    private function generateTicketNumber() {
        try {
            $date = date('Ymd');
            // Obtener último número del día
            $sql = 'SELECT COUNT(*) as count FROM tickets WHERE DATE(created_at) = CURDATE()';
            $result = $this->query($sql, []);
            $count = ($result ? intval($result[0]['count']) : 0) + 1;
            
            return 'TKT-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            
        } catch (Exception $e) {
            return 'TKT-' . date('Ymd') . '-' . rand(1000, 9999);
        }
    }
    
    /**
     * Obtener ticket con relaciones (usuario, categoría, equipos)
     * 
     * @param int $ticketId ID del ticket
     * @return array|null Ticket con relaciones
     */
    public function getWithRelations($ticketId) {
        try {
            $ticket = $this->getById($ticketId);
            if (!$ticket) {
                return null;
            }
            
            // Cargar usuario creador
            if (!empty($ticket['created_by'])) {
                $userSql = 'SELECT id, username, firstname, lastname FROM users WHERE id = ?';
                $user = $this->query($userSql, [$ticket['created_by']]);
                if ($user) {
                    $ticket['creator'] = $user[0];
                }
            }
            
            // Cargar asignado a
            if (!empty($ticket['assigned_to'])) {
                $userSql = 'SELECT id, username, firstname, lastname FROM users WHERE id = ?';
                $user = $this->query($userSql, [$ticket['assigned_to']]);
                if ($user) {
                    $ticket['assigned_user'] = $user[0];
                }
            }
            
            // Cargar categoría
            if (!empty($ticket['category_id'])) {
                $catSql = 'SELECT id, name FROM categories WHERE id = ?';
                $category = $this->query($catSql, [$ticket['category_id']]);
                if ($category) {
                    $ticket['category'] = $category[0];
                }
            }
            
            // Cargar equipos relacionados si aplica
            if (!empty($ticket['equipment_id'])) {
                $eqSql = 'SELECT id, asset_tag, name FROM equipment WHERE id = ?';
                $equipment = $this->query($eqSql, [$ticket['equipment_id']]);
                if ($equipment) {
                    $ticket['equipment'] = $equipment[0];
                }
            }
            
            return $ticket;
            
        } catch (Exception $e) {
            error_log("Ticket::getWithRelations - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Listar tickets con filtros avanzados
     * 
     * @param array $filters Filtros (status, priority, assigned_to, created_by, category_id, limit, offset)
     * @return array Tickets
     */
    public function listWithFilters($filters = []) {
        try {
            $sql = 'SELECT * FROM tickets WHERE 1=1';
            $params = [];
            
            // Filtro por estado
            if (!empty($filters['status'])) {
                $sql .= ' AND status = ?';
                $params[] = $filters['status'];
            }
            
            // Filtro por prioridad
            if (!empty($filters['priority'])) {
                $sql .= ' AND priority = ?';
                $params[] = $filters['priority'];
            }
            
            // Filtro por asignado a
            if (!empty($filters['assigned_to'])) {
                $sql .= ' AND assigned_to = ?';
                $params[] = $filters['assigned_to'];
            }
            
            // Filtro por creado por
            if (!empty($filters['created_by'])) {
                $sql .= ' AND created_by = ?';
                $params[] = $filters['created_by'];
            }
            
            // Filtro por categoría
            if (!empty($filters['category_id'])) {
                $sql .= ' AND category_id = ?';
                $params[] = $filters['category_id'];
            }
            
            $sql .= ' ORDER BY created_at DESC';
            
            // Paginación
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;
            
            return $this->query($sql, $params) ?: [];
            
        } catch (Exception $e) {
            error_log("Ticket::listWithFilters - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar tickets
     * Busca en: ticket_number, title, description
     * 
     * @param string $search Término de búsqueda
     * @return array Tickets coincidentes
     */
    public function search($search) {
        try {
            $search = '%' . trim($search) . '%';
            
            $sql = 'SELECT * FROM tickets 
                    WHERE ticket_number LIKE ? 
                    OR title LIKE ? 
                    OR description LIKE ?
                    ORDER BY created_at DESC
                    LIMIT 50';
            
            $params = [$search, $search, $search];
            return $this->query($sql, $params) ?: [];
            
        } catch (Exception $e) {
            error_log("Ticket::search - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tickets asignados a un usuario
     * 
     * @param int $userId ID del usuario
     * @param string $status Estado opcional
     * @return array Tickets
     */
    public function getAssignedTo($userId, $status = null) {
        try {
            $sql = 'SELECT * FROM tickets WHERE assigned_to = ?';
            $params = [$userId];
            
            if ($status) {
                $sql .= ' AND status = ?';
                $params[] = $status;
            }
            
            $sql .= ' ORDER BY priority DESC, created_at DESC';
            return $this->query($sql, $params) ?: [];
            
        } catch (Exception $e) {
            error_log("Ticket::getAssignedTo - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tickets creados por un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Tickets
     */
    public function getCreatedBy($userId) {
        try {
            $sql = 'SELECT * FROM tickets WHERE created_by = ? ORDER BY created_at DESC';
            return $this->query($sql, [$userId]) ?: [];
        } catch (Exception $e) {
            error_log("Ticket::getCreatedBy - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cambiar estado del ticket
     * 
     * @param int $ticketId ID del ticket
     * @param string $newStatus Nuevo estado
     * @return bool
     */
    public function changeStatus($ticketId, $newStatus) {
        try {
            $validStates = ['open', 'in_progress', 'resolved', 'closed', 'on_hold'];
            if (!in_array($newStatus, $validStates)) {
                throw new Exception("Estado inválido: {$newStatus}");
            }
            
            $this->update($ticketId, ['status' => $newStatus]);
            return true;
            
        } catch (Exception $e) {
            error_log("Ticket::changeStatus - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Asignar ticket a un usuario
     * 
     * @param int $ticketId ID del ticket
     * @param int|null $userId ID del usuario (null para desasignar)
     * @return bool
     */
    public function assignTo($ticketId, $userId) {
        try {
            $this->update($ticketId, ['assigned_to' => $userId]);
            return true;
        } catch (Exception $e) {
            error_log("Ticket::assignTo - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de tickets
     * 
     * @return array Stats (total, open, in_progress, resolved, closed, by_priority)
     */
    public function getStatistics() {
        try {
            $sql = 'SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN status = "on_hold" THEN 1 ELSE 0 END) as on_hold
                    FROM tickets';
            
            $result = $this->query($sql, []);
            $stats = $result ? $result[0] : [];
            
            // Contar por prioridad
            $priSql = 'SELECT priority, COUNT(*) as count 
                       FROM tickets 
                       WHERE status != "closed"
                       GROUP BY priority';
            $byPriority = $this->query($priSql, []);
            $stats['by_priority'] = $byPriority ?: [];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Ticket::getStatistics - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener promedio de tiempo de resolución
     * 
     * @return array Stats
     */
    public function getResolutionStats() {
        try {
            $sql = 'SELECT 
                    COUNT(*) as total_closed,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours
                    FROM tickets 
                    WHERE status = "closed" AND resolved_at IS NOT NULL';
            
            $result = $this->query($sql, []);
            return $result ? $result[0] : ['total_closed' => 0, 'avg_hours' => 0];
            
        } catch (Exception $e) {
            error_log("Ticket::getResolutionStats - " . $e->getMessage());
            return [];
        }
    }
}
