<?php
/**
 * DataStore - Capa base de acceso a datos
 * 
 * Proporciona métodos reutilizables para CRUD operations
 * usando PDO de forma segura.
 */

class DataStore {
    protected $db;     // Conexión PDO
    protected $table;  // Tabla actual
    
    public function __construct($table = null) {
        // Usar conexión PDO si existe
        if (defined('ROOT')) {
            require_once ROOT . '/config/db.php';
            $this->db = get_pdo();
        } else {
            require_once __DIR__ . '/../../config/db.php';
            $this->db = get_pdo();
        }
        
        if (!$this->db) {
            throw new Exception("No database connection available");
        }
        
        $this->table = $table;
    }
    
    /**
     * Obtener todas las filas
     * @param string $orderBy Orden SQL
     * @param int $limit Límite de resultados
     * @return array
     */
    public function getAll($orderBy = '', $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener una fila por ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Buscar por columna específica
     * @param string $column Nombre de columna
     * @param mixed $value Valor a buscar
     * @param bool $single Si retorna un solo resultado
     * @return array
     */
    public function findBy($column, $value, $single = false) {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        
        if ($single) {
            $sql .= " LIMIT 1";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':value' => $value]);
        
        return $single 
            ? $stmt->fetch(PDO::FETCH_ASSOC)
            : $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Contar filas
     * @param string $where Condición WHERE (ej: "status = 'active'")
     * @return int
     */
    public function count($where = '') {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Insertar registro
     * @param array $data Datos a insertar (columna => valor)
     * @return int|false ID del registro insertado o false
     */
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $values = array_values($data);
            $stmt->execute($values);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("INSERT ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar registro
     * @param array $data Datos a actualizar
     * @param int $id ID del registro
     * @return bool
     */
    public function update($data, $id) {
        $sets = implode(', ', array_map(function($key) {
            return "{$key} = ?";
        }, array_keys($data)));
        
        $sql = "UPDATE {$this->table} SET {$sets} WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $values = array_values($data);
            $values[] = (int)$id;
            
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("UPDATE ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar registro
     * @param int $id ID del registro
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => (int)$id]);
        } catch (Exception $e) {
            error_log("DELETE ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecutar query personalizado
     * @param string $sql Query SQL
     * @param array $params Parámetros
     * @return mixed
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            error_log("QUERY ERROR: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener conexión PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Obtener tabla actual
     * @return string
     */
    public function getTable() {
        return $this->table;
    }
}
