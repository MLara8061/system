<?php
/**
 * app/controllers/LocationController.php - Simple CRUD Controller
 */
require_once dirname(__DIR__) . '/models/Location.php';

class LocationController {
    private $model;
    
    public function __construct() {
        $this->model = new Location();
    }
    
    public function create($input) {
        if (empty($input['name'])) {
            return ['success' => false, 'message' => 'Validación fallida', 'errors' => ['Nombre requerido']];
        }
        if ($this->model->nameExists($input['name'])) {
            return ['success' => false, 'message' => 'El nombre ya existe'];
        }
        try {
            $id = $this->model->save([
                'name' => trim($input['name']),
                'description' => trim($input['description'] ?? ''),
                'address' => trim($input['address'] ?? ''),
                'active' => 1,
                'created_by' => $_SESSION['login_id'] ?? null
            ]);
            return ['success' => true, 'message' => 'Ubicación creada', 'data' => ['id' => $id]];
        } catch (Exception $e) {
            error_log("LocationController::create - " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear'];
        }
    }
    
    public function update($id, $input) {
        if (!$this->model->getById($id)) return ['success' => false, 'message' => 'No encontrado'];
        if (!empty($input['name']) && $this->model->nameExists($input['name'], $id)) {
            return ['success' => false, 'message' => 'El nombre ya existe'];
        }
        try {
            $data = [];
            if (!empty($input['name'])) $data['name'] = trim($input['name']);
            if (isset($input['description'])) $data['description'] = trim($input['description']);
            if (isset($input['address'])) $data['address'] = trim($input['address']);
            if (!empty($data)) $this->model->update($id, $data);
            return ['success' => true, 'message' => 'Actualizado'];
        } catch (Exception $e) {
            error_log("LocationController::update - " . $e->getMessage());
            return ['success' => false, 'message' => 'Error'];
        }
    }
    
    public function delete($id) {
        if (!$this->model->getById($id)) return ['success' => false, 'message' => 'No encontrado'];
        try {
            $this->model->delete($id);
            return ['success' => true, 'message' => 'Eliminado'];
        } catch (Exception $e) {
            error_log("LocationController::delete - " . $e->getMessage());
            return ['success' => false, 'message' => 'Error'];
        }
    }
    
    public function get($id) {
        $item = $this->model->getById($id);
        return $item 
            ? ['success' => true, 'data' => $item]
            : ['success' => false, 'message' => 'No encontrado'];
    }
    
    public function list() {
        return ['success' => true, 'data' => $this->model->getAll('name ASC')];
    }
    
    public function listActive() {
        return ['success' => true, 'data' => $this->model->getActive()];
    }
    
    public function search($search) {
        $search = trim($search);
        if (strlen($search) < 2) return ['success' => false, 'message' => 'Búsqueda muy corta'];
        return ['success' => true, 'data' => $this->model->search($search)];
    }
    
    public function getEquipmentCount($id) {
        if (!$this->model->getById($id)) return ['success' => false, 'message' => 'No encontrado'];
        return ['success' => true, 'data' => ['count' => $this->model->getEquipmentCount($id)]];
    }
}
