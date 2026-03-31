<?php
/**
 * AuditLogger - Registra acciones de auditoría en audit_logs
 * 
 * Uso:
 *   AuditLogger::log('equipment', 'create', 'equipments', $newId, null, $data);
 *   AuditLogger::log('equipment', 'update', 'equipments', $id, $oldData, $newData);
 *   AuditLogger::log('equipment', 'delete', 'equipments', $id, $oldData);
 */

class AuditLogger
{
    /**
     * Registrar una acción de auditoría
     *
     * @param string     $module    Módulo (equipment, ticket, inventory, user, etc.)
     * @param string     $action    Acción (create, update, delete, move, login, logout, export)
     * @param string     $table     Nombre de la tabla afectada
     * @param int|null   $recordId  ID del registro afectado
     * @param array|null $oldValues Valores anteriores (para update/delete)
     * @param array|null $newValues Valores nuevos (para create/update)
     */
    public static function log($module, $action, $table, $recordId = null, $oldValues = null, $newValues = null)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) {
                return;
            }

            // No auditar la propia tabla de auditoría
            if ($table === 'audit_logs') {
                return;
            }

            $userId   = $_SESSION['login_id'] ?? 0;
            $userName = isset($_SESSION['login_firstname'], $_SESSION['login_lastname'])
                ? trim($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname'])
                : null;
            $branchId  = $_SESSION['active_branch_id'] ?? null;
            $ipAddress = self::getClientIp();
            $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
                ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
                : null;

            // Para updates, calcular solo los campos que cambiaron
            if ($action === 'update' && $oldValues && $newValues) {
                $filteredOld = [];
                $filteredNew = [];
                foreach ($newValues as $key => $val) {
                    $oldVal = $oldValues[$key] ?? null;
                    if ((string)$oldVal !== (string)$val) {
                        $filteredOld[$key] = $oldVal;
                        $filteredNew[$key] = $val;
                    }
                }
                // Si no hubo cambios reales, no registrar
                if (empty($filteredNew)) {
                    return;
                }
                $oldValues = $filteredOld;
                $newValues = $filteredNew;
            }

            // Remover campos sensibles
            $sensitiveKeys = ['password', 'token', 'secret', 'credit_card'];
            $oldValues = self::removeSensitive($oldValues, $sensitiveKeys);
            $newValues = self::removeSensitive($newValues, $sensitiveKeys);

            $sql = "INSERT INTO audit_logs 
                    (user_id, user_name, module, action, table_name, record_id, old_values, new_values, ip_address, user_agent, branch_id)
                    VALUES (:user_id, :user_name, :module, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent, :branch_id)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id'    => (int)$userId,
                ':user_name'  => $userName,
                ':module'     => $module,
                ':action'     => $action,
                ':table_name' => $table,
                ':record_id'  => $recordId ? (int)$recordId : null,
                ':old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                ':new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':branch_id'  => $branchId ? (int)$branchId : null,
            ]);
        } catch (\Exception $e) {
            // La auditoría nunca debe romper la operación principal
            error_log('AuditLogger ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Obtener conexión PDO
     */
    private static function getPdo()
    {
        if (function_exists('get_pdo')) {
            return get_pdo();
        }

        $configPath = defined('ROOT')
            ? ROOT . '/config/db.php'
            : dirname(__DIR__, 2) . '/config/db.php';

        if (file_exists($configPath)) {
            require_once $configPath;
            if (function_exists('get_pdo')) {
                return get_pdo();
            }
        }

        return null;
    }

    /**
     * Obtener IP real del cliente
     */
    private static function getClientIp()
    {
        // Solo confiar en REMOTE_ADDR (los headers X-Forwarded-For son manipulables)
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Remover campos sensibles de los valores
     */
    private static function removeSensitive($data, array $keys)
    {
        if (!is_array($data)) {
            return $data;
        }
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***';
            }
        }
        return $data;
    }
}
