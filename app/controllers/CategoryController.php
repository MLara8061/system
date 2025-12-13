<?php
/**
 * app/controllers/CategoryController.php - Simple CRUD Controller
 */
require_once dirname(__DIR__) . '/models/Category.php';

class CategoryController {
    private $model;
    
    public function __construct() {
        $this->model = new Category();
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
                'active' => 1,
                'created_by' => $_SESSION['login_id'] ?? null
            ]);
            return ['success' => true, 'message' => 'Categoría creada', 'data' => ['id' => $id]];
        } catch (Exception $e) {
            error_log("CategoryController::create - " . $e->getMessage());
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
            if (!empty($data)) $this->model->update($id, $data);
            return ['success' => true, 'message' => 'Actualizado'];
        } catch (Exception $e) {
            error_log("CategoryController::update - " . $e->getMessage());
            return ['success' => false, 'message' => 'Error'];
        }
    }
    
    public function delete($id) {
        if (!$this->model->getById($id)) return ['success' => false, 'message' => 'No encontrado'];
        try {
            $this->model->delete($id);
            return ['success' => true, 'message' => 'Eliminado'];
        } catch (Exception $e) {
            error_log("CategoryController::delete - " . $e->getMessage());
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
}
