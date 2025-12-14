<?php
/**
 * Configuración de Modo Mantenimiento
 * 
 * Para ACTIVAR mantenimiento: cambiar $maintenance_enabled = true
 * Para DESACTIVAR: cambiar $maintenance_enabled = false
 */

return [
    // ACTIVAR/DESACTIVAR modo mantenimiento
    'maintenance_enabled' => true, // Cambiar a true para activar
    
    // Fecha y hora de finalización (lunes 16 dic 2025, 8:00am)
    'end_datetime' => '2025-12-16 08:00:00',
    
    // Fecha y hora de inicio del mantenimiento (viernes 13 dic 2025, ahora)
    'start_datetime' => '2025-12-13 20:00:00',
    
    // IPs permitidas durante mantenimiento (sin restricción)
    'allowed_ips' => [
        // '127.0.0.1',
        // '::1',
    ],
    
    // Rutas que no requieren mantenimiento (login, logout, etc.)
    'exempt_routes' => [
        '/maintenance.php',
        '/app/views/auth/login.php',
        '/app/views/auth/logout.php',
    ]
];
