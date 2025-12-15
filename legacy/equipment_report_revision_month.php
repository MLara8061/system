<?php require_once 'config/config.php'; ?>

<?php
function getInforme($fecha_inicial, $fecha_final, $conn) {

    $branchAnd = function_exists('branch_sql') ? branch_sql('AND', 'e.branch_id', 'e') : '';

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
                    AND r.date_revision <= '$fecha_final'{$branchAnd}";

    // Ejecutar la consulta y manejar errores
    $qry = $conn->query($sql) or die("Error en la consulta: " . $conn->error);

    // Siempre devolvemos el objeto, aunque tenga 0 filas
    return $qry;
}

// Fechas por defecto
$fecha_inicial = isset($_GET['fecha_inicial']) ? $_GET['fecha_inicial'] : date('Y-m-01');
$fecha_final   = isset($_GET['fecha_final'])   ? $_GET['fecha_final']   : date('Y-m-t', strtotime($fecha_inicial));

$informe = getInforme($fecha_inicial, $fecha_final, $conn);

// Tarjetas resumen (conteos sin consumir el cursor principal)
$branchAndSummary = function_exists('branch_sql') ? branch_sql('AND', 'e.branch_id', 'e') : '';
$summary = ['total' => 0, 'equipos' => 0];
$sumSql = "SELECT COUNT(*) AS total, COUNT(DISTINCT e.id) AS equipos
                     FROM equipments e
                     JOIN equipment_delivery d ON d.equipment_id = e.id
                     INNER JOIN equipment_revision r ON r.equipment_id = e.id
                     WHERE r.date_revision >= '$fecha_inicial'
                         AND r.date_revision <= '$fecha_final'{$branchAndSummary}";
$sumRes = $conn->query($sumSql);
if ($sumRes && ($row = $sumRes->fetch_assoc())) {
        $summary['total'] = (int)($row['total'] ?? 0);
        $summary['equipos'] = (int)($row['equipos'] ?? 0);
}

// Validación segura
if ($informe->num_rows > 0) {
    $equipos = $informe;
} else {
    $equipos = false;
}
?>

<div class="col-lg-12">
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

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background:#fff;">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-clipboard-check fa-2x text-primary mr-3"></i>
                            <div>
                                <h6>Total Revisiones</h6>
                                <h4><?php echo (int)$summary['total']; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background:#fff;">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-desktop fa-2x text-success mr-3"></i>
                            <div>
                                <h6>Equipos Únicos</h6>
                                <h4><?php echo (int)$summary['equipos']; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background:#fff;">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-calendar-alt fa-2x text-info mr-3"></i>
                            <div>
                                <h6>Inicio</h6>
                                <h4><?php echo htmlspecialchars(date('d/m/Y', strtotime($fecha_inicial))); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background:#fff;">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-calendar-check fa-2x text-warning mr-3"></i>
                            <div>
                                <h6>Fin</h6>
                                <h4><?php echo htmlspecialchars(date('d/m/Y', strtotime($fecha_final))); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
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
