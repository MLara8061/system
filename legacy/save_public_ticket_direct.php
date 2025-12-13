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
    
    // Generar ticket_number único
    $ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Crear el subject del ticket
    $subject = "Falla reportada: {$issue_type} - {$equipment['name']} (#{$equipment['number_inventory']})";
    $subject_escaped = $conn->real_escape_string($subject);
    
    // Crear descripción completa con formato HTML
    $full_description = "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin-bottom: 15px;'>";
    $full_description .= "<p style='margin: 0; color: #6c757d; font-size: 12px;'><strong>📱 REPORTE PÚBLICO VÍA QR</strong></p>";
    $full_description .= "</div>";
    $full_description .= "<p><strong>🖥️ Equipo:</strong> {$equipment['name']}</p>";
    $full_description .= "<p><strong>📋 N° Inventario:</strong> {$equipment['number_inventory']}</p>";
    $full_description .= "<p><strong>⚠️ Tipo de Falla:</strong> $issue_type</p>";
    $full_description .= "<hr style='margin: 15px 0; border: none; border-top: 1px solid #dee2e6;'>";
    $full_description .= "<p><strong>👤 Reportado por:</strong> $reporter_name</p>";
    if ($reporter_email) $full_description .= "<p><strong>📧 Email:</strong> $reporter_email</p>";
    if ($reporter_phone) $full_description .= "<p><strong>📞 Teléfono:</strong> $reporter_phone</p>";
    $full_description .= "<hr style='margin: 15px 0; border: none; border-top: 1px solid #dee2e6;'>";
    $full_description .= "<p><strong>📝 Descripción de la Falla:</strong></p>";
    $full_description .= "<p style='background: #fff3cd; padding: 10px; border-radius: 5px;'>" . nl2br(htmlspecialchars($description)) . "</p>";
    $description_escaped = $conn->real_escape_string($full_description);
    
    // Obtener department_id del equipo si existe
    $dept_query = $conn->query("SELECT department_id FROM equipment_delivery WHERE equipment_id = $equipment_id LIMIT 1");
    $department_id = 0;
    if ($dept_query && $dept_query->num_rows > 0) {
        $dept_row = $dept_query->fetch_assoc();
        $department_id = intval($dept_row['department_id'] ?? 0);
    }
    
    // Si no hay department_id, usar uno por defecto o NULL
    $dept_value = $department_id > 0 ? $department_id : 'NULL';
    
    // Insertar ticket
    $sql = "INSERT INTO tickets SET 
            subject = '$subject_escaped',
            description = '$description_escaped',
            status = 0,
            customer_id = 0,
            department_id = $dept_value,
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
