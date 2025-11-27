<?php
require_once 'config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID inválido');
}

$equipmentId = (int)$_GET['id'];

$equipmentQry = $conn->query("SELECT * FROM equipments WHERE id = $equipmentId");
if (!$equipmentQry || $equipmentQry->num_rows === 0) {
    die('Equipo no encontrado');
}
$equipment = $equipmentQry->fetch_assoc();

$reception = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = $equipmentId LIMIT 1")->fetch_assoc() ?: [];
$delivery = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = $equipmentId LIMIT 1")->fetch_assoc() ?: [];
$safeguard = $conn->query("SELECT * FROM equipment_safeguard WHERE equipment_id = $equipmentId LIMIT 1")->fetch_assoc() ?: [];
$documents = $conn->query("SELECT * FROM equipment_control_documents WHERE equipment_id = $equipmentId LIMIT 1")->fetch_assoc() ?: [];
$powerSpec = $conn->query("SELECT * FROM equipment_power_specs WHERE equipment_id = $equipmentId ORDER BY id DESC LIMIT 1")->fetch_assoc() ?: [];

$supplierName = 'N/A';
if (!empty($equipment['supplier_id'])) {
    $supplier = $conn->query('SELECT empresa FROM suppliers WHERE id = ' . (int)$equipment['supplier_id']);
    if ($supplier && $supplier->num_rows > 0) {
        $supplierName = $supplier->fetch_assoc()['empresa'];
    }
}

$departmentName = 'N/A';
if (!empty($delivery['department_id'])) {
    $department = $conn->query('SELECT name FROM departments WHERE id = ' . (int)$delivery['department_id']);
    if ($department && $department->num_rows > 0) {
        $departmentName = $department->fetch_assoc()['name'];
    }
}

$locationName = 'N/A';
if (!empty($delivery['location_id'])) {
    $location = $conn->query('SELECT name FROM locations WHERE id = ' . (int)$delivery['location_id']);
    if ($location && $location->num_rows > 0) {
        $locationName = $location->fetch_assoc()['name'];
    }
}

$positionName = 'N/A';
if (!empty($delivery['responsible_position'])) {
    $position = $conn->query('SELECT name FROM responsible_positions WHERE id = ' . (int)$delivery['responsible_position']);
    if ($position && $position->num_rows > 0) {
        $positionName = $position->fetch_assoc()['name'];
    }
}

$acquisitionName = 'N/A';
if (!empty($equipment['acquisition_type'])) {
    $acquisition = $conn->query('SELECT name FROM acquisition_type WHERE id = ' . (int)$equipment['acquisition_type']);
    if ($acquisition && $acquisition->num_rows > 0) {
        $acquisitionName = $acquisition->fetch_assoc()['name'];
    }
}

$maintenancePeriod = 'N/A';
if (!empty($equipment['mandate_period_id'])) {
    $period = $conn->query('SELECT name FROM maintenance_periods WHERE id = ' . (int)$equipment['mandate_period_id']);
    if ($period && $period->num_rows > 0) {
        $maintenancePeriod = $period->fetch_assoc()['name'];
    }
}
$docLabels = [
    'bailment_file' => 'Comodato',
    'contract_file' => 'Contrato',
    'usermanual_file' => 'Manual Usuario',
    'fast_guide_file' => 'Guía Rápida',
    'datasheet_file' => 'Ficha Técnica',
    'servicemanual_file' => 'Manual Servicio'
];

function equipment_doc_exists($path) {
    if (empty($path)) {
        return false;
    }
    if (file_exists($path)) {
        return true;
    }
    $alt = __DIR__ . '/' . ltrim($path, '/');
    return file_exists($alt);
}

$characteristics = $equipment['characteristics'] ?? '';

$dateCreated = !empty($equipment['date_created']) ? date('d/m/Y', strtotime($equipment['date_created'])) : '—';
$dateTraining = !empty($delivery['date_training']) ? date('d/m/Y', strtotime($delivery['date_training'])) : '—';
$dateAcquisition = !empty($safeguard['date_adquisition']) ? date('d/m/Y', strtotime($safeguard['date_adquisition'])) : '—';

$qrDataUri = null;
$qrCandidate = __DIR__ . '/uploads/qrcodes/equipment_' . $equipmentId . '.png';
if (file_exists($qrCandidate)) {
    $qrMime = mime_content_type($qrCandidate) ?: 'image/png';
    $qrDataUri = 'data:' . $qrMime . ';base64,' . base64_encode(file_get_contents($qrCandidate));
}

$imageDataUri = null;
if (!empty($equipment['image'])) {
    $imageCandidate = $equipment['image'];
    if (!file_exists($imageCandidate)) {
        $imageCandidate = __DIR__ . '/' . ltrim($equipment['image'], '/');
    }
    if (file_exists($imageCandidate)) {
        $imageMime = mime_content_type($imageCandidate) ?: 'image/png';
        $imageDataUri = 'data:' . $imageMime . ';base64,' . base64_encode(file_get_contents($imageCandidate));
    }
}

date_default_timezone_set('America/Mexico_City');
$generatedAt = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha del Equipo <?= htmlspecialchars($equipment['name']) ?></title>
    <style>
        @page {
            margin: 12mm;
            size: A4 portrait;
            @top-left { content: none; }
            @top-center { content: none; }
            @top-right { content: none; }
            @bottom-left { content: none; }
            @bottom-center { content: none; }
            @bottom-right { content: none; }
        }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            font-size: 10.5px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 185mm;
            margin: 0 auto;
            padding: 10px 12px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #0d6efd;
        }
        .meta span {
            display: block;
            font-size: 10px;
            color: #555;
        }
        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 12px;
            margin: 0 0 6px 0;
            padding-left: 6px;
            border-left: 3px solid #0d6efd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #cfd4da;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        th { background: #f8f9fa; width: 32%; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        .card {
            border: 1px solid #cfd4da;
            border-radius: 6px;
            padding: 10px;
            background: #fdfdfe;
        }
        .card h3 {
            margin: 0 0 8px;
            font-size: 11px;
            color: #0d6efd;
        }
        .image-box {
            border: 1px dashed #ced4da;
            padding: 10px;
            text-align: center;
            min-height: 140px;
        }
        .image-box img {
            max-width: 100%;
            max-height: 180px;
            object-fit: contain;
        }
        .qr-box {
            text-align: center;
            margin-top: 8px;
        }
        .qr-box img {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }
        .documents-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .documents-list li {
            margin-bottom: 4px;
        }
        .footer {
            margin-top: 18px;
            text-align: right;
            font-size: 9.5px;
            color: #777;
        }
        .text-muted { color: #6c757d; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div>
            <h1>Ficha Técnica del Equipo</h1>
            <div class="text-muted">Inventario #<?= htmlspecialchars($equipment['number_inventory'] ?? '—') ?></div>
        </div>
        <div class="meta">
            <span>Generado: <?= htmlspecialchars($generatedAt) ?></span>
            <span>ID equipo: <?= $equipmentId ?></span>
        </div>
    </div>

    <div class="section">
        <div class="grid">
            <div class="card">
                <h3>Imagen</h3>
                <div class="image-box">
                    <?php if ($imageDataUri): ?>
                        <img src="<?= $imageDataUri ?>" alt="Imagen del equipo">
                    <?php else: ?>
                        <span class="text-muted">Sin imagen disponible</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <h3>Datos Generales</h3>
                <table>
                    <tr><th>Nombre</th><td><?= htmlspecialchars($equipment['name']) ?></td></tr>
                    <tr><th>Marca</th><td><?= htmlspecialchars($equipment['brand'] ?? '—') ?></td></tr>
                    <tr><th>Modelo</th><td><?= htmlspecialchars($equipment['model'] ?? '—') ?></td></tr>
                    <tr><th>Serie</th><td><?= htmlspecialchars($equipment['serie'] ?? '—') ?></td></tr>
                    <tr><th>Fecha de ingreso</th><td><?= $dateCreated ?></td></tr>
                </table>
            </div>
            <div class="card">
                <h3>Código QR</h3>
                <div class="qr-box">
                    <?php if ($qrDataUri): ?>
                        <img src="<?= $qrDataUri ?>" alt="Código QR">
                    <?php else: ?>
                        <span class="text-muted">QR no disponible</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Características</h2>
        <table>
            <tr><th>Características</th><td><?= nl2br(htmlspecialchars($characteristics)) ?: '—' ?></td></tr>
            <tr><th>Proveedor</th><td><?= htmlspecialchars($supplierName) ?></td></tr>
            <tr><th>Valor</th><td><?= htmlspecialchars($equipment['amount'] ?? '—') ?></td></tr>
            <tr><th>Disciplina</th><td><?= htmlspecialchars($equipment['discipline'] ?? '—') ?></td></tr>
            <tr><th>Tipo de adquisición</th><td><?= htmlspecialchars($acquisitionName) ?></td></tr>
            <tr><th>Periodo de mantenimiento</th><td><?= htmlspecialchars($maintenancePeriod) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Entrega del Equipo</h2>
        <table>
            <tr><th>Departamento</th><td><?= htmlspecialchars($departmentName) ?></td></tr>
            <tr><th>Ubicación</th><td><?= htmlspecialchars($locationName) ?></td></tr>
            <tr><th>Responsable</th><td><?= htmlspecialchars($delivery['responsible_name'] ?? '—') ?></td></tr>
            <tr><th>Cargo</th><td><?= htmlspecialchars($positionName) ?></td></tr>
            <tr><th>Fecha de capacitación</th><td><?= $dateTraining ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Características Técnicas</h2>
        <table>
            <tr><th>Voltaje</th><td><?= htmlspecialchars($powerSpec['voltage'] ?? '—') ?></td></tr>
            <tr><th>Amperaje</th><td><?= htmlspecialchars($powerSpec['amperage'] ?? '—') ?></td></tr>
            <tr><th>Frecuencia</th><td><?= htmlspecialchars($powerSpec['frequency_hz'] ?? '—') ?></td></tr>
            <tr><th>Potencia</th><td><?= htmlspecialchars($powerSpec['power_kw'] ?? '—') ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Resguardo y Garantía</h2>
        <table>
            <tr><th>Tiempo de garantía</th><td><?= htmlspecialchars($safeguard['warranty_time'] ?? '—') ?></td></tr>
            <tr><th>Fecha de adquisición</th><td><?= $dateAcquisition ?></td></tr>
            <tr><th>Comentarios</th><td><?= nl2br(htmlspecialchars($reception['comments'] ?? '')) ?: '—' ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Documentos</h2>
        <table>
            <tr><th>Factura</th><td><?= htmlspecialchars($documents['invoice'] ?? '—') ?></td></tr>
            <?php foreach ($docLabels as $field => $label):
                $path = $documents[$field] ?? '';
                $status = equipment_doc_exists($path);
                $display = $status ? 'Disponible (' . htmlspecialchars(basename($path)) . ')' : 'No disponible';
            ?>
                <tr><th><?= htmlspecialchars($label) ?></th><td><?= $display ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="footer">Generado automáticamente desde el sistema de inventario.</div>
</div>
<script>
    (function() {
        const hasWindow = typeof window !== 'undefined';
        if (hasWindow && window.print) {
            setTimeout(function(){ window.print(); }, 300);
        }
    })();
</script>
</body>
</html>
