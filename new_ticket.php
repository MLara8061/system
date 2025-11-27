<?php
if (!isset($conn)) {
	require_once 'config/config.php';
}

// Capturar parámetros del equipo si vienen desde el QR
$equipment_id = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : '';
$equipment_name = isset($_GET['equipment_name']) ? htmlspecialchars($_GET['equipment_name']) : '';
$inventory = isset($_GET['inventory']) ? htmlspecialchars($_GET['inventory']) : '';
$reporter_name = isset($_GET['reporter_name']) ? htmlspecialchars($_GET['reporter_name']) : '';

// Pre-cargar el asunto si viene del equipo
$default_subject = '';
if ($equipment_name && $inventory) {
    $default_subject = "Reporte de equipo: $equipment_name (Inv: $inventory)";
}
?>
<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card shadow-sm">
			<div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
				<h4 class="mb-0"><i class="fas fa-ticket-alt"></i> Nuevo Ticket de Soporte</h4>
			</div>
			<div class="card-body">
				<?php if ($equipment_name): ?>
				<div class="alert alert-info">
					<i class="fas fa-info-circle me-2"></i>
					<strong>Reportando equipo:</strong> <?php echo $equipment_name; ?> 
					<?php if ($inventory): ?>
					- <strong>Inventario:</strong> #<?php echo $inventory; ?>
					<?php endif; ?>
				</div>
				<?php endif; ?>
				
				<form action="" id="manage_ticket">
					<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
					<input type="hidden" name="equipment_id" value="<?php echo $equipment_id ?>">
					
					<div class="row">
						<!-- NOMBRE DE QUIEN REPORTA -->
						<div class="col-md-6">
							<div class="form-group">
								<label for="" class="control-label"><i class="fas fa-user-edit"></i> Nombre de quien reporta</label>
								<input type="text" name="reporter_name" class="form-control form-control-sm" required 
								       value="<?php echo $reporter_name ?>" 
								       placeholder="Ingrese su nombre completo">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="" class="control-label"><i class="fas fa-tag"></i> Asunto</label>
								<input type="text" name="subject" class="form-control form-control-sm" required 
								       value="<?php echo isset($subject) ? $subject : $default_subject ?>" 
								       placeholder="Ingrese el asunto del ticket">
							</div>
						</div>

						<?php if ($_SESSION['login_type'] != 3) : ?>
						<div class="col-md-6">
							<div class="form-group">
								<label for="" class="control-label"><i class="fas fa-user"></i> Cliente</label>
								<select name="customer_id" id="customer_id" class="custom-select custom-select-sm select2">
									<option value="">Seleccione un cliente</option>
									<?php
									$department = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM customers order by concat(lastname,', ',firstname,' ',middlename) asc");
									while ($row = $department->fetch_assoc()) :
									?>
										<option value="<?php echo $row['id'] ?>" <?php echo isset($customer_id) && $customer_id == $row['id'] ? "selected" : '' ?>><?php echo ucwords($row['name']) ?></option>
									<?php endwhile; ?>
								</select>
							</div>
						</div>
						<?php endif; ?>

						<div class="col-md-6">
							<div class="form-group">
								<label for="" class="control-label"><i class="fas fa-building"></i> Departamento</label>
								<select name="department_id" id="department_id" class="custom-select custom-select-sm select2">
									<option value="">Seleccione un departamento</option>
									<?php
									$department = $conn->query("SELECT * FROM departments order by name asc");
									while ($row = $department->fetch_assoc()) :
									?>
										<option value="<?php echo $row['id'] ?>" <?php echo isset($department_id) && $department_id == $row['id'] ? "selected" : '' ?>><?php echo ucwords($row['name']) ?></option>
									<?php endwhile; ?>
								</select>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="service_id" class="control-label"><i class="fas fa-tools"></i> Necesitas soporte en:</label>
								<select class="custom-select custom-select-sm select2" name="service_id" id="service_id" required>
									<option value="">Seleccione un servicio</option>
									<?php 
									$service = $conn->query("SELECT s.*,c.category FROM `services` s inner join `services_category` c on c.id = s.category_id order by s.`service` asc");
									while($row = $service->fetch_assoc()):
									?>
										<option value="<?php echo $row['id'] ?>" <?php echo isset($service_id) && $service_id == $row['id'] ? "selected" : "" ?>>[<?php echo $row['category'] ?>] - <?php echo $row['service'] ?> Servicio</option>
									<?php endwhile; ?>
								</select>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label"><i class="fas fa-align-left"></i> Descripción</label>
								<textarea name="description" id="" cols="30" rows="10" class="form-control summernote"><?php echo isset($description) ? $description : '' ?></textarea>
							</div>
						</div>
					</div>

					<hr>
					<div class="row">
						<div class="col-lg-12 text-right">
							<button class="btn btn-primary" type="submit">
								<i class="fas fa-save"></i> Guardar Ticket
							</button>
							<button class="btn btn-secondary" type="reset">
								<i class="fas fa-redo"></i> Limpiar
							</button>
							<a href="./index.php?page=ticket_list" class="btn btn-default">
								<i class="fas fa-times"></i> Cancelar
							</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
	$('#manage_ticket').submit(function(e) {
		e.preventDefault()
		$('input').removeClass("border-danger")
		start_load()
		$('#msg').html('')
		$.ajax({
			url: 'ajax.php?action=save_ticket',
			data: new FormData($(this)[0]),
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			type: 'POST',
			success: function(resp) {
				if (resp == 1) {
					alert_toast('Datos guardados correctamente', "success");
					setTimeout(function() {
						location.replace('index.php?page=ticket_list')
					}, 750)
				}
			}
		})
	})
</script>