<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <div class="dropdown">
    <a href="javascript:void(0)"
      class="brand-link dropdown-toggle text-decoration-none"
      data-toggle="dropdown"
      aria-expanded="true">

      <!-- FOTO -->
      <img src="assets/avatars/<?= $_SESSION['login_avatar'] ?? 'default-avatar.png' ?>"
        alt="Avatar"
        class="brand-image img-circle elevation-3"
        style="width: 38px; height: 38px; object-fit: cover;">

      <!-- NOMBRE -->
      <span class="brand-text font-weight-light">
        <?= ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?>
      </span>
    </a>

    <div class="dropdown-menu dropdown-menu-right">
      <!-- MI CUENTA → profile.php -->
      <a class="dropdown-item" href="index.php?page=profile">
        <i class="fas fa-user-cog mr-2"></i> Mi Cuenta
      </a>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="ajax.php?action=logout">
        <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
      </a>
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
                <p>Revisiones Mensual</p>
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

        <!-- Accesorios -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-epp">
            <i class="nav-icon fas fa-hard-hat"></i>
            <p>Accesorios <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=new_accesories" class="nav-link nav-new_epp tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar </p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=accessories_list" class="nav-link nav-epp_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todos los Accesorios</p>
              </a></li>
          </ul>
        </li>

        <!-- Inventario -->
        <li class="nav-item">
          <a href="#" class="nav-link nav-epp">
            <i class="nav-icon fas fa-store"></i>
            <p>Inventario <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=manage_inventory" class="nav-link nav-new_epp tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar </p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=inventory_list" class="nav-link nav-epp_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todos</p>
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

        <!-- Registro de actividad -->
        <?php if ($_SESSION['login_role'] == 1): ?>
          <li class="nav-item">
            <a href="index.php?page=activity_log" class="nav-link">
              <i class="fas fa-history nav-icon"></i>
              <p>Registro de Actividad</p>
            </a>
          </li>
        <?php endif; ?>
        <!-- Configuración -->
         <?php if($_SESSION['login_role'] == 1): ?>
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

            <!-- Ubicaciones -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-equipment_location">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Ubicaciones <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">

                </li>
                <li class="nav-item">
                  <a href="index.php?page=locations" class="nav-link nav-equipment_locations tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista de Ubicaciones</p>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Puestos de Trabajo -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-job_position">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Puestos <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                </li>
                <li class="nav-item">
                  <a href="index.php?page=job_positions" class="nav-link nav-equipment_locations tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista de Puestos</p>
                  </a>
                </li>
              </ul>
            </li>

            <!-- Usuarios -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-maintenance">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Usuarios <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item"><a href="index.php?page=user_list" class="nav-link nav-user_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Todos los Usuarios</p>
                  </a></li>
              </ul>
            </li>

            <!-- Carga Masiva -->
            <li class="nav-item">
              <a href="#" class="nav-link nav-upload">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Carga Masiva <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?page=upload_equipment" class="nav-link nav-upload_equipment tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Equipos desde Excel</p>
                  </a>
                </li>
              </ul>
            </li>

          </ul>
        </li>
        <?php endif; ?>

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