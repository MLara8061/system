<?php
if (!isset($conn)) {
	require_once 'config/config.php';
}

// Modo edición: cuando este archivo es incluido desde edit.php, ya vienen variables ($id, $reporter_name, etc.)
$is_edit = isset($id) && (int)$id > 0;

// Capturar parámetros del equipo si vienen desde el QR (solo como valores de prellenado)
$prefill_equipment_id = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;
$prefill_equipment_name = isset($_GET['equipment_name']) ? (string)$_GET['equipment_name'] : '';
$prefill_inventory = isset($_GET['inventory']) ? (string)$_GET['inventory'] : '';
$prefill_reporter_name = isset($_GET['reporter_name']) ? (string)$_GET['reporter_name'] : '';

// Respetar valores del ticket (si existen) y usar QR solo como fallback
$form_equipment_id = $is_edit ? (int)($equipment_id ?? 0) : $prefill_equipment_id;
$form_reporter_name = $is_edit ? (string)($reporter_name ?? '') : $prefill_reporter_name;

// Pre-cargar el asunto si viene del equipo
$default_subject = '';
$equipment_name_for_subject = trim((string)($prefill_equipment_name ?: ($equipment_name ?? '')));
$inventory_for_subject = trim((string)($prefill_inventory ?: ($inventory ?? '')));
if ($equipment_name_for_subject && $inventory_for_subject) {
    $default_subject = "Reporte de equipo: {$equipment_name_for_subject} (Inv: {$inventory_for_subject})";
}

// Obtener nombre/inventario del equipo por ID (útil en edición o cuando solo viene equipment_id)
$display_equipment_name = $equipment_name_for_subject;
$display_inventory = $inventory_for_subject;
if ($form_equipment_id > 0 && (!$display_equipment_name || !$display_inventory)) {
	$eqRes = $conn->query("SELECT name, number_inventory FROM equipments WHERE id = " . (int)$form_equipment_id . " LIMIT 1");
	if ($eqRes && $eqRes->num_rows > 0) {
		$eqRow = $eqRes->fetch_assoc();
		if (!$display_equipment_name && isset($eqRow['name'])) $display_equipment_name = (string)$eqRow['name'];
		if (!$display_inventory && isset($eqRow['number_inventory'])) $display_inventory = (string)$eqRow['number_inventory'];
	}
}

$page_title = $is_edit ? ('Editar Ticket #' . (int)$id) : 'Nuevo Ticket de Soporte';
$submit_label = $is_edit ? 'Guardar cambios' : 'Guardar Ticket';
$success_toast = $is_edit ? 'Cambios guardados correctamente' : 'Datos guardados correctamente';
$redirect_after_save = $is_edit ? ('index.php?page=view_ticket&id=' . (int)$id) : 'index.php?page=ticket_list';
?>
<div class="container-fluid ticket-form-wrap">
	<div class="col-lg-12">
		<div class="card shadow-sm">
			<div class="card-header bg-light text-primary border-bottom d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
				<h4 class="mb-0 font-weight-bold"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h4>
				<div class="d-flex align-items-center flex-wrap" style="gap:.5rem;">
					<a href="./<?php echo htmlspecialchars($is_edit ? ('index.php?page=view_ticket&id=' . (int)$id) : 'index.php?page=ticket_list', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">
						<i class="fas fa-times"></i> Cancelar
					</a>
					<button class="btn btn-primary btn-sm" type="submit" form="manage_ticket">
						<i class="fas fa-save"></i> <?php echo htmlspecialchars($submit_label, ENT_QUOTES, 'UTF-8'); ?>
					</button>
				</div>
			</div>
			<form action="" id="manage_ticket">
				<script>
					window.__ticketFormLoaded = true;
				</script>
				<div class="card-body">
					<?php if ($display_equipment_name): ?>
					<div class="alert alert-info border border-info">
						<i class="fas fa-info-circle mr-2"></i>
						<strong>Reportando equipo:</strong> <?php echo htmlspecialchars($display_equipment_name, ENT_QUOTES, 'UTF-8'); ?> 
						<?php if ($display_inventory): ?>
						- <strong>Inventario:</strong> #<?php echo htmlspecialchars($display_inventory, ENT_QUOTES, 'UTF-8'); ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>
					
					<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
					<input type="hidden" name="equipment_id" value="<?php echo (int)$form_equipment_id; ?>">
					
					<div class="row">
						<!-- NOMBRE DE QUIEN REPORTA -->
						<div class="col-md-6">
							<div class="form-group">
								<label for="reporter_name" class="control-label"><i class="fas fa-user-edit"></i> Nombre de quien reporta</label>
								<input type="text" name="reporter_name" class="form-control form-control-sm" required 
								       id="reporter_name"
								       value="<?php echo htmlspecialchars((string)$form_reporter_name, ENT_QUOTES, 'UTF-8'); ?>" 
								       placeholder="Ingrese su nombre completo">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="subject" class="control-label"><i class="fas fa-tag"></i> Asunto</label>
								<input type="text" name="subject" class="form-control form-control-sm" required 
								       id="subject"
								       value="<?php echo htmlspecialchars((string)(isset($subject) ? $subject : $default_subject), ENT_QUOTES, 'UTF-8'); ?>" 
								       placeholder="Ingrese el asunto del ticket">
							</div>
						</div>

						<?php if ($_SESSION['login_type'] != 3) : ?>
						<div class="col-md-6">
							<div class="form-group">
								<label for="customer_id" class="control-label"><i class="fas fa-user"></i> Cliente</label>
								<select name="customer_id" id="customer_id" class="custom-select custom-select-sm select2">
									<option value="">Seleccione un cliente</option>
									<?php
									$customersRes = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM customers order by concat(lastname,', ',firstname,' ',middlename) asc");
									if ($customersRes):
									while ($row = $customersRes->fetch_assoc()) :
									?>
										<option value="<?php echo $row['id'] ?>" <?php echo isset($customer_id) && $customer_id == $row['id'] ? "selected" : '' ?>><?php echo ucwords($row['name']) ?></option>
									<?php endwhile; else: ?>
										<option value="" disabled>No se pudieron cargar clientes</option>
									<?php endif; ?>
								</select>
							</div>
						</div>
						<?php endif; ?>

						<div class="col-md-6">
							<div class="form-group">
								<label for="department_id" class="control-label"><i class="fas fa-building"></i> Departamento</label>
								<select name="department_id" id="department_id" class="custom-select custom-select-sm select2">
									<option value="">Seleccione un departamento</option>
									<?php
									$departmentsRes = $conn->query("SELECT * FROM departments order by name asc");
									if ($departmentsRes):
									while ($row = $departmentsRes->fetch_assoc()) :
									?>
										<option value="<?php echo $row['id'] ?>" <?php echo isset($department_id) && $department_id == $row['id'] ? "selected" : '' ?>><?php echo ucwords($row['name']) ?></option>
									<?php endwhile; else: ?>
										<option value="" disabled>No se pudieron cargar departamentos</option>
									<?php endif; ?>
								</select>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="service_id" class="control-label"><i class="fas fa-tools"></i> Necesitas soporte en:</label>
								<select class="custom-select custom-select-sm select2" name="service_id" id="service_id" required>
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

									// Fallback: si no existe services_category o no hay filas
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

									if (count($serviceOptions) > 0):
										foreach ($serviceOptions as $opt):
											$optId = (int)($opt['id'] ?? 0);
											$optLabel = (string)($opt['label'] ?? '');
									?>
										<option value="<?php echo $optId; ?>" <?php echo (isset($service_id) && (int)$service_id === $optId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8'); ?></option>
									<?php
										endforeach;
									else:
									?>
										<option value="" disabled>No hay servicios configurados</option>
									<?php endif; ?>
								</select>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="description" class="control-label"><i class="fas fa-align-left"></i> Descripción</label>
								<textarea name="description" id="description" cols="30" rows="10" class="form-control summernote"></textarea>
								<script>
									// Pre-cargar descripción de forma segura (evita cortes del DOM por </script> o </textarea>)
									window.__ticket_description_html = <?php echo json_encode(isset($description) ? (string)$description : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
								</script>
							</div>
						</div>
					</div>

					<div class="bg-light border-top ticket-actions mt-3 pt-3">
						<div class="d-flex justify-content-end align-items-center flex-wrap" style="gap: .5rem;">
							<?php if (!$is_edit): ?>
								<button class="btn btn-secondary" type="reset">
									<i class="fas fa-redo"></i> Limpiar
								</button>
							<?php endif; ?>
							<a href="./<?php echo htmlspecialchars($is_edit ? ('index.php?page=view_ticket&id=' . (int)$id) : 'index.php?page=ticket_list', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">
								<i class="fas fa-times"></i> Cancelar
							</a>
							<button class="btn btn-primary" type="submit">
								<i class="fas fa-save"></i> <?php echo htmlspecialchars($submit_label, ENT_QUOTES, 'UTF-8'); ?>
							</button>
						</div>
					</div>

				</div>
			</form>
		</div>
	</div>
</div>

<style>
.ticket-form-wrap {
	/* Evita que el footer fijo de AdminLTE tape el final del formulario */
	padding-bottom: calc(var(--ticket-fixed-footer-offset, 0px) + 2.5rem);
}

.ticket-actions {
	position: sticky;
	bottom: var(--ticket-fixed-footer-offset, 0px);
	z-index: 1020;
}

@media (max-width: 768px) {
	.card-body {
		padding: 1rem !important;
	}
	.card-header h4 {
		font-size: 1.1rem;
	}
	.btn {
		margin-bottom: 0.5rem;
	}
	.alert {
		font-size: 0.9rem;
	}
}

@media (max-width: 576px) {
	.btn {
		width: 100%;
		margin-bottom: 0.5rem;
	}
	.form-group label {
		font-size: 0.9rem;
	}
}
</style>

<script>
	// Ajustar dinámicamente el offset del footer fijo (layout-footer-fixed)
	$(function() {
		var $footer = $('.main-footer');
		var h = ($footer.length ? ($footer.outerHeight() || 0) : 0);
		document.documentElement.style.setProperty('--ticket-fixed-footer-offset', h + 'px');
	});

	$('#manage_ticket').submit(function(e) {
		e.preventDefault()
		$('input').removeClass("border-danger")
		start_load()
		$('#msg').html('')
		$.ajax({
			url: 'public/ajax/action.php?action=save_ticket',
			data: new FormData($(this)[0]),
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			type: 'POST',
			success: function(resp) {
				end_load()
				if (resp == 1) {
					alert_toast(<?php echo json_encode($success_toast); ?>, "success");
					setTimeout(function() {
						location.replace(<?php echo json_encode($redirect_after_save); ?>)
					}, 750)
				} else {
					alert_toast('Error al guardar el ticket', "error");
				}
			},
			error: function(xhr, status, error) {
				end_load()
				console.error('Error AJAX:', error);
				alert_toast('Error de conexión al guardar el ticket', "error");
			}
		})
	})
</script>
