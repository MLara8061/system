<?php
// Habilitar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';

// === VALIDAR ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger text-center'>ID inválido</div>";
    exit;
}
$equipment_id = (int)$_GET['id'];

// === CONSULTA PRINCIPAL ===
$qry = $conn->query("SELECT e.*, s.empresa as supplier_name FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id WHERE e.id = $equipment_id");
if ($qry->num_rows == 0) {
    echo "<div class='alert alert-warning text-center'>Equipo no encontrado</div>";
    exit;
}
$eq = $qry->fetch_assoc();

// === RELACIONES ===
$reception = $delivery = $safeguard = $documents = $power_spec = [];
$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $reception = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $delivery = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $safeguard = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = $equipment_id");
if ($qry->num_rows > 0) $documents = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_power_specs WHERE equipment_id = $equipment_id ORDER BY id DESC LIMIT 1");
if ($qry->num_rows > 0) $power_spec = $qry->fetch_assoc();

// === NOMBRES ADICIONALES ===
$dept = $conn->query("SELECT name FROM departments WHERE id = " . ($delivery['department_id'] ?? 0))->fetch_assoc()['name'] ?? 'N/A';
$loc = $conn->query("SELECT name FROM locations WHERE id = " . ($delivery['location_id'] ?? 0))->fetch_assoc()['name'] ?? 'N/A';
$pos = $conn->query("SELECT name FROM job_positions WHERE id = " . ($delivery['responsible_position'] ?? 0))->fetch_assoc()['name'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($eq['name']); ?> - Ficha Técnica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .info-label { font-weight: 600; color: #495057; font-size: 0.95rem; }
        .info-value { font-size: 1.1rem; color: #212529; }
        .badge-inv { font-size: 1.3rem; padding: 0.5em 1em; }
        .btn-revision { 
            background: linear-gradient(45deg, #007bff, #0056b3); 
            border: none; 
            padding: 12px 30px; 
            font-size: 1.1rem; 
            border-radius: 50px; 
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        .btn-revision:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,123,255,0.4); }
    </style>
</head>
<body>
<div class="container-fluid py-5">
    <div class="card">
        <div class="card-body p-0">

            <!-- === IMAGEN + INFO CLAVE === -->
            <div class="row g-0">
                <!-- IMAGEN -->
                <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                    <div class="text-center w-100">
                        <?php if (!empty($eq['image'])): ?>
                            <img src="<?= $eq['image'] ?>" class="img-fluid rounded shadow" 
                                 style="max-height: 380px; object-fit: contain;">
                        <?php else: ?>
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center" 
                                 style="height: 380px; border: 3px dashed #ccc;">
                                <i class="fas fa-camera fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- INFORMACIÓN CLAVE -->
                <div class="col-lg-7 p-5">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h3 class="mb-1"><?php echo htmlspecialchars($eq['name']); ?></h3>
                            <span class="badge bg-primary badge-inv">#<?= $eq['number_inventory'] ?></span>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-label">Marca</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['brand']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Modelo</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['model']); ?></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-label">Serie</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['serie']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Fecha Ingreso</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($eq['date_created'])); ?></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-label">Valor</div>
                            <div class="info-value">$<?php echo number_format($eq['amount'], 2); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Categoría</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['discipline']); ?></div>
                        </div>
                    </div>

                    <!-- CONSUMO ELÉCTRICO -->
                    <?php if (!empty($power_spec)): ?>
                    <div class="bg-light p-4 rounded mb-4">
                        <h6 class="mb-3 text-dark">
                            <i class="fas fa-bolt text-warning"></i> Consumo Eléctrico
                        </h6>
                        <div class="row text-center">
                            <div class="col">
                                <div class="info-label small">Voltaje</div>
                                <div class="info-value"><?php echo $power_spec['voltage']; ?> V</div>
                            </div>
                            <div class="col">
                                <div class="info-label small">Amperaje</div>
                                <div class="info-value"><?php echo $power_spec['amperage']; ?> A</div>
                            </div>
                            <div class="col">
                                <div class="info-label small">Frecuencia</div>
                                <div class="info-value"><?php echo $power_spec['frequency_hz']; ?> Hz</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-label">Proveedor</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['supplier_name'] ?: 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Adquisición</div>
                            <div class="info-value">
                                <?php 
                                $type = $conn->query("SELECT name FROM acquisition_type WHERE id = " . ($eq['acquisition_type'] ?? 0))->fetch_assoc()['name'] ?? 'N/A';
                                echo htmlspecialchars($type);
                                ?>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- ENTREGA -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-label">Departamento</div>
                            <div class="info-value"><?php echo htmlspecialchars($dept); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label">Ubicación</div>
                            <div class="info-value"><?php echo htmlspecialchars($loc); ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-label">Cargo Responsable</div>
                            <div class="info-value"><?php echo htmlspecialchars($pos); ?></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-label">Responsable</div>
                            <div class="info-value"><?php echo htmlspecialchars($delivery['responsible_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Capacitación</div>
                            <div class="info-value">
                                <?php echo isset($delivery['date_training']) ? date('d/m/Y', strtotime($delivery['date_training'])) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- BOTÓN SOLICITAR REVISIÓN -->
                    <div class="text-center mt-5">
                        <a href="index.php?page=equipment_new_revision&id=<?= $equipment_id ?>" 
                           class="btn btn-revision text-white">
                            <i class="fas fa-tools me-2"></i> Solicitar Revisión
                        </a>
                    </div>
                </div>
            </div>

            <!-- === CARACTERÍSTICAS === -->
            <?php if (!empty($eq['characteristics'])): ?>
            <div class="p-5 bg-white border-top">
                <h5 class="mb-3 text-primary">
                    <i class="fas fa-cogs"></i> Características Técnicas
                </h5>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($eq['characteristics'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- === DOCUMENTOS === -->
            <?php 
            $has_docs = false;
            $doc_fields = [
                'bailment_file' => 'Comodato',
                'contract_file' => 'Contrato M',
                'usermanual_file' => 'Manual Usuario',
                'fast_guide_file' => 'Guía Rápida',
                'datasheet_file' => 'Ficha Técnica',
                'servicemanual_file' => 'Man. Servicios'
            ];
            foreach ($doc_fields as $field => $label):
                if (!empty($documents[$field]) && file_exists($documents[$field])):
                    $has_docs = true;
                    break;
                endif;
            endforeach;

            if ($has_docs):
            ?>
            <div class="p-5 bg-light border-top">
                <h5 class="mb-4 text-primary">
                    <i class="fas fa-file-alt"></i> Documentos
                </h5>
                <div class="row">
                    <?php foreach ($doc_fields as $field => $label):
                        $file = $documents[$field] ?? '';
                        if ($file && file_exists($file)):
                            $filename = basename($file);
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="border p-3 rounded bg-white d-flex justify-content-between align-items-center">
                                <small class="text-truncate" style="max-width: 160px;" title="<?= $filename ?>">
                                    <i class="fas fa-file-pdf text-danger me-1"></i>
                                    <?= htmlspecialchars($label) ?>
                                </small>
                                <a href="<?= $file ?>" target="_blank" class="text-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- === HISTORIAL DE MANTENIMIENTOS === -->
            <?php
            $maintenance_qry = $conn->query("
                SELECT mr.*, 
                       u.firstname as tech_firstname, u.lastname as tech_lastname,
                       v.firstname as val_firstname, v.lastname as val_lastname
                FROM maintenance_reports mr
                LEFT JOIN users u ON mr.technician_id = u.id
                LEFT JOIN users v ON mr.validator_id = v.id
                WHERE mr.equipment_id = $equipment_id
                ORDER BY mr.report_date DESC, mr.report_time DESC
            ");
            
            if ($maintenance_qry && $maintenance_qry->num_rows > 0):
            ?>
            <div class="p-5 bg-white border-top">
                <h5 class="mb-4 text-primary">
                    <i class="fas fa-history"></i> Historial de Mantenimientos
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover" id="maintenanceTable">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Técnico</th>
                                <th>Validado por</th>
                                <th>Tipo</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($maint = $maintenance_qry->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $maint['report_date'] ? date('d/m/Y', strtotime($maint['report_date'])) : 'N/A'; ?></td>
                                <td><?php echo $maint['report_time'] ? date('H:i', strtotime($maint['report_time'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars(trim(($maint['tech_firstname'] ?? '') . ' ' . ($maint['tech_lastname'] ?? '')) ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(trim(($maint['val_firstname'] ?? '') . ' ' . ($maint['val_lastname'] ?? '')) ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $maint['execution_type'] == 'Correctivo' ? 'danger' : 'success'; ?>">
                                        <?php echo htmlspecialchars($maint['execution_type'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="report_pdf.php?id=<?php echo $maint['id']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-primary"
                                       title="Ver reporte PDF">
                                        <i class="fas fa-file-pdf"></i> Ver PDF
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    <?php if ($maintenance_qry && $maintenance_qry->num_rows > 0): ?>
    $('#maintenanceTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'desc'], [1, 'desc']],
        pageLength: 10
    });
    <?php endif; ?>
});
</script>
</body>
</html>