<?php
/**
 * app/models/AuditLog.php - Model para consulta de registros de auditoría
 * 
 * Solo lectura: no extiende DataStore (no necesita insert/update/delete con hooks).
 */

class AuditLog
{
    private $db;
    private $tableExists = null;

    public function __construct()
    {
        if (defined('ROOT')) {
            require_once ROOT . '/config/db.php';
        } else {
            require_once dirname(__DIR__, 2) . '/config/db.php';
        }
        $this->db = get_pdo();
        if (!$this->db) {
            throw new RuntimeException('No se pudo obtener conexion PDO');
        }
    }

    /**
     * Verificar si la tabla audit_logs existe
     */
    private function ensureTable()
    {
        if ($this->tableExists === null) {
            try {
                $stmt = $this->db->query("SELECT 1 FROM audit_logs LIMIT 1");
                $this->tableExists = true;
            } catch (\Throwable $e) {
                $this->tableExists = false;
            }
        }
        if (!$this->tableExists) {
            throw new RuntimeException('La tabla audit_logs no existe. Ejecute la migracion 010_create_audit_logs.sql');
        }
    }

    /**
     * Listar registros con filtros, paginación y ordenamiento
     *
     * @param array $filters  Filtros: module, action, user_id, date_from, date_to, table_name, branch_id, search
     * @param int   $page     Página actual (1-based)
     * @param int   $perPage  Registros por página
     * @param string $orderBy Columna de ordenamiento
     * @param string $orderDir ASC o DESC
     * @return array ['data' => [...], 'total' => int, 'pages' => int]
     */
    public function listFiltered(array $filters = [], $page = 1, $perPage = 50, $orderBy = 'created_at', $orderDir = 'DESC')
    {
        $this->ensureTable();
        $where  = [];
        $params = [];

        if (!empty($filters['module'])) {
            $where[]          = 'a.module = :module';
            $params[':module'] = $filters['module'];
        }

        if (!empty($filters['action'])) {
            $where[]          = 'a.action = :action';
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['user_id'])) {
            $where[]           = 'a.user_id = :user_id';
            $params[':user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['table_name'])) {
            $where[]              = 'a.table_name = :table_name';
            $params[':table_name'] = $filters['table_name'];
        }

        if (!empty($filters['branch_id'])) {
            $where[]             = 'a.branch_id = :branch_id';
            $params[':branch_id'] = (int)$filters['branch_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[]             = 'a.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[]           = 'a.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $where[] = '(a.user_name LIKE :search OR a.table_name LIKE :search2 OR a.module LIKE :search3)';
            $term = '%' . $filters['search'] . '%';
            $params[':search']  = $term;
            $params[':search2'] = $term;
            $params[':search3'] = $term;
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        // Validar columna de orden (whitelist)
        $allowedOrder = ['created_at', 'module', 'action', 'table_name', 'user_name'];
        if (!in_array($orderBy, $allowedOrder, true)) {
            $orderBy = 'created_at';
        }
        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        // Consultar total
        $countSql = "SELECT COUNT(*) as total FROM audit_logs a {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Paginar
        $page    = max(1, (int)$page);
        $perPage = min(max(1, (int)$perPage), 200);
        $offset  = ($page - 1) * $perPage;
        $pages   = (int)ceil($total / $perPage);

        // Consultar datos
        $sql = "SELECT a.* 
                FROM audit_logs a
                {$whereClause}
                ORDER BY a.{$orderBy} {$orderDir}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'    => $data,
            'total'   => $total,
            'pages'   => $pages,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Obtener módulos distintos para el filtro
     */
    public function getDistinctModules()
    {
        $this->ensureTable();
        $stmt = $this->db->query("SELECT DISTINCT module FROM audit_logs ORDER BY module");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener tablas distintas para el filtro
     */
    public function getDistinctTables()
    {
        $this->ensureTable();
        $stmt = $this->db->query("SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener usuarios distintos para el filtro
     */
    public function getDistinctUsers()
    {
        $this->ensureTable();
        $stmt = $this->db->query(
            "SELECT DISTINCT user_id, user_name FROM audit_logs WHERE user_name IS NOT NULL ORDER BY user_name"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un registro por ID (para detalle)
     */
    public function getById($id)
    {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT * FROM audit_logs WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Exportar registros filtrados (sin paginación, con límite de seguridad)
     */
    public function exportFiltered(array $filters = [], $maxRows = 10000)
    {
        $result = $this->listFiltered($filters, 1, $maxRows, 'created_at', 'DESC');
        return $result['data'];
    }
}
