<?php
ob_start();
include 'admin_class.php';
$crud = new Action();

// === OBTENER ACCIÓN DE FORMA SEGURA ===
$action = $_REQUEST['action'] ?? '';

// ===================================
// 1. LOGIN / LOGOUT
// ===================================
if ($action == 'login') {
    echo $crud->login();
    exit;
}

if ($action == 'logout') {
    echo $crud->logout();
    exit;
}

// ===================================
// 2. USUARIOS
// ===================================
if ($action == 'save_user') {
    echo $crud->save_user();
    exit;
}

if ($action == 'delete_user') {
    echo $crud->delete_user();
    exit;
}

if ($action == 'check_username') {
    echo $crud->check_username();
    exit;
}

if ($action == 'upload_avatar') {
    echo $crud->upload_avatar();
    exit;
}

// ===================================
// 3. IMAGEN PÁGINA
// ===================================
if ($action == 'save_page_img') {
    echo $crud->save_page_img();
    exit;
}

// ===================================
// 4. STAFF / DEPARTAMENTOS
// ===================================
if ($action == 'save_staff') {
    echo $crud->save_staff();
    exit;
}

if ($action == 'delete_staff') {
    echo $crud->delete_staff();
    exit;
}

if ($action == 'save_department') {
    echo $crud->save_department();
    exit;
}

if ($action == 'delete_department') {
    echo $crud->delete_department();
    exit;
}

// ===================================
// 5. TICKETS
// ===================================
if ($action == 'save_ticket') {
    echo $crud->save_ticket();
    exit;
}

if ($action == 'update_ticket') {
    echo $crud->update_ticket();
    exit;
}

if ($action == 'delete_ticket') {
    echo $crud->delete_ticket();
    exit;
}

if ($action == 'save_comment') {
    echo $crud->save_comment();
    exit;
}

if ($action == 'delete_comment') {
    echo $crud->delete_comment();
    exit;
}

// ===================================
// 6. EQUIPOS
// ===================================
if ($action == 'save_equipment') {
    echo $crud->save_equipment();
    exit;
}

// 
if ($action == 'delete_equipment_image') {
    echo $crud->delete_equipment_image();
    exit;
}

if ($action == 'delete_equipment') {
    echo $crud->delete_equipment();
    exit;
}

if ($action == 'save_equipment_unsubscribe') {
    echo $crud->save_equipment_unsubscribe();
    exit;
}

if ($action == 'save_equipment_revision') {
    echo $crud->save_equipment_revision();
    exit;
}

// ===================================
// 7. PROVEEDORES
// ===================================
if ($action == 'save_supplier') {
    echo $crud->save_supplier();
    exit;
}

if ($action == 'delete_supplier') {
    echo $crud->delete_supplier();
    exit;
}

// ===================================
// 8. HERRAMIENTAS
// ===================================
if ($action == 'save_tool') {
    error_log("AJAX save_tool llamado");
    $result = $crud->save_tool();
    error_log("Respuesta save_tool: $result");
    echo $result;
    exit;
}

if ($action == 'delete_tool') {
    $result = $crud->delete_tool();
    echo $result;
    exit;
}

// ===================================
// 9. ACCESORIOS
// ===================================
if ($action == 'save_accessory') {
    echo $crud->save_accessory();
    exit;
}

if ($action == 'delete_accessory') {
    echo $crud->delete_accessory();
    exit;
}

// ===================================
// 10. MANTENIMIENTOS
// ===================================
if ($action == 'get_mantenimientos') {
    $crud->get_mantenimientos(); // Ya tiene header + exit
    exit;
}

if ($action == 'save_maintenance') {
    echo $crud->save_maintenance();
    exit;
}

if ($action == 'complete_maintenance') {
    echo $crud->complete_maintenance();
    exit;
}

// ===================================
// 11. UBICACIONES
// ===================================
if ($action == 'save_equipment_location') {
    echo $crud->save_equipment_location();
    exit;
}

if ($action == 'delete_equipment_location') {
    echo $crud->delete_equipment_location();
    exit;
}

// ===================================
// 12. PUESTOS DE TRABAJO
// ===================================
if ($action == 'save_job_position') {
    echo $crud->save_job_position();
    exit;
}

if ($action == 'delete_job_position') {
    echo $crud->delete_job_position();
    exit;
}

// ===================================
// 13. INVENTARIO
// ===================================
if ($action == 'save_inventory') {
    error_log("AJAX save_inventory llamado");
    $result = $crud->save_inventory();
    error_log("Respuesta save_inventory: $result");
    echo $result;
    exit;
}

if ($action == 'delete_inventory') {
    error_log("AJAX delete_inventory llamado");
    $result = $crud->delete_inventory();
    error_log("Respuesta delete_inventory: $result");
    echo $result;
    exit;
}

// === FIN ===
ob_end_flush();
?>