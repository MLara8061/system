<?php
// Reporte imprimible (HTML) de ficha técnica del equipo
// Se abre en nueva pestaña y permite imprimir/guardar como PDF desde el navegador.

if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/..'));
}
if (!defined('ACCESS')) define('ACCESS', true);

require_once ROOT . '/config/config.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    header('location: ' . rtrim(BASE_URL, '/') . '/app/views/auth/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('ID inválido');
}

$equipmentId = (int)$_GET['id'];

$equipmentQry = $conn->query("SELECT * FROM equipments WHERE id = {$equipmentId} LIMIT 1");
if (!$equipmentQry || $equipmentQry->num_rows === 0) {
    http_response_code(404);
    die('Equipo no encontrado');
}
$equipment = $equipmentQry->fetch_assoc();

$reception = $conn->query("SELECT * FROM equipment_reception WHERE equipment_id = {$equipmentId} LIMIT 1")->fetch_assoc() ?: [];
$delivery = $conn->query("SELECT * FROM equipment_delivery WHERE equipment_id = {$equipmentId} LIMIT 1")->fetch_assoc() ?: [];

$supplierName = 'N/A';
if (!empty($equipment['supplier_id'])) {
    $supplier = $conn->query('SELECT empresa FROM suppliers WHERE id = ' . (int)$equipment['supplier_id']);
    if ($supplier && $supplier->num_rows > 0) {
        $supplierName = (string)$supplier->fetch_assoc()['empresa'];
    }
}

$departmentName = 'N/A';
if (!empty($delivery['department_id'])) {
    $department = $conn->query('SELECT name FROM departments WHERE id = ' . (int)$delivery['department_id']);
    if ($department && $department->num_rows > 0) {
        $departmentName = (string)$department->fetch_assoc()['name'];
    }
}

$locationName = 'N/A';
if (!empty($delivery['location_id'])) {
    $location = $conn->query('SELECT name FROM locations WHERE id = ' . (int)$delivery['location_id']);
    if ($location && $location->num_rows > 0) {
        $locationName = (string)$location->fetch_assoc()['name'];
    }
}

$positionName = 'N/A';
if (!empty($delivery['responsible_position'])) {
    $position = $conn->query('SELECT name FROM job_positions WHERE id = ' . (int)$delivery['responsible_position']);
    if ($position && $position->num_rows > 0) {
        $positionName = (string)$position->fetch_assoc()['name'];
    }
}

$acquisitionName = 'N/A';
if (!empty($equipment['acquisition_type'])) {
    $acquisition = $conn->query('SELECT name FROM acquisition_type WHERE id = ' . (int)$equipment['acquisition_type']);
    if ($acquisition && $acquisition->num_rows > 0) {
        $acquisitionName = (string)$acquisition->fetch_assoc()['name'];
    }
}

$maintenancePeriod = 'N/A';
if (!empty($equipment['mandate_period_id'])) {
    $period = $conn->query('SELECT name FROM maintenance_periods WHERE id = ' . (int)$equipment['mandate_period_id']);
    if ($period && $period->num_rows > 0) {
        $maintenancePeriod = (string)$period->fetch_assoc()['name'];
    }
}

$characteristics = (string)($equipment['characteristics'] ?? '');

$amount_formatted = (isset($equipment['amount']) && $equipment['amount'] !== '' && is_numeric($equipment['amount']))
    ? '$' . number_format((float)$equipment['amount'], 2, '.', ',')
    : '—';

$dateCreated = !empty($equipment['date_created']) ? date('d/m/Y', strtotime($equipment['date_created'])) : '—';
$dateTraining = !empty($delivery['date_training']) ? date('d/m/Y', strtotime($delivery['date_training'])) : '—';

// QR (inline): evita depender de archivos y asegura que siempre se imprima
$qrDataUri = null;
try {
    require_once ROOT . '/lib/phpqrcode/qrlib.php';
    $qrUrl = rtrim(BASE_URL, '/') . '/legacy/equipment_public.php?id=' . $equipmentId;
    ob_start();
    QRcode::png($qrUrl, null, QR_ECLEVEL_L, 6);
    $qrPng = ob_get_clean();
    if ($qrPng) {
        $qrDataUri = 'data:image/png;base64,' . base64_encode($qrPng);
    }
} catch (Throwable $e) {
    $qrDataUri = null;
}

// Imagen del equipo (inline) si existe
$imageDataUri = null;
if (!empty($equipment['image'])) {
    $imageCandidate = (string)$equipment['image'];

    $candidates = [];
    $candidates[] = $imageCandidate;
    $candidates[] = ROOT . '/' . ltrim($imageCandidate, '/');
    $candidates[] = ROOT . '/public/' . ltrim($imageCandidate, '/');

    $found = null;
    foreach ($candidates as $cand) {
        if ($cand && file_exists($cand)) {
            $found = $cand;
            break;
        }
    }

    if ($found) {
        $imageMime = mime_content_type($found) ?: 'image/png';
        $imageDataUri = 'data:' . $imageMime . ';base64,' . base64_encode(file_get_contents($found));
    }
}

date_default_timezone_set('America/Mexico_City');
$generatedAt = date('d/m/Y H:i');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha del Equipo <?= htmlspecialchars($equipment['name'] ?? '') ?></title>
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
            background: #fff;
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
        .footer {
            margin-top: 18px;
            text-align: right;
            font-size: 9.5px;
            color: #777;
        }
        .text-muted { color: #6c757d; }

        .no-print {
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 9999;
        }
        .btn-print {
            appearance: none;
            border: 0;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 13px;
            cursor: pointer;
            background: #0d6efd;
            color: #fff;
        }
        .btn-print:hover { filter: brightness(0.95); }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button type="button" class="btn-print" onclick="printReport()">Imprimir / Guardar como PDF</button>
</div>

<div class="wrapper">
    <div class="header">
        <div>
            <h1>Ficha Técnica del Equipo</h1>
            <div class="text-muted">Inventario #<?= htmlspecialchars($equipment['number_inventory'] ?? '—') ?></div>
        </div>
        <div class="meta">
            <span>Generado: <?= htmlspecialchars($generatedAt) ?></span>
            <span>ID equipo: <?= (int)$equipmentId ?></span>
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
                    <tr><th>Nombre</th><td><?= htmlspecialchars($equipment['name'] ?? '—') ?></td></tr>
                    <tr><th>Marca</th><td><?= htmlspecialchars($equipment['brand'] ?? '—') ?></td></tr>
                    <tr><th>Modelo</th><td><?= htmlspecialchars($equipment['model'] ?? '—') ?></td></tr>
                    <tr><th>Serie</th><td><?= htmlspecialchars($equipment['serie'] ?? '—') ?></td></tr>
                    <tr><th>Fecha de ingreso</th><td><?= htmlspecialchars($dateCreated) ?></td></tr>
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
        <h2>Ubicación y Responsiva</h2>
        <table>
            <tr><th>Departamento</th><td><?= htmlspecialchars($departmentName) ?></td></tr>
            <tr><th>Ubicación</th><td><?= htmlspecialchars($locationName) ?></td></tr>
            <tr><th>Responsable</th><td><?= htmlspecialchars((string)($delivery['responsible_name'] ?? '—')) ?></td></tr>
            <tr><th>Puesto</th><td><?= htmlspecialchars($positionName) ?></td></tr>
            <tr><th>Fecha capacitación</th><td><?= htmlspecialchars($dateTraining) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Compra y Mantenimiento</h2>
        <table>
            <tr><th>Proveedor</th><td><?= htmlspecialchars($supplierName) ?></td></tr>
            <tr><th>Tipo adquisición</th><td><?= htmlspecialchars($acquisitionName) ?></td></tr>
            <tr><th>Monto</th><td><?= htmlspecialchars($amount_formatted) ?></td></tr>
            <tr><th>Periodo mantenimiento</th><td><?= htmlspecialchars($maintenancePeriod) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Características</h2>
        <div class="card">
            <?= $characteristics !== '' ? nl2br(htmlspecialchars($characteristics)) : '<span class="text-muted">Sin información</span>' ?>
        </div>
    </div>

    <div class="footer">Generado por el sistema</div>
</div>

<script>
    function printReport() {
        alert("IMPORTANTE: Para que no aparezcan encabezados/pies del navegador:\n\n" +
              "1) Presiona Ctrl+P\n" +
              "2) Abre 'Más ajustes'\n" +
              "3) Desactiva 'Encabezados y pies de página'\n\n" +
              "Luego, imprime o guarda como PDF.");
        window.print();
    }
</script>
</body>
</html>
