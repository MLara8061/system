<?php
require_once 'config/config.php';

// Cargar todos los reportes con los campos necesarios
$qry = $conn->query("
    SELECT 
        id, orden_servicio, nombre, numero_inv, tipo_servicio,
        fecha_servicio, hora_inicio, hora_fin
    FROM equipment_report_sistem 
    ORDER BY id DESC
");
$reports = [];
while ($row = $qry->fetch_assoc()) {
	$reports[] = $row;
}

$total_reports = count($reports);
$preventivos = 0;
$correctivos = 0;
$otros = 0;
foreach ($reports as $r) {
	$tipo = $r['tipo_servicio'] ?? '';
	if ($tipo === 'Preventivo') {
		$preventivos++;
	} elseif ($tipo === 'Correctivo') {
		$correctivos++;
	} else {
		$otros++;
	}
}
?>

<div class="container-fluid">
	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-clipboard-list fa-2x text-primary mr-3"></i>
					<div>
						<h6>Total Reportes</h6>
						<h4><?= (int)$total_reports ?></h4>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-tools fa-2x text-success mr-3"></i>
					<div>
						<h6>Preventivos</h6>
						<h4><?= (int)$preventivos ?></h4>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-exclamation-triangle fa-2x text-danger mr-3"></i>
					<div>
						<h6>Correctivos</h6>
						<h4><?= (int)$correctivos ?></h4>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-layer-group fa-2x text-secondary mr-3"></i>
					<div>
						<h6>Otros</h6>
						<h4><?= (int)$otros ?></h4>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
		<div class="card-header bg-white py-3">
			<h4 class="mb-0 font-weight-bold text-dark">Reportes de Sistemas</h4>
		</div>
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="table table-hover mb-0">
					<thead class="thead-light">
						<tr>
							<th>Orden de Servicio</th>
							<th>Equipo</th>
							<th>Tipo de Mantenimiento</th>
							<th>Fecha</th>
							<th>Hora</th>
							<th>Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($reports)): ?>
							<tr>
								<td colspan="6" class="text-center text-muted py-4">
									No hay reportes aún
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($reports as $r): ?>
								<tr>
									<!-- ORDEN DE SERVICIO -->
									<td>
										<strong class="text-primary"><?= htmlspecialchars($r['orden_servicio']) ?></strong>
									</td>

									<!-- EQUIPO -->
									<td><?= htmlspecialchars($r['nombre']) ?></td>

									<!-- TIPO DE MANTENIMIENTO -->
									<td>
										<?php
										$tipo = $r['tipo_servicio'] ?? '';
										$badge = match ($tipo) {
											'Correctivo'     => 'badge-danger',
											'Preventivo'     => 'badge-success',
											'Capacitacion'   => 'badge-info',
											'Operativo'      => 'badge-warning',
											'Programado'     => 'badge-primary',
											'Incidencias'    => 'badge-secondary',
											default          => 'badge-light'
										};
										?>
										<span class="badge <?= $badge ?>"><?= htmlspecialchars($tipo ?: '-') ?></span>
									</td>

									<!-- FECHA -->
									<td>
										<?php
										if ($r['fecha_servicio']) {
											$date = new DateTime($r['fecha_servicio']);
											echo $date->format('d/m/Y');
										} else {
											echo '<span class="text-muted">-</span>';
										}
										?>
									</td>

									<!-- HORA -->
									<td>
										<?php
										if ($r['hora_inicio'] && $r['hora_fin']) {
											echo substr($r['hora_inicio'], 0, 5) . ' - ' . substr($r['hora_fin'], 0, 5);
										} else {
											echo '<span class="text-muted">-</span>';
										}
										?>
									</td>

									<!-- ACCIONES -->
									<td>
										<a href="index.php?page=equipment_report_sistem_editar&id=<?= $r['id'] ?>"
											class="btn btn-sm btn-info" title="Ver">
											Ver
										</a>
										<button class="btn btn-sm btn-success print-report"
											data-id="<?= $r['id'] ?>" title="PDF">
											PDF
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<style>
	.table th {
		font-weight: 600;
	}

	.btn-sm {
		padding: 0.25rem 0.5rem;
		font-size: 0.85rem;
	}

	.badge {
		font-size: 0.75rem;
		padding: 0.35em 0.65em;
	}

	.print-report {
		font-size: 0.8rem;
	}
</style>

<script>
	$(document).on('click', '.print-report', function() {
		const id = $(this).data('id');
		window.open('equipment_report_sistem_pdf.php?id=' + id, '_blank');
	});
</script>