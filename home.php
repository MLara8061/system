<!doctype html>
<html lang="en">

<?php
require_once 'config/config.php';

// Total de Equipos
$result = $conn->query("SELECT COUNT(*) AS total FROM equipments");
$total_equipos = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $total_equipos = $row ? $row['total'] : 0;
}

// Total de Equipos EPP
$result = $conn->query("SELECT COUNT(*) AS total FROM accessories");
$total_epp = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $total_epp = $row ? $row['total'] : 0;
}

// Total de Herramientas
$result = $conn->query("SELECT COUNT(*) AS total FROM tools");
$total_herramientas = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $total_herramientas = $row ? $row['total'] : 0;
}

// Valor Total de Equipos
$valor_total_equipos = 0;
$result = $conn->query("SELECT SUM(amount) AS total FROM equipments");
if ($result) {
    $row = $result->fetch_assoc();
    $valor_total_equipos = $row && $row['total'] ? $row['total'] : 0;
}

// Valor Total de Equipos EPP
$valor_total_epp = 0;
$result = $conn->query("SELECT SUM(cost) AS total FROM accessories");
if ($result) {
    $row = $result->fetch_assoc();
    $valor_total_epp = $row && $row['total'] ? $row['total'] : 0;
}

// Valor Total de Herramientas
$valor_total_herramientas = 0;
$result = $conn->query("SELECT SUM(costo) AS total FROM tools");
if ($result) {
    $row = $result->fetch_assoc();
    $valor_total_herramientas = $row && $row['total'] ? $row['total'] : 0;
}

// Valor Total de Activos
$total_valor_activos = $valor_total_equipos + $valor_total_epp + $valor_total_herramientas;
?>





<head>

  <!--begin::Accessibility Meta Tags-->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
  <meta name="color-scheme" content="light dark" />
  <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
  <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
  <!--end::Accessibility Meta Tags-->
  <!--begin::Primary Meta Tags-->
  <meta name="title" content="AdminLTE | Dashboard v2" />
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

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
  <!--begin::App Wrapper-->
  <div class="app-wrapper">
    <!--begin::Header-->

    <!--end::Header-->
    <!--begin::Sidebar-->
    <!--end::Sidebar-->
    <!--begin::App Main-->
    <main class="app-main">
      <!--begin::App Content Header-->
      <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Row-->

          <!--end::Row-->
        </div>
        <!--end::Container-->
      </div>
      <div class="app-content">
       <!--begin::Container-->
<!-- Tarjetas de resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-desktop fa-2x text-primary mr-3"></i>
                <div>
                    <h6>Total de Equipos</h6>
                    <h4><?php echo $total_equipos; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-hard-hat fa-2x text-success mr-3"></i>
                <div>
                    <h6>Total de Equipos EPP</h6>
                    <h4><?php echo $total_epp; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-tools fa-2x text-warning mr-3"></i>
                <div>
                    <h6>Total de Herramientas</h6>
                    <h4><?php echo $total_herramientas; ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm" style="background:#fff;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-dollar-sign fa-2x text-info mr-3"></i>
                <div>
                    <h6>Valor Total de Activos</h6>
                    <h4>$<?php echo number_format($total_valor_activos, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <!-- /.row -->
</div>
<!-- /.container-fluid -->

          <!--begin::Row-->
          <div class="row">
            <div class="col-md-12">
              <div class="card mb-4">
                <div class="card-header">
                  <h5 class="card-title">Resumen Mensual de Equipos</h5>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-12">
                      <?php
                      $start = date('Y-m-01', strtotime('-11 months'));
                      $end = date('Y-m-d');
                      ?>
                      <p class="text-center">
                        <strong>Equipos Adquiridos: <?php echo date('d M Y', strtotime($start)); ?> - <?php echo date('d M Y', strtotime($end)); ?></strong>
                      </p>
                      <div id="sales-chart"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!--end::Row-->
          <!--begin::Row-->
          <div class="row">
            <!-- Start col -->
            <div class="col-md-8">
              <!--begin::Row-->
              <div class="row g-4 mb-4">
                <div class="col-md-6">
                </div>
              </div>
              <!--end::Row-->
              <!--begin::Últimos Equipos-->
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Últimos Equipos Registrados</h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table m-0">
                      <thead>
                        <tr>
                          <th>Inventario</th>
                          <th>Equipo</th>
                          <th>Proveedor</th>
                          <th>Valor</th>
                          <th>Estado</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $recent = $conn->query("SELECT e.*, COALESCE(s.empresa, 'Sin Proveedor') as supplier FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id ORDER BY e.date_created DESC LIMIT 7");
                        while ($eq = $recent->fetch_assoc()):
                        ?>
                        <tr>
                          <td>
                            <a href="./index.php?page=edit_equipment&id=<?php echo $eq['id']; ?>" class="link-primary">
                              <?php echo $eq['number_inventory']; ?>
                            </a>
                          </td>
                          <td><?php echo $eq['name']; ?></td>
                          <td><small><?php echo $eq['supplier']; ?></small></td>
                          <td>$<?php echo number_format($eq['amount'], 2); ?></td>
                          <td>
                            <?php if ($eq['revision'] == 1): ?>
                              <span class="badge text-bg-success">Con Revisión</span>
                            <?php else: ?>
                              <span class="badge text-bg-warning">Sin Revisión</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="card-footer clearfix">
                  <a href="./index.php?page=new_equipment" class="btn btn-sm btn-primary float-start">
                    <i class="fas fa-plus"></i> Agregar Equipo
                  </a>
                  <a href="./index.php?page=equipment_list" class="btn btn-sm btn-secondary float-end">
                    Ver Todos los Equipos
                  </a>
                </div>
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
            <div class="col-md-4">
              <!-- /.info-box -->
              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Distribución por Proveedor</h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-12">
                      <div id="pie-chart"></div>
                    </div>
                  </div>
                </div>
                <div class="card-footer p-0">
                  <ul class="nav nav-pills flex-column">
                    <?php
                    $top_suppliers = $conn->query("SELECT COALESCE(s.empresa, 'Sin Proveedor') as supplier, COUNT(*) as cnt, ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM equipments), 1) as pct FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id GROUP BY supplier ORDER BY cnt DESC LIMIT 3");
                    while ($sup = $top_suppliers->fetch_assoc()):
                    ?>
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        <?php echo $sup['supplier']; ?>
                        <span class="float-end text-primary">
                          <strong><?php echo $sup['pct']; ?>%</strong>
                        </span>
                      </a>
                    </li>
                    <?php endwhile; ?>
                  </ul>
                </div>
              </div>
              

            </div>
            <!-- /.col -->
          </div>
          <!--end::Row-->
        </div>
        <!--end::Container-->
      </div>
      <!--end::App Content-->
    </main>
    <!--end::App Main-->
    <!--begin::Footer-->

    <!--end::Footer-->
  </div>
  <!--end::App Wrapper-->
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
    <?php
    // Preparar datos reales para los gráficos
    // Series mensual: conteo y suma (amount) de equipments últimos 12 meses
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-{$i} months"));
    }

    // Inicializar arrays con ceros
    $counts = array_fill(0, 12, 0);
    $sums = array_fill(0, 12, 0);

    $start_date = date('Y-m-01', strtotime('-11 months'));
    $qry = $conn->query("SELECT DATE_FORMAT(date_created, '%Y-%m') AS ym, COUNT(*) AS cnt, SUM(amount) AS total FROM equipments WHERE date_created >= '" . $conn->real_escape_string($start_date) . "' GROUP BY ym ORDER BY ym ASC");
    $map = [];
    while ($r = $qry->fetch_assoc()) {
        $map[$r['ym']] = $r;
    }
    foreach ($months as $idx => $m) {
        if (isset($map[$m])) {
            $counts[$idx] = (int)$map[$m]['cnt'];
            $sums[$idx] = (float)$map[$m]['total'];
        }
    }

    // Categories (fecha del primer día del mes) para eje X
    $categories = array_map(function ($m) { return $m . "-01"; }, $months);

    // Pie chart: distribución por proveedor (top 6)
    $pie_labels = [];
    $pie_values = [];
    $pq = $conn->query("SELECT COALESCE(s.empresa, 'Sin Proveedor') as supplier, COUNT(*) as cnt FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id GROUP BY supplier ORDER BY cnt DESC LIMIT 6");
    while ($p = $pq->fetch_assoc()) {
        $pie_labels[] = $p['supplier'];
        $pie_values[] = (int)$p['cnt'];
    }

    ?>

    // Monthly equipments: counts and sums from DB
    const salesCategories = <?php echo json_encode($categories); ?>;
    const salesCounts = <?php echo json_encode($counts); ?>;
    const salesSums = <?php echo json_encode($sums); ?>;

    const sales_chart_options = {
      series: [{
          name: 'Equipos (cantidad)',
          data: salesCounts,
        },
        {
          name: 'Valor (MXN)',
          data: salesSums,
        },
      ],
      chart: {
        height: 300,
        type: 'area',
        toolbar: { show: false },
        locales: [{
          name: 'es',
          options: {
            months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            shortMonths: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
          }
        }],
        defaultLocale: 'es',
      },
      legend: { show: true, position: 'top' },
      colors: ['#0d6efd', '#20c997'],
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      xaxis: { 
        type: 'datetime', 
        categories: salesCategories,
      },
      yaxis: [
        { 
          title: { text: 'Cantidad de Equipos' },
          labels: { formatter: function(val) { return Math.floor(val); } }
        },
        { 
          opposite: true, 
          title: { text: 'Valor Total (MXN)' },
          labels: { formatter: function(val) { return '$' + val.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ','); } }
        }
      ],
      tooltip: { 
        shared: true,
        intersect: false,
        x: { format: 'MMMM yyyy' },
        y: [
          { formatter: function(val) { return val + ' equipos'; } },
          { formatter: function(val) { return '$' + val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); } }
        ]
      },
    };

    const sales_chart = new ApexCharts(document.querySelector('#sales-chart'), sales_chart_options);
    sales_chart.render();

    // Pie chart: distribución por proveedor
    const pieLabels = <?php echo json_encode($pie_labels); ?>;
    const pieValues = <?php echo json_encode($pie_values); ?>;
    const pie_chart_options = {
      series: pieValues,
      chart: { 
        type: 'donut',
        height: 320
      },
      labels: pieLabels,
      dataLabels: { 
        enabled: true,
        formatter: function(val, opts) {
          return opts.w.config.series[opts.seriesIndex] + ' equipos';
        }
      },
      colors: ['#0d6efd', '#20c997', '#ffc107', '#d63384', '#6f42c1', '#adb5bd'],
      legend: {
        position: 'bottom',
        horizontalAlign: 'center'
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return val + ' equipos';
          }
        }
      }
    };

    const pie_chart = new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options);
    pie_chart.render();
  </script>
  <!--end::Script-->
  

</body>
<!--end::Body-->

</html>