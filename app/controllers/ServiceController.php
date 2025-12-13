<?php
/**
 * app/controllers/ServiceController.php - Controller para Servicios
 */
class ServiceController {
    private $model;
    
    public function __construct() {
        $this->model = new Service();
    }
    
    public function create($input) {
        if (empty($input['name'])) {
            return ['success' => false, 'errors' => ['Nombre requerido']];
        }
        if ($this->model->nameExists($input['name'])) {
            return ['success' => false, 'message' => 'El nombre ya existe'];
        }
        $id = $this->model->save([
            'name' => trim($input['name']),
            'description' => trim($input['description'] ?? ''),
            'cost' => (float)($input['cost'] ?? 0),
            'active' => 1,
            'created_by' => $_SESSION['login_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return ['success' => true, 'data' => ['id' => $id]];
    }
    
    public function update($id, $input) {
        if (empty($id) || empty($input['name'])) {
            return ['success' => false, 'message' => 'Datos requeridos'];
        }
        if ($this->model->nameExists($input['name'], $id)) {
            return ['success' => false, 'message' => 'El nombre ya existe'];
        }
        $this->model->update($id, [
            'name' => trim($input['name']),
            'description' => trim($input['description'] ?? ''),
            'cost' => (float)($input['cost'] ?? 0),
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
        $r = $this->model->getById($id);
        return $r ? ['success' => true, 'data' => $r] : ['success' => false, 'message' => 'No encontrado'];
    }
    
    public function list() {
        return ['success' => true, 'data' => $this->model->getAll()];
    }
    
    public function listActive() {
        return ['success' => true, 'data' => $this->model->getActive()];
    }
    
    public function search($q) {
        return ['success' => true, 'data' => $this->model->search($q)];
    }
    
    public function getTicketCount($id) {
        return ['success' => true, 'data' => $this->model->getTicketCount($id)];
    }
    
    public function getStatistics() {
        return ['success' => true, 'data' => $this->model->getStatistics()];
    }
    
    public function toggle($id) {
        $this->model->toggleActive($id);
        return ['success' => true, 'message' => 'Actualizado'];
    }
}
