<?php
// generate_pdf.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar zona horaria de México
date_default_timezone_set('America/Cancun');

session_start();
define('ACCESS', true);
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid access.");
}

// === RECIBIR DATOS ===
$order_number = $_POST['orden_mto'] ?? '';
$report_date = $_POST['fecha_reporte'] ?? '';
$report_time = date('H:i'); // Hora de generación
$engineer_name = $_POST['ingeniero_nombre'] ?? 'ING. AMALIA BACAB';

$client_name = $_POST['cliente_nombre'] ?? '';
$client_phone = $_POST['cliente_tel'] ?? '';
$client_address = $_POST['cliente_domicilio'] ?? '';
$client_email = $_POST['cliente_email'] ?? '';

$equipment_id = $_POST['equipo_id_select'] ?? 0;
$equipment_name = $_POST['equipo_nombre'] ?? '';
$equipment_brand = $_POST['equipo_marca'] ?? '';
$equipment_model = $_POST['equipo_modelo'] ?? '';
$equipment_serial = $_POST['equipo_serie'] ?? '';
$equipment_inventory_code = $_POST['equipo_inventario'] ?? '';
$equipment_location = $_POST['equipo_ubicacion'] ?? '';
$location_id = $_POST['location_id'] ?? 0;

$service_type = $_POST['tipo_servicio'] ?? 'MP';
$execution_type = $_POST['ejecucion'] ?? 'PLAZA';
$service_date = $_POST['service_date'] ?? '';
$service_start_time = $_POST['service_start_time'] ?? '';
$service_end_time = $_POST['service_end_time'] ?? '';
$description = $_POST['descripcion'] ?? '';
$observations = $_POST['observaciones'] ?? '';
$final_status = $_POST['status_final'] ?? 'FUNCIONAL';
$received_by = $_POST['recibe_nombre'] ?? '';

// === REFACCIONES ===
$parts_used = [];
if (isset($_POST['refaccion_item_id']) && is_array($_POST['refaccion_item_id'])) {
    for ($i = 0; $i < count($_POST['refaccion_item_id']); $i++) {
        if (!empty($_POST['refaccion_item_id'][$i])) {
            $parts_used[] = [
                'item_id' => (int)$_POST['refaccion_item_id'][$i],
                'quantity' => (int)($_POST['refaccion_qty'][$i] ?? 1)
            ];
        }
    }
}
$parts_used_json = json_encode($parts_used, JSON_UNESCAPED_UNICODE);

// === VERIFICAR SI EXISTEN LAS COLUMNAS NUEVAS ===
$check = $conn->query("SHOW COLUMNS FROM maintenance_reports LIKE 'service_date'");
$has_new_columns = ($check && $check->num_rows > 0);

// === INSERTAR REPORTE ===
if ($has_new_columns) {
    // Con las columnas nuevas
    $stmt = $conn->prepare("
        INSERT INTO maintenance_reports (
            order_number, report_date, report_time, engineer_name,
            client_name, client_phone, client_address, client_email,
            equipment_id, equipment_name, equipment_brand, equipment_model, equipment_serial, equipment_inventory_code, equipment_location, location_id,
            service_type, execution_type, service_date, service_start_time, service_end_time, description, observations, final_status, received_by,
            parts_used
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->bind_param(
        "sssssssssssssissssssssssss",
        $order_number, $report_date, $report_time, $engineer_name,
        $client_name, $client_phone, $client_address, $client_email,
        $equipment_id, $equipment_name, $equipment_brand, $equipment_model, $equipment_serial, $equipment_inventory_code, $equipment_location, $location_id,
        $service_type, $execution_type, $service_date, $service_start_time, $service_end_time, $description, $observations, $final_status, $received_by,
        $parts_used_json
    );
} else {
    // Sin las columnas nuevas (versión anterior)
    $stmt = $conn->prepare("
        INSERT INTO maintenance_reports (
            order_number, report_date, report_time, engineer_name,
            client_name, client_phone, client_address, client_email,
            equipment_id, equipment_name, equipment_brand, equipment_model, equipment_serial, equipment_inventory_code, equipment_location, location_id,
            service_type, execution_type, description, observations, final_status, received_by,
            parts_used
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->bind_param(
        "ssssssssssssissssssssss",
        $order_number, $report_date, $report_time, $engineer_name,
        $client_name, $client_phone, $client_address, $client_email,
        $equipment_id, $equipment_name, $equipment_brand, $equipment_model, $equipment_serial, $equipment_inventory_code, $equipment_location, $location_id,
        $service_type, $execution_type, $description, $observations, $final_status, $received_by,
        $parts_used_json
    );
}

if (!$stmt->execute()) {
    die("Error saving report: " . $stmt->error . " | " . $conn->error);
}
$report_id = $conn->insert_id;
$stmt->close();

// === DESCONTAR STOCK ===
foreach ($parts_used as $part) {
    $stmt = $conn->prepare("UPDATE inventory SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmt->bind_param("iii", $part['quantity'], $part['item_id'], $part['quantity']);
    $stmt->execute();
    $stmt->close();
}

// === REDIRIGIR A PDF ===
header("Location: report_pdf.php?id=" . $report_id);
exit;
?>