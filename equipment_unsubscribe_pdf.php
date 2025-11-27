<?php
require_once 'config/config.php';

header('Content-Type: text/html; charset=UTF-8');

$unsubscribeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($unsubscribeId <= 0) {
    die('<h3 style="color:#c0392b;text-align:center;margin-top:40px;">Identificador de baja no válido.</h3>');
}

$sql = $conn->prepare("SELECT eu.*, e.name AS equipment_name, e.brand, e.model, e.number_inventory, e.serie, e.date_created, e.image, e.amount, e.discipline, e.location_id, e.id AS equipment_ref
                        FROM equipment_unsubscribe eu
                        INNER JOIN equipments e ON e.id = eu.equipment_id
                        WHERE eu.id = ? LIMIT 1");
$sql->bind_param('i', $unsubscribeId);
$sql->execute();
$result = $sql->get_result();
$unsubscribe = $result ? $result->fetch_assoc() : null;
$sql->close();

if (!$unsubscribe) {
    die('<h3 style="color:#c0392b;text-align:center;margin-top:40px;">No se encontró la información de la baja.</h3>');
}

$equipmentId = (int)$unsubscribe['equipment_id'];
$folio = $unsubscribe['folio'] ?? '';
$processedName = $unsubscribe['processed_by_name'] ?? '';
$processedName = $processedName !== '' ? $processedName : 'No registrado';

$reasonsSelected = [];
if (!empty($unsubscribe['withdrawal_reason'])) {
    $decoded = json_decode($unsubscribe['withdrawal_reason'], true);
    if (is_array($decoded)) {
        $reasonsSelected = array_filter(array_map('intval', $decoded));
    }
}

$reasonsCatalog = [];
$reasonResult = $conn->query('SELECT id, name FROM equipment_withdrawal_reason');
while ($reasonResult && $row = $reasonResult->fetch_assoc()) {
    $reasonsCatalog[(int)$row['id']] = $row['name'];
}
if ($reasonResult) {
    $reasonResult->free();
}

$reasonsLabels = [];
foreach ($reasonsSelected as $reasonId) {
    if (isset($reasonsCatalog[$reasonId])) {
        $reasonsLabels[] = $reasonsCatalog[$reasonId];
    }
}

$responsibleLabels = [
    1 => 'Jefe de servicio',
    2 => 'Proveedor externo'
];
$destinationLabels = [
    1 => 'Guardar en bodega',
    2 => 'Devolución al proveedor',
    3 => 'Donar',
    4 => 'Venta',
    5 => 'Basura'
];

$opinionLabel = '';
if (isset($unsubscribe['opinion'])) {
    $opinionLabel = ((int)$unsubscribe['opinion'] === 1) ? 'Funcional' : 'Disfuncional';
}

$dateValue = !empty($unsubscribe['date']) ? date('d/m/Y', strtotime($unsubscribe['date'])) : '';
$timeValue = !empty($unsubscribe['time']) ? date('H:i', strtotime($unsubscribe['time'])) : '';
$dateCreated = !empty($unsubscribe['date_created']) ? date('d/m/Y', strtotime($unsubscribe['date_created'])) : '';

$history = [];
$historySql = "SELECT order_number, report_date, report_time, service_type, execution_type, engineer_name, final_status
                FROM maintenance_reports
                WHERE equipment_id = ?
                ORDER BY report_date DESC, report_time DESC
                LIMIT 12";
$historyStmt = $conn->prepare($historySql);
if ($historyStmt) {
    $historyStmt->bind_param('i', $equipmentId);
    if ($historyStmt->execute()) {
        $historyRes = $historyStmt->get_result();
        while ($historyRes && $row = $historyRes->fetch_assoc()) {
            $history[] = $row;
        }
    } else {
        error_log('equipment_unsubscribe_pdf: fallo al ejecutar historial -> ' . $historyStmt->error);
    }
    $historyStmt->close();
} else {
    error_log('equipment_unsubscribe_pdf: fallo al preparar historial -> ' . $conn->error);
    $historyFallbackSql = "SELECT id AS order_number, report_date, report_time, service_type, execution_type, engineer_name, final_status
                            FROM maintenance_reports
                            WHERE equipment_id = ?
                            ORDER BY id DESC
                            LIMIT 12";
    $historyStmtLegacy = $conn->prepare($historyFallbackSql);
    if ($historyStmtLegacy) {
        $historyStmtLegacy->bind_param('i', $equipmentId);
        if ($historyStmtLegacy->execute()) {
            $historyResLegacy = $historyStmtLegacy->get_result();
            while ($historyResLegacy && $row = $historyResLegacy->fetch_assoc()) {
                $history[] = $row;
            }
        }
        $historyStmtLegacy->close();
    }
}

$equipmentName = $unsubscribe['equipment_name'] ?? '';
$inventory = $unsubscribe['number_inventory'] ?? '';
$brand = $unsubscribe['brand'] ?? '';
$model = $unsubscribe['model'] ?? '';
$serie = $unsubscribe['serie'] ?? '';
$discipline = $unsubscribe['discipline'] ?? '';
$amount = $unsubscribe['amount'] ?? '';
$imagePath = $unsubscribe['image'] ?? '';
$imageData = '';
if (!empty($imagePath) && file_exists($imagePath)) {
    $imageExt = pathinfo($imagePath, PATHINFO_EXTENSION);
    $imageContent = file_get_contents($imagePath);
    if ($imageContent !== false) {
        $imageData = 'data:image/' . $imageExt . ';base64,' . base64_encode($imageContent);
    }
}

$destinationText = isset($destinationLabels[(int)$unsubscribe['destination']]) ? $destinationLabels[(int)$unsubscribe['destination']] : 'No especificado';
$responsibleText = isset($responsibleLabels[(int)$unsubscribe['responsible']]) ? $responsibleLabels[(int)$unsubscribe['responsible']] : 'No especificado';

$createdAt = !empty($unsubscribe['created_at']) ? date('d/m/Y H:i', strtotime($unsubscribe['created_at'])) : '';
$updatedAt = !empty($unsubscribe['updated_at']) ? date('d/m/Y H:i', strtotime($unsubscribe['updated_at'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Folio <?= htmlspecialchars($folio ?: ('BAJ-' . date('Y') . '-' . str_pad($unsubscribeId, 4, '0', STR_PAD_LEFT))) ?></title>
    <style>
        @page {
            margin: 12mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #2c3e50;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .container {
            max-width: 200mm;
            margin: 0 auto;
            padding: 4mm 6mm;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            border-bottom: 2px solid #2980b9;
            padding-bottom: 6px;
        }
        .header .titles h1 {
            font-size: 20px;
            color: #2980b9;
            margin: 0;
        }
        .header .titles p {
            margin: 2px 0;
            font-size: 12px;
        }
        .folio-box {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #c0392b;
        }
        .section {
            margin-top: 14px;
        }
        .section h2 {
            font-size: 14px;
            color: #34495e;
            border-left: 4px solid #2980b9;
            padding-left: 8px;
            margin: 0 0 8px 0;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
        }
        table.info td {
            padding: 6px 8px;
            border: 1px solid #d0d7de;
            vertical-align: top;
        }
        table.info td.label {
            width: 30%;
            background: #ecf3fa;
            font-weight: 600;
        }
        table.history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.history th, table.history td {
            border: 1px solid #d0d7de;
            padding: 6px 5px;
            font-size: 11px;
        }
        table.history th {
            background: #f0f6fc;
            font-weight: 600;
            color: #2c3e50;
        }
        ul.reasons {
            margin: 0;
            padding-left: 20px;
        }
        .flex-row {
            display: flex;
            gap: 10px;
        }
        .flex-col {
            flex: 1;
        }
        .signature {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
        }
        .signature .line {
            border-top: 1px solid #2c3e50;
            margin-top: 40px;
            text-align: center;
            padding-top: 6px;
            font-size: 11px;
        }
        .image-wrapper {
            text-align: right;
        }
        .image-wrapper img {
            max-height: 120px;
            max-width: 160px;
            object-fit: contain;
            border: 1px solid #d0d7de;
            padding: 4px;
            border-radius: 6px;
            background: #fff;
        }
        .meta {
            font-size: 10px;
            color: #7f8c8d;
            margin-top: 6px;
        }
        .no-data {
            font-style: italic;
            color: #7f8c8d;
        }
        .badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="titles">
                <h1>Formato de Baja de Equipo</h1>
                <p>Registro de baja y dictamen técnico</p>
                <?php if ($dateValue): ?>
                    <p class="meta">Fecha de emisión: <?= htmlspecialchars($dateValue) ?> <?= $timeValue ? ' | Hora: ' . htmlspecialchars($timeValue) : '' ?></p>
                <?php endif; ?>
            </div>
            <div class="folio-box">
                <div>Folio</div>
                <div><?= htmlspecialchars($folio ?: ('BAJ-' . date('Y') . '-' . str_pad($unsubscribeId, 4, '0', STR_PAD_LEFT))) ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Datos del equipo</h2>
            <div class="flex-row">
                <div class="flex-col">
                    <table class="info">
                        <tr>
                            <td class="label">Nombre del equipo</td>
                            <td><?= htmlspecialchars($equipmentName) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Número de inventario</td>
                            <td><?= htmlspecialchars($inventory) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Marca / Modelo</td>
                            <td><?= htmlspecialchars(trim($brand . ' ' . $model)) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Serie</td>
                            <td><?= htmlspecialchars($serie) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Disciplina / Área</td>
                            <td><?= htmlspecialchars($discipline ?: 'No especificado') ?></td>
                        </tr>
                        <tr>
                            <td class="label">Valor referencial</td>
                            <td><?= htmlspecialchars($amount !== '' ? $amount : 'No especificado') ?></td>
                        </tr>
                        <tr>
                            <td class="label">Fecha de alta</td>
                            <td><?= htmlspecialchars($dateCreated ?: 'No disponible') ?></td>
                        </tr>
                    </table>
                </div>
                <?php if ($imageData): ?>
                <div class="image-wrapper">
                    <img src="<?= $imageData ?>" alt="Fotografía del equipo">
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Detalle de la baja</h2>
            <table class="info">
                <tr>
                    <td class="label">Fecha y hora de baja</td>
                    <td><?= htmlspecialchars(trim($dateValue . ($timeValue ? ' ' . $timeValue : ''))) ?></td>
                </tr>
                <tr>
                    <td class="label">Usuario que registra la baja</td>
                    <td><?= htmlspecialchars($processedName) ?></td>
                </tr>
                <tr>
                    <td class="label">Responsable de evaluación</td>
                    <td><?= htmlspecialchars($responsibleText) ?></td>
                </tr>
                <tr>
                    <td class="label">Destino definido</td>
                    <td><?= htmlspecialchars($destinationText) ?></td>
                </tr>
                <tr>
                    <td class="label">Dictamen</td>
                    <td><span class="badge"><?= htmlspecialchars($opinionLabel ?: 'Sin dictamen') ?></span></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Descripción del estado funcional</h2>
            <table class="info">
                <tr>
                    <td><?= nl2br(htmlspecialchars($unsubscribe['description'] ?? '')) ?: '<span class="no-data">Sin descripción proporcionada.</span>' ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Comentarios y observaciones</h2>
            <table class="info">
                <tr>
                    <td><?= nl2br(htmlspecialchars($unsubscribe['comments'] ?? '')) ?: '<span class="no-data">Sin comentarios registrados.</span>' ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>Causas de retiro</h2>
            <?php if (!empty($reasonsLabels)): ?>
                <ul class="reasons">
                    <?php foreach ($reasonsLabels as $label): ?>
                        <li><?= htmlspecialchars($label) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data">No se registraron causas específicas.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Historial de mantenimientos</h2>
            <?php if (!empty($history)): ?>
                <table class="history">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Tipo de servicio</th>
                            <th>Tipo de ejecución</th>
                            <th>Ingeniero</th>
                            <th>Estado final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['order_number'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars(!empty($record['report_date']) ? date('d/m/Y', strtotime($record['report_date'])) : '-') ?></td>
                                <td><?= htmlspecialchars(!empty($record['report_time']) ? date('H:i', strtotime($record['report_time'])) : '-') ?></td>
                                <td><?= htmlspecialchars($record['service_type'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($record['execution_type'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($record['engineer_name'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($record['final_status'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No se encontraron mantenimientos registrados para este equipo.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Firmas</h2>
            <div class="signature">
                <div class="flex-col">
                    <div class="line">Quien realiza la baja<br><?= htmlspecialchars($processedName) ?></div>
                </div>
                <div class="flex-col">
                    <div class="line">Vo.Bo. Jefe de servicio</div>
                </div>
            </div>
        </div>

        <div class="meta">
            <?php if ($createdAt): ?>
                Registro creado el <?= htmlspecialchars($createdAt) ?><?php if ($updatedAt && $updatedAt !== $createdAt): ?> | Actualizado el <?= htmlspecialchars($updatedAt) ?><?php endif; ?>.
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
