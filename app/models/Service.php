<?php
/**
 * app/models/Service.php - Model para Servicios
 */
class Service extends DataStore {
    protected $table = 'services';
    protected $primaryKey = 'id';
    
    public function getByName($name) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function getActive() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE active = 1 ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function search($q) {
        $q = "%{$q}%";
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE name LIKE ? OR description LIKE ? 
             ORDER BY name ASC LIMIT 50"
        );
        $stmt->execute([$q, $q]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function nameExists($name, $excludeId = null) {
        $q = "SELECT COUNT(*) FROM {$this->table} WHERE name = ?";
        $params = [$name];
        if ($excludeId) {
            $q .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($q);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    public function getTicketCount($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE service_id = ? AND active = 1");
        $stmt->execute([$id]);
        return ['count' => (int)$stmt->fetchColumn()];
    }
    
    public function getStatistics() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT id) as total,
                COUNT(DISTINCT CASE WHEN active = 1 THEN id END) as active,
                COUNT(DISTINCT CASE WHEN active = 0 THEN id END) as inactive
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function toggleActive($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET active = NOT active WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
