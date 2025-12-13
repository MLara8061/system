<!DOCTYPE html>
<html lang="en">
<?php 
// Constante para permitir includes de vistas
define('ALLOW_DIRECT_ACCESS', true);
define('ROOT', __DIR__);

// Cargar sesión hardened
require_once ROOT . '/config/session.php';

// Validar sesión activa y timeout
if (!isset($_SESSION['login_id'])) {
  header('location: app/views/auth/login.php');
  exit();
}

// Validar timeout de sesión (30 minutos inactividad)
if (!validate_session()) {
  header('location: app/views/auth/logout.php?timeout=1');
  exit();
}
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
          
          $page = $_GET['page'] ?? 'home';
          
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
              <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Guardar</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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