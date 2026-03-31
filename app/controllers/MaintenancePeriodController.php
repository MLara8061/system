<?php
/**
 * app/controllers/MaintenancePeriodController.php
 */
require_once dirname(__DIR__) . '/models/MaintenancePeriod.php';

class MaintenancePeriodController {

    private MaintenancePeriod $model;

    public function __construct() {
        $this->model = new MaintenancePeriod();
    }

    public function list(): array {
        return ['success' => true, 'data' => $this->model->getAll('days_interval ASC')];
    }

    public function get(int $id): array {
        $item = $this->model->getById($id);
        return $item
            ? ['success' => true, 'data' => $item]
            : ['success' => false, 'message' => 'Periodo no encontrado'];
    }

    public function create(array $input): array {
        $name  = trim($input['name'] ?? '');
        $days  = (int)($input['days_interval'] ?? 0);

        if ($name === '') {
            return ['success' => false, 'message' => 'El nombre es requerido'];
        }
        if ($days <= 0) {
            return ['success' => false, 'message' => 'El intervalo en días debe ser mayor a 0'];
        }
        if ($this->model->nameExists($name)) {
            return ['success' => false, 'message' => 'Ya existe un periodo con ese nombre'];
        }

        $id = $this->model->insert(['name' => $name, 'days_interval' => $days]);
        return $id
            ? ['success' => true, 'message' => 'Periodo creado', 'data' => ['id' => $id]]
            : ['success' => false, 'message' => 'Error al crear el periodo'];
    }

    public function update(int $id, array $input): array {
        if (!$this->model->getById($id)) {
            return ['success' => false, 'message' => 'Periodo no encontrado'];
        }

        $data = [];

        if (isset($input['name'])) {
            $name = trim($input['name']);
            if ($name === '') {
                return ['success' => false, 'message' => 'El nombre no puede estar vacío'];
            }
            if ($this->model->nameExists($name, $id)) {
                return ['success' => false, 'message' => 'Ya existe un periodo con ese nombre'];
            }
            $data['name'] = $name;
        }

        if (isset($input['days_interval'])) {
            $days = (int)$input['days_interval'];
            if ($days <= 0) {
                return ['success' => false, 'message' => 'El intervalo en días debe ser mayor a 0'];
            }
            $data['days_interval'] = $days;
        }

        if (empty($data)) {
            return ['success' => false, 'message' => 'Sin datos para actualizar'];
        }

        $ok = $this->model->update($data, $id);
        return $ok
            ? ['success' => true, 'message' => 'Periodo actualizado']
            : ['success' => false, 'message' => 'Error al actualizar'];
    }

    public function delete(int $id): array {
        if (!$this->model->getById($id)) {
            return ['success' => false, 'message' => 'Periodo no encontrado'];
        }
        if ($this->model->isInUse($id)) {
            return ['success' => false, 'message' => 'No se puede eliminar: el periodo está asignado a uno o más equipos'];
        }

        $ok = $this->model->delete($id);
        return $ok
            ? ['success' => true, 'message' => 'Periodo eliminado']
            : ['success' => false, 'message' => 'Error al eliminar'];
    }
}
