<?php
/**
 * Equipment Model - Gestión de equipos del sistema
 * 
 * Maneja equipos y sus relaciones con otras entidades
 * (recepciones, entregas, mantenimiento, etc.)
 */

require_once __DIR__ . '/DataStore.php';

class Equipment extends DataStore {
    
    public function __construct() {
        parent::__construct('equipment');
    }
    
    /**
     * Guardar equipo (crear o actualizar)
     * @param array $data Datos del equipo
     * @return int|bool ID si creado, true si actualizado, false si error
     */
    public function save($data) {
        // Campos permitidos para equipment
        $allowed_fields = [
            'asset_tag', 'name', 'description', 'model', 'serial_number',
            'category_id', 'supplier_id', 'purchase_date', 'purchase_price',
            'status', 'location_id', 'assigned_to', 'notes'
        ];
        
        $filtered_data = array_intersect_key($data, array_flip($allowed_fields));
        
        if (empty($filtered_data)) {
            error_log("EQUIPMENT SAVE: No valid fields provided");
            return false;
        }
        
        // Si hay ID, es actualización
        if (isset($data['id'])) {
            $id = (int)$data['id'];
            return $this->update($filtered_data, $id);
        }
        
        // Crear nuevo equipo
        if (!isset($data['asset_tag'])) {
            $data['asset_tag'] = $this->generateAssetTag();
        }
        
        return $this->insert($filtered_data);
    }
    
    /**
     * Generar asset tag único
     * @return string
     */
    public function generateAssetTag() {
        $prefix = 'ACT';
        $timestamp = time();
        $random = rand(1000, 9999);
        return "{$prefix}-{$timestamp}-{$random}";
    }
    
    /**
     * Obtener equipo con todas sus relaciones
     * @param int $id ID del equipo
     * @return array|null
     */
    public function getWithRelations($id) {
        $equipment = $this->getById($id);
        
        if (!$equipment) {
            return null;
        }
        
        // Cargar categoría
        if ($equipment['category_id']) {
            $equipment['category'] = $this->loadRelation(
                'categories',
                $equipment['category_id']
            );
        }
        
        // Cargar proveedor
        if ($equipment['supplier_id']) {
            $equipment['supplier'] = $this->loadRelation(
                'suppliers',
                $equipment['supplier_id']
            );
        }
        
        // Cargar ubicación
        if ($equipment['location_id']) {
            $equipment['location'] = $this->loadRelation(
                'locations',
                $equipment['location_id']
            );
        }
        
        // Cargar asignado a (usuario)
        if ($equipment['assigned_to']) {
            $equipment['assigned_user'] = $this->loadRelation(
                'users',
                $equipment['assigned_to']
            );
        }
        
        return $equipment;
    }
    
    /**
     * Cargar relación (helper)
     * @param string $table Tabla relacionada
     * @param int $id ID del registro
     * @return array|null
     */
    private function loadRelation($table, $id) {
        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Listar equipos con filtros opcionales
     * @param array $filters ['status' => string, 'category_id' => int, 'location_id' => int]
     * @return array
     */
    public function listWithFilters($filters = []) {
        $sql = "SELECT e.*, 
                       c.name as category_name,
                       s.company_name as supplier_name,
                       l.name as location_name,
                       u.firstname, u.lastname
                FROM equipment e
                LEFT JOIN categories c ON e.category_id = c.id
                LEFT JOIN suppliers s ON e.supplier_id = s.id
                LEFT JOIN locations l ON e.location_id = l.id
                LEFT JOIN users u ON e.assigned_to = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND e.category_id = :category_id";
            $params[':category_id'] = (int)$filters['category_id'];
        }
        
        if (!empty($filters['location_id'])) {
            $sql .= " AND e.location_id = :location_id";
            $params[':location_id'] = (int)$filters['location_id'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND e.assigned_to = :assigned_to";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        
        $sql .= " ORDER BY e.asset_tag ASC";
        
        $stmt = $this->query($sql, $params);
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar equipos
     * @param string $search Término de búsqueda
     * @return array
     */
    public function search($search) {
        $search = "%{$search}%";
        
        $sql = "SELECT e.*, 
                       c.name as category_name,
                       s.company_name as supplier_name
                FROM equipment e
                LEFT JOIN categories c ON e.category_id = c.id
                LEFT JOIN suppliers s ON e.supplier_id = s.id
                WHERE e.asset_tag LIKE :search
                   OR e.name LIKE :search
                   OR e.serial_number LIKE :search
                   OR c.name LIKE :search
                ORDER BY e.asset_tag ASC";
        
        $stmt = $this->query($sql, [':search' => $search]);
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener equipos por categoría
     * @param int $categoryId
     * @return array
     */
    public function getByCategory($categoryId) {
        $sql = "SELECT * FROM equipment WHERE category_id = :category_id ORDER BY asset_tag ASC";
        
        $stmt = $this->query($sql, [':category_id' => (int)$categoryId]);
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener equipos por ubicación
     * @param int $locationId
     * @return array
     */
    public function getByLocation($locationId) {
        $sql = "SELECT * FROM equipment WHERE location_id = :location_id ORDER BY asset_tag ASC";
        
        $stmt = $this->query($sql, [':location_id' => (int)$locationId]);
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener equipos asignados a usuario
     * @param int $userId
     * @return array
     */
    public function getAssignedTo($userId) {
        $sql = "SELECT * FROM equipment WHERE assigned_to = :user_id ORDER BY asset_tag ASC";
        
        $stmt = $this->query($sql, [':user_id' => (int)$userId]);
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener equipos por estado
     * @param string $status Estado del equipo
     * @return array
     */
    public function getByStatus($status) {
        $sql = "SELECT * FROM equipment WHERE status = :status ORDER BY asset_tag ASC";
        
        $stmt = $this->query($sql, [':status' => $status]);
        if ($stmt === false) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Contar equipos por estado
     * @param string $status
     * @return int
     */
    public function countByStatus($status) {
        return $this->count("status = '{$status}'");
    }
    
    /**
     * Obtener estadísticas de equipos
     * @return array
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN status = 'retired' THEN 1 ELSE 0 END) as retired,
                    SUM(CASE WHEN assigned_to IS NOT NULL THEN 1 ELSE 0 END) as assigned,
                    SUM(CAST(purchase_price AS DECIMAL(10,2))) as total_value
                FROM equipment";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cambiar estado de equipo
     * @param int $id ID del equipo
     * @param string $status Nuevo estado
     * @return bool
     */
    public function changeStatus($id, $status) {
        return $this->update(['status' => $status], $id);
    }
    
    /**
     * Asignar equipo a usuario
     * @param int $equipmentId ID del equipo
     * @param int $userId ID del usuario (null para desasignar)
     * @return bool
     */
    public function assignToUser($equipmentId, $userId = null) {
        return $this->update(['assigned_to' => $userId], $equipmentId);
    }
    
    /**
     * Mover equipo a ubicación
     * @param int $equipmentId ID del equipo
     * @param int $locationId ID de la ubicación
     * @return bool
     */
    public function moveToLocation($equipmentId, $locationId) {
        return $this->update(['location_id' => $locationId], $equipmentId);
    }
}
