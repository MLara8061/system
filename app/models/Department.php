<?php
/**
 * app/models/Department.php
 * Model de dominio para Departamentos
 * Modelo simple con CRUD básico
 */

require_once dirname(__FILE__) . '/DataStore.php';

class Department extends DataStore {
    
    /**
     * Constructor - Inicializar con tabla 'departments'
     */
    public function __construct() {
        parent::__construct('departments');
    }
    
    /**
     * Guardar departamento (crear o actualizar)
     * 
     * @param array $data Datos del departamento
     * @return int ID del departamento
     */
    public function save($data) {
        try {
            if (empty($data['name'])) {
                throw new Exception('Nombre del departamento requerido');
            }
            
            if (!empty($data['id'])) {
                $this->update($data['id'], $data);
                return $data['id'];
            }
            
            return $this->insert($data);
            
        } catch (Exception $e) {
            error_log("Department::save - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener departamento por nombre
     * 
     * @param string $name Nombre del departamento
     * @return array|null Departamento o null
     */
    public function getByName($name) {
        try {
            return $this->findBy('name', trim($name), true);
        } catch (Exception $e) {
            error_log("Department::getByName - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Listar departamentos activos
     * 
     * @return array Departamentos activos
     */
    public function getActive() {
        try {
            return $this->findBy('active', 1);
        } catch (Exception $e) {
            error_log("Department::getActive - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar departamentos
     * 
     * @param string $search Término de búsqueda
     * @return array Departamentos coincidentes
     */
    public function search($search) {
        try {
            $search = '%' . trim($search) . '%';
            
            $sql = 'SELECT * FROM departments 
                    WHERE name LIKE ? 
                    OR description LIKE ?
                    ORDER BY name ASC
                    LIMIT 50';
            
            $params = [$search, $search];
            return $this->query($sql, $params) ?: [];
            
        } catch (Exception $e) {
            error_log("Department::search - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si nombre existe (excluyendo un ID si es actualización)
     * 
     * @param string $name Nombre a verificar
     * @param int|null $excludeId ID a excluir de búsqueda
     * @return bool
     */
    public function nameExists($name, $excludeId = null) {
        try {
            $sql = 'SELECT COUNT(*) as count FROM departments WHERE name = ?';
            $params = [trim($name)];
            
            if ($excludeId) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            
            $result = $this->query($sql, $params);
            return $result && $result[0]['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Department::nameExists - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cambiar estado del departamento
     * 
     * @param int $departmentId ID del departamento
     * @param int $active 1 o 0
     * @return bool
     */
    public function toggleActive($departmentId, $active = null) {
        try {
            $dept = $this->getById($departmentId);
            if (!$dept) {
                throw new Exception('Departamento no encontrado');
            }
            
            $newActive = $active !== null ? $active : (!$dept['active'] ? 1 : 0);
            $this->update($departmentId, ['active' => $newActive]);
            return true;
            
        } catch (Exception $e) {
            error_log("Department::toggleActive - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de departamentos
     * 
     * @return array Stats (total, active)
     */
    public function getStatistics() {
        try {
            $sql = 'SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active
                    FROM departments';
            
            $result = $this->query($sql, []);
            return $result ? $result[0] : ['total' => 0, 'active' => 0];
            
        } catch (Exception $e) {
            error_log("Department::getStatistics - " . $e->getMessage());
            return ['total' => 0, 'active' => 0];
        }
    }
}
