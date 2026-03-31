<?php
// report_pdf.php
define('ACCESS', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../app/helpers/permissions.php';
require_once __DIR__ . '/../app/helpers/company_config_helper.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    die('<h3 style="color:red;text-align:center;margin:50px;">Sesion expirada</h3>');
}

$canExport = function_exists('can')
    ? (can('export', 'reports') || can('view', 'reports') || can('export', 'maintenance_reports') || can('view', 'maintenance_reports'))
    : ((int)($_SESSION['login_type'] ?? 0) === 1);
if (!$canExport && (int)($_SESSION['login_type'] ?? 0) !== 1) {
    http_response_code(403);
    die('<h3 style="color:red;text-align:center;margin:50px;">Sin permisos para exportar</h3>');
}

$report_id = $_GET['id'] ?? 0;
if (!$report_id || !is_numeric($report_id)) {
    die('<h3 style="color:red;text-align:center;margin:50px;">Report ID not provided</h3>');
}

$report_id = (int)$report_id;

$stmt = $conn->prepare("SELECT * FROM maintenance_reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    die('<h3 style="color:red;text-align:center;margin:50px;">Report not found</h3>');
}

// === MULTI-SUCURSAL: validar acceso al reporte ===
$login_type = (int)($_SESSION['login_type'] ?? 0);
$active_bid = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);

$report_branch_id = 0;
if (array_key_exists('branch_id', $report) && is_numeric($report['branch_id'])) {
    $report_branch_id = (int)$report['branch_id'];
}
if ($report_branch_id <= 0) {
    $eq_id = (int)($report['equipment_id'] ?? 0);
    if ($eq_id > 0) {
        $stmt = $conn->prepare('SELECT branch_id FROM equipments WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $eq_id);
        $stmt->execute();
        $eq = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $report_branch_id = (int)($eq['branch_id'] ?? 0);
    }
}
if ($report_branch_id <= 0) {
    die('<h3 style="color:red;text-align:center;margin:50px;">Report branch not found</h3>');
}

// Admin con sucursal 0 (todas): sin filtro; si no, debe coincidir.
if ($login_type !== 1 || $active_bid > 0) {
    if ($active_bid <= 0 || $report_branch_id !== $active_bid) {
        die('<h3 style="color:red;text-align:center;margin:50px;">Sin permiso para esta sucursal</h3>');
    }
}

// === REFACCIONES ===
$parts = json_decode($report['parts_used'], true) ?: [];
$parts_list = '';
foreach ($parts as $p) {
    $item_id = (int)($p['item_id'] ?? 0);
    if ($item_id <= 0) continue;
    $stmt = $conn->prepare("SELECT name, stock FROM inventory WHERE id = ? AND branch_id = ?");
    $stmt->bind_param("ii", $item_id, $report_branch_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $parts_list .= '<li style="margin-left: 20px;">' . htmlspecialchars($p['quantity'] . ' × ' . ($item['name'] ?? 'Desconocido')) . '</li>';
}
if (empty($parts_list)) {
    $parts_list = '<em style="margin-left: 20px;">Ninguna refacción utilizada</em>';
}
// === ADJUNTOS FOTOGRÁFICOS ===
$evidence_photos = [];
try {
    require_once ROOT . '/config/db.php';
    $pdo_pdf = get_pdo();
    $att_stmt = $pdo_pdf->prepare(
        "SELECT file_name, file_path FROM report_attachments
          WHERE report_id = :rid
          ORDER BY sort_order ASC, id ASC"
    );
    $att_stmt->execute([':rid' => $report_id]);
    $evidence_photos = $att_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('report_pdf evidence load error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte <?= $report['order_number'] ?></title>

    <style>
        @page {
            margin: 8mm;
            size: A4 portrait;
            @top-left { content: none; }
            @top-center { content: none; }
            @top-right { content: none; }
            @bottom-left { content: none; }
            @bottom-center { content: none; }
            @bottom-right { content: none; }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10.5px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 195mm;
            margin: 0 auto;
            padding: 8px;
            background: #fff;
        }

        /* MEMBRETE + ORDEN + FECHA EN MISMA LÍNEA */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 10px;
            padding: 10px 12px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: linear-gradient(135deg, #fbfdff 0%, #f0f6ff 100%);
            font-size: 9.5px;
            line-height: 1.2;
        }

        .membrete {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
            min-width: 0;
            padding-right: 10px;
        }

        .membrete-logo {
            width: 120px;
            min-width: 120px;
            height: 62px;
            margin-bottom: 0;
            border: 1px solid #d6e0ec;
            border-radius: 10px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
        }

        .membrete-logo img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .membrete-copy {
            min-width: 0;
        }

        .membrete .company {
            font-weight: bold;
            font-size: 12px;
            color: #1a2f45;
        }

        .membrete .address {
            margin: 1px 0;
            color: #415466;
        }

        .membrete .description {
            margin-top: 5px;
            font-style: italic;
            color: #617487;
        }

        .order-info {
            text-align: right;
            min-width: 235px;
            padding-left: 14px;
            border-left: 1px solid #d6e0ec;
            font-size: 11px;
            font-weight: bold;
            color: #2f455a;
            line-height: 1.3;
        }

        .order-info .title-large {
            font-size: 15px;
            font-weight: 300;
            letter-spacing: 0.04em;
            color: #5c6f82;
        }

        .order-info .label {
            display: block;
            margin-top: 10px;
            margin-bottom: 4px;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7c8f;
        }

        .order-info .value-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 10px;
            background: #fff1f0;
            border: 1px solid #f2c1bf;
            color: #8f2d2a;
        }

        .order-info .fecha {
            font-weight: normal;
            font-size: 11px;
            color: #555;
        }

        .title {
            text-align: center;
            font-size: 15px;
            color: #007bff;
            margin: 5px 0 8px 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }

        .section {
            margin: 10px 0;
            page-break-inside: avoid;
        }

        .section h2 {
            font-size: 12px;
            background: #f8f9fa;
            padding: 4px 6px;
            margin: 0 0 6px 0;
            border-left: 3px solid #007bff;
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 10px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }

        table th {
            background: #f1f1f1;
            font-weight: 600;
            width: 32%;
        }

        /* Tabla de dos columnas para servicio */
        .service-grid {
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }

        .service-col {
            flex: 1;
        }

        .service-col table {
            margin: 0;
        }

        .materiales {
            margin-top: 6px;
            font-size: 10px;
        }

        .firmas {
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .firma {
            width: 30%;
            display: inline-block;
            text-align: center;
            vertical-align: top;
        }

        .firma .linea {
            border-bottom: 1px solid #000;
            width: 75%;
            margin: 25px auto 4px auto;
        }

        .firma .nombre {
            font-weight: bold;
            font-size: 9.5px;
        }

        .no-print {
            text-align: center;
            margin-top: 10px;
        }

        @media print {
            body, .container { margin: 0; padding: 0; }
            .no-print { display: none; }
            .firma .linea { border-bottom: 1px solid #000; }
            .evidence-img { max-width: 100%; }
        }

        /* EVIDENCIA FOTOGRÁFICA */
        .evidence-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 6px;
        }
        .evidence-cell {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
            page-break-inside: avoid;
        }
        .evidence-img {
            max-width: 100%;
            max-height: 140px;
            object-fit: cover;
        }
        .evidence-caption {
            font-size: 8.5px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>

<div class="container">

    <!-- MEMBRETE + ORDEN + FECHA (EN UNA LÍNEA) -->
    <div class="header-row">
        <div class="membrete">
<?php
$_pdf_branch_id = (int)($report_branch_id ?? 0);
$_pdf_cfg = get_company_config($conn, $_pdf_branch_id);
$_pdf_logo = get_company_logo_url($conn, $_pdf_branch_id);
?>
<?php if (!empty($_pdf_logo)): ?>
            <div class="membrete-logo">
                <img src="<?= htmlspecialchars($_pdf_logo) ?>" alt="Logo">
            </div>
<?php endif; ?>
            <div class="company"><?= htmlspecialchars($_pdf_cfg['company_name']) ?></div>
            <div class="address"><?= htmlspecialchars($_pdf_cfg['address_line_1']) ?></div>
            <div class="address"><?= htmlspecialchars($_pdf_cfg['address_line_2']) ?></div>
            <div class="address"><?= htmlspecialchars($_pdf_cfg['city_state_zip']) ?></div>
            <div class="address"><?= htmlspecialchars($_pdf_cfg['phone_number']) ?></div>
<?php if (!empty($_pdf_cfg['company_description'])): ?>
            <div class="address" style="font-style:italic;"><?= htmlspecialchars($_pdf_cfg['company_description']) ?></div>
<?php endif; ?>
        </div>
        <div class="header-row">
            <div class="membrete">
                <?php $logoUrl = get_company_logo_url($conn, $report_branch_id); ?>
                <?php $cfg = get_company_config($conn, $report_branch_id); ?>
                <div class="membrete-logo">
                    <?php if (!empty($logoUrl)): ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo">
                    <?php else: ?>
                        <span style="font-size:8px;color:#7b8794;text-align:center;display:block;">Logo<br>institucional</span>
                    <?php endif; ?>
                </div>
                <div class="membrete-copy">
                    <div class="company"><?= htmlspecialchars($cfg['company_name']) ?></div>
                    <div class="address"><?= htmlspecialchars($cfg['address_line_1']) ?></div>
                    <div class="address"><?= htmlspecialchars($cfg['address_line_2']) ?></div>
                    <div class="address"><?= htmlspecialchars($cfg['city_state_zip']) ?></div>
                    <div class="address"><?= htmlspecialchars($cfg['phone_number']) ?></div>
                    <?php if (!empty($cfg['company_description'])): ?>
                        <div class="description"><?= htmlspecialchars($cfg['company_description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-info">
                <div class="title-large">Orden de Mantto</div>
                <span class="label">Orden</span>
                <span class="value-badge"><?= htmlspecialchars($report['order_number']) ?></span>
                <span class="label">Fecha</span>
                <span class="fecha"><?= htmlspecialchars($report['report_date']) ?></span>
            </div>
        </div>
        <h2>DATOS DEL EQUIPO</h2>
        <table>
            <tr><th>Nombre</th><td><?= htmlspecialchars($report['equipment_name']) ?></td></tr>
            <tr><th>Marca</th><td><?= htmlspecialchars($report['equipment_brand']) ?></td></tr>
            <tr><th>Modelo</th><td><?= htmlspecialchars($report['equipment_model']) ?></td></tr>
            <tr><th>Serie</th><td><?= htmlspecialchars($report['equipment_serial']) ?></td></tr>
            <tr><th>Inventario</th><td><?= htmlspecialchars($report['equipment_inventory_code']) ?></td></tr>
            <tr><th>Ubicación</th><td><?= htmlspecialchars($report['equipment_location']) ?></td></tr>
        </table>
    </div>

    <!-- SERVICIO Y HORARIO (DOS COLUMNAS) -->
    <div class="section">
        <h2>SERVICIO Y HORARIO</h2>
        <div class="service-grid">
            <!-- Columna 1: Servicio -->
            <div class="service-col">
                <table>
                    <tr><th>Tipo</th><td><strong><?= $report['service_type'] ?></strong></td></tr>
                    <tr><th>Ejecución</th><td><?= $report['execution_type'] ?></td></tr>
                </table>
            </div>
            <!-- Columna 2: Horario -->
            <div class="service-col">
                <table>
                    <?php if (!empty($report['service_date'])): ?>
                    <tr><th>Fecha</th><td><?= htmlspecialchars($report['service_date']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($report['service_start_time'])): ?>
                    <tr><th>Hora Inicio</th><td><?= htmlspecialchars($report['service_start_time']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($report['service_end_time'])): ?>
                    <tr><th>Hora Fin</th><td><?= htmlspecialchars($report['service_end_time']) ?></td></tr>
                    <?php endif; ?>
                    <?php 
                    // Rellenar filas vacías si faltan datos del horario
                    $row_count = 0;
                    if (!empty($report['service_date'])) $row_count++;
                    if (!empty($report['service_start_time'])) $row_count++;
                    if (!empty($report['service_end_time'])) $row_count++;
                    
                    // Asegurar que ambas tablas tengan la misma altura (mínimo 2 filas)
                    while ($row_count < 2) {
                        echo '<tr><th>&nbsp;</th><td>&nbsp;</td></tr>';
                        $row_count++;
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>

    <!-- DESCRIPCIÓN -->
    <div class="section">
        <h2>DESCRIPCIÓN</h2>
        <?php 
        // Limitar descripción a 800 caracteres para evitar que el PDF se divida en dos páginas
        $description = $report['description'];
        $max_chars = 800;
        if (strlen($description) > $max_chars) {
            $description = substr($description, 0, $max_chars) . '... (texto truncado)';
        }
        ?>
        <p style="margin:4px 0;"><?= nl2br(htmlspecialchars($description)) ?: '—' ?></p>
    </div>

    <!-- REFACCIONES -->
    <div class="section">
        <h2>REFACCIONES</h2>
        <div class="materiales">
            <ul style="margin:4px 0; padding-left:0; list-style:none;">
                <?= $parts_list ?>
            </ul>
        </div>
    </div>

    <!-- OBSERVACIONES -->
    <div class="section">
        <h2>OBSERVACIONES</h2>
        <p style="margin:4px 0;"><?= nl2br(htmlspecialchars($report['observations'])) ?: '—' ?></p>
    </div>

    <!-- STATUS -->
    <div class="section">
        <h2>STATUS FINAL</h2>
        <p style="margin:4px 0;"><strong><?= $report['final_status'] ?></strong></p>
    </div>

    <!-- EVIDENCIA FOTOGRÁFICA -->
    <?php if (!empty($evidence_photos)): ?>
    <div class="section" style="page-break-before: auto;">
        <h2>EVIDENCIA FOTOGRÁFICA</h2>
        <div class="evidence-grid">
            <?php
            $base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            foreach ($evidence_photos as $idx => $photo):
                $img_src = $base_url . '/' . ltrim($photo['file_path'], '/');
            ?>
            <div class="evidence-cell">
                <img src="<?= htmlspecialchars($img_src) ?>" class="evidence-img" alt="Foto <?= $idx + 1 ?>">
                <div class="evidence-caption">Foto <?= $idx + 1 ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FIRMAS -->
    <div class="section firmas">
        <h2 style="text-align:center; margin-bottom:8px;">FIRMAS</h2>
        <div style="display:flex; justify-content:space-between;">
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">INGENIERO</div>
                <div style="font-size:9px;"><?= htmlspecialchars($report['engineer_name']) ?></div>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">RECIBE</div>
                <div style="font-size:9px;"><?= htmlspecialchars($report['received_by']) ?: 'Nombre y firma' ?></div>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">ADMINISTRATIVO</div>
                <div style="font-size:9px;"><?= htmlspecialchars($report['admin_name']) ?: 'No registrado' ?></div>
            </div>
        </div>
    </div>

    <!-- BOTÓN -->
    <div class="no-print">
        <button onclick="printReport()" style="padding:8px 16px; font-size:13px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;">
            Guardar como PDF
        </button>
    </div>

</div>

<script>
function printReport() {
    alert("PDF EN 1 PÁGINA:\n\n" +
          "1. CTRL+P → 'Guardar como PDF'\n" +
          "2. Desactiva 'Encabezados y pies'\n" +
          "3. Ajusta escala si es necesario (95%)\n\n" +
          "¡Todo cabe en una hoja!");
    window.print();
}
</script>

</body>
</html>