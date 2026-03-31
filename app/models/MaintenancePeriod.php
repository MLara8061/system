<?php
/**
 * app/models/MaintenancePeriod.php
 */
require_once dirname(__DIR__) . '/models/DataStore.php';

class MaintenancePeriod extends DataStore {

    public function __construct() {
        parent::__construct('maintenance_periods', 'maintenance');
    }

    /**
     * Verificar si el periodo está asignado a algún equipo
     * @param int $id
     * @return bool
     */
    public function isInUse(int $id): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM equipments WHERE mandate_period_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si el nombre ya existe (para otro registro distinto)
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM maintenance_periods WHERE name = :name";
        $params = [':name' => $name];
        if ($excludeId) {
            $sql .= " AND id != :exclude";
            $params[':exclude'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
