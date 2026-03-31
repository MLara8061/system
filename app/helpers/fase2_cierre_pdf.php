<?php
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/../..'));
}
if (!defined('ACCESS')) define('ACCESS', true);

require_once ROOT . '/config/config.php';
require_once ROOT . '/config/session.php';
require_once ROOT . '/app/helpers/permissions.php';
require_once ROOT . '/app/helpers/company_config_helper.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    header('location: ' . rtrim(BASE_URL, '/') . '/app/views/auth/login.php');
    exit;
}

$canView = function_exists('can')
    ? (can('view', 'reports') || can('export', 'reports') || can('view', 'audit_logs'))
    : ((int)($_SESSION['login_type'] ?? 0) === 1);
if (!$canView && (int)($_SESSION['login_type'] ?? 0) !== 1) {
    http_response_code(403);
    die('Sin permisos para visualizar el cierre de Fase 2');
}

$branchId = function_exists('active_branch_id') ? (int)active_branch_id() : (int)($_SESSION['login_active_branch_id'] ?? 0);
$company = get_company_config($conn, $branchId);
$logoUrl = get_company_logo_url($conn, $branchId);

$fecha = date('d/m/Y');
$hora = date('H:i');

$epicas = [
    ['E1', 'Auditoria y Logging', 'Completada', 'audit_logs, vista filtrable, exportacion, hooks en operaciones clave'],
    ['E2', 'Tickets y Comunicacion', 'Completada', 'adjuntos, notificaciones, historial de estados, hilo de respuestas, reporte de tiempos'],
    ['E3', 'Equipos y Catalogo', 'Completada', 'serie unica, validacion en captura, formato nombre+inventario, campos personalizados'],
    ['E4', 'Mantenimiento y Calendario', 'Completada', 'CRUD de periodos, exportacion de calendario, flujo de reportes consolidado'],
    ['E5', 'Insumos y Sustancias Peligrosas', 'Completada', 'flag hazardous, documentacion de seguridad, permisos y modulo dedicado'],
    ['E6', 'Reportes y Exportaciones', 'Completada', 'consumo electrico, ranking tickets/equipos, top gastos y reporteria Sprint 5'],
    ['E7', 'Branding y Configuracion', 'Completada', 'logo dinamico en PDFs, endurecimiento integral de exportaciones y descargas'],
];

$extras = [
    'Hardening de migraciones idempotentes para entornos parciales (especialmente migracion 018).',
    'Normalizacion de controles de acceso en exportadores app/helpers, legacy y public.',
    'Fallback de permisos en modo seguro: sin helper de permisos, solo admin.',
    'Proteccion de endpoints de impresion/PDF accesibles por URL directa.',
    'Compatibilidad defensiva por diferencias de esquema (columnas opcionales y chequeos SHOW COLUMNS).',
    'Mejoras de consistencia multi-sucursal en validaciones de lectura y exportacion.',
];

$evidencias = [
    'database/migrations/018_hazardous_inventory.sql',
    'app/views/dashboard/reports/tickets_report.php',
    'app/helpers/export_tickets_report.php',
    'app/helpers/export_sprint5_reports.php',
    'app/helpers/export_maintenance_calendar.php',
    'app/helpers/export_equipment.php',
    'app/helpers/export_suppliers.php',
    'legacy/export_equipment.php',
    'legacy/export_suppliers.php',
    'app/helpers/equipment_report_pdf.php',
    'app/helpers/equipment_report_sistem_pdf.php',
    'app/helpers/equipment_unsubscribe_pdf.php',
    'legacy/equipment_report_pdf.php',
    'legacy/equipment_report_sistem_pdf.php',
    'legacy/equipment_unsubscribe_pdf.php',
    'public/equipment_report_pdf.php',
    'public/ajax/action.php',
    'app/helpers/generate_pdf.php',
    'legacy/generate_pdf.php',
    'app/routing.php',
];

$autoPrint = isset($_GET['download']) && (string)$_GET['download'] === '1';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cierre Ejecutivo Fase 2</title>
  <style>
    @page {
      margin: 12mm;
      size: A4;
      @top-left { content: none; }
      @top-center { content: none; }
      @top-right { content: none; }
      @bottom-left { content: none; }
      @bottom-center { content: none; }
      @bottom-right { content: none; }
    }

    :root {
      --azul-900: #0f2f5f;
      --azul-700: #1f4f96;
      --azul-500: #2f78d6;
      --azul-100: #eaf3ff;
      --gris-900: #1f2937;
      --gris-700: #374151;
      --gris-500: #6b7280;
      --gris-200: #e5e7eb;
      --ok: #1f7a4c;
      --ok-bg: #e9f8f0;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      font-family: "Segoe UI", Tahoma, Arial, sans-serif;
      color: var(--gris-900);
      background: #f8fbff;
      line-height: 1.45;
    }

    .wrap {
      max-width: 196mm;
      margin: 0 auto;
      background: #fff;
      min-height: 100vh;
      padding: 14px 18px 18px;
      border: 1px solid var(--gris-200);
    }

    .toolbar {
      position: sticky;
      top: 0;
      z-index: 50;
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      background: #fff;
      padding: 8px 0;
    }

    .btn {
      border: 1px solid var(--azul-700);
      background: var(--azul-700);
      color: #fff;
      padding: 8px 14px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn.secondary {
      background: #fff;
      color: var(--azul-700);
    }

    .hero {
      border: 1px solid var(--gris-200);
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 16px;
    }

    .hero-top {
      background: linear-gradient(135deg, var(--azul-900), var(--azul-500));
      color: #fff;
      padding: 14px 16px;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 10px;
      align-items: center;
    }

    .hero-top h1 {
      margin: 0;
      font-size: 22px;
      letter-spacing: .2px;
    }

    .hero-top .sub {
      margin-top: 4px;
      font-size: 12px;
      opacity: .95;
    }

    .logo {
      max-height: 58px;
      max-width: 170px;
      object-fit: contain;
      background: #fff;
      border-radius: 6px;
      padding: 6px;
    }

    .hero-meta {
      display: grid;
      grid-template-columns: repeat(4, minmax(120px, 1fr));
      gap: 8px;
      padding: 10px 12px;
      background: var(--azul-100);
      border-top: 1px solid #c9ddff;
      font-size: 12px;
    }

    .meta-item {
      background: #fff;
      border: 1px solid #d7e7ff;
      border-radius: 6px;
      padding: 8px;
    }

    .meta-label { color: var(--gris-500); font-size: 11px; margin-bottom: 2px; }
    .meta-value { font-weight: 700; color: var(--azul-900); }

    h2 {
      margin: 16px 0 8px;
      font-size: 16px;
      color: var(--azul-900);
      border-bottom: 2px solid var(--azul-500);
      padding-bottom: 4px;
    }

    p { margin: 6px 0; color: var(--gris-700); font-size: 13px; }

    .kpi {
      display: grid;
      grid-template-columns: repeat(4, minmax(120px, 1fr));
      gap: 8px;
      margin: 8px 0 12px;
    }

    .card {
      border: 1px solid var(--gris-200);
      border-radius: 8px;
      padding: 10px;
      background: #fff;
    }

    .card .n { font-size: 20px; font-weight: 800; color: var(--azul-900); }
    .card .t { font-size: 11px; color: var(--gris-500); text-transform: uppercase; letter-spacing: .4px; }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
      margin: 8px 0 12px;
    }

    th, td {
      border: 1px solid var(--gris-200);
      padding: 8px;
      vertical-align: top;
      text-align: left;
    }

    th {
      background: #edf4ff;
      color: var(--azul-900);
      font-weight: 700;
    }

    .status-ok {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      background: var(--ok-bg);
      color: var(--ok);
      font-weight: 700;
      font-size: 11px;
      border: 1px solid #b8e8cf;
    }

    ul {
      margin: 8px 0 12px;
      padding-left: 20px;
      font-size: 13px;
      color: var(--gris-700);
    }

    li { margin: 4px 0; }

    .foot {
      margin-top: 16px;
      border-top: 1px dashed var(--gris-200);
      padding-top: 10px;
      font-size: 11px;
      color: var(--gris-500);
      text-align: center;
    }

    .page-break { page-break-before: always; }

    @media print {
      body { background: #fff; }
      .wrap { border: none; padding: 0; max-width: 100%; }
      .toolbar { display: none !important; }
      .btn { display: none !important; }
      a { text-decoration: none; color: inherit; }
    }
  </style>
</head>
<body<?= $autoPrint ? ' onload="window.print()"' : '' ?>>
  <div class="wrap">
    <div class="toolbar">
      <button class="btn secondary" onclick="window.open('?download=1','_blank')">Abrir modo PDF</button>
      <button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>

    <section class="hero">
      <div class="hero-top">
        <div>
          <h1>Cierre Ejecutivo Fase 2</h1>
          <div class="sub">Estado consolidado de implementación (E1 a E7) y mejoras complementarias</div>
        </div>
        <?php if (!empty($logoUrl)): ?>
          <img class="logo" src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
        <?php endif; ?>
      </div>
      <div class="hero-meta">
        <div class="meta-item">
          <div class="meta-label">Organización</div>
          <div class="meta-value"><?= htmlspecialchars($company['company_name'] ?? 'Sistema de Gestión', ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Fecha</div>
          <div class="meta-value"><?= $fecha ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Hora</div>
          <div class="meta-value"><?= $hora ?></div>
        </div>
        <div class="meta-item">
          <div class="meta-label">Ambito</div>
          <div class="meta-value">Fase 2 completa</div>
        </div>
      </div>
    </section>

    <h2>1. Resumen Ejecutivo</h2>
    <p>
      La Fase 2 se encuentra cerrada a nivel funcional en sus siete épicas estratégicas (E1-E7),
      con entregables operativos en auditoría, tickets, catálogo, mantenimiento, insumos peligrosos,
      analítica y branding. Además de los requerimientos base, se ejecutó una capa de endurecimiento
      transversal en migraciones idempotentes, seguridad de exportaciones y control de acceso a rutas
      de reporte/PDF expuestas por URL directa.
    </p>

    <div class="kpi">
      <div class="card"><div class="n">7/7</div><div class="t">Épicas cerradas</div></div>
      <div class="card"><div class="n">100%</div><div class="t">Cobertura E1-E7</div></div>
      <div class="card"><div class="n">+15</div><div class="t">Endpoints endurecidos</div></div>
      <div class="card"><div class="n">0</div><div class="t">Errores en diagnósticos finales</div></div>
    </div>

    <h2>2. Estado por Épica (E1-E7)</h2>
    <table>
      <thead>
        <tr>
          <th style="width:58px;">Épica</th>
          <th style="width:210px;">Nombre</th>
          <th style="width:120px;">Estado</th>
          <th>Entregables clave</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($epicas as $e): ?>
          <tr>
            <td><strong><?= htmlspecialchars($e[0], ENT_QUOTES, 'UTF-8') ?></strong></td>
            <td><?= htmlspecialchars($e[1], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="status-ok"><?= htmlspecialchars($e[2], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars($e[3], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>3. Mejoras Extra Aplicadas</h2>
    <ul>
      <?php foreach ($extras as $item): ?>
        <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>

    <div class="page-break"></div>

    <h2>4. Implementaciones Técnicas Relevantes</h2>
    <p>
      Se consolidó la arquitectura de reportes con criterios homogéneos de seguridad: validación de sesión,
      control por permisos de módulo/acción y consistencia de respuesta en exportaciones Excel/PDF.
      También se reforzó la compatibilidad entre ambientes mediante chequeos de existencia de tablas,
      columnas y restricciones en migraciones y scripts de consulta.
    </p>

    <h2>5. Evidencias de Código (Muestra)</h2>
    <table>
      <thead>
        <tr>
          <th style="width:45px;">#</th>
          <th>Archivo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($evidencias as $i => $f): ?>
          <tr>
            <td><?= (int)$i + 1 ?></td>
            <td><?= htmlspecialchars($f, ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>6. Conclusión</h2>
    <p>
      La Fase 2 queda presentada como cerrada y estable para operación, con un nivel de control superior al
      inicialmente definido en planeación gracias a mejoras extra en seguridad, robustez e idempotencia.
      El sistema queda preparado para transición a siguientes frentes evolutivos con una base de permisos,
      reporteo y multi-sucursal consistentemente aplicada.
    </p>

    <div class="foot">
      Documento generado automáticamente por el sistema · Cierre integral de Fase 2 · <?= $fecha ?> <?= $hora ?>
    </div>
  </div>
</body>
</html>
