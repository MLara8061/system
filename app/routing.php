<?php
/**
 * Router de aplicación - Mapea URLs legadas a nuevas vistas
 * Uso: require_once ROOT . '/app/routing.php'
 */

// Mapeo de páginas legadas a nuevas rutas
$ROUTE_MAP = [
    // Dashboard
    'home' => 'app/views/dashboard/home.php',
    
    // Usuarios
    'user_list' => 'app/views/dashboard/users/list.php',
    'create_user' => 'app/views/dashboard/users/create.php',
    'manage_user' => 'app/views/dashboard/users/manage_modal.php',
    'manage_user_modal' => 'app/views/dashboard/users/modal.php',
    
    // Equipos
    'equipment_list' => 'app/views/dashboard/equipment/list.php',
    'new_equipment' => 'app/views/dashboard/equipment/new.php',
    'edit_equipment' => 'app/views/dashboard/equipment/edit.php',
    'view_equipment' => 'app/views/dashboard/equipment/view.php',
    'equipment_public' => 'app/views/dashboard/equipment/public.php',
    'equipment_report_sistem_list' => 'app/views/dashboard/equipment/report_sistem_list.php',
    'equipment_report_revision_month' => 'app/views/dashboard/equipment/report_revision_month.php',
    'equipment_new_revision' => 'app/views/dashboard/equipment/new_revision.php',
    'equipment_report_responsible' => 'app/views/dashboard/equipment/report_responsible.php',
    'equipment_report_sistem' => 'app/views/dashboard/equipment/report_sistem.php',
    'equipment_unsubscribe' => 'app/views/dashboard/equipment/unsubscribe.php',
    'equipment_unsubscribe_report' => 'app/views/dashboard/equipment/unsubscribe_report.php',
    'equipment_report_sistem_editar' => 'app/views/dashboard/equipment/report_sistem_editar.php',
    'equipment_report_pdf' => 'app/views/dashboard/equipment/report_pdf.php',
    'equipment_unsubscribe_pdf' => 'app/views/dashboard/equipment/unsubscribe_pdf.php',
    
    // Clientes
    'customer_list' => 'app/views/dashboard/customers/list.php',
    'new_customer' => 'app/views/dashboard/customers/new.php',
    'edit_customer' => 'app/views/dashboard/customers/edit.php',
    
    // Técnicos/Staff
    'staff_list' => 'app/views/dashboard/staff/list.php',
    'new_staff' => 'app/views/dashboard/staff/new.php',
    'edit_staff' => 'app/views/dashboard/staff/edit.php',
    
    // Proveedores
    'suppliers' => 'app/views/dashboard/suppliers/list.php',
    'new_supplier' => 'app/views/dashboard/suppliers/new.php',
    'edit_supplier' => 'app/views/dashboard/suppliers/edit.php',
    
    // Tickets
    'ticket_list' => 'app/views/dashboard/tickets/list.php',
    'new_ticket' => 'app/views/dashboard/tickets/new.php',
    'edit_ticket' => 'app/views/dashboard/tickets/edit.php',
    'view_ticket' => 'app/views/dashboard/tickets/view.php',
    
    // Herramientas
    'tools_list' => 'app/views/dashboard/equipment/tools_list.php',
    'new_tool' => 'app/views/dashboard/equipment/new_tool.php',
    'edit_tool' => 'app/views/dashboard/equipment/edit_tool.php',
    
    // Accesorios
    'accessories_list' => 'app/views/dashboard/equipment/accessories_list.php',
    'new_accesories' => 'app/views/dashboard/equipment/new_accesories.php',
    'edit_accesories' => 'app/views/dashboard/equipment/edit_accesories.php',
    
    // Equipment adicionales
    'equipment_report_sistem_add' => 'app/views/dashboard/equipment/report_sistem_add.php',
    'equipment_report_sistem_update' => 'app/views/dashboard/equipment/report_sistem_update.php',
    
    // Configuración
    'profile' => 'app/views/dashboard/settings/profile.php',
    'activity_log' => 'app/views/dashboard/settings/activity_log.php',
    'department_list' => 'app/views/dashboard/settings/departments.php',
    'manage_department' => 'app/views/dashboard/settings/manage_department.php',
    'manage_category' => 'app/views/dashboard/settings/manage_category.php',
    'category' => 'app/views/dashboard/settings/categories.php',
    'manage_services' => 'app/views/dashboard/settings/manage_services.php',
    'service_list' => 'app/views/dashboard/settings/services.php',
    'locations' => 'app/views/dashboard/settings/locations.php',
    'manage_equipment_location' => 'app/views/dashboard/settings/manage_equipment_location.php',
    'job_positions' => 'app/views/dashboard/settings/job_positions.php',
    'manage_job_position' => 'app/views/dashboard/settings/manage_job_position.php',
    
    // Reportes
    'report_form' => 'app/views/dashboard/reports/form.php',
    'generate_pdf' => 'app/helpers/generate_pdf.php',
    'equipment_report_pdf' => 'app/helpers/equipment_report_pdf.php',
    'upload_equipment' => 'app/views/dashboard/equipment/upload.php',
    'download_template' => 'app/helpers/download_template.php',
    'generate_excel_template' => 'app/helpers/generate_excel_template.php',
    
    // Otros
    'calendar' => 'app/views/dashboard/calendar.php',
    'manage_inventory' => 'app/views/dashboard/inventory/manage.php',
    'inventory_list' => 'app/views/dashboard/inventory/list.php',
    'descargar_manual' => 'app/views/dashboard/descargar_manual.php',
    'check_structure' => 'app/views/dashboard/check_structure.php',
];

/**
 * Resolver página solicitada
 * @param string $page Nombre de página
 * @return string Ruta a archivo o false si no existe
 */
function resolve_route($page) {
    global $ROUTE_MAP;
    
    // Limpiar nombre de página (seguridad)
    $page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
    
    // Buscar en mapeo
    if (isset($ROUTE_MAP[$page])) {
        $file = ROOT . '/' . $ROUTE_MAP[$page];
        if (file_exists($file)) {
            return $file;
        }
    }
    
    // Búsqueda backwards-compatible: buscar archivo directo en raíz
    $legacy_file = ROOT . '/' . $page . '.php';
    if (file_exists($legacy_file)) {
        return $legacy_file;
    }
    
    return false;
}
