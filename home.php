<?php include('db_connect.php') ?>
<!-- Info boxes -->
<?php if ($_SESSION['login_type'] == 1) : ?>

  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>AdminLTE | Dashboard </title>
    <!--begin::Accessibility Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <!--end::Accessibility Meta Tags-->
    <!--begin::Primary Meta Tags-->
    <meta name="title" content="AdminLTE | Dashboard v3" />
    <meta name="author" content="ColorlibHQ" />
    <meta
      name="description"
      content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance." />
    <meta
      name="keywords"
      content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
    <!--end::Primary Meta Tags-->
    <!--begin::Accessibility Features-->
    <!-- Skip links will be dynamically added by accessibility.js -->
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="./css/adminlte.css" as="style" />
    <!--end::Accessibility Features-->
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
      media="print"
      onload="this.media='all'" />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous" />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous" />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="./css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous" />
  </head>

  <!--begin::Sidebar-->

  <!--end::Sidebar-->
  <!--begin::App Main-->
  <main class="app-main">
    <!--begin::App Content Header-->

    <!--end::Container-->
    </div>
    <div class="app-content">
      <!--begin::Container-->
      <!-- Info Boxes -->
<section class="row mb-4">
  <!-- Número de Activos -->
  <div class="col-lg-4 col-md-6 mb-3">
    <div class="card card-outline card-primary h-100">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Número de Activos</h5>
          <h2 class="mb-0">120</h2>
        </div>
        <div>
          <i class="fas fa-box fa-2x text-primary"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Valor Total de Activos -->
  <div class="col-lg-4 col-md-6 mb-3">
    <div class="card card-outline card-success h-100">
      <div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-1">Valor Total de Activos</h5>
          <h2 class="mb-0">$450,000</h2>
        </div>
        <div>
          <i class="fas fa-dollar-sign fa-2x text-success"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Pie Chart -->
  <div class="col-lg-4 col-md-12 mb-3">
    <div class="card card-outline card-info h-100">
      <div class="card-body text-center">
        <h5 class="mb-3">Valor de Activos por Categoría</h5>
        <canvas id="pieChart" style="max-height: 180px; max-width: 180px; margin: auto;"></canvas>
      </div>
    </div>
  </div>
</section>
<!-- JS Pie Chart -->
 <script>
 const ctx = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(ctx, {
  type: 'pie',
  data: {
    labels: ['Laptops', 'Smartphones', 'Tablets', 'Impresoras', 'Proyectores'],
    datasets: [{
      label: 'Valor de Activos',
      data: [150000, 90000, 50000, 70000, 80000],
      backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});
</script>

      <div class="container-fluid">
        <!--begin::Row-->
        <div class="row">
          <div class="col-lg-6">
            <div class="card mb-4">
              <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                  <h3 class="card-title">Mantenimientos realizados</h3>
                  <a
                    href="javascript:void(0);"
                    class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">View Report</a>
                </div>
              </div>
              <div class="card-body">
                <div class="d-flex">
                  <p class="d-flex flex-column">
                    <span class="fw-bold fs-5">820</span> <span>Totales </span>
                  </p>
                  <p class="ms-auto d-flex flex-column text-end">
                    <span class="text-success"> <i class="bi bi-arrow-up"></i> 12.5% </span>
                    <span class="text-secondary"> Since last week</span>
                  </p>
                </div>
                <!-- /.d-flex -->
                <div class="position-relative mb-4">
                  <div id="visitors-chart"></div>
                </div>
                <div class="d-flex flex-row justify-content-end">
                  <span class="me-2">
                    <i class="bi bi-square-fill text-primary"></i> This Week
                  </span>
                  <span> <i class="bi bi-square-fill text-secondary"></i> Last Week </span>
                </div>
              </div>
            </div>
            <!-- /.card -->
            <div class="card">
              <div class="card-header border-0">
                <h3 class="card-title">Equipos</h3>
                <div class="card-tools">
                  <a href="./index.php?page=new_equipment" class="btn btn-tool btn-sm">
                    <i class="fas fa-plus"></i>
                  </a>
                  <a href="./index.php?page=equipment_list" class="btn btn-tool btn-sm">
                    <i class="fas fa-list"></i>
                  </a>
                </div>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-striped table-valign-middle">
                  <thead>
                    <tr>
                      <th>Equipo</th>
                      <th>Inventario</th>
                      <th>Estado</th>
                      <th>Ver</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $qry = $conn->query("SELECT * FROM equipments ORDER BY id DESC LIMIT 5");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                      <tr>
                        <td>
                          <i class="fas fa-laptop text-primary mr-2"></i>
                          <?php echo $row['name'] ?>
                        </td>
                        <td># <?php echo $row['number_inventory'] ?></td>
                        <td>
                          <?php if ($row['revision'] == 1): ?>
                            <small class="text-success mr-1">
                              <i class="fas fa-arrow-up"></i>
                              100%
                            </small>
                            Con Revisión
                          <?php else: ?>
                            <small class="text-warning mr-1">
                              <i class="fas fa-exclamation-triangle"></i>
                              0%
                            </small>
                            Sin Revisión
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="./index.php?page=edit_equipment&id=<?php echo $row['id'] ?>" class="text-muted">
                            <i class="fas fa-search"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col-md-6 -->
          <div class="col-lg-6">
            <div class="card mb-4">
              <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                  <h3 class="card-title">Tickets Atendidos</h3>
                  <a
                    href="javascript:void(0);"
                    class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">View Report</a>
                </div>
              </div>
              <div class="card-body">
                <div class="d-flex">
                  <p class="d-flex flex-column">
                    <span class="fw-bold fs-5">90 </span> <span>Totales </span>
                  </p>
                  <p class="ms-auto d-flex flex-column text-end">
                    <span class="text-success"> <i class="bi bi-arrow-up"></i> 33.1% </span>
                    <span class="text-secondary">Since Past Year</span>
                  </p>
                </div>
                <!-- /.d-flex -->
                <div class="position-relative mb-4">
                  <div id="sales-chart"></div>
                </div>
                <div class="d-flex flex-row justify-content-end">
                  <span class="me-2">
                    <i class="bi bi-square-fill text-primary"></i> This year
                  </span>
                  <span> <i class="bi bi-square-fill text-secondary"></i> Last year </span>
                </div>
              </div>
            </div>
            <!-- /.card -->
            <div class="card">
              <div class="card-header border-0">
                <h3 class="card-title">Incidencias por Departamento</h3>
                <div class="card-tools">
                  <a href="#" class="btn btn-tool btn-sm" title="Agregar">
                    <i class="fas fa-plus"></i>
                  </a>
                  <a href="#" class="btn btn-tool btn-sm" title="Ver Lista">
                    <i class="fas fa-list"></i>
                  </a>
                </div>
              </div>

              <div class="card-body table-responsive p-0">
                <table class="table table-striped table-valign-middle">
                  <thead>
                    <tr>
                      <th></th>
                      <th>Departamento</th>
                      <th>Tendencia</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="text-success fs-5">
                        <i class="bi bi-bar-chart-line-fill"></i>
                      </td>
                      <td>Biomédica</td>
                      <td>
                        <small class="text-success mr-1">
                          <i class="bi bi-graph-up-arrow"></i> +15%
                        </small>
                        Aumento de incidencias
                      </td>
                    </tr>
                    <tr>
                      <td class="text-info fs-5">
                        <i class="bi bi-bar-chart-fill"></i>
                      </td>
                      <td>Enfermería</td>
                      <td>
                        <small class="text-info mr-1">
                          <i class="bi bi-graph-up-arrow"></i> +5%
                        </small>
                        Ligero aumento
                      </td>
                    </tr>
                    <tr>
                      <td class="text-warning fs-5">
                        <i class="bi bi-pie-chart-fill"></i>
                      </td>
                      <td>Pediatría</td>
                      <td>
                        <small class="text-warning mr-1">
                          <i class="bi bi-graph-down-arrow"></i> -8%
                        </small>
                        Disminución leve
                      </td>
                    </tr>
                    <tr>
                      <td class="text-danger fs-5">
                        <i class="bi bi-bar-chart-line-fill"></i>
                      </td>
                      <td>IT</td>
                      <td>
                        <small class="text-danger mr-1">
                          <i class="bi bi-graph-up-arrow"></i> +20%
                        </small>
                        Alto incremento
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </div>
    <!-- /.app-content -->
  </main>
  <!--end::App Main-->
  <!--begin::Footer-->
  <!--begin::Script-->
  <!--begin::Third Party Plugin(OverlayScrollbars)-->
  <script
    src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>
  <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
  <script
    src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
  <script src="./js/adminlte.js"></script>
  <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
  <script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = {
      scrollbarTheme: 'os-theme-light',
      scrollbarAutoHide: 'leave',
      scrollbarClickScroll: true,
    };
    document.addEventListener('DOMContentLoaded', function() {
      const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
      if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
        OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
          scrollbars: {
            theme: Default.scrollbarTheme,
            autoHide: Default.scrollbarAutoHide,
            clickScroll: Default.scrollbarClickScroll,
          },
        });
      }
    });
  </script>
  <!--end::OverlayScrollbars Configure-->
  <!-- OPTIONAL SCRIPTS -->
  <!-- apexcharts -->
  <script
    src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
    crossorigin="anonymous"></script>
  <script>
    // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
    // IT'S ALL JUST JUNK FOR DEMO
    // ++++++++++++++++++++++++++++++++++++++++++

    const visitors_chart_options = {
      series: [{
          name: 'High - 2023',
          data: [100, 120, 170, 167, 180, 177, 160],
        },
        {
          name: 'Low - 2023',
          data: [60, 80, 70, 67, 80, 77, 100],
        },
      ],
      chart: {
        height: 200,
        type: 'line',
        toolbar: {
          show: false,
        },
      },
      colors: ['#0d6efd', '#adb5bd'],
      stroke: {
        curve: 'smooth',
      },
      grid: {
        borderColor: '#e7e7e7',
        row: {
          colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
          opacity: 0.5,
        },
      },
      legend: {
        show: false,
      },
      markers: {
        size: 1,
      },
      xaxis: {
        categories: ['22th', '23th', '24th', '25th', '26th', '27th', '28th'],
      },
    };

    const visitors_chart = new ApexCharts(
      document.querySelector('#visitors-chart'),
      visitors_chart_options,
    );
    visitors_chart.render();

    const sales_chart_options = {
      series: [{
          name: 'Net Profit',
          data: [44, 55, 57, 56, 61, 58, 63, 60, 66],
        },
        {
          name: 'Revenue',
          data: [76, 85, 101, 98, 87, 105, 91, 114, 94],
        },
        {
          name: 'Free Cash Flow',
          data: [35, 41, 36, 26, 45, 48, 52, 53, 41],
        },
      ],
      chart: {
        type: 'bar',
        height: 200,
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          endingShape: 'rounded',
        },
      },
      legend: {
        show: false,
      },
      colors: ['#0d6efd', '#20c997', '#ffc107'],
      dataLabels: {
        enabled: false,
      },
      stroke: {
        show: true,
        width: 2,
        colors: ['transparent'],
      },
      xaxis: {
        categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
      },
      fill: {
        opacity: 1,
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return '$ ' + val + ' thousands';
          },
        },
      },
    };

    const sales_chart = new ApexCharts(
      document.querySelector('#sales-chart'),
      sales_chart_options,
    );
    sales_chart.render();
  </script>
  <!--end::Script-->
  </body>
  <!--end::Body-->
<?php else : ?>
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        Welcome <?php echo $_SESSION['login_name'] ?>!
      </div>
    </div>
  </div>
<?php endif; ?>