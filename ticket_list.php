<?php require_once 'config/config.php'; ?>
<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card shadow-sm">
			<div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
				<h4 class="mb-0"><i class="fas fa-ticket-alt"></i> Tickets de Soporte Técnico</h4>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<a href="./index.php?page=new_ticket" class="btn btn-primary btn-sm">
						<i class="fas fa-plus"></i> Nuevo Ticket
					</a>
				</div>
				
				<div class="table-responsive">
				<table class="table table-hover table-bordered table-sm" id="list">
					<colgroup>
						<col width="5%">
						<col width="12%">
						<col width="18%">
						<col width="18%">
						<col width="25%">
						<col width="12%">
						<col width="10%">
					</colgroup>
					<thead style="background-color: #f8f9fa;">
						<tr>
							<th class="text-center">#</th>
							<th>Fecha Creación</th>
							<th>Cliente</th>
							<th>Asunto</th>
							<th>Descripción</th>
							<th class="text-center">Estado</th>
							<th class="text-center">Acción</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$where = '';
						if ($_SESSION['login_type'] == 2)
							$where .= " where t.department_id = {$_SESSION['login_department_id']} ";
						if ($_SESSION['login_type'] == 3)
							$where .= " where t.customer_id = {$_SESSION['login_id']} ";
						$qry = $conn->query("SELECT t.*,concat(c.lastname,', ',c.firstname,' ',c.middlename) as cname FROM tickets t inner join customers c on c.id= t.customer_id $where order by unix_timestamp(t.date_created) desc");
						while ($row = $qry->fetch_assoc()) :
							$trans = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
							unset($trans["\""], $trans["<"], $trans[">"], $trans["<h2"]);
							$desc = strtr(html_entity_decode($row['description']), $trans);
							$desc = str_replace(array("<li>", "</li>"), array("", ", "), $desc);
						?>
							<tr>
								<td class="text-center"><?php echo $i++ ?></td>
								<td><?php echo date("d/m/Y", strtotime($row['date_created'])) ?></td>
								<td><?php echo ucwords($row['cname']) ?></td>
								<td><strong><?php echo $row['subject'] ?></strong></td>
								<td class="truncate"><?php echo strip_tags($desc) ?></td>
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
								<td class="text-center">
									<div class="btn-group">
										<button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
											<i class="fas fa-cog"></i>
										</button>
										<div class="dropdown-menu">
											<a class="dropdown-item" href="./index.php?page=view_ticket&id=<?php echo $row['id'] ?>">
												<i class="fas fa-eye text-info"></i> Ver
											</a>
											<a class="dropdown-item" href="./index.php?page=edit_ticket&id=<?php echo $row['id'] ?>">
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
			url: 'ajax.php?action=delete_ticket',
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