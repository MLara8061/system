<?php
/**
 * CustomFieldController - CRUD de definiciones de campos personalizados
 */

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

require_once ROOT . '/app/models/CustomField.php';

class CustomFieldController {

    private CustomField $model;

    public function __construct() {
        $this->model = new CustomField();
    }

    // -----------------------------------------------------------------------
    public function list(string $entityType = '', ?int $branchId = null): array {
        if ($entityType) {
            return $this->model->getForEntity($entityType, $branchId);
        }
        return $this->model->getAll('entity_type ASC, sort_order ASC, id ASC');
    }

    // -----------------------------------------------------------------------
    public function get(int $id): array {
        $row = $this->model->getById($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Campo no encontrado'];
        }
        return ['success' => true, 'data' => $row];
    }

    // -----------------------------------------------------------------------
    public function create(array $input): array {
        $entityType = $input['entity_type'] ?? '';
        $fieldName  = trim($input['field_name'] ?? '');
        $fieldLabel = trim($input['field_label'] ?? '');
        $fieldType  = $input['field_type'] ?? 'text';
        $branchId   = isset($input['branch_id']) && $input['branch_id'] !== '' ? (int)$input['branch_id'] : null;

        if (!$fieldName || !$fieldLabel || !$entityType) {
            return ['success' => false, 'message' => 'Nombre, etiqueta y tipo de entidad son requeridos'];
        }

        // Normalizar field_name: solo alfanumérico + guiones bajos, máx 100 chars
        $fieldName = preg_replace('/[^a-z0-9_]/', '_', strtolower($fieldName));
        $fieldName = substr($fieldName, 0, 100);

        if ($this->model->nameExists($entityType, $fieldName, $branchId)) {
            return ['success' => false, 'message' => 'Ya existe un campo con ese nombre para esta entidad/sucursal'];
        }

        $options = null;
        if ($fieldType === 'select' && !empty($input['options'])) {
            $opts = array_filter(array_map('trim', explode("\n", $input['options'])));
            $options = json_encode(array_values($opts), JSON_UNESCAPED_UNICODE);
        }

        $data = [
            'entity_type' => $entityType,
            'field_name'  => $fieldName,
            'field_label' => $fieldLabel,
            'field_type'  => $fieldType,
            'options'     => $options,
            'is_required' => isset($input['is_required']) ? 1 : 0,
            'sort_order'  => (int)($input['sort_order'] ?? 0),
            'active'      => 1,
            'branch_id'   => $branchId,
        ];

        $id = $this->model->insert($data);
        if (!$id) {
            return ['success' => false, 'message' => 'Error al crear el campo'];
        }
        return ['success' => true, 'message' => 'Campo creado correctamente', 'id' => $id];
    }

    // -----------------------------------------------------------------------
    public function update(int $id, array $input): array {
        $existing = $this->model->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Campo no encontrado'];
        }

        $fieldLabel = trim($input['field_label'] ?? $existing['field_label']);
        $fieldType  = $input['field_type'] ?? $existing['field_type'];

        $options = $existing['options'];
        if ($fieldType === 'select' && isset($input['options'])) {
            $opts = array_filter(array_map('trim', explode("\n", $input['options'])));
            $options = json_encode(array_values($opts), JSON_UNESCAPED_UNICODE);
        } elseif ($fieldType !== 'select') {
            $options = null;
        }

        $data = [
            'field_label' => $fieldLabel,
            'field_type'  => $fieldType,
            'options'     => $options,
            'is_required' => isset($input['is_required']) ? 1 : 0,
            'sort_order'  => (int)($input['sort_order'] ?? $existing['sort_order']),
            'active'      => isset($input['active']) ? (int)$input['active'] : (int)$existing['active'],
        ];

        $ok = $this->model->update($id, $data);
        if (!$ok) {
            return ['success' => false, 'message' => 'Error al actualizar'];
        }
        return ['success' => true, 'message' => 'Campo actualizado correctamente'];
    }

    // -----------------------------------------------------------------------
    public function delete(int $id): array {
        $existing = $this->model->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Campo no encontrado'];
        }
        $ok = $this->model->delete($id);
        if (!$ok) {
            return ['success' => false, 'message' => 'Error al eliminar'];
        }
        return ['success' => true, 'message' => 'Campo eliminado correctamente'];
    }

    // -----------------------------------------------------------------------
    public function saveValues(string $entityType, int $entityId, array $values): array {
        if ($entityId <= 0) {
            return ['success' => false, 'message' => 'ID de entidad inválido'];
        }
        try {
            $this->model->saveValues($entityType, $entityId, $values);
        } catch (Exception $e) {
            error_log('CF saveValues error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar valores'];
        }
        return ['success' => true];
    }
}
