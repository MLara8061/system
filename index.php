<!DOCTYPE html>
<html lang="en">
<?php 
// === VERIFICAR MODO MANTENIMIENTO PRIMERO ===
$maintenanceConfig = require __DIR__ . '/config/maintenance_config.php';
if ($maintenanceConfig['maintenance_enabled']) {
    // Verificar si la IP está en la lista de permitidas
    $userIP = $_SERVER['REMOTE_ADDR'] ?? '';
    $isAllowedIP = in_array($userIP, $maintenanceConfig['allowed_ips']);
    
    if (!$isAllowedIP) {
        // Cargar directamente la página de mantenimiento
        require __DIR__ . '/components/maintenance.php';
        exit();
    }
}

// Constante para permitir includes de vistas
define('ALLOW_DIRECT_ACCESS', true);
define('ROOT', __DIR__);

// Cargar configuración base
require_once ROOT . '/config/config.php';

// Cargar sesión hardened
require_once ROOT . '/config/session.php';

// Capturar errores fatales para evitar pantalla con loader infinito
register_shutdown_function(function () {
  $err = error_get_last();
  if (!$err) return;

  $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
  if (!in_array($err['type'] ?? 0, $fatalTypes, true)) return;

  $message = (string)($err['message'] ?? 'Fatal error');
  $file = (string)($err['file'] ?? '');
  $line = (int)($err['line'] ?? 0);

  $stamp = date('Y-m-d H:i:s');
  $logLine = "[$stamp] {$message} in {$file}:{$line}\n";

  // Log a archivo para diagnóstico (Hostinger)
  $logPath = __DIR__ . '/logs/php_fatal.log';
  @file_put_contents($logPath, $logLine, FILE_APPEND);

  // Render mínimo (si aún hay salida posible)
  if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
  }
  echo "\n<style>#page-loading-indicator{display:none!important}</style>";
  echo "<div style='padding:16px;font-family:Arial,Helvetica,sans-serif'>";
  echo "<h3 style='margin:0 0 8px 0;color:#b91c1c'>Error interno</h3>";
  echo "<div style='color:#374151'>Se registró el detalle en <b>logs/php_fatal.log</b>. Recarga la página.</div>";
  echo "</div>";
});

// Validar sesión activa y timeout
if (!isset($_SESSION['login_id'])) {
  header('location: ' . rtrim(BASE_URL, '/') . '/app/views/auth/login.php');
  exit();
}

// Validar timeout de sesión (30 minutos inactividad)
if (!validate_session()) {
  header('location: app/views/auth/logout.php?timeout=1');
  exit();
}

// Páginas que no deben cargar el layout HTML (helpers/endpoints)
$noLayoutPages = [
    'generate_excel_template',
    'generate_pdf',
    'equipment_report_pdf',
    'equipment_report_sistem_pdf',
    'equipment_unsubscribe_pdf',
    'equipment_unsubscribe_report',
    'export_equipment',
    'export_suppliers',
    'manual_usuario_pdf',
    'report_pdf',
    'generate_qr'
];

$page = $_GET['page'] ?? 'home';

// Si es una página sin layout, procesarla directamente
if (in_array($page, $noLayoutPages)) {
    require_once ROOT . '/app/routing.php';
    $file = resolve_route($page);
    
    if ($file && file_exists($file)) {
        include $file;
        exit();
    }
}

// Cargar el layout normal para todas las otras páginas
include ROOT . '/app/views/layouts/header.php';
?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
  <div id="page-loading-indicator">
    <div class="spinner" aria-hidden="true"></div>
  </div>
  <div class="wrapper">
    <?php include ROOT . '/app/views/layouts/topbar.php' ?>
    <?php include ROOT . '/app/views/layouts/sidebar.php' ?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <div class="toast" id="alert_toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-body text-white">
        </div>
      </div>
      <div id="toastsContainerTopRight" class="toasts-top-right fixed"></div>
      <!-- Content Header (Page header) -->
      <div class="content-header">
       
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <?php
          require_once ROOT . '/app/routing.php';
          
          // $page ya fue definida arriba en la lógica de noLayoutPages
          // Resolver ruta
          $file = resolve_route($page);
          
          
          
          if ($file) {
              include $file;
          } else {
              // === PÁGINA NO ENCONTRADA  ===
              ?>
              <div class="container-fluid text-center py-5">
                  <div class="jumbotron bg-light border rounded p-5">
                      <h1 class="display-4 text-danger">404</h1>
                      <h3>Página no encontrada</h3>
                      <p class="lead">Lo sentimos, la página <strong><?= htmlspecialchars($page) ?></strong> no existe.</p>
                      <hr class="my-4">
                      <a href="index.php" class="btn btn-primary btn-lg">
                          Volver al inicio
                      </a>
                  </div>
              </div>
              <?php
          }
          ?>
        </div><!--/. container-fluid -->
      </section>
      <!-- /.content -->
      <div class="modal fade" id="confirm_modal" role='dialog'>
        <div class="modal-dialog modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Confirmación</h5>
            </div>
            <div class="modal-body">
              <div id="delete_content"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-primary" id='confirm' onclick="">Continuar</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="uni_modal" role='dialog'>
        <div class="modal-dialog modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"></h5>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-primary submit" id='submit' onclick="$('#uni_modal form').submit()">Guardar</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="uni_modal_right" role='dialog'>
        <div class="modal-dialog modal-full-height  modal-md" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span class="fa fa-arrow-right"></span>
              </button>
            </div>
            <div class="modal-body">
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="viewer_modal" role='dialog'>
        <div class="modal-dialog modal-md" role="document">
          <div class="modal-content">
            <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
            <img src="" alt="">
          </div>
        </div>
      </div>
    </div>
    <!-- /.content-wrapper -->

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    <!-- Main Footer -->
    <footer class="main-footer">
      <strong>Copyright © 2026 All rights reserved <a href="#">Amerimed</a></strong>
      <div class="float-right d-none d-sm-inline-block">
        <b>Powered by Arla</b>
      </div>
    </footer>
  </div>
  <!-- ./wrapper -->

  <!-- REQUIRED SCRIPTS -->
  <!-- jQuery -->
  <!-- Bootstrap -->
  <?php include ROOT . '/app/views/layouts/footer.php' ?>
</body>

</html>