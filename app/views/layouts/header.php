<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php
  // Cabeceras de seguridad básicas
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: DENY');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  // CSP permisiva (incluye CDN y recursos externos)
  header("Content-Security-Policy: default-src 'self' https:; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' https:; connect-src 'self' https:");
  ob_start();
  $title = isset($_GET['page']) ? ucwords(str_replace("_", ' ', $_GET['page'])) : "Home";
  ?>
  <title><?php echo $title ?> | Sistema de Soporte Técnico</title>
  <?php ob_end_flush() ?>

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="assets/plugins/toastr/toastr.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/dist/css/styles.css">
  <!-- Botones Responsive UX -->
  <link rel="stylesheet" href="assets/css/button-responsive.css">
  <!-- Sistema de Alertas Moderno -->
  <link rel="stylesheet" href="assets/css/custom-alerts.css">
  <script src="assets/plugins/jquery/jquery.min.js"></script>
  <!-- summernote -->
  <link rel="stylesheet" href="assets/plugins/summernote/summernote-bs4.min.css">


<style type="text/css">
  .float-left{
    float: left;
  }

  /* Estilos personalizados para badges - Fondo claro con texto fuerte */
  .badge-primary {
    background-color: #cfe2ff !important;
    color: #084298 !important;
    border: 1px solid #b6d4fe;
  }
  
  .badge-success {
    background-color: #d1e7dd !important;
    color: #0f5132 !important;
    border: 1px solid #badbcc;
  }
  
  .badge-info {
    background-color: #cff4fc !important;
    color: #055160 !important;
    border: 1px solid #b6effb;
  }
  
  .badge-warning {
    background-color: #fff3cd !important;
    color: #997404 !important;
    border: 1px solid #ffe69c;
  }
  
  .badge-danger {
    background-color: #f8d7da !important;
    color: #842029 !important;
    border: 1px solid #f5c2c7;
  }
  
  .badge-secondary {
    background-color: #e2e3e5 !important;
    color: #41464b !important;
    border: 1px solid #d3d6d8;
  }
  
  .badge-light {
    background-color: #f8f9fa !important;
    color: #495057 !important;
    border: 1px solid #dee2e6;
  }
  
  .badge-dark {
    background-color: #d6d8db !important;
    color: #141619 !important;
    border: 1px solid #c6c8ca;
  }
  
  /* Mantener peso de fuente */
  .badge {
    font-weight: 600 !important;
  }

  #page-loading-indicator {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
  }

  #page-loading-indicator.is-hidden {
    display: none;
  }

  .spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>
</head>