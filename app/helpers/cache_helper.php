<?php
/**
 * Helper para leer datos del caché del dashboard
 * Retorna datos cached o fallback si no existe
 */

function get_cached_data($conn, $cache_key, $fallback = null) {
    $result = $conn->query("SELECT cache_data FROM dashboard_cache WHERE cache_key = '$cache_key' LIMIT 1");
    
    if ($result && $row = $result->fetch_assoc()) {
        $data = json_decode($row['cache_data'], true);
        return $data ?? $fallback;
    }
    
    return $fallback;
}

function cache_is_fresh($conn, $cache_key, $max_age_minutes = 60) {
    $result = $conn->query("SELECT TIMESTAMPDIFF(MINUTE, updated_at, NOW()) as age_minutes FROM dashboard_cache WHERE cache_key = '$cache_key' LIMIT 1");
    
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['age_minutes'] <= $max_age_minutes;
    }
    
    return false;
}

function get_cache_age($conn, $cache_key) {
    $result = $conn->query("SELECT updated_at FROM dashboard_cache WHERE cache_key = '$cache_key' LIMIT 1");
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['updated_at'];
    }
    
    return null;
}
