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
    error_log("=== AJAX LOGIN LLAMADO ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("REQUEST data: " . json_encode($_REQUEST));
    $result = $crud->login();
    error_log("Login result: $result");
    error_log("SESSION después de login: " . json_encode($_SESSION));
    echo $result;
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
    error_log("=== AJAX save_user ===");
    error_log("POST data: " . json_encode($_POST));
    error_log("SESSION login_id: " . ($_SESSION['login_id'] ?? 'NOT SET'));
    error_log("SESSION login_type: " . ($_SESSION['login_type'] ?? 'NOT SET'));
    $result = $crud->save_user();
    error_log("Result: " . var_export($result, true));
    echo $result;
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
    header('Content-Type: application/json; charset=utf-8');
    echo $crud->save_equipment_unsubscribe();
    exit;
}

if ($action == 'save_equipment_revision') {
    echo $crud->save_equipment_revision();
    exit;
}

if ($action == 'upload_excel_equipment') {
    error_log("AJAX upload_excel_equipment llamado");
    $result = $crud->upload_excel_equipment();
    error_log("Respuesta upload_excel_equipment: $result");
    echo $result;
    exit;
}

if ($action == 'download_template') {
    // Limpiar buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Configurar encabezados para descarga de Excel
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="plantilla_equipos_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    header('Pragma: public');
    
    // Generar contenido de la plantilla
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        .example { background-color: #f0f0f0; font-style: italic; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Serie</th>
                <th>Nombre</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Tipo de Adquisición</th>
                <th>Características</th>
                <th>Disciplina</th>
                <th>Proveedor</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <tr class="example">
                <td>EQ-001-2024</td>
                <td>Laptop Dell</td>
                <td>Dell</td>
                <td>Latitude 5520</td>
                <td>Compra</td>
                <td>Intel i5, 16GB RAM, 512GB SSD</td>
                <td>Informática</td>
                <td>Dell México</td>
                <td>1</td>
            </tr>
            <tr class="example">
                <td>EQ-002-2024</td>
                <td>Proyector</td>
                <td>Epson</td>
                <td>PowerLite X49</td>
                <td>Donación</td>
                <td>3LCD, 3600 lúmenes, HDMI</td>
                <td>Audiovisual</td>
                <td>Epson</td>
                <td>1</td>
            </tr>
            <tr class="example">
                <td>EQ-003-2024</td>
                <td>Impresora</td>
                <td>HP</td>
                <td>LaserJet Pro M404dn</td>
                <td>Comodato</td>
                <td>Blanco y negro, 38 ppm, dúplex</td>
                <td>Oficina</td>
                <td></td>
                <td>2</td>
            </tr>
            <?php for ($i = 0; $i < 20; $i++): ?>
            <tr>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</body>
</html>
    <?php
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

if ($action == 'save_maintenance_report') {
    echo $crud->save_maintenance_report();
    exit;
}

if ($action == 'get_equipo_details') {
    echo $crud->get_equipo_details(); 
    exit;
}

if ($action == 'update_and_save_report') {
}

// ================== SERVICIOS Y CATEGORÍAS ==================
if ($action == 'save_category') {
    echo $crud->save_category();
    exit;
}

if ($action == 'delete_service_category') {
    echo $crud->delete_service_category();
    exit;
}

if ($action == 'load_service_category') {
    echo $crud->load_service_category();
    exit;
}

if ($action == 'save_service') {
    echo $crud->save_service();
    exit;
}

if ($action == 'delete_service') {
    echo $crud->delete_service();
    exit;
}

if ($action == 'load_service') {
    echo $crud->load_service();
    exit;
}

ob_end_flush();
?>