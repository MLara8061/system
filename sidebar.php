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
        <!--Proveedores-->
        <li class="nav-item">
          <a href="#" class="nav-link nav-client">
            <i class="nav-icon fas fa-users"></i>
            <p>Proveedores <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./index.php?page=new_supplier" class="nav-link nav-customer_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Agregar</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./index.php?page=suppliers" class="nav-link nav-customer_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Todos los proveedores</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Staff -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-staff">
            <i class="nav-icon fas fa-user"></i>
            <p>Usuarios <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./index.php?page=new_staff" class="nav-link nav-staff_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Agregar</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./index.php?page=staff_list" class="nav-link nav-staff_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Empleados</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Departamentos -->
        <li class="nav-item">
          <a href="./index.php?page=department_list" class="nav-link nav-department_list">
            <i class="nav-icon fas fa-building"></i>
            <p>Departamentos</p>
          </a>
        </li>
        <!-- Equipos -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-equipment">
            <i class="nav-icon fas fa-laptop"></i>
            <p>Equipos <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=new_equipment" class="nav-link nav-equipment_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar Equipo</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=equipment_list" class="nav-link nav-equipment_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Todos Los Equipos</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=equipment_report_sistem_list" class="nav-link nav-equipment_system tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Reporte de Sistemas</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=equipment_report_revision_month" class="nav-link nav-equipment_month tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Informe Revisiones Mensual</p>
              </a>
            </li>
          </ul>
        </li>
        <!-- Herramientas -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-tools">
            <i class="nav-icon fas fa-tools"></i>
            <p>
              Herramientas
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=new_tool" class="nav-link nav-tools_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar Herramienta</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=tools_list" class="nav-link nav-tools_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Todas las Herramientas</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Tickets -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-ticket">
            <i class="nav-icon fas fa-ticket-alt"></i>
            <p>Ticket <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="./index.php?page=new_ticket" class="nav-link nav-ticket_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Agregar</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="./index.php?page=ticket_list" class="nav-link nav-ticket_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Listar Tickets</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Servicios -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-service">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Servicios <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=manage_category" class="nav-link nav-category_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Nueva Categoría</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=category" class="nav-link nav-category_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Lista Categorías</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=manage_services" class="nav-link nav-service_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Crear Servicios</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=service_list" class="nav-link nav-service_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Lista Servicios</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="tickets/admin/?page=quote" class="nav-link nav-quote tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Requerimientos</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Maintenance -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-maintenance">
            <i class="nav-icon fas fa-tools"></i>
            <p>Maintenance <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="index.php?page=create_user" class="nav-link nav-maintenance_add tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Crear Usuario</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php?page=user_list" class="nav-link nav-maintenance_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Lista de Usuarios</p>
              </a>
            </li>
          </ul>
        </li>
      </ul>
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