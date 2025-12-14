<?php
// Este archivo se incluye dentro de index.php; no debe declarar <html>/<body> ni recargar assets globales.
$traceFile = ROOT . '/home_trace.log';
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: start\n", FILE_APPEND);

$user_id = $_SESSION['login_id'] ?? 0;
$user_branch = null;
if ($user_id) {
    $user_query = $conn->query("SELECT u.*, b.name AS branch_name FROM users u LEFT JOIN branches b ON u.active_branch_id = b.id WHERE u.id = {$user_id}");
    $user_branch = $user_query ? $user_query->fetch_assoc() : null;
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . '] HOME LOAD: fetched user_branch: ' . json_encode($user_branch) . "\n", FILE_APPEND);
}

file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: before branches query\n", FILE_APPEND);
$branches = $conn->query("SELECT id, name FROM branches WHERE active = 1 ORDER BY name ASC");
if ($branches === false) {
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . '] HOME LOAD: branches query FAILED: ' . ($conn->error ?? 'unknown') . "\n", FILE_APPEND);
    return;
}
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: fetched branches OK\n", FILE_APPEND);

$branch_filter = $user_branch && $user_branch['active_branch_id'] ? 'AND e.branch_id = ' . (int)$user_branch['active_branch_id'] : '';

$total_equipos = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $total_equipos = (int)($row['total'] ?? 0);
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: total_equipos={$total_equipos}\n", FILE_APPEND);
}

$total_epp = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM accessories WHERE 1=1 {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $total_epp = (int)($row['total'] ?? 0);
}

$total_herramientas = 0;
$result = $conn->query("SELECT COUNT(*) AS total FROM tools WHERE 1=1 {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $total_herramientas = (int)($row['total'] ?? 0);
}

$valor_total_equipos = 0;
$result = $conn->query("SELECT SUM(e.amount) AS total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $valor_total_equipos = (float)($row['total'] ?? 0);
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: valor_total_equipos={$valor_total_equipos}\n", FILE_APPEND);
}

$valor_total_epp = 0;
$result = $conn->query("SELECT SUM(cost) AS total FROM accessories WHERE 1=1 {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $valor_total_epp = (float)($row['total'] ?? 0);
}

$valor_total_herramientas = 0;
$result = $conn->query("SELECT SUM(costo) AS total FROM tools WHERE 1=1 {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $valor_total_herramientas = (float)($row['total'] ?? 0);
}

$total_valor_activos = $valor_total_equipos + $valor_total_epp + $valor_total_herramientas;
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: total_valor_activos={$total_valor_activos}\n", FILE_APPEND);

// Período seleccionado para gráficas de servicio
$period = $_GET['period'] ?? '6m';
$period_label = '';
$months_count = 6;
switch ($period) {
    case '6m':
        $start_service = date('Y-m-01', strtotime('-5 months'));
        $period_label = 'Últimos 6 meses';
        $months_count = 6;
        break;
    case '12m':
        $start_service = date('Y-m-01', strtotime('-11 months'));
        $period_label = 'Últimos 12 meses';
        $months_count = 12;
        break;
    case 'year':
        $start_service = date('Y-01-01');
        $period_label = 'Este año (' . date('Y') . ')';
        $months_count = (int)date('m');
        break;
    case 'all':
        $start_service = '2000-01-01';
        $period_label = 'Todos los registros';
        $months_count = 24;
        break;
    default:
        $start_service = date('Y-m-01', strtotime('-5 months'));
        $period_label = 'Últimos 6 meses';
        $months_count = 6;
}

// Datos para gráficas de mantenimiento
$service_types = [];
$service_counts = [];
$service_query = $conn->query("SELECT service_type, COUNT(*) AS total FROM maintenance_reports WHERE STR_TO_DATE(report_date, '%d/%m/%Y') >= '{$start_service}' GROUP BY service_type ORDER BY total DESC");
if ($service_query) {
    while ($row = $service_query->fetch_assoc()) {
        $service_types[] = $row['service_type'] ?: 'Sin especificar';
        $service_counts[] = (int)$row['total'];
    }
}
if (empty($service_types)) {
    $service_types = ['Sin datos'];
    $service_counts = [0];
}

$exec_months = [];
for ($i = $months_count - 1; $i >= 0; $i--) {
    $exec_months[] = date('Y-m', strtotime("-{$i} months"));
}
$mp_data = array_fill(0, $months_count, 0);
$mc_data = array_fill(0, $months_count, 0);
$exec_query = $conn->query("SELECT DATE_FORMAT(STR_TO_DATE(report_date, '%d/%m/%Y'), '%Y-%m') AS month, service_type, COUNT(*) AS total FROM maintenance_reports WHERE STR_TO_DATE(report_date, '%d/%m/%Y') >= '{$start_service}' GROUP BY month, service_type ORDER BY month ASC");
$exec_map = [];
if ($exec_query) {
    while ($row = $exec_query->fetch_assoc()) {
        if ($row['month']) {
            $exec_map[$row['month']][$row['service_type']] = (int)$row['total'];
        }
    }
}
foreach ($exec_months as $idx => $month) {
    if (isset($exec_map[$month])) {
        $mp_data[$idx] = $exec_map[$month]['MP'] ?? 0;
        $mc_data[$idx] = $exec_map[$month]['MC'] ?? 0;
    }
}
$exec_categories = array_map(function ($m) { return $m . '-01'; }, $exec_months);

// Datos para series mensuales de equipos
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-{$i} months"));
}
$counts = array_fill(0, 12, 0);
$sums = array_fill(0, 12, 0);
$start_date = date('Y-m-01', strtotime('-11 months'));
$qry = $conn->query("SELECT DATE_FORMAT(e.date_created, '%Y-%m') AS ym, COUNT(*) AS cnt, SUM(e.amount) AS total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL AND e.date_created >= '{$start_date}' GROUP BY ym ORDER BY ym ASC");
$map = [];
while ($qry && ($r = $qry->fetch_assoc())) {
    $map[$r['ym']] = $r;
}
foreach ($months as $idx => $m) {
    if (isset($map[$m])) {
        $counts[$idx] = (int)$map[$m]['cnt'];
        $sums[$idx] = (float)$map[$m]['total'];
    }
}
$categories = array_map(function ($m) { return $m . '-01'; }, $months);

// Pie proveedores
$pie_labels = [];
$pie_values = [];
$pq = $conn->query("SELECT COALESCE(s.empresa, 'Sin Proveedor') AS supplier, COUNT(*) AS cnt FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL GROUP BY supplier ORDER BY cnt DESC LIMIT 6");
while ($pq && ($p = $pq->fetch_assoc())) {
    $pie_labels[] = $p['supplier'];
    $pie_values[] = (int)$p['cnt'];
}
?>

<div class="row align-items-center mb-3">
  <div class="col-md-8">
    <h3 class="mb-0">Dashboard</h3>
  </div>
  <div class="col-md-4">
    <div class="d-flex align-items-center justify-content-end">
      <label class="mr-2 font-weight-bold mb-0">Sucursal Activa:</label>
      <select id="branch_selector" class="form-control form-control-sm" style="width: 200px;">
        <?php while ($branch = $branches->fetch_assoc()): ?>
          <option value="<?= $branch['id'] ?>" <?= $user_branch && $user_branch['active_branch_id'] == $branch['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($branch['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-3">
    <div class="card shadow-sm">
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
    <div class="card shadow-sm">
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
    <div class="card shadow-sm">
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
    <div class="card shadow-sm">
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

<div class="row mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-body p-2 d-flex align-items-center justify-content-between flex-wrap">
        <div class="mb-2 mb-md-0">
          <i class="fas fa-filter mr-2"></i>
          <strong>Período de datos:</strong>
        </div>
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" class="btn btn-outline-primary <?= $period === '6m' ? 'active' : '' ?>" onclick="changePeriod('6m')">
            <i class="fas fa-calendar-alt"></i> 6 Meses
          </button>
          <button type="button" class="btn btn-outline-primary <?= $period === '12m' ? 'active' : '' ?>" onclick="changePeriod('12m')">
            <i class="fas fa-calendar"></i> 12 Meses
          </button>
          <button type="button" class="btn btn-outline-primary <?= $period === 'year' ? 'active' : '' ?>" onclick="changePeriod('year')">
            <i class="fas fa-calendar-check"></i> Este Año
          </button>
          <button type="button" class="btn btn-outline-primary <?= $period === 'all' ? 'active' : '' ?>" onclick="changePeriod('all')">
            <i class="fas fa-infinity"></i> Todo
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mb-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-chart-pie mr-2"></i>Reportes por Tipo de Servicio
          <small class="text-muted">(<?php echo htmlspecialchars($period_label); ?>)</small>
        </h5>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
            <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
            <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div id="service-type-chart" style="min-height: 350px;"></div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="fas fa-chart-line mr-2"></i>Reportes Mensuales por Tipo de Ejecución
          <small class="text-muted">(<?php echo htmlspecialchars($period_label); ?>)</small>
        </h5>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
            <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
            <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div id="execution-monthly-chart" style="min-height: 350px;"></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">Resumen Mensual de Equipos</h5>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
            <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
            <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <?php $start = date('Y-m-01', strtotime('-11 months')); $end = date('Y-m-d'); ?>
        <p class="text-center mb-4">
          <strong>Equipos Adquiridos: <?php echo date('d M Y', strtotime($start)); ?> - <?php echo date('d M Y', strtotime($end)); ?></strong>
        </p>
        <div id="sales-chart"></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-8">
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
              $recent = $conn->query("SELECT e.*, COALESCE(s.empresa, 'Sin Proveedor') AS supplier FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL ORDER BY e.date_created DESC LIMIT 7");
              while ($recent && ($eq = $recent->fetch_assoc())): ?>
                <tr>
                  <td><a href="./index.php?page=edit_equipment&id=<?php echo $eq['id']; ?>" class="link-primary"><?php echo $eq['number_inventory']; ?></a></td>
                  <td><?php echo $eq['name']; ?></td>
                  <td><small><?php echo $eq['supplier']; ?></small></td>
                  <td>$<?php echo number_format($eq['amount'], 2); ?></td>
                  <td>
                    <?php if ((int)$eq['revision'] === 1): ?>
                      <span class="badge badge-success"><strong>Con Revisión</strong></span>
                    <?php else: ?>
                      <span class="badge badge-warning"><strong>Sin Revisión</strong></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer clearfix">
        <a href="./index.php?page=new_equipment" class="btn btn-sm btn-primary float-start"><i class="fas fa-plus"></i> Agregar Equipo</a>
        <a href="./index.php?page=equipment_list" class="btn btn-sm btn-secondary float-end">Ver Todos los Equipos</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
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
        <div id="pie-chart"></div>
      </div>
      <div class="card-footer p-0">
        <ul class="nav nav-pills flex-column">
          <?php
          $top_suppliers = $conn->query("SELECT COALESCE(s.empresa, 'Sin Proveedor') AS supplier, COUNT(*) AS cnt, ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM equipments e2 LEFT JOIN equipment_unsubscribe u2 ON e2.id = u2.equipment_id WHERE u2.id IS NULL), 1) AS pct FROM equipments e LEFT JOIN suppliers s ON e.supplier_id = s.id LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL GROUP BY supplier ORDER BY cnt DESC LIMIT 3");
          while ($top_suppliers && ($sup = $top_suppliers->fetch_assoc())): ?>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <?php echo $sup['supplier']; ?>
                <span class="float-end text-primary"><strong><?php echo $sup['pct']; ?>%</strong></span>
              </a>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  function changePeriod(period) {
    const url = new URL(window.location.href);
    url.searchParams.set('period', period);
    url.searchParams.set('page', 'home');
    window.location.href = url.toString();
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js" integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
<script>
  const salesCategories = <?php echo json_encode($categories); ?>;
  const salesCounts = <?php echo json_encode($counts); ?>;
  const salesSums = <?php echo json_encode($sums); ?>;
  const pieLabels = <?php echo json_encode($pie_labels); ?>;
  const pieValues = <?php echo json_encode($pie_values); ?>;
  const serviceTypes = <?php echo json_encode($service_types); ?>;
  const serviceCounts = <?php echo json_encode($service_counts); ?>;
  const execCategories = <?php echo json_encode($exec_categories); ?>;
  const mpData = <?php echo json_encode($mp_data); ?>;
  const mcData = <?php echo json_encode($mc_data); ?>;

  const sales_chart_options = {
    series: [
      { name: 'Equipos (cantidad)', data: salesCounts },
      { name: 'Valor (MXN)', data: salesSums },
    ],
    chart: { height: 300, type: 'area', toolbar: { show: false }, locales: [{
      name: 'es', options: { months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'], shortMonths: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] }
    }], defaultLocale: 'es' },
    legend: { show: true, position: 'top' },
    colors: ['#0d6efd', '#20c997'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: { type: 'datetime', categories: salesCategories },
    yaxis: [
      { title: { text: 'Cantidad de Equipos' }, labels: { formatter: val => Math.floor(val) } },
      { opposite: true, title: { text: 'Valor Total (MXN)' }, labels: { formatter: val => '$' + val.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',') } }
    ],
    tooltip: { shared: true, intersect: false, x: { format: 'MMMM yyyy' }, y: [ val => `${val} equipos`, val => '$' + val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') ] },
  };
  if (document.querySelector('#sales-chart')) {
    new ApexCharts(document.querySelector('#sales-chart'), sales_chart_options).render();
  }

  const pie_chart_options = {
    series: pieValues,
    chart: { type: 'donut', height: 320 },
    labels: pieLabels,
    dataLabels: { enabled: true, formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] + ' equipos' },
    colors: ['#0d6efd', '#20c997', '#ffc107', '#d63384', '#6f42c1', '#adb5bd'],
    legend: { position: 'bottom', horizontalAlign: 'center' },
    tooltip: { y: { formatter: val => val + ' equipos' } }
  };
  if (document.querySelector('#pie-chart')) {
    new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options).render();
  }

  const serviceTypeChartOptions = {
    series: serviceCounts,
    chart: { type: 'donut', height: 350 },
    labels: serviceTypes,
    colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1'],
    dataLabels: { enabled: true, formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] + ' reportes' },
    legend: { position: 'bottom', horizontalAlign: 'center', fontSize: '14px' },
    plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total Reportes', formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0) } } } } },
    tooltip: { y: { formatter: val => val + ' reportes' } }
  };
  if (document.querySelector('#service-type-chart')) {
    new ApexCharts(document.querySelector('#service-type-chart'), serviceTypeChartOptions).render();
  }

  const executionMonthlyOptions = {
    series: [ { name: 'MP (Preventivo)', data: mpData }, { name: 'MC (Correctivo)', data: mcData } ],
    chart: { height: 350, type: 'line', toolbar: { show: false }, locales: [{ name: 'es', options: { months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'], shortMonths: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] } }], defaultLocale: 'es' },
    colors: ['#198754', '#dc3545'],
    dataLabels: { enabled: true, formatter: val => (val > 0 ? val : '') },
    stroke: { curve: 'smooth', width: 3 },
    markers: { size: 5, hover: { size: 7 } },
    xaxis: { type: 'datetime', categories: execCategories },
    yaxis: { title: { text: 'Número de Reportes' }, labels: { formatter: val => Math.floor(val) } },
    legend: { show: true, position: 'top', horizontalAlign: 'center', fontSize: '14px' },
    tooltip: { shared: true, intersect: false, x: { format: 'MMMM yyyy' }, y: { formatter: val => val + ' reportes' } },
    grid: { borderColor: '#f1f1f1' }
  };
  if (document.querySelector('#execution-monthly-chart')) {
    new ApexCharts(document.querySelector('#execution-monthly-chart'), executionMonthlyOptions).render();
  }

  $('#branch_selector').on('change', function () {
    var branch_id = $(this).val();
    if (branch_id) {
      $.ajax({
        url: 'ajax.php?action=update_user_branch',
        method: 'POST',
        data: { branch_id: branch_id },
        dataType: 'json',
        success: function (data) {
          if (data.success) {
            alert_toast('Sucursal cambiada correctamente', 'success');
            location.reload();
          } else {
            alert_toast('Error al cambiar sucursal', 'error');
          }
        },
        error: function () {
          alert_toast('Error de conexión', 'error');
        }
      });
    }
  });
</script>
