<?php
/**
 * NotificationService - Gestiona notificaciones in-app
 */
class NotificationService
{
    /**
     * Crear notificación para un usuario
     */
    public static function notify($userId, $type, $title, $message, $link = null)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return false;

            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([(int)$userId, $type, $title, $message, $link]);
        } catch (\Exception $e) {
            error_log('NotificationService ERROR: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificar a múltiples usuarios
     */
    public static function notifyMany(array $userIds, $type, $title, $message, $link = null)
    {
        foreach ($userIds as $uid) {
            self::notify($uid, $type, $title, $message, $link);
        }
    }

    /**
     * Obtener notificaciones no leidas de un usuario
     */
    public static function getUnread($userId, $limit = 10)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return [];

            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([(int)$userId, (int)$limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('NotificationService ERROR: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar no leidas
     */
    public static function countUnread($userId)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return 0;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([(int)$userId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Marcar como leida
     */
    public static function markRead($notificationId)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return false;

            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
            return $stmt->execute([(int)$notificationId]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Marcar todas como leidas para un usuario
     */
    public static function markAllRead($userId)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return false;

            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
            return $stmt->execute([(int)$userId]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener historial de notificaciones (paginado)
     */
    public static function getAll($userId, $page = 1, $perPage = 20)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return ['data' => [], 'total' => 0];

            $offset = ($page - 1) * $perPage;

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
            $countStmt->execute([(int)$userId]);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([(int)$userId, (int)$perPage, (int)$offset]);

            return ['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC), 'total' => $total];
        } catch (\Exception $e) {
            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * Notificar cambio de estado de ticket a usuarios relevantes
     */
    public static function notifyTicketStatusChange($ticketId, $oldStatus, $newStatus, $changedBy)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return;

            $statusLabels = [0 => 'Abierto', 1 => 'En Proceso', 2 => 'Finalizado'];
            $newLabel = $statusLabels[$newStatus] ?? "Estado $newStatus";

            // Obtener datos del ticket
            $stmt = $pdo->prepare("SELECT subject, customer_id, staff_id, admin_id, assigned_to FROM tickets WHERE id = ?");
            $stmt->execute([(int)$ticketId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ticket) return;

            $title = "Ticket actualizado";
            $message = "El ticket \"{$ticket['subject']}\" cambio a: {$newLabel}";
            $link = "index.php?page=view_ticket&id={$ticketId}";

            // Notificar a todos los involucrados excepto quien hizo el cambio
            $recipients = array_filter([
                (int)($ticket['customer_id'] ?? 0),
                (int)($ticket['staff_id'] ?? 0),
                (int)($ticket['admin_id'] ?? 0),
                (int)($ticket['assigned_to'] ?? 0),
            ], fn($uid) => $uid > 0 && $uid !== (int)$changedBy);

            // También notificar a admins
            $admins = $pdo->query("SELECT id FROM users WHERE type = 1")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($admins as $adminId) {
                if ((int)$adminId !== (int)$changedBy) {
                    $recipients[] = (int)$adminId;
                }
            }

            $recipients = array_unique($recipients);
            self::notifyMany($recipients, 'ticket_status', $title, $message, $link);
        } catch (\Exception $e) {
            error_log('NotificationService ticketStatusChange ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Notificar nuevo comentario en ticket
     */
    public static function notifyTicketComment($ticketId, $commentBy)
    {
        try {
            $pdo = self::getPdo();
            if (!$pdo) return;

            $stmt = $pdo->prepare("SELECT subject, customer_id, staff_id, admin_id, assigned_to FROM tickets WHERE id = ?");
            $stmt->execute([(int)$ticketId]);
            $ticket = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ticket) return;

            $title = "Nuevo comentario en ticket";
            $message = "Se agrego un comentario al ticket \"{$ticket['subject']}\"";
            $link = "index.php?page=view_ticket&id={$ticketId}";

            $recipients = array_filter([
                (int)($ticket['customer_id'] ?? 0),
                (int)($ticket['staff_id'] ?? 0),
                (int)($ticket['admin_id'] ?? 0),
                (int)($ticket['assigned_to'] ?? 0),
            ], fn($uid) => $uid > 0 && $uid !== (int)$commentBy);

            $recipients = array_unique($recipients);
            self::notifyMany($recipients, 'ticket_comment', $title, $message, $link);
        } catch (\Exception $e) {
            error_log('NotificationService ticketComment ERROR: ' . $e->getMessage());
        }
    }

    private static function getPdo()
    {
        if (function_exists('get_pdo')) {
            return get_pdo();
        }
        $dbFile = defined('ROOT') ? ROOT . '/config/db.php' : __DIR__ . '/../../config/db.php';
        if (file_exists($dbFile)) {
            require_once $dbFile;
            if (function_exists('get_pdo')) return get_pdo();
        }
        return null;
    }
}
