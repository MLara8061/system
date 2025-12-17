<?php
require_once 'config/config.php';

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ticketId <= 0) {
	?>
	<div class="container-fluid">
		<div class="alert alert-danger mb-0">ID de ticket inválido.</div>
	</div>
	<?php
	return;
}

$qryRes = $conn->query("SELECT * FROM tickets WHERE id = {$ticketId} LIMIT 1");
if (!$qryRes || $qryRes->num_rows === 0) {
	?>
	<div class="container-fluid">
		<div class="alert alert-warning mb-0">Ticket no encontrado.</div>
	</div>
	<?php
	return;
}

$ticket = $qryRes->fetch_assoc();
foreach ($ticket as $k => $v) {
	$$k = $v;
}

$page_title = 'Editar Ticket #' . (int)$ticketId;
$redirect_after_save = 'index.php?page=view_ticket&id=' . (int)$ticketId;

// El ticket guarda la descripción como entidades; para editar necesitamos HTML real.
$description_html = html_entity_decode((string)($description ?? ''), ENT_QUOTES);
?>

<div class="container-fluid">
	<div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
		<div class="card-body p-5">
			<div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: .75rem;">
				<h4 class="mb-0 font-weight-bold text-dark"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h4>
				<div>
					<a href="./<?php echo htmlspecialchars($redirect_after_save, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
						<i class="fas fa-times"></i> Cancelar
					</a>
					<button type="submit" form="manage_ticket_edit" class="btn btn-primary">
						<i class="fas fa-save"></i> Guardar cambios
					</button>
				</div>
			</div>

			<hr class="my-4">

			<form id="manage_ticket_edit" enctype="multipart/form-data">
				<input type="hidden" name="id" value="<?php echo (int)$ticketId; ?>">
				<input type="hidden" name="equipment_id" value="<?php echo isset($equipment_id) ? (int)$equipment_id : 0; ?>">

				<div class="row">
					<div class="col-lg-6">
						<div class="form-group">
							<label class="font-weight-bold text-dark"><i class="fas fa-user-edit"></i> Nombre de quien reporta</label>
							<input type="text" name="reporter_name" class="form-control" required value="<?php echo htmlspecialchars((string)($reporter_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						</div>

						<div class="form-group">
							<label class="font-weight-bold text-dark"><i class="fas fa-tag"></i> Asunto</label>
							<input type="text" name="subject" class="form-control" required value="<?php echo htmlspecialchars((string)($subject ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
						</div>

						<?php if (($_SESSION['login_type'] ?? 0) != 3) : ?>
						<div class="form-group">
							<label class="font-weight-bold text-dark"><i class="fas fa-user"></i> Cliente</label>
							<select name="customer_id" class="custom-select select2">
								<option value="">Seleccione un cliente</option>
								<?php
								$customersRes = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM customers order by concat(lastname,', ',firstname,' ',middlename) asc");
								if ($customersRes) :
									while ($row = $customersRes->fetch_assoc()) :
										$cid = (int)($row['id'] ?? 0);
										$sel = (isset($customer_id) && (int)$customer_id === $cid) ? 'selected' : '';
									?>
									<option value="<?php echo $cid; ?>" <?php echo $sel; ?>><?php echo ucwords($row['name']); ?></option>
									<?php endwhile; endif; ?>
							</select>
						</div>
						<?php endif; ?>
					</div>

					<div class="col-lg-6">
						<div class="form-group">
							<label class="font-weight-bold text-dark"><i class="fas fa-building"></i> Departamento</label>
							<select name="department_id" class="custom-select select2">
								<option value="">Seleccione un departamento</option>
								<?php
								$departmentsRes = $conn->query("SELECT * FROM departments order by name asc");
								if ($departmentsRes) :
									while ($row = $departmentsRes->fetch_assoc()) :
										$did = (int)($row['id'] ?? 0);
										$sel = (isset($department_id) && (int)$department_id === $did) ? 'selected' : '';
									?>
									<option value="<?php echo $did; ?>" <?php echo $sel; ?>><?php echo ucwords($row['name']); ?></option>
									<?php endwhile; endif; ?>
							</select>
						</div>

						<div class="form-group">
							<label class="font-weight-bold text-dark"><i class="fas fa-tools"></i> Necesitas soporte en:</label>
							<select class="custom-select select2" name="service_id" required>
								<option value="">Seleccione un servicio</option>
								<?php
								$serviceOptions = [];
								$servicesRes = @$conn->query("SELECT s.id, s.service, c.category, c.clave FROM `services` s INNER JOIN `services_category` c ON c.id = s.category_id ORDER BY s.`service` ASC");
								if ($servicesRes) {
									while ($row = $servicesRes->fetch_assoc()) {
										$serviceOptions[] = [
											'id' => (int)($row['id'] ?? 0),
											'label' => '[' . ($row['clave'] ?? '') . ' - ' . ($row['category'] ?? '') . '] - ' . ($row['service'] ?? '') . ' Servicio',
										];
									}
								}
								if (count($serviceOptions) === 0) {
									$fallbackRes = @$conn->query("SELECT id, service FROM `services` ORDER BY `service` ASC");
									if ($fallbackRes) {
										while ($row = $fallbackRes->fetch_assoc()) {
											$serviceOptions[] = [
												'id' => (int)($row['id'] ?? 0),
												'label' => (string)($row['service'] ?? ''),
											];
										}
									}
								}
								foreach ($serviceOptions as $opt) {
									$optId = (int)($opt['id'] ?? 0);
									$optLabel = (string)($opt['label'] ?? '');
									$sel = (isset($service_id) && (int)$service_id === $optId) ? 'selected' : '';
									echo '<option value="' . $optId . '" ' . $sel . '>' . htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8') . '</option>';
								}
								?>
							</select>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="font-weight-bold text-dark"><i class="fas fa-align-left"></i> Descripción</label>
					<textarea name="description" class="form-control summernote" rows="10" data-initial-html="<?php echo htmlspecialchars(json_encode($description_html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>"></textarea>
				</div>

				<hr class="my-4">
				<div class="text-center btn-container-mobile">
					<button type="submit" class="btn btn-primary btn-lg px-5">Actualizar Ticket</button>
					<a href="./<?php echo htmlspecialchars($redirect_after_save, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-lg px-5">Cancelar</a>
				</div>
			</form>
		</div>
	</div>
</div>

<style>
	.form-control, .custom-select {
		border-radius: 10px !important;
		box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	}
	.select2-container--default .select2-selection--single {
		border-radius: 10px !important;
		height: 38px;
		line-height: 36px;
	}
	.select2-container--default .select2-selection--single .select2-selection__arrow {
		height: 36px;
	}
</style>

<script>
	$(function() {
		// Select2 (por consistencia con proveedores)
		$('.select2').select2({
			width: '100%',
			placeholder: 'Seleccionar',
			allowClear: false
		});

		// Summernote + cargar contenido inicial seguro
		$('.summernote').summernote({
			height: 220,
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'italic', 'strikethrough', 'superscript', 'subscript', 'clear']],
				['fontsize', ['fontsize']],
				['para', ['ol', 'ul', 'paragraph', 'height']],
				['view', ['undo', 'redo']]
			]
		});

		var $desc = $('.summernote').first();
		var raw = $desc.attr('data-initial-html');
		if (raw) {
			try {
				var html = JSON.parse(raw);
				$desc.summernote('code', html || '');
			} catch (e) {
				// No-op
			}
		}

		$('#manage_ticket_edit').off('submit.ticketEdit').on('submit.ticketEdit', function(e) {
			e.preventDefault();
			start_load();
			$.ajax({
				url: 'public/ajax/action.php?action=save_ticket',
				data: new FormData(this),
				cache: false,
				contentType: false,
				processData: false,
				method: 'POST',
				success: function(resp) {
					end_load();
					resp = (resp || '').toString().trim();
					if (resp == '1') {
						alert_toast('Cambios guardados correctamente', 'success');
						setTimeout(function(){
							location.replace(<?php echo json_encode($redirect_after_save); ?>);
						}, 750);
					} else {
						alert_toast('Error al guardar el ticket', 'error');
					}
				},
				error: function() {
					end_load();
					alert_toast('Error de conexión al guardar el ticket', 'error');
				}
			});
		});
	});
</script>