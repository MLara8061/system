<!DOCTYPE html>
<html lang="en">
<?php 
// Constante para permitir includes de vistas
define('ALLOW_DIRECT_ACCESS', true);

session_start();
if (!isset($_SESSION['login_id'])) {
  header('location:login.php');
  exit();
}
include 'header.php';
?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
  <div class="wrapper">
    <?php include 'topbar.php' ?>
    <?php include 'sidebar.php' ?>

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
$page = $_GET['page'] ?? 'home';

// === LIMPIAR NOMBRE DE PÁGINA (SEGURIDAD) ===
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// === PÁGINAS PERMITIDAS ===
$allowed_pages = [
    'home',
    'new_equipment',
    'equipment_list',
    'edit_equipment',
    'equipment_report_sistem_list',
    'equipment_report_revision_month',
    'equipment_new_revision',
    'equipment_report_responsible',
    'equipment_report_sistem',
    'equipment_unsubscribe',
    'equipment_report_sistem_editar',
    'new_supplier',
    'suppliers',
    'edit_supplier',
    'accessories_list',
    'new_accesories',
    'edit_accesories',
    'tools_list',
    'new_tool',
    'edit_tool',
    'new_epp',
    'edit_epp',
    'calendar',
    'department_list',
    'manage_category',
    'category',
    'manage_services',
    'service_list',
    'locations',
    'job_positions',
    'create_user',
    'user_list',
    'manage_user',
    'manage_inventory',
    'inventory_list',
    'profile',
    'activity_log',
    'report_form',
    'generate_pdf',
    'upload_equipment',
    'download_template',
    'generate_excel_template',
    'ticket_list',
    'new_ticket',
    'edit_ticket',
    'view_ticket',
    

    // AÑADE MÁS AQUÍ
];

// === VERIFICAR SI EXISTE Y ESTÁ PERMITIDA ===
if (in_array($page, $allowed_pages) && file_exists($page . '.php')) {
    include $page . '.php';
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
  <?php include 'footer.php' ?>
</body>

</html>