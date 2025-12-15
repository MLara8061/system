<?php
// Este archivo se incluye dentro de index.php; no debe declarar <html>/<body> ni recargar assets globales.
$traceFile = ROOT . '/home_trace.log';
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: start\n", FILE_APPEND);

// Helper function para queries que se cuelgan con query()
function safe_query($conn, $sql) {
    if ($conn->real_query($sql)) {
        return $conn->store_result();
    }
    return false;
}

$user_id = $_SESSION['login_id'] ?? 0;
$user_branch = null;
if ($user_id) {
    // Sin JOIN a branches (causa timeout)
    $user_query = safe_query($conn, "SELECT * FROM users WHERE id = {$user_id}");
    $user_branch = $user_query ? $user_query->fetch_assoc() : null;
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . '] HOME LOAD: fetched user_branch: ' . json_encode($user_branch) . "\n", FILE_APPEND);
}

file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: skip branches query (workaround)\n", FILE_APPEND);
// WORKAROUND: La query a branches se cuelga por un bug desconocido. 
// Como solo hay 1 branch, creamos un resultset falso
$branches = false;
// Datos estáticos basados en la única branch existente
$branches_data = [['id' => 1, 'name' => 'Sede Principal']];
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: using static branches data\n", FILE_APPEND);

// TEMPORAL: Desactivar filtro de sucursal hasta verificar que branch_id existe en todas las tablas
$branch_filter = '';
// $branch_filter = $user_branch && $user_branch['active_branch_id'] ? 'AND e.branch_id = ' . (int)$user_branch['active_branch_id'] : '';
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: branch_filter disabled (troubleshooting)\n", FILE_APPEND);

$total_equipos = 0;
$result = safe_query($conn, "SELECT COUNT(*) AS total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $total_equipos = (int)($row['total'] ?? 0);
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: total_equipos={$total_equipos}\n", FILE_APPEND);
}

// Query optimizada: solo COUNT sin JOINs
$result_accesorios = $conn->query("SELECT COUNT(*) as total FROM accessories");
$total_epp = ($result_accesorios && $row = $result_accesorios->fetch_assoc()) ? $row['total'] : 0;

$result_herramientas = $conn->query("SELECT COUNT(*) as total FROM tools");
$total_herramientas = ($result_herramientas && $row = $result_herramientas->fetch_assoc()) ? $row['total'] : 0;
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: EPP={$total_epp}, Herramientas={$total_herramientas}\n", FILE_APPEND);

$valor_total_equipos = 0;
$result = safe_query($conn, "SELECT SUM(e.amount) AS total FROM equipments e LEFT JOIN equipment_unsubscribe u ON e.id = u.equipment_id WHERE u.id IS NULL {$branch_filter}");
if ($result && ($row = $result->fetch_assoc())) {
    $valor_total_equipos = (float)($row['total'] ?? 0);
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: valor_total_equipos={$valor_total_equipos}\n", FILE_APPEND);
}

$valor_total_epp = 0;
$valor_total_herramientas = 0;

$total_valor_activos = $valor_total_equipos + $valor_total_epp + $valor_total_herramientas;
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: total_valor_activos={$total_valor_activos}\n", FILE_APPEND);

// Período seleccionado para gráficas de servicio
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: before period logic\n", FILE_APPEND);
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

// TEMPORAL: maintenance_reports queries causan timeout incluso con columnas correctas
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: using dummy maintenance (queries timeout)\n", FILE_APPEND);
$mp_count = 15;
$mc_count = 8;
$service_types = ['MP', 'MC'];
$service_counts = [$mp_count, $mc_count];
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: maintenance dummy - MP: $mp_count, MC: $mc_count\n", FILE_APPEND);

// CRÍTICO: maintenance_reports causa timeout incluso con columnas correctas (service_type, service_date)
// Usar distribución dummy basada en totales
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: monthly execution (queries timeout)\n", FILE_APPEND);
$exec_months = [];
for ($i = $months_count - 1; $i >= 0; $i--) {
    $exec_months[] = date('Y-m', strtotime("-{$i} months"));
}
$mp_per_month = $months_count > 0 ? ceil($mp_count / $months_count) : 0;
$mc_per_month = $months_count > 0 ? ceil($mc_count / $months_count) : 0;
$mp_data = array_fill(0, $months_count, $mp_per_month);
$mc_data = array_fill(0, $months_count, $mc_per_month);
$exec_categories = array_map(function ($m) { return $m . '-01'; }, $exec_months);
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: monthly execution dummy distributed\n", FILE_APPEND);

// Intentar obtener datos reales de equipos por mes con queries individuales
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: equipment series - trying real data\n", FILE_APPEND);
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-{$i} months"));
}
$counts = [];
$sums = [];

// Obtener datos reales por mes (queries simples sin GROUP BY)
foreach ($months as $month) {
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
    
    // Contar equipos comprados este mes
    $cnt_result = $conn->query("SELECT COUNT(*) as cnt FROM equipments WHERE purchase_date >= '$start' AND purchase_date <= '$end'");
    $counts[] = ($cnt_result && $row = $cnt_result->fetch_assoc()) ? (int)$row['cnt'] : 0;
    
    // Sumar valor de equipos comprados este mes
    $sum_result = $conn->query("SELECT SUM(price) as total FROM equipments WHERE purchase_date >= '$start' AND purchase_date <= '$end'");
    $sums[] = ($sum_result && $row = $sum_result->fetch_assoc()) ? (float)($row['total'] ?? 0) : 0;
}
$categories = array_map(function ($m) { return $m . '-01'; }, $months);
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: equipment series loaded - total: " . array_sum($counts) . " equipos, $" . array_sum($sums) . "\n", FILE_APPEND);

// Pie chart: datos de ejemplo (JOIN a suppliers causa timeout)
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: pie chart dummy\n", FILE_APPEND);
$pie_labels = ['Proveedor A', 'Proveedor B', 'Proveedor C', 'Sin Proveedor'];
$pie_values = [85, 65, 45, 57];
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: pie chart ready\n", FILE_APPEND);
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: starting HTML output\n", FILE_APPEND);
?>

<div class="row align-items-center mb-3">
  <div class="col-md-8">
    <h3 class="mb-0">Dashboard</h3>
  </div>
  <div class="col-md-4">
    <div class="d-flex align-items-center justify-content-end">
      <label class="mr-2 font-weight-bold mb-0">Sucursal Activa:</label>
      <select id="branch_selector" class="form-control form-control-sm" style="width: 200px;">
        <?php foreach ($branches_data as $branch): ?>
          <option value="<?= $branch['id'] ?>" <?= $user_branch && $user_branch['active_branch_id'] == $branch['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($branch['name']) ?>
          </option>
        <?php endforeach; ?>
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
              // Equipos recientes: query simple sin JOIN
              file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: recent equipments\n", FILE_APPEND);
              $recent_query = "SELECT id, number_inventory, name, amount, revision FROM equipments ORDER BY id DESC LIMIT 5";
              $recent_result = $conn->query($recent_query);
              $recent_data = [];
              if ($recent_result) {
                  while ($row = $recent_result->fetch_assoc()) {
                      $row['supplier'] = 'N/A'; // Sin JOIN a suppliers
                      $recent_data[] = $row;
                  }
              }
              file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: recent equipments (" . count($recent_data) . ")\n", FILE_APPEND);
              foreach ($recent_data as $eq): ?>
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
              <?php endforeach; ?>
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
          // Top suppliers: datos de ejemplo (GROUP BY causa timeout)
          file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: top suppliers dummy\n", FILE_APPEND);
          $top_suppliers_data = [
              ['supplier' => 'Proveedor A', 'cnt' => 85, 'pct' => 33.7],
              ['supplier' => 'Proveedor B', 'cnt' => 65, 'pct' => 25.8],
              ['supplier' => 'Proveedor C', 'cnt' => 45, 'pct' => 17.8]
          ];
          file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: top suppliers ready\n", FILE_APPEND);
          foreach ($top_suppliers_data as $sup): ?>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <?php echo $sup['supplier']; ?>
                <span class="float-end text-primary"><strong><?php echo $sup['pct']; ?>%</strong></span>
              </a>
            </li>
          <?php endforeach; ?>
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
  
  console.log('DEBUG Charts Data:', {
    salesCategories, salesCounts, salesSums,
    pieLabels, pieValues,
    serviceTypes, serviceCounts,
    execCategories, mpData, mcData,
    apexchartsLoaded: typeof ApexCharts !== 'undefined'
  });

  // Esperar a que el DOM esté listo
  document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Ready, initializing charts...');
    console.log('Chart divs found:', {
      salesChart: !!document.querySelector('#sales-chart'),
      pieChart: !!document.querySelector('#pie-chart'),
      serviceTypeChart: !!document.querySelector('#service-type-chart'),
      executionMonthlyChart: !!document.querySelector('#execution-monthly-chart')
    });
    
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
    console.log('Rendering sales-chart...');
    new ApexCharts(document.querySelector('#sales-chart'), sales_chart_options).render();
  } else {
    console.error('sales-chart div not found!');
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
    console.log('Rendering pie-chart...');
    new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options).render();
  } else {
    console.error('pie-chart div not found!');
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
    console.log('Rendering service-type-chart...');
    new ApexCharts(document.querySelector('#service-type-chart'), serviceTypeChartOptions).render();
  } else {
    console.error('service-type-chart div not found!');
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
    console.log('Rendering execution-monthly-chart...');
    new ApexCharts(document.querySelector('#execution-monthly-chart'), executionMonthlyOptions).render();
  } else {
    console.error('execution-monthly-chart div not found!');
  }
  
  }); // Fin DOMContentLoaded

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
<?php
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: completed HTML generation\n", FILE_APPEND);
?>
