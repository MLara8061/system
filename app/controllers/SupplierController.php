<?php
/**
 * app/controllers/SupplierController.php - Simple CRUD Controller
 */
require_once dirname(__DIR__) . '/models/Supplier.php';

class SupplierController {
    private $model;
    
    public function __construct() {
        $this->model = new Supplier();
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
                'contact_person' => trim($input['contact_person'] ?? ''),
                'email' => strtolower(trim($input['email'] ?? '')),
                'phone' => trim($input['phone'] ?? ''),
                'address' => trim($input['address'] ?? ''),
                'city' => trim($input['city'] ?? ''),
                'country' => trim($input['country'] ?? ''),
                'active' => 1,
                'created_by' => $_SESSION['login_id'] ?? null
            ]);
            return ['success' => true, 'message' => 'Proveedor creado', 'data' => ['id' => $id]];
        } catch (Exception $e) {
            error_log("SupplierController::create - " . $e->getMessage());
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
            if (isset($input['contact_person'])) $data['contact_person'] = trim($input['contact_person']);
            if (isset($input['email'])) $data['email'] = strtolower(trim($input['email']));
            if (isset($input['phone'])) $data['phone'] = trim($input['phone']);
            if (isset($input['address'])) $data['address'] = trim($input['address']);
            if (isset($input['city'])) $data['city'] = trim($input['city']);
            if (isset($input['country'])) $data['country'] = trim($input['country']);
            if (!empty($data)) $this->model->update($id, $data);
            return ['success' => true, 'message' => 'Actualizado'];
        } catch (Exception $e) {
            error_log("SupplierController::update - " . $e->getMessage());
            return ['success' => false, 'message' => 'Error'];
        }
    }
    
    public function delete($id) {
        if (!$this->model->getById($id)) return ['success' => false, 'message' => 'No encontrado'];
        try {
            $this->model->delete($id);
            return ['success' => true, 'message' => 'Eliminado'];
        } catch (Exception $e) {
            error_log("SupplierController::delete - " . $e->getMessage());
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
