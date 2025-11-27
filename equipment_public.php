<?php
// Vista pública de equipos - sin requerir autenticación
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/config/config.php';

// === VALIDAR ID ===
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger text-center'>ID inválido</div>";
    exit;
}
$equipment_id = (int)$_GET['id'];

// === CONSULTA PRINCIPAL ===
$qry = $conn->query("SELECT e.*, s.empresa as supplier_name FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id WHERE e.id = $equipment_id");
if (!$qry || $qry->num_rows == 0) {
    echo "<div class='alert alert-warning text-center'>Equipo no encontrado</div>";
    exit;
}
$eq = $qry->fetch_assoc();

// === RELACIONES ===
$reception = $delivery = $safeguard = $documents = $power_spec = [];

$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) $reception = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) $delivery = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) $safeguard = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) $documents = $qry->fetch_assoc();

$qry = $conn->query("SELECT * FROM equipment_power_specs WHERE equipment_id = $equipment_id ORDER BY id DESC LIMIT 1");
if ($qry && $qry->num_rows > 0) $power_spec = $qry->fetch_assoc();

// === NOMBRES ADICIONALES ===
$dept = 'N/A';
$qry = $conn->query("SELECT name FROM departments WHERE id = " . intval($delivery['department_id'] ?? 0));
if ($qry && $qry->num_rows > 0) $dept = $qry->fetch_assoc()['name'];

$loc = 'N/A';
$qry = $conn->query("SELECT name FROM locations WHERE id = " . intval($delivery['location_id'] ?? 0));
if ($qry && $qry->num_rows > 0) $loc = $qry->fetch_assoc()['name'];

$pos = 'N/A';
$qry = $conn->query("SELECT name FROM job_positions WHERE id = " . intval($delivery['responsible_position'] ?? 0));
if ($qry && $qry->num_rows > 0) $pos = $qry->fetch_assoc()['name'];
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($eq['name']); ?> - Consulta Pública</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            padding: 10px; 
        }
        .card { 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        .info-label { 
            font-weight: 600; 
            color: #495057; 
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        .info-value { 
            font-size: 1rem; 
            color: #212529; 
            word-break: break-word;
        }
        .badge-inv { 
            font-size: 1.1rem; 
            padding: 0.4em 0.8em; 
        }
        .section-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 1.5rem; 
            margin: 0;
        }
        .section-header h3 {
            font-size: 1.5rem;
            word-break: break-word;
            padding-right: 100px;
        }
        .public-badge { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            font-size: 0.75rem; 
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 5px; }
            .section-header { padding: 1rem; }
            .section-header h3 { 
                font-size: 1.2rem; 
                padding-right: 80px;
            }
            .badge-inv { font-size: 1rem; }
            .info-label { font-size: 0.8rem; }
            .info-value { font-size: 0.95rem; }
            .public-badge { 
                font-size: 0.7rem;
                top: 10px;
                right: 10px;
            }
            .table-responsive { overflow-x: auto; }
        }
        
        @media (max-width: 576px) {
            .section-header h3 { 
                font-size: 1rem;
                padding-right: 70px;
            }
            .btn-lg { 
                width: 100%;
                padding: 0.75rem 1rem;
            }
        }
        
        /* Estilos personalizados para badges */
        .badge-primary {
            background-color: #cfe2ff !important;
            color: #084298 !important;
            border: 1px solid #b6d4fe;
        }
        
        .badge-success {
            background-color: #d1e7dd !important;
            color: #0f5132 !important;
            border: 1px solid #badbcc;
        }
        
        .badge-danger {
            background-color: #f8d7da !important;
            color: #842029 !important;
            border: 1px solid #f5c2c7;
        }
        
        .badge {
            font-weight: 600 !important;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4" style="max-width: 1200px;">
    <div class="card">
        <div class="card-body p-0">

            <!-- === HEADER === -->
            <div class="section-header position-relative">
                <span class="badge bg-light text-dark public-badge">
                    <i class="fas fa-eye"></i> Vista Pública
                </span>
                <h3 class="mb-2"><?php echo htmlspecialchars($eq['name']); ?></h3>
                <h5 class="mb-0">
                    <span class="badge bg-white text-primary badge-inv">
                        #<?= $eq['number_inventory'] ?>
                    </span>
                </h5>
            </div>

            <!-- === IMAGEN + INFO CLAVE === -->
            <div class="row g-0 p-4">
                <!-- IMAGEN -->
                <div class="col-lg-5 d-flex align-items-center justify-content-center mb-4 mb-lg-0">
                    <?php if (!empty($eq['image'])): ?>
                        <img src="<?= $eq['image'] ?>" class="img-fluid rounded shadow" 
                             style="max-height: 350px; object-fit: contain;">
                    <?php else: ?>
                        <div class="bg-light border rounded d-flex align-items-center justify-content-center" 
                             style="height: 350px; width: 100%; border: 3px dashed #ccc;">
                            <i class="fas fa-camera fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- INFORMACIÓN PRINCIPAL -->
                <div class="col-lg-7 ps-lg-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label">Marca</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['brand']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Modelo</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['model']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Serie</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['serie']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Fecha Ingreso</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($eq['date_created'])); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Categoría</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['discipline']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Proveedor</div>
                            <div class="info-value"><?php echo htmlspecialchars($eq['supplier_name'] ?: 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Tipo de Adquisición</div>
                            <div class="info-value">
                                <?php 
                                $type_qry = $conn->query("SELECT name FROM acquisition_type WHERE id = " . intval($eq['acquisition_type'] ?? 0));
                                $type = ($type_qry && $type_qry->num_rows > 0) ? $type_qry->fetch_assoc()['name'] : 'N/A';
                                echo htmlspecialchars($type);
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- CONSUMO ELÉCTRICO -->
                    <?php if (!empty($power_spec)): ?>
                    <div class="bg-light p-3 rounded mt-4">
                        <h6 class="mb-3 text-dark">
                            <i class="fas fa-bolt text-warning"></i> Consumo Eléctrico
                        </h6>
                        <div class="row text-center g-3">
                            <div class="col-4">
                                <div class="info-label small">Voltaje</div>
                                <div class="info-value"><?php echo $power_spec['voltage']; ?> V</div>
                            </div>
                            <div class="col-4">
                                <div class="info-label small">Amperaje</div>
                                <div class="info-value"><?php echo $power_spec['amperage']; ?> A</div>
                            </div>
                            <div class="col-4">
                                <div class="info-label small">Frecuencia</div>
                                <div class="info-value"><?php echo $power_spec['frequency_hz']; ?> Hz</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="my-4 mx-4">

            <!-- === UBICACIÓN Y RESPONSABLE === -->
            <div class="px-4 pb-4">
                <h5 class="mb-3 text-primary">
                    <i class="fas fa-map-marker-alt"></i> Ubicación y Responsable
                </h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="info-label">Departamento</div>
                        <div class="info-value"><?php echo htmlspecialchars($dept); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Ubicación</div>
                        <div class="info-value"><?php echo htmlspecialchars($loc); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Cargo</div>
                        <div class="info-value"><?php echo htmlspecialchars($pos); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-label">Responsable</div>
                        <div class="info-value"><?php echo htmlspecialchars($delivery['responsible_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Fecha de Capacitación</div>
                        <div class="info-value">
                            <?php echo isset($delivery['date_training']) ? date('d/m/Y', strtotime($delivery['date_training'])) : 'N/A'; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">Fecha de Adquisición</div>
                        <div class="info-value">
                            <?= isset($reception['date_reception']) ? date('d/m/Y', strtotime($reception['date_reception'])) : 'N/A' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === CARACTERÍSTICAS === -->
            <?php if (!empty($eq['characteristics'])): ?>
            <div class="px-4 pb-4">
                <hr class="mb-4">
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
                'usermanual_file' => 'Manual Usuario',
                'datasheet_file' => 'Ficha Técnica',
                'fast_guide_file' => 'Guía Rápida'
            ];
            foreach ($doc_fields as $field => $label):
                if (!empty($documents[$field]) && file_exists($documents[$field])):
                    $has_docs = true;
                    break;
                endif;
            endforeach;

            if ($has_docs):
            ?>
            <div class="px-4 pb-4">
                <hr class="mb-4">
                <h5 class="mb-3 text-primary">
                    <i class="fas fa-file-alt"></i> Documentos
                </h5>
                <div class="row g-3">
                    <?php foreach ($doc_fields as $field => $label):
                        $file = $documents[$field] ?? '';
                        if ($file && file_exists($file)):
                            $filename = basename($file);
                    ?>
                        <div class="col-md-4">
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
                SELECT * 
                FROM maintenance_reports
                WHERE equipment_id = $equipment_id
                ORDER BY service_date DESC, service_start_time DESC
            ");
            ?>
            <div class="px-4 pb-4">
                <hr class="mb-4">
                <h5 class="mb-3 text-primary">
                    <i class="fas fa-history"></i> Historial de Mantenimientos
                </h5>
                <?php if ($maintenance_qry && $maintenance_qry->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="maintenanceTable">
                        <thead class="table-light">
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
                                <td><?php echo !empty($maint['service_date']) ? date('d/m/Y', strtotime($maint['service_date'])) : 'N/A'; ?></td>
                                <td><?php echo !empty($maint['service_start_time']) ? date('H:i', strtotime($maint['service_start_time'])) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($maint['engineer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($maint['received_by'] ?? 'N/A'); ?></td>
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
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay registros de mantenimiento para este equipo.
                </div>
                <?php endif; ?>
            </div>

            <!-- BOTÓN REPORTAR EQUIPO -->
            <div class="px-4 pb-4">
                <hr class="mb-4">
                <div class="text-center">
                    <a href="index.php?page=new_ticket&equipment_id=<?php echo $equipment_id; ?>&equipment_name=<?php echo urlencode($eq['name']); ?>&inventory=<?php echo urlencode($eq['number_inventory']); ?>" 
                       class="btn btn-lg btn-danger px-5">
                        <i class="fas fa-exclamation-triangle me-2"></i> Reportar Equipo
                    </a>
                    <p class="text-muted mt-2 mb-0">
                        <small>Reportar algún problema o solicitar soporte técnico</small>
                    </p>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="text-center py-3 bg-light border-top">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Para más información, contacte al departamento correspondiente
                </small>
            </div>
        </div>
    </div>
</div>

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
        pageLength: 10,
        responsive: true
    });
    <?php endif; ?>
});
</script>
</body>
</html>