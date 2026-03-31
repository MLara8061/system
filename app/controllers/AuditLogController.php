<?php
/**
 * app/controllers/AuditLogController.php
 * Controller para consulta y exportación de registros de auditoría
 */

require_once dirname(__DIR__) . '/models/AuditLog.php';

class AuditLogController
{
    private $model;

    public function __construct()
    {
        $this->model = new AuditLog();
    }

    /**
     * Listar registros con filtros y paginación
     */
    public function list($filters = [], $page = 1, $perPage = 50)
    {
        $result = $this->model->listFiltered($filters, $page, $perPage);

        return [
            'success' => true,
            'data'    => $result['data'],
            'total'   => $result['total'],
            'pages'   => $result['pages'],
            'page'    => $result['page'],
        ];
    }

    /**
     * Obtener detalle de un registro
     */
    public function get($id)
    {
        $record = $this->model->getById($id);
        if (!$record) {
            return ['success' => false, 'message' => 'Registro no encontrado'];
        }

        // Decodificar JSON para el detalle
        if ($record['old_values']) {
            $record['old_values'] = json_decode($record['old_values'], true);
        }
        if ($record['new_values']) {
            $record['new_values'] = json_decode($record['new_values'], true);
        }

        return ['success' => true, 'data' => $record];
    }

    /**
     * Obtener opciones de filtros (módulos, tablas, usuarios)
     */
    public function getFilterOptions()
    {
        return [
            'success' => true,
            'data'    => [
                'modules' => $this->model->getDistinctModules(),
                'tables'  => $this->model->getDistinctTables(),
                'users'   => $this->model->getDistinctUsers(),
                'actions' => ['create', 'update', 'delete', 'move', 'login', 'logout', 'export'],
            ],
        ];
    }

    /**
     * Exportar registros filtrados a formato array (para Excel)
     */
    public function export($filters = [])
    {
        $rows = $this->model->exportFiltered($filters);

        $exportData = [];
        foreach ($rows as $row) {
            $exportData[] = [
                'ID'          => $row['id'],
                'Fecha'       => $row['created_at'],
                'Usuario'     => $row['user_name'] ?? ('ID: ' . $row['user_id']),
                'Modulo'      => $row['module'],
                'Accion'      => $this->translateAction($row['action']),
                'Tabla'       => $row['table_name'],
                'Registro ID' => $row['record_id'],
                'IP'          => $row['ip_address'],
                'Sucursal ID' => $row['branch_id'],
            ];
        }

        return ['success' => true, 'data' => $exportData, 'total' => count($exportData)];
    }

    /**
     * Traducir acción a español
     */
    private function translateAction($action)
    {
        $map = [
            'create' => 'Registro',
            'update' => 'Actualizacion',
            'delete' => 'Eliminacion',
            'move'   => 'Movimiento',
            'login'  => 'Inicio de sesion',
            'logout' => 'Cierre de sesion',
            'export' => 'Exportacion',
        ];
        return $map[$action] ?? $action;
    }
}
