<?php
/**
 * Configuración de límites para mantenimientos
 * 
 * Este archivo define los límites para prevenir sobrecarga
 * de eventos de mantenimiento en el sistema.
 */

return [
    // Máximo de eventos de mantenimiento permitidos por día
    // Ajustar según la capacidad del equipo de mantenimiento
    'max_events_per_day' => 20,
    
    // Notificar cuando se alcance este porcentaje del límite
    'warning_threshold_percent' => 80,
    
    // Permitir override del límite por administradores
    'admin_can_override' => true,
    
    // Días de la semana con límites especiales (opcional)
    // 'weekend_max' => 10, // Fin de semana con menor capacidad
];
