<?php
// Evitar inicio de sesión - este es un archivo público
session_start(); // Iniciar pero no validar
define('ACCESS', true);
require_once 'config/config.php';

// Obtener ID del equipo
if (!isset($_GET['equipment_id']) || !is_numeric($_GET['equipment_id'])) {
    die('<div class="alert alert-danger">ID de equipo inválido</div>');
}

$equipment_id = (int)$_GET['equipment_id'];

// Consultar información del equipo
$qry = $conn->query("SELECT e.*, d.name as department_name, l.name as location_name 
                     FROM equipments e 
                     LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
                     LEFT JOIN departments d ON d.id = ed.department_id
                     LEFT JOIN locations l ON l.id = ed.location_id
                     WHERE e.id = $equipment_id");

if (!$qry || $qry->num_rows === 0) {
    die('<div class="alert alert-danger">Equipo no encontrado</div>');
}

$equipment = $qry->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Falla - <?= htmlspecialchars($equipment['name']) ?></title>
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .report-card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .equipment-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .equipment-info strong {
            color: #667eea;
        }
        .btn-report {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 25px;
            transition: transform 0.2s;
        }
        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .success-message {
            display: none;
            text-align: center;
            padding: 30px;
        }
        .success-message i {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .success-message {
                padding: 20px 15px;
            }
            .success-message i {
                font-size: 40px;
            }
            .success-message h4 {
                font-size: 1.25rem;
            }
            .success-message .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="report-card">
        <div class="card-header-custom">
            <h3 class="mb-0"><i class="fas fa-tools"></i> Reportar Falla de Equipo</h3>
        </div>
        
        <div class="card-body p-4" id="formContainer">
            <div class="equipment-info">
                <h5 class="mb-3"><i class="fas fa-desktop"></i> Información del Equipo</h5>
                <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($equipment['name']) ?></p>
                <p class="mb-1"><strong>Marca:</strong> <?= htmlspecialchars($equipment['brand']) ?></p>
                <p class="mb-1"><strong>Modelo:</strong> <?= htmlspecialchars($equipment['model']) ?></p>
                <p class="mb-1"><strong>N° Inventario:</strong> <?= htmlspecialchars($equipment['number_inventory']) ?></p>
                <?php if (!empty($equipment['department_name'])): ?>
                <p class="mb-1"><strong>Departamento:</strong> <?= htmlspecialchars($equipment['department_name']) ?></p>
                <?php endif; ?>
                <?php if (!empty($equipment['location_name'])): ?>
                <p class="mb-0"><strong>Ubicación:</strong> <?= htmlspecialchars($equipment['location_name']) ?></p>
                <?php endif; ?>
            </div>

            <form id="reportForm">
                <input type="hidden" name="equipment_id" value="<?= $equipment_id ?>">
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Tu Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="reporter_name" class="form-control" required placeholder="Nombre completo">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email (opcional)</label>
                    <input type="email" name="reporter_email" class="form-control" placeholder="correo@ejemplo.com">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono (opcional)</label>
                    <input type="tel" name="reporter_phone" class="form-control" placeholder="999 999 9999">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exclamation-circle"></i> Tipo de Falla <span class="text-danger">*</span></label>
                    <select name="issue_type" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <option value="No enciende">No enciende</option>
                        <option value="Error en funcionamiento">Error en funcionamiento</option>
                        <option value="Ruidos extraños">Ruidos extraños</option>
                        <option value="Daño físico">Daño físico</option>
                        <option value="Pantalla rota">Pantalla rota</option>
                        <option value="No imprime/No lee">No imprime/No lee</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment-alt"></i> Descripción de la Falla <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="Describe detalladamente el problema que presenta el equipo..."></textarea>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-report">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Reporte
                    </button>
                </div>
            </form>
        </div>

        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i>
            <h4 class="text-success">¡Reporte Enviado Exitosamente!</h4>
            <p class="text-muted">Tu reporte ha sido registrado. El equipo de soporte se pondrá en contacto contigo pronto.</p>
            <p><strong>Número de Ticket: <span id="ticketNumber"></span></strong></p>
            <button type="button" class="btn btn-secondary mt-3" onclick="location.reload()">
                <i class="fas fa-plus mr-2"></i>Reportar Otra Falla
            </button>
        </div>
    </div>

    <script src="assets/plugins/jquery/jquery.min.js"></script>
    <script>
        $('#reportForm').submit(function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...');
            
            $.ajax({
                url: 'save_public_ticket_direct.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 1) {
                        $('#ticketNumber').text(response.ticket_number || 'N/A');
                        $('#formContainer').fadeOut(300, function() {
                            $('#successMessage').fadeIn(300);
                        });
                    } else {
                        alert('Error: ' + (response.message || 'No se pudo enviar el reporte'));
                        submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Enviar Reporte');
                    }
                },
                error: function() {
                    alert('Error de conexión. Por favor intenta nuevamente.');
                    submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-2"></i>Enviar Reporte');
                }
            });
        });
    </script>
</body>
</html>
