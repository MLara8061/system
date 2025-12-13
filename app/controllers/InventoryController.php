<?php
/**
 * app/controllers/InventoryController.php - Controller para Inventario
 */
class InventoryController {
    private $model;
    
    public function __construct() {
        $this->model = new Inventory();
    }
    
    public function create($input) {
        if (empty($input['equipment_id']) || empty($input['location_id'])) {
            return ['success' => false, 'errors' => ['Equipment y Location requeridos']];
        }
        $id = $this->model->save([
            'equipment_id' => (int)$input['equipment_id'],
            'location_id' => (int)$input['location_id'],
            'responsible_id' => (int)($input['responsible_id'] ?? $_SESSION['login_id'] ?? 0),
            'status' => trim($input['status'] ?? 'pending'),
            'notes' => trim($input['notes'] ?? ''),
            'created_by' => $_SESSION['login_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return ['success' => true, 'data' => ['id' => $id]];
    }
    
    public function update($id, $input) {
        if (empty($id)) {
            return ['success' => false, 'message' => 'ID requerido'];
        }
        $this->model->update($id, [
            'status' => trim($input['status'] ?? 'pending'),
            'notes' => trim($input['notes'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        return ['success' => true, 'message' => 'Actualizado'];
    }
    
    public function delete($id) {
        return $this->model->delete($id) 
            ? ['success' => true, 'message' => 'Eliminado'] 
            : ['success' => false, 'message' => 'Error al eliminar'];
    }
    
    public function get($id) {
        $r = $this->model->getWithRelations($id);
        return $r ? ['success' => true, 'data' => $r] : ['success' => false, 'message' => 'No encontrado'];
    }
    
    public function list($limit = 50, $offset = 0) {
        return ['success' => true, 'data' => $this->model->listWithFilters([], $limit, $offset)];
    }
    
    public function listWithFilters($filters = [], $limit = 50, $offset = 0) {
        return ['success' => true, 'data' => $this->model->listWithFilters($filters, $limit, $offset)];
    }
    
    public function search($q) {
        return ['success' => true, 'data' => $this->model->search($q)];
    }
    
    public function getByEquipment($equipmentId) {
        return ['success' => true, 'data' => $this->model->getByEquipment($equipmentId)];
    }
    
    public function getByLocation($locationId) {
        return ['success' => true, 'data' => $this->model->getByLocation($locationId)];
    }
    
    public function getStatistics() {
        return ['success' => true, 'data' => $this->model->getStatistics()];
    }
}
