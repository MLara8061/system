<?php
/**
 * Helper para leer datos del caché del dashboard
 * Retorna datos cached o fallback si no existe
 */

function dashboard_cache_key($cache_key, $branch_id = null) {
    $cache_key = (string)$cache_key;
    $branch_id = $branch_id === null ? null : (int)$branch_id;
    if ($branch_id !== null && $branch_id > 0) {
        return $cache_key . ':b' . $branch_id;
    }
    return $cache_key;
}

function dashboard_cache_branch_id() {
    $login_type = (int)($_SESSION['login_type'] ?? 0);
    $active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
    // Admin en "Todas" (0) => clave global
    if ($login_type === 1 && $active_bid === 0) {
        return 0;
    }
    // Usuarios/no-admin deben ir por sucursal
    return $active_bid;
}

function get_cached_data($conn, $cache_key, $fallback = null) {
    $branch_id = dashboard_cache_branch_id();
    $keys_to_try = [];
    if ($branch_id > 0) {
        $keys_to_try[] = dashboard_cache_key($cache_key, $branch_id);
    }
    $keys_to_try[] = dashboard_cache_key($cache_key, null);

    foreach ($keys_to_try as $key) {
        $stmt = $conn->prepare('SELECT cache_data FROM dashboard_cache WHERE cache_key = ? LIMIT 1');
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row && isset($row['cache_data'])) {
            $data = json_decode($row['cache_data'], true);
            return $data ?? $fallback;
        }
    }

    return $fallback;
}

function cache_is_fresh($conn, $cache_key, $max_age_minutes = 60) {
    $branch_id = dashboard_cache_branch_id();
    $keys_to_try = [];
    if ($branch_id > 0) {
        $keys_to_try[] = dashboard_cache_key($cache_key, $branch_id);
    }
    $keys_to_try[] = dashboard_cache_key($cache_key, null);

    foreach ($keys_to_try as $key) {
        $stmt = $conn->prepare('SELECT TIMESTAMPDIFF(MINUTE, updated_at, NOW()) as age_minutes FROM dashboard_cache WHERE cache_key = ? LIMIT 1');
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row && isset($row['age_minutes'])) {
            return (int)$row['age_minutes'] <= (int)$max_age_minutes;
        }
    }

    return false;
}

function get_cache_age($conn, $cache_key) {
    $branch_id = dashboard_cache_branch_id();
    $keys_to_try = [];
    if ($branch_id > 0) {
        $keys_to_try[] = dashboard_cache_key($cache_key, $branch_id);
    }
    $keys_to_try[] = dashboard_cache_key($cache_key, null);

    foreach ($keys_to_try as $key) {
        $stmt = $conn->prepare('SELECT updated_at FROM dashboard_cache WHERE cache_key = ? LIMIT 1');
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row && isset($row['updated_at'])) {
            return $row['updated_at'];
        }
    }

    return null;
}
