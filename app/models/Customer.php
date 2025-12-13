<?php
/**
 * app/models/Customer.php
 * Model de dominio para Clientes
 * 
 * Extiende DataStore para acceso genérico a datos
 * Agrrega métodos específicos de negocio para clientes
 */

require_once dirname(__FILE__) . '/DataStore.php';

class Customer extends DataStore {
    
    /**
     * Constructor - Inicializar con tabla 'customers'
     */
    public function __construct() {
        parent::__construct('customers');
    }
    
    /**
     * Guardar cliente (crear o actualizar)
     * 
     * @param array $data Datos del cliente
     * @return int ID del cliente
     */
    public function save($data) {
        try {
            // Validar campos requeridos
            if (empty($data['company_name']) || empty($data['email'])) {
                throw new Exception('Nombre de empresa y email requeridos');
            }
            
            // Si tiene ID, es actualización
            if (!empty($data['id'])) {
                $this->update($data['id'], $data);
                return $data['id'];
            }
            
            // Si no tiene ID, es creación
            return $this->insert($data);
            
        } catch (Exception $e) {
            error_log("Customer::save - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener cliente por email
     * 
     * @param string $email Email del cliente
     * @return array|null Cliente o null
     */
    public function getByEmail($email) {
        try {
            return $this->findBy('email', trim(strtolower($email)), true);
        } catch (Exception $e) {
            error_log("Customer::getByEmail - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener cliente por teléfono
     * 
     * @param string $phone Teléfono del cliente
     * @return array|null Cliente o null
     */
    public function getByPhone($phone) {
        try {
            return $this->findBy('phone', $phone, true);
        } catch (Exception $e) {
            error_log("Customer::getByPhone - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verificar si email existe (excluyendo un ID si es actualización)
     * 
     * @param string $email Email a verificar
     * @param int|null $excludeId ID a excluir de búsqueda
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = 'SELECT COUNT(*) as count FROM customers WHERE email = ?';
            $params = [strtolower(trim($email))];
            
            if ($excludeId) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            
            $result = $this->query($sql, $params);
            return $result && $result[0]['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Customer::emailExists - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar clientes por estado
     * 
     * @param string $status Estado ('active', 'inactive', 'suspended')
     * @return array Clientes
     */
    public function getByStatus($status) {
        try {
            return $this->findBy('status', $status);
        } catch (Exception $e) {
            error_log("Customer::getByStatus - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Listar clientes activos
     * 
     * @return array Clientes activos
     */
    public function getActive() {
        try {
            return $this->getByStatus('active');
        } catch (Exception $e) {
            error_log("Customer::getActive - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar clientes
     * Busca en: company_name, contact_name, email, phone
     * 
     * @param string $search Término de búsqueda
     * @return array Clientes coincidentes
     */
    public function search($search) {
        try {
            $search = '%' . trim($search) . '%';
            
            $sql = 'SELECT * FROM customers 
                    WHERE company_name LIKE ? 
                    OR contact_name LIKE ? 
                    OR email LIKE ? 
                    OR phone LIKE ?
                    ORDER BY company_name ASC
                    LIMIT 50';
            
            $params = [$search, $search, $search, $search];
            
            return $this->query($sql, $params) ?: [];
            
        } catch (Exception $e) {
            error_log("Customer::search - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Listar clientes con filtros y paginación
     * 
     * @param array $filters Filtros (status, city, country, limit, offset)
     * @return array Clientes
     */
    public function listWithFilters($filters = []) {
        try {
            $sql = 'SELECT * FROM customers WHERE 1=1';
            $params = [];
            
            // Filtro por estado
            if (!empty($filters['status'])) {
                $sql .= ' AND status = ?';
                $params[] = $filters['status'];
            }
            
            // Filtro por ciudad
            if (!empty($filters['city'])) {
                $sql .= ' AND city = ?';
                $params[] = $filters['city'];
            }
            
            // Filtro por país
            if (!empty($filters['country'])) {
                $sql .= ' AND country = ?';
                $params[] = $filters['country'];
            }
            
            $sql .= ' ORDER BY company_name ASC';
            
            // Paginación
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;
            
            return $this->query($sql, $params) ?: [];
            
        } catch (Exception $e) {
            error_log("Customer::listWithFilters - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener cliente con detalles de contacto
     * 
     * @param int $customerId ID del cliente
     * @return array|null Cliente sin datos sensibles
     */
    public function getWithDetails($customerId) {
        try {
            $customer = $this->getById($customerId);
            
            if ($customer) {
                // Remover campos no necesarios en respuesta
                unset($customer['created_at_admin']);
                unset($customer['updated_at_admin']);
            }
            
            return $customer;
            
        } catch (Exception $e) {
            error_log("Customer::getWithDetails - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cambiar estado del cliente
     * 
     * @param int $customerId ID del cliente
     * @param string $newStatus Nuevo estado
     * @return bool
     */
    public function changeStatus($customerId, $newStatus) {
        try {
            $validStates = ['active', 'inactive', 'suspended'];
            if (!in_array($newStatus, $validStates)) {
                throw new Exception("Estado inválido: {$newStatus}");
            }
            
            $this->update($customerId, ['status' => $newStatus]);
            return true;
            
        } catch (Exception $e) {
            error_log("Customer::changeStatus - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener clientes en una ciudad
     * 
     * @param string $city Ciudad
     * @return array Clientes en la ciudad
     */
    public function getByCity($city) {
        try {
            return $this->findBy('city', $city);
        } catch (Exception $e) {
            error_log("Customer::getByCity - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener clientes en un país
     * 
     * @param string $country País
     * @return array Clientes en el país
     */
    public function getByCountry($country) {
        try {
            return $this->findBy('country', $country);
        } catch (Exception $e) {
            error_log("Customer::getByCountry - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de clientes
     * 
     * @return array Stats (total, active, inactive, suspended, by_country)
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Conteos por estado
            $sql = 'SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = "inactive" THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN status = "suspended" THEN 1 ELSE 0 END) as suspended
                    FROM customers';
            
            $result = $this->query($sql, []);
            if ($result) {
                $stats = array_merge($stats, $result[0]);
            }
            
            // Top 5 países
            $sql = 'SELECT country, COUNT(*) as count 
                    FROM customers 
                    WHERE country IS NOT NULL 
                    GROUP BY country 
                    ORDER BY count DESC 
                    LIMIT 5';
            
            $byCountry = $this->query($sql, []);
            $stats['by_country'] = $byCountry ?: [];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Customer::getStatistics - " . $e->getMessage());
            return [];
        }
    }
}
