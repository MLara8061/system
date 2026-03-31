<?php
/**
 * Helper: Obtener configuración de empresa para la sucursal activa.
 * Requiere que $conn (mysqli) esté disponible y config/config.php cargado.
 */

if (!defined('ACCESS')) exit('Acceso no permitido.');

/**
 * Obtiene la configuración de empresa para una sucursal.
 * Si no existe, devuelve valores por defecto vacíos.
 *
 * @param mysqli $conn   Conexión a BD
 * @param int    $branch_id  ID de sucursal (0 = usar active_branch_id)
 * @return array Asociativo con claves: company_name, address_line_1, etc.
 */
function get_company_config(mysqli $conn, int $branch_id = 0): array {
    if ($branch_id <= 0) {
        $branch_id = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
    }

    $defaults = [
        'id' => 0,
        'branch_id' => $branch_id,
        'company_name' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city_state_zip' => '',
        'phone_number' => '',
        'company_description' => '',
        'logo_path' => '',
        'report_prefix' => 'O.T',
        'unsubscribe_prefix' => 'BAJA',
        'report_current_number' => 0,
        'report_current_year' => 0,
        'report_current_month' => 0,
        'unsubscribe_current_number' => 0,
        'unsubscribe_current_year' => 0,
        'unsubscribe_current_month' => 0,
    ];

    // Verificar que la tabla exista
    $tableCheck = $conn->query("SHOW TABLES LIKE 'company_config'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        return $defaults;
    }

    if ($branch_id <= 0) {
        return $defaults;
    }

    $stmt = $conn->prepare("SELECT * FROM company_config WHERE branch_id = ? LIMIT 1");
    if (!$stmt) {
        return $defaults;
    }
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return $defaults;
    }

    return array_merge($defaults, $row);
}

/**
 * Genera el siguiente número de folio consecutivo para reportes u bajas.
 * Formato: PREFIJO-YYYY-MM-NNN
 * Si cambia el año o mes, el consecutivo se reinicia a 1.
 *
 * @param mysqli $conn       Conexión a BD
 * @param int    $branch_id  ID de sucursal
 * @param string $type       'report' o 'unsubscribe'
 * @return string Folio generado (ej: "O.T-2026-03-001")
 */
function generate_sequential_folio(mysqli $conn, int $branch_id, string $type = 'report'): string {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'company_config'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        // Fallback si la tabla no existe
        if ($type === 'unsubscribe') {
            return 'BAJA-' . date('Y-m') . '-001';
        }
        return 'O.T-' . date('Y-m') . '-001';
    }

    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');

    $prefixCol = ($type === 'unsubscribe') ? 'unsubscribe_prefix' : 'report_prefix';
    $numberCol = ($type === 'unsubscribe') ? 'unsubscribe_current_number' : 'report_current_number';
    $yearCol   = ($type === 'unsubscribe') ? 'unsubscribe_current_year' : 'report_current_year';
    $monthCol  = ($type === 'unsubscribe') ? 'unsubscribe_current_month' : 'report_current_month';

    // Intentar obtener registro existente
    $stmt = $conn->prepare("SELECT id, {$prefixCol} AS prefix, {$numberCol} AS current_number, {$yearCol} AS current_year, {$monthCol} AS current_month FROM company_config WHERE branch_id = ? LIMIT 1");
    if (!$stmt) {
        $defaultPrefix = ($type === 'unsubscribe') ? 'BAJA' : 'O.T';
        return $defaultPrefix . '-' . date('Y') . '-' . date('m') . '-001';
    }
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $defaultPrefix = ($type === 'unsubscribe') ? 'BAJA' : 'O.T';

    if (!$row) {
        // Crear registro con valores por defecto
        $stmtInsert = $conn->prepare("INSERT INTO company_config (branch_id, {$numberCol}, {$yearCol}, {$monthCol}) VALUES (?, 1, ?, ?) ON DUPLICATE KEY UPDATE {$numberCol} = 1, {$yearCol} = ?, {$monthCol} = ?");
        if ($stmtInsert) {
            $stmtInsert->bind_param('iiiii', $branch_id, $currentYear, $currentMonth, $currentYear, $currentMonth);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
        return $defaultPrefix . '-' . date('Y') . '-' . sprintf('%02d', $currentMonth) . '-001';
    }

    $prefix = !empty($row['prefix']) ? $row['prefix'] : $defaultPrefix;
    $storedYear = (int)$row['current_year'];
    $storedMonth = (int)$row['current_month'];
    $storedNumber = (int)$row['current_number'];

    // Reiniciar consecutivo si cambió año o mes
    if ($storedYear !== $currentYear || $storedMonth !== $currentMonth) {
        $newNumber = 1;
    } else {
        $newNumber = $storedNumber + 1;
    }

    // Actualizar en BD
    $stmtUpdate = $conn->prepare("UPDATE company_config SET {$numberCol} = ?, {$yearCol} = ?, {$monthCol} = ? WHERE branch_id = ?");
    if ($stmtUpdate) {
        $stmtUpdate->bind_param('iiii', $newNumber, $currentYear, $currentMonth, $branch_id);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

    return $prefix . '-' . $currentYear . '-' . sprintf('%02d', $currentMonth) . '-' . sprintf('%03d', $newNumber);
}

/**
 * Resuelve URL del logo de empresa por sucursal.
 * Fallback: system_info(meta_field='logo')
 */
function get_company_logo_url(mysqli $conn, int $branch_id = 0): string {
    $cfg = get_company_config($conn, $branch_id);
    $path = trim((string)($cfg['logo_path'] ?? ''));

    if ($path !== '') {
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    $res = @$conn->query("SELECT meta_value FROM system_info WHERE meta_field='logo' LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    $fallback = trim((string)($row['meta_value'] ?? ''));
    if ($fallback === '') {
        return '';
    }
    if (strpos($fallback, 'http://') === 0 || strpos($fallback, 'https://') === 0) {
        return $fallback;
    }
    return rtrim(BASE_URL, '/') . '/' . ltrim($fallback, '/');
}
