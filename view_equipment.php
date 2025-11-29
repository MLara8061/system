<?php
define('ACCESS', true);
require_once 'config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID inválido'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$equipment_id = (int)$_GET['id'];

$qry = $conn->query("SELECT * FROM equipments WHERE id = $equipment_id");
if (!$qry || $qry->num_rows === 0) {
    echo "<script>alert('Equipo no encontrado'); window.location='index.php?page=equipment_list';</script>";
    exit;
}
$eq = $qry->fetch_assoc();

if (!function_exists('equipment_file_exists')) {
    function equipment_file_exists($path)
    {
        if (empty($path)) {
            return false;
        }
        $fullPath = $path;
        if (!file_exists($fullPath)) {
            $fullPath = __DIR__ . '/' . ltrim($path, '/');
        }
        return file_exists($fullPath);
    }
}

$reception = $delivery = $safeguard = $documents = $power_spec = [];
$qry = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) {
    $reception = $qry->fetch_assoc();
}

$qry = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) {
    $delivery = $qry->fetch_assoc();
}

$qry = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) {
    $safeguard = $qry->fetch_assoc();
}

$qry = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = $equipment_id");
if ($qry && $qry->num_rows > 0) {
    $documents = $qry->fetch_assoc();
}

$qry = $conn->query("SELECT * FROM equipment_power_specs WHERE equipment_id = $equipment_id ORDER BY id DESC LIMIT 1");
if ($qry && $qry->num_rows > 0) {
    $power_spec = $qry->fetch_assoc();
}

$supplierName = 'N/A';
if (!empty($eq['supplier_id'])) {
    $supplierRes = $conn->query('SELECT empresa FROM suppliers WHERE id = ' . (int)$eq['supplier_id']);
    if ($supplierRes && $supplierRes->num_rows > 0) {
        $supplierName = $supplierRes->fetch_assoc()['empresa'];
    }
}

$acquisitionName = 'N/A';
if (!empty($eq['acquisition_type'])) {
    $acquisitionRes = $conn->query('SELECT name FROM acquisition_type WHERE id = ' . (int)$eq['acquisition_type']);
    if ($acquisitionRes && $acquisitionRes->num_rows > 0) {
        $acquisitionName = $acquisitionRes->fetch_assoc()['name'];
    }
}

$maintenancePeriod = 'N/A';
if (!empty($eq['mandate_period_id'])) {
    $periodRes = $conn->query('SELECT name FROM maintenance_periods WHERE id = ' . (int)$eq['mandate_period_id']);
    if ($periodRes && $periodRes->num_rows > 0) {
        $maintenancePeriod = $periodRes->fetch_assoc()['name'];
    }
}

$departmentName = 'N/A';
if (!empty($delivery['department_id'])) {
    $departmentRes = $conn->query('SELECT name FROM departments WHERE id = ' . (int)$delivery['department_id']);
    if ($departmentRes && $departmentRes->num_rows > 0) {
        $departmentName = $departmentRes->fetch_assoc()['name'];
    }
}

$locationName = 'N/A';
if (!empty($delivery['location_id'])) {
    $locationRes = $conn->query('SELECT name FROM locations WHERE id = ' . (int)$delivery['location_id']);
    if ($locationRes && $locationRes->num_rows > 0) {
        $locationName = $locationRes->fetch_assoc()['name'];
    }
}

$positionName = 'N/A';
if (!empty($delivery['responsible_position'])) {
    $positionRes = $conn->query('SELECT name FROM job_positions WHERE id = ' . (int)$delivery['responsible_position']);
    if ($positionRes && $positionRes->num_rows > 0) {
        $positionName = $positionRes->fetch_assoc()['name'];
    }
}

$receptionStateText = 'Sin registro';
if (isset($reception['state'])) {
    $receptionStateText = ((int)$reception['state'] === 1) ? 'Acepto' : 'Rechazo';
}

$dateCreated = !empty($eq['date_created']) ? date('Y-m-d', strtotime($eq['date_created'])) : '';
$dateTraining = !empty($delivery['date_training']) ? date('Y-m-d', strtotime($delivery['date_training'])) : '';
$dateAdquisition = !empty($safeguard['date_adquisition']) ? date('Y-m-d', strtotime($safeguard['date_adquisition'])) : '';

$imageExists = !empty($eq['image']) && equipment_file_exists($eq['image']);

$docFields = [
    'bailment_file' => 'Comodato',
    'contract_file' => 'Contrato M',
    'usermanual_file' => 'Manual Usuario',
    'fast_guide_file' => 'Guía Rápida',
    'datasheet_file' => 'Ficha Técnica',
    'servicemanual_file' => 'Man. Servicios'
];

$maintenance_query = $conn->query("SELECT * FROM maintenance_reports WHERE equipment_id = {$equipment_id} ORDER BY report_date DESC, report_time DESC");
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-lg-5 bg-light d-flex align-items-center justify-content-center p-4">
                    <div class="text-center w-100 position-relative" style="min-height: 420px;">
                        <?php if ($imageExists): ?>
                            <img src="<?= htmlspecialchars($eq['image']) ?>" class="img-fluid rounded shadow" style="max-height: 380px; object-fit: contain;">
                        <?php else: ?>
                            <div class="bg-white border-dashed rounded d-flex align-items-center justify-content-center" style="height: 380px; border: 3px dashed #ccc;">
                                <i class="fas fa-camera fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-7 p-5">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-8">
                            <label class="text-muted small">Nombre del equipo</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eq['name']) ?>" readonly disabled>
                        </div>
                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <span class="badge badge-primary font-weight-bold p-2" style="font-size: 1.1rem;">#<?= htmlspecialchars($eq['number_inventory']) ?></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Marca</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eq['brand']) ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Modelo</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eq['model']) ?>" readonly disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Serie</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eq['serie']) ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Fecha de ingreso</label>
                            <input type="date" class="form-control" value="<?= htmlspecialchars($dateCreated) ?>" readonly disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Valor</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eq['amount']) ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Categoría</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($eq['discipline']) ?>" readonly disabled>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="mb-3 text-dark">Consumo Eléctrico</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="small text-muted">Voltaje (V)</label>
                                <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($power_spec['voltage'] ?? 'N/A') ?>" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Amperaje (A)</label>
                                <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($power_spec['amperage'] ?? 'N/A') ?>" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Frecuencia (Hz)</label>
                                <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($power_spec['frequency_hz'] ?? 'N/A') ?>" readonly disabled>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Proveedor</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($supplierName) ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Tipo de adquisición</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($acquisitionName) ?>" readonly disabled>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Periodo de mantenimiento</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($maintenancePeriod) ?>" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Estado de revisión</label>
                            <input type="text" class="form-control" value="<?= ((int)$eq['revision'] === 1) ? 'Con revisión' : 'Sin revisión' ?>" readonly disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white">
                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">Entrega del Equipo</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="text-muted small">Departamento</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($departmentName) ?>" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Ubicación</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($locationName) ?>" readonly disabled>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Cargo responsable</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($positionName) ?>" readonly disabled>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Nombre responsable</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($delivery['responsible_name'] ?? 'N/A') ?>" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Fecha de capacitación</label>
                                <input type="date" class="form-control" value="<?= htmlspecialchars($dateTraining) ?>" readonly disabled>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">Características Técnicas</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="3" readonly disabled><?= htmlspecialchars($eq['characteristics'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark">Documentos de Control</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small">Factura Nro</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($documents['invoice'] ?? 'N/A') ?>" readonly disabled>
                            </div>
                        </div>
                        <div class="row">
                            <?php foreach ($docFields as $field => $label): 
                                $filePath = $documents[$field] ?? '';
                                $hasFile = $filePath && equipment_file_exists($filePath);
                                $filename = $hasFile ? basename($filePath) : '';
                            ?>
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small"><?= htmlspecialchars($label) ?></label>
                                <?php if ($hasFile): ?>
                                    <div class="border p-2 rounded bg-light d-flex justify-content-between align-items-center">
                                        <small class="text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($filename) ?>"><?= htmlspecialchars($filename) ?></small>
                                        <a href="<?= htmlspecialchars($filePath) ?>" target="_blank" class="text-primary" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="border p-2 rounded bg-light text-muted">Sin archivo</div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0"><h6 class="text-dark">Recepción</h6></div>
                            <div class="card-body">
                                <label class="text-muted small">Estado</label>
                                <input type="text" class="form-control mb-3" value="<?= htmlspecialchars($receptionStateText) ?>" readonly disabled>
                                <label class="text-muted small">Comentarios</label>
                                <textarea class="form-control" rows="2" readonly disabled><?= htmlspecialchars($reception['comments'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light border-0"><h6 class="text-dark">Resguardo</h6></div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="text-muted small">Garantía (años)</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($safeguard['warranty_time'] ?? 'N/A') ?>" readonly disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small">Fecha de adquisición</label>
                                        <input type="date" class="form-control" value="<?= htmlspecialchars($dateAdquisition) ?>" readonly disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0 text-dark"><i class="fas fa-tools mr-2"></i>Historial de Mantenimientos</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($maintenance_query && $maintenance_query->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table id="maintenanceTable" class="table table-striped table-hover">
                                    <thead class="bg-secondary">
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
                                        <?php while ($maint = $maintenance_query->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($maint['report_date']))) ?></td>
                                                <td><?= htmlspecialchars(date('H:i', strtotime($maint['report_time']))) ?></td>
                                                <td><?= !empty($maint['engineer_name']) ? htmlspecialchars($maint['engineer_name']) : '<span class="text-muted">No asignado</span>' ?></td>
                                                <td><?= !empty($maint['received_by']) ? htmlspecialchars($maint['received_by']) : '<span class="text-muted">No validado</span>' ?></td>
                                                <td>
                                                    <?php if ($maint['execution_type'] === 'Correctivo'): ?>
                                                        <span class="badge badge-danger">Correctivo</span>
                                                    <?php elseif ($maint['execution_type'] === 'Preventivo'): ?>
                                                        <span class="badge badge-success">Preventivo</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary"><?= htmlspecialchars($maint['execution_type']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="report_pdf.php?id=<?= (int)$maint['id'] ?>" target="_blank" class="btn btn-sm btn-info" title="Descargar Reporte PDF">
                                                        <i class="fas fa-file-pdf mr-1"></i>PDF
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>No hay registros de mantenimiento para este equipo.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr>
                <div class="text-center btn-container-mobile d-flex flex-column flex-md-row justify-content-center gap-3">
                    <a href="equipment_report_pdf.php?id=<?= $equipment_id ?>" class="btn btn-danger btn-lg px-4 mb-3 mb-md-0" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf mr-2"></i>Reporte
                    </a>
                    <a href="index.php?page=equipment_list" class="btn btn-secondary btn-lg px-4">Volver</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-dashed { border-style: dashed !important; }
    .form-control, .custom-select { border-radius: 10px; }
    .text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .badge { font-size: 1.1rem; }
</style>

<script>
    $(function(){
        if ($('#maintenanceTable').length) {
            $('#maintenanceTable').DataTable({
                language: {
                    sProcessing: 'Procesando...',
                    sLengthMenu: 'Mostrar _MENU_ registros',
                    sZeroRecords: 'No se encontraron resultados',
                    sEmptyTable: 'Ningún dato disponible en esta tabla',
                    sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
                    sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
                    sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
                    sSearch: 'Buscar:',
                    oPaginate: {
                        sFirst: 'Primero',
                        sLast: 'Último',
                        sNext: 'Siguiente',
                        sPrevious: 'Anterior'
                    }
                },
                order: [[0, 'desc'], [1, 'desc']],
                pageLength: 10,
                responsive: true,
                autoWidth: false
            });
        }
    });
</script>
