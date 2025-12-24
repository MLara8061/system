<?php
/**
 * Sistema de Permisos y Control de Acceso
 * 
 * Funciones helper para verificar permisos de usuario
 * Uso: require_once 'app/helpers/permissions.php';
 */

if (!function_exists('get_user_role')) {
    /**
     * Obtiene el rol del usuario actual
     * @return array|null
     */
    function get_user_role() {
        static $cached_role = null;
        
        if ($cached_role !== null) {
            return $cached_role;
        }
        
        if (!isset($_SESSION['login_id'])) {
            return null;
        }
        
        global $conn;
        $user_id = (int)$_SESSION['login_id'];
        
        $query = "SELECT r.* FROM roles r 
                  INNER JOIN users u ON u.role_id = r.id 
                  WHERE u.id = $user_id LIMIT 1";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $cached_role = $result->fetch_assoc();
            return $cached_role;
        }
        
        return null;
    }
}

if (!function_exists('is_admin')) {
    /**
     * Verifica si el usuario es administrador global
     * @return bool
     */
    function is_admin() {
        $role = get_user_role();
        return $role && (int)$role['is_admin'] === 1;
    }
}

if (!function_exists('can')) {
    /**
     * Verifica si el usuario tiene un permiso específico en un módulo
     * 
     * @param string $action Acción: 'view', 'create', 'edit', 'delete', 'export'
     * @param string $module Módulo: 'equipments', 'tools', 'accessories', etc.
     * @return bool
     */
    function can($action, $module) {
        // Admin siempre tiene acceso
        if (is_admin()) {
            return true;
        }
        
        if (!isset($_SESSION['login_id'])) {
            return false;
        }
        
        global $conn;
        $user_id = (int)$_SESSION['login_id'];
        $module = $conn->real_escape_string($module);
        $action = strtolower($action);
        
        // Mapear acción a campo de BD
        $action_field_map = [
            'view' => 'can_view',
            'read' => 'can_view',
            'create' => 'can_create',
            'add' => 'can_create',
            'edit' => 'can_edit',
            'update' => 'can_edit',
            'delete' => 'can_delete',
            'remove' => 'can_delete',
            'export' => 'can_export'
        ];
        
        $field = $action_field_map[$action] ?? null;
        
        if (!$field) {
            return false;
        }
        
        // Consulta con cache en sesión
        $cache_key = "perm_{$user_id}_{$module}_{$action}";
        
        if (isset($_SESSION[$cache_key])) {
            return (bool)$_SESSION[$cache_key];
        }
        
        $query = "SELECT rp.{$field} 
                  FROM users u
                  INNER JOIN roles r ON u.role_id = r.id
                  LEFT JOIN role_permissions rp ON r.id = rp.role_id AND rp.module_code = '$module'
                  WHERE u.id = $user_id
                  LIMIT 1";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $has_permission = (bool)($row[$field] ?? 0);
            $_SESSION[$cache_key] = $has_permission;
            return $has_permission;
        }
        
        return false;
    }
}

if (!function_exists('can_view_all_departments')) {
    /**
     * Verifica si el usuario puede ver todos los departamentos
     * @return bool
     */
    function can_view_all_departments() {
        // Admin siempre puede ver todo
        if (is_admin()) {
            return true;
        }
        
        if (!isset($_SESSION['login_id'])) {
            return false;
        }
        
        global $conn;
        $user_id = (int)$_SESSION['login_id'];
        
        $query = "SELECT can_view_all_departments FROM users WHERE id = $user_id";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool)$row['can_view_all_departments'];
        }
        
        return false;
    }
}

if (!function_exists('get_user_department')) {
    /**
     * Obtiene el ID del departamento del usuario actual
     * @return int|null
     */
    function get_user_department() {
        if (!isset($_SESSION['login_id'])) {
            return null;
        }
        
        // Cache en sesión
        if (isset($_SESSION['user_department_id'])) {
            return $_SESSION['user_department_id'];
        }
        
        global $conn;
        $user_id = (int)$_SESSION['login_id'];
        
        $query = "SELECT department_id FROM users WHERE id = $user_id";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user_department_id'] = $row['department_id'];
            return $row['department_id'];
        }
        
        return null;
    }
}

if (!function_exists('department_filter_sql')) {
    /**
     * Genera filtro SQL para limitar consultas al departamento del usuario
     * 
     * @param string $clause 'WHERE' o 'AND'
     * @param string $table_alias Alias de la tabla (ej: 'ed' para equipment_delivery)
     * @param string $column Nombre de la columna de departamento (default: 'department_id')
     * @return string
     */
    function department_filter_sql($clause = 'AND', $table_alias = '', $column = 'department_id') {
        // Admin o usuarios con acceso global no necesitan filtro
        if (is_admin() || can_view_all_departments()) {
            return '';
        }
        
        $dept_id = get_user_department();
        
        if (!$dept_id) {
            return '';
        }
        
        $prefix = $table_alias ? "{$table_alias}." : '';
        $clause = strtoupper(trim($clause));
        
        if ($clause !== 'WHERE' && $clause !== 'AND') {
            $clause = 'AND';
        }
        
        return " $clause {$prefix}{$column} = $dept_id ";
    }
}

if (!function_exists('require_permission')) {
    /**
     * Requiere un permiso específico o redirige/muestra error
     * 
     * @param string $action
     * @param string $module
     * @param bool $redirect Si es true, redirige. Si es false, muestra mensaje y muere
     */
    function require_permission($action, $module, $redirect = true) {
        if (!can($action, $module)) {
            if ($redirect) {
                header('Location: index.php?page=dashboard');
                exit;
            } else {
                http_response_code(403);
                die('<div class="alert alert-danger">
                    <i class="fas fa-ban"></i> 
                    No tienes permisos para realizar esta acción.
                </div>');
            }
        }
    }
}

if (!function_exists('get_user_permissions')) {
    /**
     * Obtiene todos los permisos del usuario actual
     * @return array
     */
    function get_user_permissions() {
        if (!isset($_SESSION['login_id'])) {
            return [];
        }
        
        global $conn;
        $user_id = (int)$_SESSION['login_id'];
        
        $query = "SELECT * FROM vw_user_permissions WHERE user_id = $user_id";
        $result = $conn->query($query);
        
        if (!$result) {
            return [];
        }
        
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row;
        }
        
        return $permissions;
    }
}

if (!function_exists('clear_permission_cache')) {
    /**
     * Limpia el cache de permisos de la sesión
     * Llamar cuando se actualicen los permisos de un usuario
     */
    function clear_permission_cache() {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'perm_') === 0) {
                unset($_SESSION[$key]);
            }
        }
        unset($_SESSION['user_department_id']);
    }
}
