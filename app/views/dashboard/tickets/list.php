<?php require_once 'config/config.php'; ?>
<?php
$where = '';
if ($_SESSION['login_type'] == 2) {
	$where .= " where t.department_id = {$_SESSION['login_department_id']} ";
}
if ($_SESSION['login_type'] == 3) {
	$where .= " where t.customer_id = {$_SESSION['login_id']} ";
}

$ticketSummary = [
	'total' => 0,
	'abiertos' => 0,
	'en_proceso' => 0,
	'finalizados' => 0,
];

$sumRes = $conn->query("SELECT
	COUNT(*) AS total,
	SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS abiertos,
	SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS en_proceso,
	SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) AS finalizados
FROM tickets t {$where}");

// === AUDITORÍA: Debug si valores son 0 ===
if ($sumRes && ($row = $sumRes->fetch_assoc())) {
	$ticketSummary['total'] = (int)($row['total'] ?? 0);
	$ticketSummary['abiertos'] = (int)($row['abiertos'] ?? 0);
	$ticketSummary['en_proceso'] = (int)($row['en_proceso'] ?? 0);
	$ticketSummary['finalizados'] = (int)($row['finalizados'] ?? 0);
	
	// LOG si valores son 0 (para investigación)
	if ($ticketSummary['en_proceso'] == 0 && $ticketSummary['finalizados'] == 0) {
		error_log('[TICKET_DASHBOARD] WARNING: en_proceso=0, finalizados=0. WHERE=' . $where . ', login_type=' . $_SESSION['login_type']);
	}
}

// Mapas de prioridad y SLA
$priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Critica'];
$priorityClasses = ['low' => 'info', 'medium' => 'primary', 'high' => 'warning', 'critical' => 'danger'];
$priorityIcons = ['low' => 'arrow-down', 'medium' => 'minus', 'high' => 'arrow-up', 'critical' => 'exclamation-triangle'];
$slaHours = ['low' => 72, 'medium' => 48, 'high' => 24, 'critical' => 8];
?>
<div class="container-fluid">
	<div class="row mb-3">
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-clipboard-list fa-2x text-primary mr-3"></i>
					<div>
						<h6>Total</h6>
						<h4><?php echo (int)$ticketSummary['total']; ?></h4>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-folder-open fa-2x text-info mr-3"></i>
					<div>
						<h6>Abiertos</h6>
						<h4><?php echo (int)$ticketSummary['abiertos']; ?></h4>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-spinner fa-2x text-warning mr-3"></i>
					<div>
						<h6>En Proceso</h6>
						<h4><?php echo (int)$ticketSummary['en_proceso']; ?></h4>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card shadow-sm" style="background:#fff;">
				<div class="card-body d-flex align-items-center">
					<i class="fas fa-check-circle fa-2x text-success mr-3"></i>
					<div>
						<h6>Finalizados</h6>
						<h4><?php echo (int)$ticketSummary['finalizados']; ?></h4>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
	<div class="col-lg-12">
		<div class="card shadow-sm">
			<div class="card-header bg-light text-primary border-bottom">
				<h4 class="mb-0 font-weight-bold"><i class="fas fa-ticket-alt"></i> Tickets de Soporte Técnico</h4>
			</div>
			<div class="card-body">
				<div class="d-flex justify-content-end mb-3">
					<a href="./index.php?page=new_ticket" class="btn btn-primary btn-sm">
						<i class="fas fa-plus"></i> Nuevo Ticket
					</a>
				</div>
				
				<div class="table-responsive">
				<table class="table table-hover table-bordered table-sm" id="list">
					<colgroup>
						<col width="4%">
						<col width="9%">
						<col width="13%">
						<col width="16%">
						<col width="8%">
						<col width="6%">
						<col width="10%">
						<col width="10%">
						<col width="8%">
					</colgroup>
					<thead class="thead-light text-primary">
						<tr>
							<th class="text-center">#</th>
							<th>Fecha</th>
							<th>Reportado por</th>
							<th>Asunto</th>
							<th class="text-center">Prioridad</th>
							<th class="text-center">SLA</th>
							<th class="text-center">Estado</th>
							<th>Asignado a</th>
							<th class="text-center">Accion</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$qry = $conn->query("SELECT t.*, COALESCE(CONCAT(ua.firstname,' ',ua.lastname),'') as assigned_name 
							FROM tickets t LEFT JOIN users ua ON ua.id = t.assigned_to $where ORDER BY unix_timestamp(t.date_created) desc");
						while ($row = $qry->fetch_assoc()) :
							$pr = $row['priority'] ?? 'medium';
							$prLabel = $priorityLabels[$pr] ?? 'Media';
							$prClass = $priorityClasses[$pr] ?? 'primary';
							$prIcon = $priorityIcons[$pr] ?? 'minus';
							// SLA
							$elapsed = time() - strtotime($row['date_created']);
							$slaLimit = ($slaHours[$pr] ?? 48) * 3600;
							$slaPct = $elapsed / max($slaLimit, 1);
							if ($row['status'] >= 2) { $slaClass = 'success'; $slaIcon = 'check'; }
							elseif ($slaPct < 0.5) { $slaClass = 'success'; $slaIcon = 'check'; }
							elseif ($slaPct < 0.85) { $slaClass = 'warning'; $slaIcon = 'clock'; }
							else { $slaClass = 'danger'; $slaIcon = 'exclamation-circle'; }
						?>
							<tr>
								<td class="text-center"><?php echo $i++ ?></td>
								<td><?php echo date("d/m/Y", strtotime($row['date_created'])) ?></td>
								<td>
									<?php echo ucwords($row['reporter_name'] ?? 'N/A') ?>
									<?php if (isset($row['is_public']) && $row['is_public'] == 1): ?>
										<br><small class="badge badge-warning"><i class="fas fa-qrcode"></i> Publico</small>
									<?php endif; ?>
								</td>
								<td><strong><?php echo htmlspecialchars($row['subject'] ?? '') ?></strong></td>
								<td class="text-center">
									<span class="badge badge-<?php echo $prClass; ?>"><i class="fas fa-<?php echo $prIcon; ?>"></i> <?php echo $prLabel; ?></span>
								</td>
								<td class="text-center">
									<i class="fas fa-<?php echo $slaIcon; ?> text-<?php echo $slaClass; ?>" title="SLA"></i>
								</td>
								<td class="text-center">
									<?php if ($row['status'] == 0) : ?>
										<span class="badge badge-primary"><i class="fas fa-folder-open"></i> Abierto</span>
									<?php elseif ($row['status'] == 1) : ?>
										<span class="badge badge-info"><i class="fas fa-spinner"></i> En Proceso</span>
									<?php elseif ($row['status'] == 2) : ?>
										<span class="badge badge-success"><i class="fas fa-check-circle"></i> Finalizado</span>
									<?php else : ?>
										<span class="badge badge-secondary"><i class="fas fa-times-circle"></i> Cerrado</span>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($row['assigned_name'] ?? ''); ?></td>
								<td class="text-center">
									<div class="btn-group">
										<button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
											<i class="fas fa-cog"></i>
										</button>
										<div class="dropdown-menu">
											<a class="dropdown-item" href="./index.php?page=view_ticket&id=<?php echo $row['id'] ?>">
												<i class="fas fa-eye text-info"></i> Ver
											</a>
											<a class="dropdown-item" href="./index.php?page=edit_ticket&id=<?php echo (int)$row['id']; ?>">
												<i class="fas fa-edit text-primary"></i> Editar
											</a>
											<div class="dropdown-divider"></div>
											<a class="dropdown-item delete_ticket" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
												<i class="fas fa-trash text-danger"></i> Eliminar
											</a>
										</div>
									</div>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
				</div>
			</div>
		</div>
	</div>
	</div>
</div>

<style>
.truncate {
	max-width: 300px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* Responsive Improvements */
@media (max-width: 768px) {
	.card-body {
		padding: 1rem !important;
	}
	.table-responsive {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
	}
	#list {
		font-size: 0.85rem;
	}
	#list th,
	#list td {
		padding: 0.5rem !important;
		white-space: nowrap;
	}
	.truncate {
		max-width: 150px;
	}
	.btn-sm {
		padding: 0.25rem 0.5rem;
		font-size: 0.75rem;
	}
	.badge {
		font-size: 0.7rem;
	}
	.card-header h4 {
		font-size: 1.1rem;
	}
}

@media (max-width: 576px) {
	#list {
		font-size: 0.75rem;
	}
	.truncate {
		max-width: 100px;
	}
}
</style>
<script>
	$(document).ready(function() {
		$('#list').dataTable({
			responsive: true,
			scrollX: true,
			autoWidth: false,
			language: {
				url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
			}
		})
		$('.delete_ticket').click(function() {
			const ticketId = $(this).attr('data-id');
			confirm_toast(
				'¿Estás seguro de eliminar este ticket? Esta acción no se puede deshacer.',
				function() { delete_ticket(ticketId); }
			);
		})
	})

	function delete_ticket($id) {
		start_load()
		$.ajax({
			url: 'public/ajax/action.php?action=delete_ticket',
			method: 'POST',
			data: {
				id: $id
			},
			success: function(resp) {
				if (resp == 1) {
					alert_toast("Datos eliminados correctamente", 'success')
					setTimeout(function() {
						location.reload()
					}, 1500)

				}
			}
		})
	}
</script>
