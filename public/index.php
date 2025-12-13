<?php
/**
 * Punto de entrada único de la aplicación
 * Todas las requests deben pasar por aquí (configurar en web server)
 */

// Definir constante ROOT para toda la aplicación
define('ROOT', dirname(__DIR__));

// Permitir acceso
define('ALLOW_DIRECT_ACCESS', true);

// Cargar sesión hardened
require_once ROOT . '/config/session.php';

// Verificar sesión activa
if (!isset($_SESSION['login_id'])) {
    header('location: app/views/auth/login.php');
    exit();
}

// Validar timeout de sesión (30 minutos inactividad)
if (!validate_session()) {
    header('location: logout.php?timeout=1');
    exit();
}

// Incluir header y estructura de página
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
      
      <!-- Placeholder para contenido dinámico -->
      <div id="page-content">
        <!-- El contenido de la página se cargará aquí -->
        <p>Cargando...</p>
      </div>
    </div>

    <?php include ROOT . '/app/views/layouts/footer.php' ?>
  </div>
  
  <!-- Scripts globales -->
  <script src="/assets/plugins/jquery-ui/jquery-ui.min.js"></script>
  <script src="/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/plugins/chart.js/Chart.min.js"></script>
  <script src="/assets/plugins/sparklines/sparkline.js"></script>
  <script src="/assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
  <script src="/assets/dist/js/adminlte.js"></script>
  <script src="/assets/plugins/datatables/jquery.dataTables.min.js"></script>
  <script src="/assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
  <script src="/assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
  <script src="/assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
  <script src="/assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
  <script src="/assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
  <script src="/assets/plugins/select2/js/select2.full.min.js"></script>
  <script src="/assets/plugins/sweetalert2/sweetalert2.min.js"></script>
  <script src="/assets/plugins/toastr/toastr.min.js"></script>
  <script src="/assets/plugins/summernote/summernote-bs4.min.js"></script>
  <script src="/assets/dist/js/demo.js"></script>
</body>
</html>
