<?php
// Este archivo se incluye dentro de index.php; no debe declarar <html>/<body> ni recargar assets globales.
$traceFile = ROOT . '/home_trace.log';
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: start\n", FILE_APPEND);

$user_id = $_SESSION['login_id'] ?? 0;
$user_branch = null;
if ($user_id) {
    // Sin JOIN a branches (causa timeout)
    $user_query = $conn->query("SELECT * FROM users WHERE id = {$user_id}");
    $user_branch = $user_query ? $user_query->fetch_assoc() : null;
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . '] HOME LOAD: fetched user_branch: ' . json_encode($user_branch) . "\n", FILE_APPEND);
}

file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: fetching branches\n", FILE_APPEND);
$branches_data = [];
$branches_result = $conn->query("SELECT id, name FROM branches ORDER BY id ASC");
if ($branches_result) {
  while ($row = $branches_result->fetch_assoc()) {
    $branches_data[] = ['id' => (int)$row['id'], 'name' => $row['name']];
  }
}
if (empty($branches_data)) {
  // Fallback si la tabla está vacía o falla el query
  $branches_data = [['id' => 1, 'name' => 'Sede Principal']];
}
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: branches loaded (" . count($branches_data) . ")\n", FILE_APPEND);

// TEMPORAL: Desactivar filtro de sucursal hasta verificar que branch_id existe en todas las tablas
$active_branch_id = (int)($user_branch['active_branch_id'] ?? 0);
$branch_where = $active_branch_id ? " WHERE branch_id = {$active_branch_id}" : '';
$branch_and = $active_branch_id ? " AND branch_id = {$active_branch_id}" : '';
$equip_alias_where = $active_branch_id ? " WHERE e.branch_id = {$active_branch_id}" : '';
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: branch_filter enabled (active_branch_id={$active_branch_id})\n", FILE_APPEND);

$total_equipos = 0;
// Simplificado: sin JOIN que causa timeout
$result = $conn->query("SELECT COUNT(*) AS total FROM equipments{$branch_where}");
if ($result && ($row = $result->fetch_assoc())) {
    $total_equipos = (int)($row['total'] ?? 0);
    file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: total_equipos={$total_equipos}\n", FILE_APPEND);
}

// Query optimizada: solo COUNT sin JOINs
$result_accesorios = $conn->query("SELECT COUNT(*) as total FROM accessories{$branch_where}");
$total_epp = ($result_accesorios && $row = $result_accesorios->fetch_assoc()) ? $row['total'] : 0;

$result_herramientas = $conn->query("SELECT COUNT(*) as total FROM tools{$branch_where}");
$total_herramientas = ($result_herramientas && $row = $result_herramientas->fetch_assoc()) ? $row['total'] : 0;
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: EPP={$total_epp}, Herramientas={$total_herramientas}\n", FILE_APPEND);

$valor_total_equipos = 0;
// Simplificado: sin JOIN que causa timeout
$result = $conn->query("SELECT SUM(amount) AS total FROM equipments{$branch_where}");
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

// maintenance_reports: datos reales (service_type, service_date, branch_id)
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: maintenance_reports real queries\n", FILE_APPEND);

$mp_count = 0;
$mc_count = 0;
$service_types = ['MP', 'MC'];

$mp_res = $conn->query("SELECT COUNT(*) AS count FROM maintenance_reports WHERE service_type='MP' AND service_date >= '{$start_service}'{$branch_and}");
$mc_res = $conn->query("SELECT COUNT(*) AS count FROM maintenance_reports WHERE service_type='MC' AND service_date >= '{$start_service}'{$branch_and}");

if ($mp_res && ($row = $mp_res->fetch_assoc())) $mp_count = (int)($row['count'] ?? 0);
if ($mc_res && ($row = $mc_res->fetch_assoc())) $mc_count = (int)($row['count'] ?? 0);

$service_counts = [$mp_count, $mc_count];
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: maintenance real - MP: {$mp_count}, MC: {$mc_count}\n", FILE_APPEND);

// Monthly execution: queries individuales (evita GROUP BY)
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: monthly execution (real)\n", FILE_APPEND);
$exec_months = [];
for ($i = $months_count - 1; $i >= 0; $i--) {
  $exec_months[] = date('Y-m', strtotime("-{$i} months"));
}

$mp_data = [];
$mc_data = [];
foreach ($exec_months as $month) {
  $start_date = $month . '-01';
  $end_date = date('Y-m-t', strtotime($start_date));

  $mp_q = "SELECT COUNT(*) AS count FROM maintenance_reports WHERE service_type='MP' AND service_date >= '{$start_date}' AND service_date <= '{$end_date}'{$branch_and}";
  $mc_q = "SELECT COUNT(*) AS count FROM maintenance_reports WHERE service_type='MC' AND service_date >= '{$start_date}' AND service_date <= '{$end_date}'{$branch_and}";

  $mp_r = $conn->query($mp_q);
  $mc_r = $conn->query($mc_q);

  $mp_data[] = ($mp_r && ($row = $mp_r->fetch_assoc())) ? (int)($row['count'] ?? 0) : 0;
  $mc_data[] = ($mc_r && ($row = $mc_r->fetch_assoc())) ? (int)($row['count'] ?? 0) : 0;
}

$exec_categories = array_map(function ($m) { return $m . '-01'; }, $exec_months);
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: monthly execution real loaded\n", FILE_APPEND);

// Equipment series: datos reales (date_created + amount, con índice en date_created)
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: equipment series - real data\n", FILE_APPEND);
$months = [];
for ($i = 11; $i >= 0; $i--) {
  $months[] = date('Y-m', strtotime("-{$i} months"));
}

$counts = [];
$sums = [];
foreach ($months as $month) {
  $start = $month . '-01';
  $end = date('Y-m-t', strtotime($start));
  $start_dt = $start . ' 00:00:00';
  $end_dt = $end . ' 23:59:59';

  $cnt_q = "SELECT COUNT(*) AS cnt FROM equipments WHERE date_created >= '{$start_dt}' AND date_created <= '{$end_dt}'" . ($active_branch_id ? " AND branch_id = {$active_branch_id}" : "");
  $sum_q = "SELECT SUM(amount) AS total FROM equipments WHERE date_created >= '{$start_dt}' AND date_created <= '{$end_dt}'" . ($active_branch_id ? " AND branch_id = {$active_branch_id}" : "");

  $cnt_r = $conn->query($cnt_q);
  $sum_r = $conn->query($sum_q);

  $counts[] = ($cnt_r && ($row = $cnt_r->fetch_assoc())) ? (int)($row['cnt'] ?? 0) : 0;
  $sums[] = ($sum_r && ($row = $sum_r->fetch_assoc())) ? (float)($row['total'] ?? 0) : 0;
}

$categories = array_map(function ($m) { return $m . '-01'; }, $months);
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: equipment series real loaded\n", FILE_APPEND);

// Pie chart: datos de ejemplo (JOIN a suppliers causa timeout)
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: pie chart real\n", FILE_APPEND);
$pie_labels = [];
$pie_values = [];
$pie_query = "SELECT COALESCE(s.empresa, 'Sin Proveedor') AS supplier, COUNT(1) AS cnt\n"
  . "FROM equipments e\n"
  . "LEFT JOIN suppliers s ON e.supplier_id = s.id\n"
  . ($equip_alias_where ? $equip_alias_where . "\n" : "")
  . "GROUP BY supplier\n"
  . "ORDER BY cnt DESC\n"
  . "LIMIT 6";
$pie_result = $conn->query($pie_query);
if ($pie_result) {
  while ($row = $pie_result->fetch_assoc()) {
    $pie_labels[] = $row['supplier'];
    $pie_values[] = (int)($row['cnt'] ?? 0);
  }
}
if (empty($pie_labels)) {
  $pie_labels = ['Sin datos'];
  $pie_values = [0];
}
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: pie chart ready\n", FILE_APPEND);
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: starting HTML output\n", FILE_APPEND);
?>

<div class="row align-items-center mb-3">
  <div class="col-md-8">
    <h3 class="mb-0">Dashboard</h3>
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
        <div class="d-flex flex-wrap align-items-center">
          <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 <?= $period === '6m' ? 'active' : '' ?>" onclick="changePeriod('6m')">
            <i class="fas fa-calendar-alt"></i> 6 Meses
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 <?= $period === '12m' ? 'active' : '' ?>" onclick="changePeriod('12m')">
            <i class="fas fa-calendar"></i> 12 Meses
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 <?= $period === 'year' ? 'active' : '' ?>" onclick="changePeriod('year')">
            <i class="fas fa-calendar-check"></i> Este Año
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 <?= $period === 'all' ? 'active' : '' ?>" onclick="changePeriod('all')">
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
                // Equipos recientes: incluir proveedor
              file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: recent equipments\n", FILE_APPEND);
                $recent_query = "SELECT e.id, e.number_inventory, e.name, e.amount, e.revision, COALESCE(s.empresa, 'Sin Proveedor') AS supplier "
                  . "FROM equipments e "
                  . "LEFT JOIN suppliers s ON e.supplier_id = s.id "
                  . ($equip_alias_where ? $equip_alias_where . " " : "")
                  . "ORDER BY e.id DESC LIMIT 5";
              $recent_result = $conn->query($recent_query);
              $recent_data = [];
              if ($recent_result) {
                  while ($row = $recent_result->fetch_assoc()) {
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
            // Top suppliers: datos reales
            file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: top suppliers real\n", FILE_APPEND);
            $top_suppliers_data = [];
            $top_query = "SELECT COALESCE(s.empresa, 'Sin Proveedor') AS supplier, COUNT(1) AS cnt\n"
              . "FROM equipments e\n"
              . "LEFT JOIN suppliers s ON e.supplier_id = s.id\n"
              . ($equip_alias_where ? $equip_alias_where . "\n" : "")
              . "GROUP BY supplier\n"
              . "ORDER BY cnt DESC\n"
              . "LIMIT 3";
            $top_result = $conn->query($top_query);
            if ($top_result) {
              while ($row = $top_result->fetch_assoc()) {
                $cnt = (int)($row['cnt'] ?? 0);
                $pct = $total_equipos > 0 ? round(($cnt * 100.0) / $total_equipos, 1) : 0.0;
                $top_suppliers_data[] = [
                  'supplier' => $row['supplier'],
                  'cnt' => $cnt,
                  'pct' => $pct,
                ];
              }
            }
            if (empty($top_suppliers_data)) {
              $top_suppliers_data = [
                ['supplier' => 'Sin datos', 'cnt' => 0, 'pct' => 0.0],
              ];
            }
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

</script>
<?php
file_put_contents($traceFile, '[' . date('Y-m-d H:i:s') . "] HOME LOAD: completed HTML generation\n", FILE_APPEND);
?>
