<aside class="main-sidebar elevation-4" style="background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);">
  <div class="dropdown">
    <a href="javascript:void(0)"
      class="brand-link dropdown-toggle text-decoration-none"
      data-toggle="dropdown"
      aria-expanded="true">

      <img src="assets/avatars/<?= $_SESSION['login_avatar'] ?? 'default-avatar.png' ?>"
        alt="Avatar"
        class="brand-image img-circle elevation-3"
        style="width: 38px; height: 38px; object-fit: cover;">

      <span class="brand-text font-weight-light">
        <?= ucwords($_SESSION['login_firstname'] . ' ' . $_SESSION['login_lastname']) ?>
      </span>
    </a>

    <div class="dropdown-menu dropdown-menu-right">
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

        <li class="nav-item">
          <a href="./" class="nav-link nav-home">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

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

        <li class="nav-item">
          <a href="#" class="nav-link nav-epp">
            <i class="nav-icon fas fa-store"></i>
            <p>Inventario <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item"><a href="index.php?page=manage_inventory" class="nav-link nav-manage_inventory tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Ingresar </p>
              </a></li>
            <li class="nav-item"><a href="index.php?page=inventory_list" class="nav-link nav-inventory_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                <p>Todos</p>
              </a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a href="index.php?page=calendar" class="nav-link nav-calendar">
            <i class="fas fa-calendar-alt nav-icon"></i>
            <p>Mantenimientos</p>
          </a>
        </li>
        
        <li class="nav-item">
          <a href="index.php?page=report_form" class="nav-link nav-reporte_form">
            <i class="fas fa-file-invoice nav-icon"></i>
            <p>Generar Reportes</p>
          </a>
        </li>

        <?php if ($_SESSION['login_type'] == 1): ?>
          <li class="nav-item">
            <a href="index.php?page=activity_log" class="nav-link nav-activity_log">
              <i class="fas fa-history nav-icon"></i>
              <p>Registro de Actividad</p>
            </a>
          </li>
        <?php endif; ?>
        
        <?php if($_SESSION['login_type'] == 1): ?>
        <li class="nav-item">
          <a href="#" class="nav-link nav-cogs">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Configuración <i class="right fas fa-angle-left"></i></p>
          </a>
          <ul class="nav nav-treeview">

            <li class="nav-item">
              <a href="./index.php?page=department_list" class="nav-link nav-department_list tree-item">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Departamentos</p>
              </a>
            </li>

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

            <li class="nav-item">
              <a href="#" class="nav-link nav-equipment_location">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Ubicaciones <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                </li>
                <li class="nav-item">
                  <a href="index.php?page=locations" class="nav-link nav-locations tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista de Ubicaciones</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="#" class="nav-link nav-job_position">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Puestos <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                </li>
                <li class="nav-item">
                  <a href="index.php?page=job_positions" class="nav-link nav-job_positions tree-item">
                    <i class="fas fa-angle-right nav-icon"></i>
                    <p>Lista de Puestos</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="#" class="nav-link nav-user">
                <i class="fas fa-angle-right nav-icon"></i>
                <p>Usuarios <i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item"><a href="index.php?page=user_list" class="nav-link nav-user_list tree-item"><i class="fas fa-angle-right nav-icon"></i>
                    <p>Todos los Usuarios</p>
                  </a></li>
              </ul>
            </li>
          </ul>
        </li>
      <?php endif; ?>
      </ul>
    </nav>
  </div>
</aside>

<style>
/* === SIDEBAR: Colores Gris Intermedio Moderno === */
.main-sidebar {
  border-right: 1px solid #dee2e6;
}

.main-sidebar .brand-link {
  border-bottom: 1px solid #dee2e6;
  background: #fff;
  color: #495057 !important;
}

.main-sidebar .brand-text {
  color: #495057 !important;
  font-weight: 600;
}

/* Links principales */
.main-sidebar .nav-link {
  color: #495057 !important;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.main-sidebar .nav-link:hover {
  background-color: rgba(0, 123, 255, 0.08) !important;
  color: #007bff !important;
  border-left-color: #007bff;
}

.main-sidebar .nav-link.active {
  background-color: rgba(0, 123, 255, 0.12) !important;
  color: #007bff !important;
  font-weight: 600;
  border-left-color: #007bff;
}

/* Iconos */
.main-sidebar .nav-icon {
  color: #6c757d;
}

.main-sidebar .nav-link:hover .nav-icon,
.main-sidebar .nav-link.active .nav-icon {
  color: #007bff;
}

/* Submenús */
.main-sidebar .nav-treeview {
  background-color: rgba(0, 0, 0, 0.02);
}

.main-sidebar .nav-treeview > .nav-item > .nav-link {
  color: #6c757d !important;
  padding-left: 2.5rem;
  border-left: 3px solid transparent;
}

.main-sidebar .nav-treeview > .nav-item > .nav-link:hover {
  background-color: rgba(0, 123, 255, 0.06) !important;
  color: #007bff !important;
  border-left-color: transparent;
}

.main-sidebar .nav-treeview > .nav-item > .nav-link.active {
  background-color: rgba(0, 123, 255, 0.1) !important;
  color: #007bff !important;
  font-weight: 600;
  border-left-color: transparent;
}

/* Flechas de dropdown */
.main-sidebar .right {
  transition: transform 0.2s ease;
  color: #6c757d;
}

.main-sidebar .nav-item.menu-open > .nav-link .right {
  transform: rotate(-90deg);
  color: #007bff;
}

/* Hover en items con submenú */
.main-sidebar .nav-item:has(.nav-treeview) > .nav-link:hover .right {
  color: #007bff;
}
</style>

<script>
$(document).ready(function() {
  // === INICIALIZAR TREEVIEW DE ADMINLTE (método nativo) ===
  $('[data-widget="treeview"]').Treeview('init');
  
  // === MARCAR PÁGINA ACTIVA ===
  const page = '<?php echo $_GET["page"] ?? "home"; ?>';
  
  // Remover todas las clases active primero
  $('.nav-sidebar .nav-link').removeClass('active');
  $('.nav-sidebar .nav-item').removeClass('menu-open');
  $('.nav-treeview').hide();
  
  // Marcar el link de la página actual
  const $currentLink = $('.nav-link.nav-' + page);
  if ($currentLink.length > 0) {
    $currentLink.addClass('active');
    
    // Si está dentro de un treeview, abrir el padre
    const $treeview = $currentLink.closest('.nav-treeview');
    if ($treeview.length > 0) {
      $treeview.show();
      $treeview.parent('.nav-item').addClass('menu-open');
      $treeview.siblings('a.nav-link').addClass('active');
      
      // Si hay un nivel más arriba (menú anidado de tercer nivel)
      const $parentTreeview = $treeview.closest('.nav-item').parent('.nav-treeview');
      if ($parentTreeview.length > 0) {
        $parentTreeview.show();
        $parentTreeview.parent('.nav-item').addClass('menu-open');
      }
    }
  }
  
  // === MANEJAR CLICKS EN MENÚS CON SUBMENÚ ===
  $('.nav-sidebar > .nav-item > .nav-link').on('click', function(e) {
    const $link = $(this);
    const $item = $link.parent('.nav-item');
    const $treeview = $link.next('.nav-treeview');
    
    // Solo procesar si tiene submenú
    if ($treeview.length > 0) {
      e.preventDefault();
      e.stopPropagation();
      
      const isOpen = $item.hasClass('menu-open');
      
      if (isOpen) {
        // CERRAR el menú actual
        $item.removeClass('menu-open');
        $treeview.slideUp(250, function() {
          $(this).hide();
        });
      } else {
        // CERRAR todos los otros menús del mismo nivel
        $item.siblings('.menu-open').each(function() {
          $(this).removeClass('menu-open');
          $(this).find('.nav-treeview').slideUp(250, function() {
            $(this).hide();
          });
        });
        
        // ABRIR el menú clickeado
        $item.addClass('menu-open');
        $treeview.slideDown(250, function() {
          $(this).show();
        });
      }
    }
  });
  
  // === MANEJAR SUBMENÚS ANIDADOS (segundo y tercer nivel) ===
  $('.nav-treeview .nav-link').on('click', function(e) {
    const $link = $(this);
    const $item = $link.parent('.nav-item');
    const $nestedTreeview = $link.next('.nav-treeview');
    
    // Solo procesar si tiene submenú anidado
    if ($nestedTreeview.length > 0) {
      e.preventDefault();
      e.stopPropagation();
      
      const isOpen = $item.hasClass('menu-open');
      
      if (isOpen) {
        $item.removeClass('menu-open');
        $nestedTreeview.slideUp(200);
      } else {
        // Cerrar hermanos
        $item.siblings('.menu-open').removeClass('menu-open').find('.nav-treeview').slideUp(200);
        
        // Abrir actual
        $item.addClass('menu-open');
        $nestedTreeview.slideDown(200);
      }
    }
  });
});
</script>

<!-- Fallback CSS por si AdminLTE no aplica sus reglas de colapso -->
<style>
  /* Dejar que AdminLTE maneje el colapso nativamente */
</style>