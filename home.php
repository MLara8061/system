<!doctype html>
<html lang="en">

<?php
include 'db_connect.php';

// Total de Equipos
$result = $conn->query("SELECT COUNT(*) AS total FROM equipments");
$total_equipos = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $total_equipos = $row ? $row['total'] : 0;
}

// Total de Equipos EPP
$result = $conn->query("SELECT COUNT(*) AS total FROM equipment_epp");
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
$result = $conn->query("SELECT SUM(costo) AS total FROM equipment_epp");
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
                  <h5 class="card-title">Monthly Recap Report</h5>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                    <div class="btn-group">
                      <button
                        type="button"
                        class="btn btn-tool dropdown-toggle"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-wrench"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-end" role="menu">
                        <a href="#" class="dropdown-item">Action</a>
                        <a href="#" class="dropdown-item">Another action</a>
                        <a href="#" class="dropdown-item"> Something else here </a>
                        <a class="dropdown-divider"></a>
                        <a href="#" class="dropdown-item">Separated link</a>
                      </div>
                    </div>
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-remove">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <!--begin::Row-->
                  <div class="row">
                    <div class="col-md-8">
                      <p class="text-center">
                        <strong>Sales: 1 Jan, 2023 - 30 Jul, 2023</strong>
                      </p>
                      <div id="sales-chart"></div>
                    </div>
                    <!-- /.col -->
                    <div class="col-md-4">
                      <p class="text-center"><strong>Goal Completion</strong></p>
                      <div class="progress-group">
                        Add Products to Cart
                        <span class="float-end"><b>160</b>/200</span>
                        <div class="progress progress-sm">
                          <div class="progress-bar text-bg-primary" style="width: 80%"></div>
                        </div>
                      </div>
                      <!-- /.progress-group -->
                      <div class="progress-group">
                        Complete Purchase
                        <span class="float-end"><b>310</b>/400</span>
                        <div class="progress progress-sm">
                          <div class="progress-bar text-bg-danger" style="width: 75%"></div>
                        </div>
                      </div>
                      <!-- /.progress-group -->
                      <div class="progress-group">
                        <span class="progress-text">Visit Premium Page</span>
                        <span class="float-end"><b>480</b>/800</span>
                        <div class="progress progress-sm">
                          <div class="progress-bar text-bg-success" style="width: 60%"></div>
                        </div>
                      </div>
                      <!-- /.progress-group -->
                      <div class="progress-group">
                        Send Inquiries
                        <span class="float-end"><b>250</b>/500</span>
                        <div class="progress progress-sm">
                          <div class="progress-bar text-bg-warning" style="width: 50%"></div>
                        </div>
                      </div>
                      <!-- /.progress-group -->
                    </div>
                    <!-- /.col -->
                  </div>
                  <!--end::Row-->
                </div>
                <!-- ./card-body -->

                <!-- /.card-footer -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
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
              <!--begin::Latest Order Widget-->
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Latest Orders</h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-remove">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table m-0">
                      <thead>
                        <tr>
                          <th>Order ID</th>
                          <th>Item</th>
                          <th>Status</th>
                          <th>Popularity</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR9842</a>
                          </td>
                          <td>Call of Duty IV</td>
                          <td><span class="badge text-bg-success"> Shipped </span></td>
                          <td>
                            <div id="table-sparkline-1"></div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR1848</a>
                          </td>
                          <td>Samsung Smart TV</td>
                          <td><span class="badge text-bg-warning">Pending</span></td>
                          <td>
                            <div id="table-sparkline-2"></div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR7429</a>
                          </td>
                          <td>iPhone 6 Plus</td>
                          <td><span class="badge text-bg-danger"> Delivered </span></td>
                          <td>
                            <div id="table-sparkline-3"></div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR7429</a>
                          </td>
                          <td>Samsung Smart TV</td>
                          <td><span class="badge text-bg-info">Processing</span></td>
                          <td>
                            <div id="table-sparkline-4"></div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR1848</a>
                          </td>
                          <td>Samsung Smart TV</td>
                          <td><span class="badge text-bg-warning">Pending</span></td>
                          <td>
                            <div id="table-sparkline-5"></div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR7429</a>
                          </td>
                          <td>iPhone 6 Plus</td>
                          <td><span class="badge text-bg-danger"> Delivered </span></td>
                          <td>
                            <div id="table-sparkline-6"></div>
                          </td>
                        </tr>
                        <tr>
                          <td>
                            <a
                              href="pages/examples/invoice.html"
                              class="link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">OR9842</a>
                          </td>
                          <td>Call of Duty IV</td>
                          <td><span class="badge text-bg-success">Shipped</span></td>
                          <td>
                            <div id="table-sparkline-7"></div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <!-- /.table-responsive -->
                </div>
                <!-- /.card-body -->
                <div class="card-footer clearfix">
                  <a href="javascript:void(0)" class="btn btn-sm btn-primary float-start">
                    Place New Order
                  </a>
                  <a href="javascript:void(0)" class="btn btn-sm btn-secondary float-end">
                    View All Orders
                  </a>
                </div>
                <!-- /.card-footer -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
            <div class="col-md-4">
              <!-- /.info-box -->
              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Browser Usage</h3>
                  <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
                      <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                      <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-lte-toggle="card-remove">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                  <!--begin::Row-->
                  <div class="row">
                    <div class="col-12">
                      <div id="pie-chart"></div>
                    </div>
                    <!-- /.col -->
                  </div>
                  <!--end::Row-->
                </div>
                <!-- /.card-body -->
                <div class="card-footer p-0">
                  <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        United States of America
                        <span class="float-end text-danger">
                          <i class="bi bi-arrow-down fs-7"></i>
                          12%
                        </span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        India
                        <span class="float-end text-success">
                          <i class="bi bi-arrow-up fs-7"></i> 4%
                        </span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="#" class="nav-link">
                        China
                        <span class="float-end text-info">
                          <i class="bi bi-arrow-left fs-7"></i> 0%
                        </span>
                      </a>
                    </li>
                  </ul>
                </div>
                <!-- /.footer -->
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
    // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
    // IT'S ALL JUST JUNK FOR DEMO
    // ++++++++++++++++++++++++++++++++++++++++++

    /* apexcharts
     * -------
     * Here we will create a few charts using apexcharts
     */

    //-----------------------
    // - MONTHLY SALES CHART -
    //-----------------------

    const sales_chart_options = {
      series: [{
          name: 'Digital Goods',
          data: [28, 48, 40, 19, 86, 27, 90],
        },
        {
          name: 'Electronics',
          data: [65, 59, 80, 81, 56, 55, 40],
        },
      ],
      chart: {
        height: 180,
        type: 'area',
        toolbar: {
          show: false,
        },
      },
      legend: {
        show: false,
      },
      colors: ['#0d6efd', '#20c997'],
      dataLabels: {
        enabled: false,
      },
      stroke: {
        curve: 'smooth',
      },
      xaxis: {
        type: 'datetime',
        categories: [
          '2023-01-01',
          '2023-02-01',
          '2023-03-01',
          '2023-04-01',
          '2023-05-01',
          '2023-06-01',
          '2023-07-01',
        ],
      },
      tooltip: {
        x: {
          format: 'MMMM yyyy',
        },
      },
    };

    const sales_chart = new ApexCharts(
      document.querySelector('#sales-chart'),
      sales_chart_options,
    );
    sales_chart.render();

    //---------------------------
    // - END MONTHLY SALES CHART -
    //---------------------------

    function createSparklineChart(selector, data) {
      const options = {
        series: [{
          data
        }],
        chart: {
          type: 'line',
          width: 150,
          height: 30,
          sparkline: {
            enabled: true,
          },
        },
        colors: ['var(--bs-primary)'],
        stroke: {
          width: 2,
        },
        tooltip: {
          fixed: {
            enabled: false,
          },
          x: {
            show: false,
          },
          y: {
            title: {
              formatter() {
                return '';
              },
            },
          },
          marker: {
            show: false,
          },
        },
      };

      const chart = new ApexCharts(document.querySelector(selector), options);
      chart.render();
    }

    const table_sparkline_1_data = [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54];
    const table_sparkline_2_data = [12, 56, 21, 39, 73, 45, 64, 52, 36, 59, 44];
    const table_sparkline_3_data = [15, 46, 21, 59, 33, 15, 34, 42, 56, 19, 64];
    const table_sparkline_4_data = [30, 56, 31, 69, 43, 35, 24, 32, 46, 29, 64];
    const table_sparkline_5_data = [20, 76, 51, 79, 53, 35, 54, 22, 36, 49, 64];
    const table_sparkline_6_data = [5, 36, 11, 69, 23, 15, 14, 42, 26, 19, 44];
    const table_sparkline_7_data = [12, 56, 21, 39, 73, 45, 64, 52, 36, 59, 74];

    createSparklineChart('#table-sparkline-1', table_sparkline_1_data);
    createSparklineChart('#table-sparkline-2', table_sparkline_2_data);
    createSparklineChart('#table-sparkline-3', table_sparkline_3_data);
    createSparklineChart('#table-sparkline-4', table_sparkline_4_data);
    createSparklineChart('#table-sparkline-5', table_sparkline_5_data);
    createSparklineChart('#table-sparkline-6', table_sparkline_6_data);
    createSparklineChart('#table-sparkline-7', table_sparkline_7_data);

    //-------------
    // - PIE CHART -
    //-------------

    const pie_chart_options = {
      series: [700, 500, 400, 600, 300, 100],
      chart: {
        type: 'donut',
      },
      labels: ['Chrome', 'Edge', 'FireFox', 'Safari', 'Opera', 'IE'],
      dataLabels: {
        enabled: false,
      },
      colors: ['#0d6efd', '#20c997', '#ffc107', '#d63384', '#6f42c1', '#adb5bd'],
    };

    const pie_chart = new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options);
    pie_chart.render();

    //-----------------
    // - END PIE CHART -
    //-----------------
  </script>
  <!--end::Script-->
  

</body>
<!--end::Body-->

</html>