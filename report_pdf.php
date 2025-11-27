<?php
// report_pdf.php
define('ACCESS', true);
require_once 'config/config.php';

$report_id = $_GET['id'] ?? 0;
if (!$report_id || !is_numeric($report_id)) {
    die('<h3 style="color:red;text-align:center;margin:50px;">Report ID not provided</h3>');
}

$stmt = $conn->prepare("SELECT * FROM maintenance_reports WHERE id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();

if (!$report) {
    die('<h3 style="color:red;text-align:center;margin:50px;">Report not found</h3>');
}

// === REFACCIONES ===
$parts = json_decode($report['parts_used'], true) ?: [];
$parts_list = '';
foreach ($parts as $p) {
    $stmt = $conn->prepare("SELECT name, stock FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $p['item_id']);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $parts_list .= '<li style="margin-left: 20px;">' . htmlspecialchars($p['quantity'] . ' × ' . ($item['name'] ?? 'Desconocido')) . '</li>';
}
if (empty($parts_list)) {
    $parts_list = '<em style="margin-left: 20px;">Ninguna refacción utilizada</em>';
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
            margin-bottom: 8px;
            font-size: 9.5px;
            line-height: 1.2;
        }

        .membrete {
            flex: 1;
            padding-right: 10px;
        }

        .membrete .company {
            font-weight: bold;
            font-size: 10px;
        }

        .membrete .address {
            margin: 1px 0;
        }

        .order-info {
            text-align: right;
            font-size: 11px;
            font-weight: bold;
            color: #d35400;
            line-height: 1.3;
        }

        .order-info .fecha {
            font-weight: normal;
            font-size: 10px;
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
            width: 48%;
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
        }
    </style>
</head>
<body>

<div class="container">

    <!-- MEMBRETE + ORDEN + FECHA (EN UNA LÍNEA) -->
    <div class="header-row">
        <div class="membrete">
            <div class="company">Venta, Mantenimiento Preventivo y Correctivo de Equipo Médico</div>
            <div class="address">Murillo No.26 Lote 28 Mz-75 Smz-321</div>
            <div class="address">Fracc. Villas del Arte</div>
            <div class="address">Benito Juarez Cancún, Quintana Roo C.P 77560</div>
            <div class="address">TEL: (998) 214 86 73/ 998 214 91 91</div>
        </div>
        <div class="order-info">
            <div>ORDEN: <?= htmlspecialchars($report['order_number']) ?></div>
            <div class="fecha">FECHA: <?= htmlspecialchars($report['report_date']) ?></div>
            <div class="fecha">HORA: <?= htmlspecialchars($report['report_time'] ?? date('H:i')) ?></div>
        </div>
    </div>

    <!-- TÍTULO -->
    <div class="title">REPORTE DE MANTENIMIENTO</div>

    <!-- CLIENTE -->
    <div class="section">
        <h2>DATOS DEL CLIENTE</h2>
        <table>
            <tr><th>Nombre</th><td><?= htmlspecialchars($report['client_name']) ?></td></tr>
            <tr><th>Teléfono</th><td><?= htmlspecialchars($report['client_phone']) ?></td></tr>
            <tr><th>Domicilio</th><td><?= htmlspecialchars($report['client_address']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($report['client_email']) ?></td></tr>
        </table>
    </div>

    <!-- EQUIPO -->
    <div class="section">
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