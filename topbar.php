<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Activos</title>

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="./assets/dist/css/adminlte.min.css">

  <style>
    /* CORREGIR Z-INDEX Y OVERLAY DEL DROPDOWN */
    .user-menu .dropdown-menu {
      z-index: 1051 !important;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
      border: 1px solid #e2e8f0 !important;
    }

    .user-menu .dropdown-menu::before {
      content: none !important;
    }

    .user-header {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      position: relative;
      z-index: 1;
    }

    .user-footer {
      background: #ffffff !important;
      border-top: 1px solid #e2e8f0 !important;
      padding: 1rem !important;
    }

    .user-footer .btn {
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.875rem;
      padding: 0.375rem 0.75rem;
      min-width: 110px;
    }

    .user-footer .btn-outline-primary {
      color: #4361ee;
      border-color: #4361ee;
    }

    .user-footer .btn-outline-primary:hover {
      background-color: #4361ee;
      color: white;
    }

    /* TOPBAR HOVER */
    .user-menu .nav-link {
      padding: 0.5rem 1rem !important;
      transition: background 0.2s ease;
    }

    .user-menu .nav-link:hover {
      background-color: rgba(0, 0, 0, 0.05);
      border-radius: 8px;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .user-menu .d-none.d-md-inline {
        display: none !important;
      }

      .user-footer .flex-fill {
        min-width: 100%;
        margin: 5px 0;
      }
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
      </li>

      <!-- Messages Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-comments"></i>
          <span class="badge badge-danger navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">...</a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
        </div>
      </li>

      <!-- Notifications Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">15</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">15 Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>

      <!-- Fullscreen -->
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>

      <!-- User Menu Dropdown -->
      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center p-0 px-3" data-toggle="dropdown">
          <img src="assets/avatars/<?= $_SESSION['login_avatar'] ?? 'default-avatar.png' ?>"
            class="user-image img-circle elevation-2 mr-2"
            alt="Avatar"
            style="width: 32px; height: 32px; object-fit: cover; border: 2px solid #e2e8f0;">
          <span class="d-none d-md-inline font-weight-medium text-gray-700">
            <?= ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?>
          </span>
        </a>

        <!-- DROPDOWN MENU -->
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0"
          style="width: 280px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.12); overflow: visible; z-index: 1055;">

          <!-- HEADER -->
          <div class="px-4 py-3 text-center bg-white" style="border-bottom: 1px solid #e2e8f0;">
            <img src="assets/avatars/<?= $_SESSION['login_avatar'] ?? 'default-avatar.png' ?>"
              class="img-circle elevation-2 mb-2"
              alt="Avatar"
              style="width: 70px; height: 70px; object-fit: cover; border: 3px solid #f1f5f9;">
            <h6 class="mb-1 font-weight-bold text-gray-900">
              <?= ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?>
            </h6>
            <p class="mb-1 text-gray-600 small">
              <i class="fas fa-user-tag mr-1"></i>
              <?= ($_SESSION['login_role'] ?? 2) == 1 ? 'Administrador' : 'Usuario' ?>
            </p>
            <p class="text-gray-500 text-xs">
              <i class="far fa-calendar-alt mr-1"></i>
              Miembro desde <?= date('d/m/Y', $_SESSION['login_date_created'] ?? time()) ?>
            </p>
          </div>

          <!-- BOTONES AL LADO -->
          <div class="p-2 bg-light d-flex justify-content-center gap-1">
            <a href="index.php?page=profile"
              class="btn btn-outline-primary btn-xs px-3 py-1 d-flex align-items-center"
              style="font-size: 0.75rem; border-radius: 6px;">
              <i class="fas fa-user-cog mr-1" style="font-size: 0.8rem;"></i> Mi Perfil
            </a>
            <a href="ajax.php?action=logout"
              class="btn btn-outline-primary btn-xs px-3 py-1 d-flex align-items-center"
              style="font-size: 0.75rem; border-radius: 6px;">
              <i class="fas fa-sign-out-alt mr-1" style="font-size: 0.8rem;"></i> Cerrar
            </a>
          </div>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- SCRIPTS OBLIGATORIOS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="./assets/dist/js/adminlte.min.js"></script>

  <!-- ACTIVAR PUSHMENU (SIDEBAR) -->
  <script>
    $(function() {
      // Asegurar que pushmenu funcione
      $('[data-widget="pushmenu"]').on('click', function() {
        $('body').toggleClass('sidebar-collapse');
      });
    });
  </script>

</body>

</html>