<?php define('ACCESS', true); require_once 'config/config.php'; ?>
<?php
// Consulta adaptada para soportar tickets públicos (sin customer_id)
$qry = $conn->query("SELECT t.*, 
    COALESCE(CONCAT(c.lastname,', ',c.firstname,' ',c.middlename), t.reporter_name, 'Cliente Público') as cname, 
    COALESCE(d.name, 'Sin Departamento') as dname,
    t.is_public,
    t.reporter_email,
    t.reporter_phone,
    t.ticket_number,
    e.name as equipment_name,
    e.number_inventory
FROM tickets t 
LEFT JOIN customers c ON c.id = t.customer_id 
LEFT JOIN departments d ON d.id = t.department_id
LEFT JOIN equipments e ON e.id = t.equipment_id
WHERE t.id = " . intval($_GET['id']))->fetch_array();

foreach ($qry as $k => $v) {
	$$k = $v;
}

// Variables adicionales para tickets públicos
$is_public_ticket = isset($is_public) && $is_public == 1;
?>
<style>
	.d-list {
		display: list-item;
	}
	.comment-card {
		border-left: 3px solid #667eea;
	}
	
	/* Responsive */
	@media (max-width: 768px) {
		.card-body {
			padding: 1rem !important;
		}
		.card-header h4 {
			font-size: 1.1rem;
		}
		.btn-sm {
			font-size: 0.8rem;
			padding: 0.4rem 0.6rem;
		}
		.ml-3 {
			margin-left: 0.5rem !important;
		}
	}
	
	@media (max-width: 576px) {
		.btn-sm {
			width: 100%;
			margin-bottom: 0.5rem;
		}
		.update_status {
			margin-left: 0 !important;
			margin-top: 0.5rem;
		}
	}
</style>

<div class="container-fluid">
	<div class="row">
		<!-- Ticket Details -->
		<div class="col-md-8">
			<div class="card shadow-sm">
				<div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
					<h4 class="mb-0">
						<i class="fas fa-ticket-alt"></i> Detalles del Ticket
						<?php if ($is_public_ticket): ?>
							<span class="badge badge-warning ml-2"><i class="fas fa-qrcode"></i> Reporte Público</span>
						<?php endif; ?>
					</h4>
				</div>
				<div class="card-body">
					<?php if ($is_public_ticket): ?>
					<!-- Información adicional para tickets públicos -->
					<div class="alert alert-info mb-3">
						<h6 class="mb-2"><i class="fas fa-info-circle"></i> Ticket Público</h6>
						<p class="mb-1"><strong>N° Ticket:</strong> <?php echo htmlspecialchars($ticket_number ?? 'N/A'); ?></p>
						<?php if (!empty($equipment_name)): ?>
						<p class="mb-1"><strong>Equipo:</strong> <?php echo htmlspecialchars($equipment_name); ?> 
							<?php if (!empty($number_inventory)): ?>
								(#<?php echo htmlspecialchars($number_inventory); ?>)
							<?php endif; ?>
						</p>
						<?php endif; ?>
						<p class="mb-1"><strong>Reportado por:</strong> <?php echo htmlspecialchars($cname); ?></p>
						<?php if (!empty($reporter_email)): ?>
						<p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($reporter_email); ?></p>
						<?php endif; ?>
						<?php if (!empty($reporter_phone)): ?>
						<p class="mb-0"><strong>Teléfono:</strong> <?php echo htmlspecialchars($reporter_phone); ?></p>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-6">
								<label for="" class="control-label font-weight-bold">
									<i class="fas fa-tag text-primary"></i> Asunto
								</label>
								<p class="ml-3 mb-3"><?php echo $subject ?></p>
								
								<label for="" class="control-label font-weight-bold">
									<i class="fas fa-user text-primary"></i> Cliente
								</label>
								<p class="ml-3 mb-3"><?php echo $cname ?></p>
							</div>
							<div class="col-md-6">
								<label for="" class="control-label font-weight-bold">
									<i class="fas fa-info-circle text-primary"></i> Estado
								</label>
								<p class="ml-3 mb-3">
									<?php if ($status == 0) : ?>
										<span class="badge badge-primary"><i class="fas fa-folder-open"></i> Abierto/Pendiente</span>
									<?php elseif ($status == 1) : ?>
										<span class="badge badge-info"><i class="fas fa-spinner"></i> En Proceso</span>
									<?php elseif ($status == 2) : ?>
										<span class="badge badge-success"><i class="fas fa-check-circle"></i> Finalizado</span>
									<?php else : ?>
										<span class="badge badge-secondary"><i class="fas fa-times-circle"></i> Cerrado</span>
									<?php endif; ?>
									<?php if ($_SESSION['login_type'] != 3) : ?>
										<button class="btn btn-sm btn-outline-primary update_status ml-2" data-id='<?php echo $id ?>'>
											<i class="fas fa-edit"></i> Actualizar
										</button>
									<?php endif; ?>
								</p>
								
								<label for="" class="control-label font-weight-bold">
									<i class="fas fa-building text-primary"></i> Departamento
								</label>
								<p class="ml-3 mb-3"><?php echo $dname ?></p>
							</div>
						</div>
						<hr>
						<div class="bg-light p-3 rounded">
							<label class="font-weight-bold"><i class="fas fa-align-left text-primary"></i> Descripción</label>
							<?php echo html_entity_decode($description) ?>
						</div>
						
						<div class="mt-3">
							<a href="./index.php?page=edit_ticket&id=<?php echo $id ?>" class="btn btn-primary btn-sm">
								<i class="fas fa-edit"></i> Editar Ticket
							</a>
							<a href="./index.php?page=ticket_list" class="btn btn-secondary btn-sm">
								<i class="fas fa-arrow-left"></i> Volver a la Lista
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Comments Section -->
		<div class="col-md-4">
			<div class="card shadow-sm">
				<div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
					<h4 class="mb-0"><i class="fas fa-comments"></i> Comentarios</h4>
				</div>
				<div class="card-body p-0 py-2" style="max-height: 600px; overflow-y: auto;">
					<div class="container-fluid">
						<?php
						$comments = $conn->query("SELECT * FROM comments where ticket_id = '$id' order by unix_timestamp(date_created) asc");
						if ($comments->num_rows > 0) :
							while ($row = $comments->fetch_assoc()) :
								if ($row['user_type'] == 1)
									$uname = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM users where id = {$row['user_id']}")->fetch_array()['name'];
								if ($row['user_type'] == 2)
									$uname = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM staff where id = {$row['user_id']}")->fetch_array()['name'];
								if ($row['user_type'] == 3)
									$uname = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM customers where id = {$row['user_id']}")->fetch_array()['name'];
						?>
							<div class="card comment-card mb-2 mx-2">
								<div class="card-header py-2" style="background-color: #f8f9fa;">
									<h6 class="card-title mb-0"><i class="fas fa-user-circle"></i> <?php echo ucwords($uname) ?></h6>
									<div class="card-tools">
										<small class="text-muted"><?php echo date("d/m/Y H:i", strtotime($row['date_created'])) ?></small>
										<?php if ($row['user_type'] == $_SESSION['login_type'] && $row['user_id'] == $_SESSION['login_id']) : ?>
											<div class="btn-group dropleft">
												<button type="button" class="btn btn-tool" data-toggle="dropdown">
													<i class="fas fa-ellipsis-v"></i>
												</button>
												<div class="dropdown-menu">
													<a class="dropdown-item edit_comment" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
														<i class="fas fa-edit text-primary"></i> Editar
													</a>
													<div class="dropdown-divider"></div>
													<a class="dropdown-item delete_comment" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
														<i class="fas fa-trash text-danger"></i> Eliminar
													</a>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>
								<div class="card-body py-2">
									<?php echo html_entity_decode($row['comment']) ?>
								</div>
							</div>
						<?php endwhile; 
						else : ?>
							<div class="text-center text-muted py-4">
								<i class="fas fa-comment-slash fa-3x mb-2"></i>
								<p>No hay comentarios aún</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="card-footer">
					<form action="" id="manage-comment">
						<div class="form-group mb-2">
							<input type="hidden" name="id" value="">
							<input type="hidden" name="ticket_id" value="<?php echo $id ?>">
							<label for="" class="control-label font-weight-bold">
								<i class="fas fa-comment-medical"></i> Nuevo Comentario
							</label>
							<textarea name="comment" id="" cols="30" rows="" class="form-control summernote2"></textarea>
						</div>
						<button class="btn btn-primary btn-sm float-right">
							<i class="fas fa-paper-plane"></i> Enviar
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	$(function() {
		$('.summernote2').summernote({
			height: 150,
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'italic', 'strikethrough', 'superscript', 'subscript', 'clear']],
				['fontsize', ['fontsize']],
				['para', ['ol', 'ul', 'paragraph', 'height']],
				['view', ['undo', 'redo']]
			]
		})

	})
	$('.edit_comment').click(function() {
		uni_modal("Editar Comentario", "manage_comment.php?id=" + $(this).attr('data-id'))
	})
	$('.update_status').click(function() {
		uni_modal("Actualizar estado del ticket", "manage_ticket.php?id=" + $(this).attr('data-id'))
	})
	$('#manage-comment').submit(function(e) {
		e.preventDefault()
		start_load()
		$.ajax({
			url: 'ajax.php?action=save_comment',
			data: new FormData($(this)[0]),
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			type: 'POST',
			success: function(resp) {
				if (resp == 1) {
					alert_toast('Comentario guardado correctamente', "success");
					setTimeout(function() {
						location.reload()
					}, 1500)
				}
			}
		})
	})
	$('.delete_comment').click(function() {
		_conf("¿Deseas eliminar este comentario?", "delete_comment", [$(this).attr('data-id')])
	})

	function delete_comment($id) {
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_comment',
			method: 'POST',
			data: {
				id: $id
			},
			success: function(resp) {
				if (resp == 1) {
					alert_toast("Comentario eliminado correctamente", 'success')
					setTimeout(function() {
						location.reload()
					}, 1500)

				}
			}
		})
	}
</script>