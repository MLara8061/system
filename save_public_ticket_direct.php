<?php
// Endpoint público para crear tickets sin autenticación
// NO usar admin_class.php que requiere sesión

define('ACCESS', true);
require_once 'config/config.php';

header('Content-Type: application/json');

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 0, 'message' => 'Método no permitido']);
    exit;
}

try {
    $equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
    $reporter_name = isset($_POST['reporter_name']) ? $conn->real_escape_string(trim($_POST['reporter_name'])) : '';
    $reporter_email = isset($_POST['reporter_email']) ? $conn->real_escape_string(trim($_POST['reporter_email'])) : '';
    $reporter_phone = isset($_POST['reporter_phone']) ? $conn->real_escape_string(trim($_POST['reporter_phone'])) : '';
    $issue_type = isset($_POST['issue_type']) ? $conn->real_escape_string($_POST['issue_type']) : '';
    $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
    
    if ($equipment_id <= 0 || empty($reporter_name) || empty($description)) {
        echo json_encode(['status' => 0, 'message' => 'Datos incompletos']);
        exit;
    }
    
    // Obtener información del equipo
    $eq_query = $conn->query("SELECT name, number_inventory FROM equipments WHERE id = $equipment_id");
    if (!$eq_query || $eq_query->num_rows === 0) {
        echo json_encode(['status' => 0, 'message' => 'Equipo no encontrado']);
        exit;
    }
    $equipment = $eq_query->fetch_assoc();
    
    // Generar número de ticket
    $ticket_number = 'PUB-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    
    // Crear el subject del ticket
    $subject = "Falla reportada: {$issue_type} - {$equipment['name']} (#{$equipment['number_inventory']})";
    
    // Crear descripción completa
    $full_description = "**REPORTE PÚBLICO VÍA QR**\n\n";
    $full_description .= "**Equipo:** {$equipment['name']}\n";
    $full_description .= "**N° Inventario:** {$equipment['number_inventory']}\n";
    $full_description .= "**Tipo de Falla:** $issue_type\n\n";
    $full_description .= "**Reportado por:** $reporter_name\n";
    if ($reporter_email) $full_description .= "**Email:** $reporter_email\n";
    if ($reporter_phone) $full_description .= "**Teléfono:** $reporter_phone\n";
    $full_description .= "\n**Descripción:**\n$description";
    
    // Insertar ticket
    $sql = "INSERT INTO tickets SET 
            subject = '$subject',
            description = '" . htmlentities(str_replace("'", "&#x2019;", $full_description)) . "',
            status = 0,
            equipment_id = $equipment_id,
            reporter_name = '$reporter_name',
            reporter_email = '$reporter_email',
            reporter_phone = '$reporter_phone',
            issue_type = '$issue_type',
            ticket_number = '$ticket_number',
            is_public = 1,
            date_created = NOW()";
    
    $save = $conn->query($sql);
    
    if (!$save) {
        error_log("Error al guardar ticket público: " . $conn->error);
        echo json_encode(['status' => 0, 'message' => 'Error al guardar el ticket: ' . $conn->error]);
        exit;
    }
    
    $ticket_id = $conn->insert_id;
    
    echo json_encode([
        'status' => 1,
        'ticket_id' => $ticket_id,
        'ticket_number' => $ticket_number,
        'message' => 'Ticket creado exitosamente'
    ]);
    
} catch (Exception $e) {
    error_log("Exception en save_public_ticket_direct: " . $e->getMessage());
    echo json_encode(['status' => 0, 'message' => 'Error inesperado: ' . $e->getMessage()]);
}
?>
