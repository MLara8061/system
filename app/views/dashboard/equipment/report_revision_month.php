<?php require_once 'config/config.php'; ?>

<?php
function getInforme($fecha_inicial, $fecha_final, $conn) {

    $sql = "SELECT
        r.id AS revision_id,
        e.id AS equipo_id,
        e.name,
        e.brand,
        e.model,
        e.serie,
        e.number_inventory AS inventario,
        r.date_revision AS date_revision
        FROM equipments e 
        JOIN equipment_delivery d ON d.equipment_id = e.id
        INNER JOIN equipment_revision r ON r.equipment_id = e.id
        WHERE r.date_revision >= '$fecha_inicial' 
          AND r.date_revision <= '$fecha_final'";

    // Ejecutar la consulta y manejar errores
    $qry = $conn->query($sql) or die("Error en la consulta: " . $conn->error);

    // Siempre devolvemos el objeto, aunque tenga 0 filas
    return $qry;
}

// Fechas por defecto
$fecha_inicial = isset($_GET['fecha_inicial']) ? $_GET['fecha_inicial'] : date('Y-m-01');
$fecha_final   = isset($_GET['fecha_final'])   ? $_GET['fecha_final']   : date('Y-m-t', strtotime($fecha_inicial));

$informe = getInforme($fecha_inicial, $fecha_final, $conn);

// Resumen (conteos sin consumir el resultset)
$summary = [
    'total_revisiones' => 0,
    'equipos_distintos' => 0,
];
try {
    $branch_and_e = function_exists('branch_sql') ? branch_sql('AND', 'branch_id', 'e') : '';
    $summary_sql = "SELECT COUNT(*) AS total_revisiones, COUNT(DISTINCT e.id) AS equipos_distintos
        FROM equipments e
        JOIN equipment_delivery d ON d.equipment_id = e.id
        INNER JOIN equipment_revision r ON r.equipment_id = e.id
        WHERE r.date_revision >= '" . $conn->real_escape_string($fecha_inicial) . "'
          AND r.date_revision <= '" . $conn->real_escape_string($fecha_final) . "'";
    if (!empty($branch_and_e)) {
        $summary_sql .= $branch_and_e;
    }
    $summary_q = $conn->query($summary_sql);
    if ($summary_q) {
        $summary_row = $summary_q->fetch_assoc();
        $summary['total_revisiones'] = (int)($summary_row['total_revisiones'] ?? 0);
        $summary['equipos_distintos'] = (int)($summary_row['equipos_distintos'] ?? 0);
    }
} catch (Throwable $e) {
    // no-op
}

// Validación segura
if ($informe->num_rows > 0) {
    $equipos = $informe;
} else {
    $equipos = false;
}
?>

<div class="col-lg-12">

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-clipboard-check fa-2x text-primary mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Total Revisiones</h6>
                        <h4 class="mb-0"><?= (int)$summary['total_revisiones'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-desktop fa-2x text-success mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Equipos Distintos</h6>
                        <h4 class="mb-0"><?= (int)$summary['equipos_distintos'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-calendar-alt fa-2x text-info mr-3"></i>
                    <div>
                        <h6 class="mb-0 text-muted">Periodo</h6>
                        <h4 class="mb-0"><?php echo date('d/m/Y', strtotime($fecha_inicial)) . ' - ' . date('d/m/Y', strtotime($fecha_final)); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div>
                <form action="index.php?page=equipment_report_revision_month">
                    <input type="hidden" name="page" value="equipment_report_revision_month">
                    <div class="form-group col-md-3 float-left">
                        <label for="" class="control-label">Fecha Inicial</label>
                        <input type="date" name="fecha_inicial" class="form-control form-control-sm" required 
                               value="<?php echo isset($fecha_inicial) ? date('Y-m-d', strtotime($fecha_inicial)) : '' ?>">
                    </div>

                    <div class="form-group col-md-3 float-left">
                        <label for="" class="control-label">Fecha Final</label>
                        <input type="date" name="fecha_final" class="form-control form-control-sm" required 
                               value="<?php echo isset($fecha_final) ? date('Y-m-d', strtotime($fecha_final)) : '' ?>">
                    </div>

                    <div class="form-group col-md-3 float-left">
                        <label>&nbsp;</label><br/>
                        <button type="submit" class="btn btn-success">Enviar</button>
                    </div>
                </form>
            </div>

            <table class="table tabe-hover table-bordered" id="list">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Equipo</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Nro Inventario</th>
                        <th>Fecha</th>
                        <th>Reporte</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($equipos && $row = $equipos->fetch_object()):
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $row->equipo_id ?></td>
                            <td><?php echo $row->name ?></td>
                            <td><?php echo $row->brand ?></td>
                            <td><?php echo $row->model ?></td>
                            <td><?php echo $row->serie ?></td>
                            <td><?php echo $row->inventario ?></td>
                            <td><?php echo $row->date_revision ?></td>
                            <td><?php echo $row->revision_id ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Aquí puedes inicializar DataTables u otras funciones JS si es necesario
    });
</script>
