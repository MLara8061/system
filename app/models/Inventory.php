<?php
/**
 * app/models/Inventory.php - Model para Inventario
 */
class Inventory extends DataStore {
    protected $table = 'inventory';
    protected $primaryKey = 'id';
    
    public function getWithRelations($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, 
                   e.asset_tag, e.name as equipment_name,
                   l.name as location_name,
                   u.name as user_name
            FROM {$this->table} i
            LEFT JOIN equipment e ON i.equipment_id = e.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN users u ON i.responsible_id = u.id
            WHERE i.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function listWithFilters($filters = [], $limit = 50, $offset = 0) {
        $q = "SELECT i.*, e.asset_tag, e.name as equipment_name, l.name as location_name FROM {$this->table} i
              LEFT JOIN equipment e ON i.equipment_id = e.id
              LEFT JOIN locations l ON i.location_id = l.id WHERE 1=1";
        $params = [];
        
        if (!empty($filters['equipment_id'])) {
            $q .= " AND i.equipment_id = ?";
            $params[] = $filters['equipment_id'];
        }
        if (!empty($filters['location_id'])) {
            $q .= " AND i.location_id = ?";
            $params[] = $filters['location_id'];
        }
        if (!empty($filters['status'])) {
            $q .= " AND i.status = ?";
            $params[] = $filters['status'];
        }
        
        $q .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($q);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function search($q) {
        $q = "%{$q}%";
        $stmt = $this->db->prepare(
            "SELECT i.*, e.asset_tag, e.name as equipment_name
             FROM {$this->table} i
             LEFT JOIN equipment e ON i.equipment_id = e.id
             WHERE e.asset_tag LIKE ? OR e.name LIKE ? OR i.notes LIKE ?
             ORDER BY i.created_at DESC LIMIT 50"
        );
        $stmt->execute([$q, $q, $q]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByEquipment($equipmentId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE equipment_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$equipmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByLocation($locationId) {
        $stmt = $this->db->prepare(
            "SELECT i.*, e.asset_tag, e.name FROM {$this->table} i
             LEFT JOIN equipment e ON i.equipment_id = e.id
             WHERE i.location_id = ? ORDER BY i.created_at DESC"
        );
        $stmt->execute([$locationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT id) as total_movements,
                COUNT(DISTINCT equipment_id) as equipments_moved,
                COUNT(DISTINCT CASE WHEN status = 'completed' THEN id END) as completed,
                COUNT(DISTINCT CASE WHEN status = 'pending' THEN id END) as pending
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
