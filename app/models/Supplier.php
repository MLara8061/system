<?php
/**
 * app/models/Supplier.php - Simple CRUD Model
 */
require_once dirname(__FILE__) . '/DataStore.php';

class Supplier extends DataStore {
    public function __construct() {
        parent::__construct('suppliers');
    }
    
    public function save($data) {
        try {
            if (empty($data['name'])) throw new Exception('Nombre requerido');
            if (!empty($data['id'])) {
                $this->update($data['id'], $data);
                return $data['id'];
            }
            return $this->insert($data);
        } catch (Exception $e) {
            error_log("Supplier::save - " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getByName($name) {
        return $this->findBy('name', trim($name), true);
    }
    
    public function getByEmail($email) {
        return $this->findBy('email', strtolower(trim($email)), true);
    }
    
    public function getActive() {
        return $this->findBy('active', 1);
    }
    
    public function search($search) {
        $search = '%' . trim($search) . '%';
        $sql = 'SELECT * FROM suppliers WHERE name LIKE ? OR contact_person LIKE ? OR email LIKE ? ORDER BY name ASC LIMIT 50';
        return $this->query($sql, [$search, $search, $search]) ?: [];
    }
    
    public function nameExists($name, $excludeId = null) {
        $sql = 'SELECT COUNT(*) as count FROM suppliers WHERE name = ?';
        $params = [trim($name)];
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $result = $this->query($sql, $params);
        return $result && $result[0]['count'] > 0;
    }
    
    public function getEquipmentCount($supplierId) {
        $sql = 'SELECT COUNT(*) as count FROM equipment WHERE supplier_id = ?';
        $result = $this->query($sql, [$supplierId]);
        return $result ? intval($result[0]['count']) : 0;
    }
    
    public function getByCountry($country) {
        return $this->findBy('country', trim($country));
    }
}
