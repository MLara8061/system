<?php
/**
 * CustomField - Modelo para campos personalizados
 */

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__FILE__))));
}

require_once ROOT . '/app/models/DataStore.php';

class CustomField extends DataStore {

    public function __construct() {
        parent::__construct('custom_field_definitions', 'custom_fields');
    }

    /**
     * Obtiene definiciones activas para un tipo y sucursal.
     * Incluye definiciones globales (branch_id IS NULL) y las de la sucursal específica.
     */
    public function getForEntity(string $entityType, ?int $branchId = null): array {
        $sql = "SELECT * FROM custom_field_definitions
                 WHERE entity_type = :type
                   AND active = 1
                   AND (:bid IS NULL OR branch_id IS NULL OR branch_id = :bid2)
                 ORDER BY sort_order ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':type' => $entityType,
            ':bid'  => $branchId,
            ':bid2' => $branchId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los valores guardados para una entidad específica.
     * Retorna [definition_id => field_value].
     */
    public function getValues(string $entityType, int $entityId): array {
        $stmt = $this->db->prepare(
            "SELECT definition_id, field_value
               FROM custom_field_values
              WHERE entity_type = :type AND entity_id = :eid"
        );
        $stmt->execute([':type' => $entityType, ':eid' => $entityId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['definition_id']] = $row['field_value'];
        }
        return $map;
    }

    /**
     * Guarda (UPSERT) valores para una entidad.
     * $values = [definition_id => value, ...]
     */
    public function saveValues(string $entityType, int $entityId, array $values): void {
        if (empty($values)) return;

        $sql = "INSERT INTO custom_field_values (definition_id, entity_type, entity_id, field_value)
                VALUES (:did, :type, :eid, :val)
                ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)";
        $stmt = $this->db->prepare($sql);

        foreach ($values as $defId => $value) {
            $defId = (int)$defId;
            if ($defId <= 0) continue;
            $stmt->execute([
                ':did'  => $defId,
                ':type' => $entityType,
                ':eid'  => $entityId,
                ':val'  => $value === '' ? null : (string)$value,
            ]);
        }
    }

    /**
     * Verifica si un field_name ya existe para el mismo entity_type y branch.
     */
    public function nameExists(string $entityType, string $fieldName, ?int $branchId, ?int $excludeId = null): bool {
        $sql = "SELECT id FROM custom_field_definitions
                 WHERE entity_type = :type AND field_name = :name
                   AND (branch_id <=> :bid)";
        $params = [':type' => $entityType, ':name' => $fieldName, ':bid' => $branchId];
        if ($excludeId) {
            $sql .= " AND id != :excl";
            $params[':excl'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    /**
     * Devuelve todos los tipos de entidad soportados.
     */
    public static function entityTypes(): array {
        return ['equipment', 'tool', 'accessory', 'inventory'];
    }

    /**
     * Devuelve los tipos de campo soportados.
     */
    public static function fieldTypes(): array {
        return ['text', 'number', 'date', 'select', 'textarea', 'checkbox'];
    }
}
