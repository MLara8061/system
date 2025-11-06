<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <div class="dropdown">
    <a href="javascript:void(0)" class="brand-link dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
      <span class="brand-image img-circle elevation-3 d-flex justify-content-center align-items-center bg-primary text-white font-weight-500" style="width: 38px;height:50px">
        <?php echo strtoupper(substr($_SESSION['login_firstname'], 0, 1) . substr($_SESSION['login_lastname'], 0, 1)) ?>
      </span>
      <span class="brand-text font-weight-light"><?php echo ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?></span>
    </a>
    <div class="dropdown-menu">
      <a class="dropdown-item manage_account" href="javascript:void(0)" data-id="<?php echo $_SESSION['login_id'] ?>">Gestionar Cuenta</a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="ajax.php?action=logout">Cerrar Sesión</a>
    </div>
  </div>

  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">

        <!-- Dashboard -->
        <li class="nav-item">
          <a href="./" class="nav-link nav-home">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- Equipos -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-equipment">
            <i class="nav-icon fas fa-laptop"></i>
            <p>Equipos <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=new_equipment" class="nav-link nav-new_equipment tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar Equipo</p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=equipment_list" class="nav-link nav-equipment_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todos Los Equipos</p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=equipment_report_sistem_list" class="nav-link nav-equipment_report_sistem_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Reporte de Sistemas</p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=equipment_report_revision_month" class="nav-link nav-equipment_report_revision_month tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Informe Revisiones Mensual</p>
              </a></li>
          </ul>
        </li>

        <!-- Proveedores -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-client">
            <i class="nav-icon fas fa-users"></i>
            <p>Proveedores <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="./index.php?page=new_supplier" class="nav-link nav-new_supplier tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Agregar</p>
              </a></li>
            <li class="nav-item"><a href="./index.php?page=suppliers" class="nav-link nav-suppliers tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todos los proveedores</p>
              </a></li>
          </ul>
        </li>

        <!-- Herramientas -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-tools">
            <i class="nav-icon fas fa-tools"></i>
            <p>Herramientas <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=new_tool" class="nav-link nav-new_tool tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar Herramienta</p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=tools_list" class="nav-link nav-tools_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todas las Herramientas</p>
              </a></li>
          </ul>
        </li>

        <!-- Equipos EPP -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-epp">
            <i class="nav-icon fas fa-hard-hat"></i>
            <p>Equipos EPP <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=new_epp" class="nav-link nav-new_epp tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar Equipo EPP</p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=epp_list" class="nav-link nav-epp_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todos los Equipos EPP</p>
              </a></li>
          </ul>
        </li>

        <!-- Calendario de Mantenimientos -->
        <li class="nav-item">
          <a href="index.php?page=calendar" class="nav-link">
            <i class="fas fa-calendar-alt nav-icon"></i>
            <p>Mantenimientos</p>
          </a>
        </li>

        <!-- Configuración -->
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Configuración <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">

            <!-- Departamentos -->
            <li class="nav-item">
              <a href="./index.php?page=department_list" class="nav-link nav-department_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Departamentos</p>
              </a>
            </li>

            <!-- Servicios -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-service">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Servicios <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item"><a href="index.php?page=manage_category" class="nav-link nav-manage_category tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Nueva Categoría</p>
                  </a></li>
                <li class="nav-item"><a href="index.php?page=category" class="nav-link nav-category tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista Categorías</p>
                  </a></li>
                <li class="nav-item"><a href="index.php?page=manage_services" class="nav-link nav-manage_services tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Crear Servicios</p>
                  </a></li>
                <li class="nav-item"><a href="index.php?page=service_list" class="nav-link nav-service_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista Servicios</p>
                  </a></li>
                <li class="nav-item"><a href="tickets/admin/?page=quote" class="nav-link nav-quote tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Requerimientos</p>
                  </a></li>
              </ul>
            </li>

            <!-- Mantenimientos -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-maintenance">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Mantenimientos <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item"><a href="index.php?page=create_user" class="nav-link nav-create_user tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Crear Usuario</p>
                  </a></li>
                <li class="nav-item"><a href="index.php?page=user_list" class="nav-link nav-user_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista de Usuarios</p>
                  </a></li>
              </ul>
            </li>
            <!-- Usuarios -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-staff">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Usuarios <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./index.php?page=new_staff" class="nav-link nav-new_staff tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Agregar</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./index.php?page=staff_list" class="nav-link nav-staff_list tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Todos los usuarios</p>
                  </a>
                </li>
              </ul>
            </li>
            <!-- Tickets -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-ticket">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Tickets <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="./index.php?page=new_ticket" class="nav-link nav-new_ticket tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Nuevo Ticket</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="./index.php?page=ticket_list" class="nav-link nav-ticket_list tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Todos los tickets</p>
                  </a>
                </li>
              </ul>
            </li>
    </nav>
  </div>
</aside>

<style>
  /* Sobrescribir azul predeterminado de AdminLTE */
  .sidebar-dark-primary .nav-sidebar .nav-link.active {
    background-color: #495057 !important;
    /* gris oscuro minimalista */
    color: #fff !important;
  }

  .sidebar-dark-primary .nav-sidebar .nav-item.menu-open>.nav-link {
    background-color: #343a40 !important;
    /* gris aún más oscuro para padres */
    color: #fff !important;
  }
</style>

<script>
  $(document).ready(function() {
    var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
    var link = $('.nav-link.nav-' + page);

    if (link.length > 0) {
      link.addClass('active');
      if (link.hasClass('tree-item')) {
        link.closest('.nav-treeview').siblings('a').addClass('active');
        link.closest('.nav-treeview').parent().addClass('menu-open');
      }
    }

    $('.manage_account').click(function() {
      uni_modal('Gestionar Cuenta', 'manage_user.php?id=' + $(this).attr('data-id'));
    });
  });
</script>