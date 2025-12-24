<?php
define('ACCESS', true);
require_once __DIR__ . '/../../config/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID inválido');
}
$id = (int)$_GET['id'];

// Cargar reporte
$qry = $conn->query("SELECT * FROM equipment_report_sistem WHERE id = $id");
if ($qry->num_rows == 0) die('Reporte no encontrado');
$r = $qry->fetch_array();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Servicio <?= $r['orden_servicio'] ?></title>
    <style>
        /* AJUSTE CLAVE PARA ELIMINAR TEXTOS DE ENCABEZADO Y PIE DE PÁGINA */
        @page {
            /* 1. Márgenes reducidos para el contenido */
            margin: 10mm; 
            size: A4 portrait;

            /* 2. Directivas para suprimir el texto del navegador (MÁXIMO ESFUERZO CSS) */
            /* La efectividad varía, por eso se complementa con la instrucción al usuario. */
            @top-left { content: none; }
            @top-center { content: none; }
            @top-right { content: none; }
            @bottom-left { content: none; }
            @bottom-center { content: none; }
            @bottom-right { content: none; }
        }

        /* --- Resto del CSS sin cambios en las dimensiones --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            margin: 0;
            padding: 0; 
        }
        .container {
            max-width: 200mm;
            margin: 0 auto;
            padding: 10px;
            border: 1px solid #ddd;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 8px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #007bff;
        }
        .folio {
            font-size: 14px;
            font-weight: bold;
            color: #d35400;
        }
        .section {
            margin: 15px 0;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 13px;
            background: #f8f9fa;
            padding: 6px;
            margin: 0 0 8px 0;
            border-left: 4px solid #007bff;
            color: #222;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }
        table th, table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        table th {
            background: #f1f1f1;
            font-weight: 600;
            width: 35%;
        }
        .materiales {
            margin-top: 8px;
            font-size: 11px;
        }
        .materiales li {
            margin: 3px 0;
            padding-left: 5px;
        }
        .firmas {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .firma {
            width: 45%; 
            display: inline-block;
            text-align: center;
            margin-top: 20px;
            vertical-align: top;
        }
        .firma:nth-child(2n+1) {
            margin-right: 5%;
        }
        .firma .linea {
            border-bottom: 1px solid #000;
            width: 80%;
            margin: 30px auto 6px auto;
        }
        .firma .nombre {
            font-weight: bold;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .mt-3 { margin-top: 0.5rem; } 
        
        @media print {
            body { padding: 0; margin: 0; }
            .container {
                border: none;
                padding: 0;
                margin: 0 auto;
            }
            .no-print { display: none; }
            .firma .linea { border-bottom: 1px solid #000; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>REPORTE DE SERVICIO TÉCNICO</h1>
        <div class="folio">Folio: <?= htmlspecialchars($r['orden_servicio']) ?></div>
    </div>

    <div class="section">
        <h2>DATOS DEL SERVICIO</h2>
        <table>
            <tr><th>Fecha del Servicio</th><td><?= date('d/m/Y', strtotime($r['fecha_servicio'])) ?></td></tr>
            <tr><th>Hora</th><td><?= substr($r['hora_inicio'], 0, 5) ?> - <?= substr($r['hora_fin'], 0, 5) ?></td></tr>
            <tr><th>Tipo de Servicio</th><td><strong><?= htmlspecialchars($r['tipo_servicio']) ?></strong></td></tr>
            <tr><th>Fecha de Entrega Tentativa</th><td><?= date('d/m/Y', strtotime($r['fecha_entrega'])) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>DATOS DEL EQUIPO</h2>
        <table>
            <tr><th>Nombre</th><td><?= htmlspecialchars($r['nombre']) ?></td></tr>
            <tr><th>N° Inventario</th><td><?= htmlspecialchars($r['numero_inv']) ?></td></tr>
            <tr><th>N° Serie</th><td><?= htmlspecialchars($r['serie']) ?></td></tr>
            <tr><th>Modelo</th><td><?= htmlspecialchars($r['modelo']) ?></td></tr>
            <tr><th>Marca</th><td><?= htmlspecialchars($r['marca']) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>DESCRIPCIÓN DEL SERVICIO REALIZADO</h2>
        <table>
            <tr><th>Mantenimiento Preventivo</th><td><?= nl2br(htmlspecialchars($r['mantenimientoPreventivo'] ?: '—')) ?></td></tr>
            <tr><th>Limpieza de Unidad de Riesgo</th><td><?= nl2br(htmlspecialchars($r['unidad_riesgo'] ?: '—')) ?></td></tr>
            <tr><th>Limpieza de Componentes</th><td><?= nl2br(htmlspecialchars($r['componentes'] ?: '—')) ?></td></tr>
            <tr><th>Extracción de Toner Residual</th><td><?= nl2br(htmlspecialchars($r['toner'] ?: '—')) ?></td></tr>
            <tr><th>Impresión de Pruebas</th><td><?= nl2br(htmlspecialchars($r['impresiom_pruebas'] ?: '—')) ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>MATERIAL UTILIZADO</h2>
        <div class="materiales">
            <?php
            $mats = [];
            if ($r['numero1']) $mats[] = $r['numero1'] . ' × ' . $r['material1'];
            if ($r['numero2']) $mats[] = $r['numero2'] . ' × ' . $r['material2'];
            if (empty($mats)) {
                echo '<em>Ningún material utilizado</em>';
            } else {
                echo '<ul style="margin:0; padding-left:20px;">';
                foreach ($mats as $m) {
                    echo '<li>' . htmlspecialchars($m) . '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
    </div>

    <div class="section firmas">
        <h2 class="text-center">FIRMAS DE CONFORMIDAD</h2>
        <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">TÉCNICO RESPONSABLE</div>
                <div style="font-size:10px;">Nombre y firma</div>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <div class="nombre">SUPERVISOR / JEFE DE ÁREA</div>
                <div style="font-size:10px;">Nombre y firma</div>
            </div>
            <div class="firma" style="width:95%; margin-top:20px; margin-left:auto; margin-right:auto;">
                <div class="linea"></div>
                <div class="nombre">USUARIO FINAL / CLIENTE</div>
                <div style="font-size:10px;">Nombre y firma</div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3 no-print">
        <button onclick="printReport()" class="btn btn-success" style="padding:10px 20px; font-size:14px;">
            Imprimir / Guardar como PDF
        </button>
        <a href="index.php?page=equipment_report_sistem_list" class="btn btn-secondary" style="padding:10px 20px; font-size:14px; margin-left:10px;">
            Volver
        </a>
    </div>
</div>

<script>
    function printReport() {
        // Muestra una alerta con las instrucciones clave
        alert("🚨 IMPORTANTE: Eliminar encabezados y pies de página:\n\n" +
              "1. En el diálogo de impresión (CTRL+P / CMD+P).\n" +
              "2. Busque la opción: 'Más ajustes' o 'Opciones'.\n" +
              "3. Desmarque la casilla: 'Encabezados y pies de página'.\n\n" +
              "Esto eliminará la fecha, hora, título y dirección del sitio web.");
        
        // Abre el diálogo de impresión
        window.print();
    }
</script>

</body>
</html>