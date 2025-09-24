<?php
include 'db_connect.php';
$qry = $conn->query("SELECT * FROM equipments where id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_reception where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_delivery where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_safeguard where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_control_documents where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
	$$k = $v;
}
//Seteamos el id de nuevo por toda las consultas anteriores;
$id = $_GET['id'];

?>
<div class="col-lg-12">
	<div class="card">
		<div class="card-body">
			<form action="" id="manage_customer">
				<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
				<div class="row">
					<div class="col-md-12 border-right">
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Nro Inventario </label>
							<input type="text" name="number_inventory" class="form-control form-control-sm" value="<?php echo $id ?>" readonly>
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Fecha Ingreso</label>
							<input type="date" name="date_created" class="form-control form-control-sm" required value="<?php echo isset($date_created) ? date('Y-m-d', strtotime($date_created)) : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Serie </label>
							<input type="text" name="serie" class="form-control form-control-sm alfanumerico" required value="<?php echo isset($serie) ? $serie : '' ?>">
						</div>

						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Equipo</label>
							<input type="text" name="name" class="form-control form-control-sm" required value="<?php echo isset($name) ? $name : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Marca</label>
							<input type="text" name="brand" class="form-control form-control-sm" value="<?php echo isset($brand) ? $brand : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Modelo</label>
							<input type="text" name="model" class="form-control form-control-sm" required value="<?php echo isset($model) ? $model : '' ?>">
						</div>

						<div class="form-group col-md-6 float-left">
							<label for="" class="control-label">Caracteristicas</label><br />
							<textarea name="characteristics" style="width: 100%; height: 80px"><?php echo $characteristics ?></textarea>
						</div>

						<div class="form-group  col-md-3 float-left">
							<label for="" class="control-label">Valor del Equipo</label>
							<input type="text" name="amount" class="form-control form-control-sm solonumeros" required value="<?php echo isset($amount) ? $amount : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Categoría</label>
							<input type="text" name="discipline" class="form-control form-control-sm" required value="<?php echo isset($discipline) ? $discipline : '' ?>">
						</div>

						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Tipo De Adquisición</label>
							<select name="acquisition_type" class="custom-select custom-select-sm select2">
								<?php
								$options = ['Compra', 'Renta', 'Comodato'];
								foreach ($options as $opt):
								?>
									<option value="<?php echo $opt ?>" <?php echo (isset($acquisition_type) && $acquisition_type == $opt) ? 'selected' : ''; ?>>
										<?php echo $opt; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>


						<div class="form-group col-md-3 float-left">
							<label class="control-label">Periodo De Manto</label>
							<select name="mandate_period" class="custom-select custom-select-sm select2">
								<option value="1">1</option>
								<option value="2">2</option>
							</select>

						</div>




					</div>
				</div>
				<hr />
				<div class="row">
					<div class="col-md-12">
						<b class="text-muted">Documentos Control</b>
						<div class="form-group col-md-12 float-left">
							<label class="control-label">Factura Nro</label>
							<input type="text" class="form-control form-control-sm alfanumerico" name="invoice" required value="<?php echo isset($invoice) ? $invoice : '' ?>">
							<small id="#msg"></small>
						</div>
						<div class="form-group col-md-4 float-left">
							<label class="control-label">Comodato</label>
							<?php if ($bailment_file): ?>
								/ <a href="<?php echo $bailment_file ?>" target="_blank">Ver Archivo Comodato</a>
							<?php endif ?>
							<input type="file" class="file form-control form-control-sm" name="bailment_file">
						</div>

						<div class="form-group col-md-4 float-left">
							<label class="control-label">Contrato M</label>
							<?php if ($contract_file): ?>
								/ <a href="<?php echo $contract_file ?>" target="_blank">Ver Archivo Contrato M</a>
							<?php endif ?>
							<input type="file" class="file form-control form-control-sm" name="contract_file">
						</div>

						<div class="form-group col-md-4 float-left">
							<label class="control-label">Manual Usuario</label>
							<?php if ($usermanual_file): ?>
								/ <a href="<?php echo $usermanual_file ?>" target="_blank">Ver Manual Usuario</a>
							<?php endif ?>
							<input type="file" class="file form-control form-control-sm" name="usermanual_file">
						</div>

						<div class="form-group col-md-4 float-left">
							<label class="control-label">Guia Rapida</label>
							<?php if ($fast_guide_file): ?>
								/ <a href="<?php echo $fast_guide_file ?>" target="_blank">Ver Archivo Guia Rapida</a>
							<?php endif ?>
							<input type="file" class="file form-control form-control-sm" name="fast_guide_file">
						</div>
						<div class="form-group col-md-4 float-left">
							<label class="control-label">Ficha Tecnica</label>
							<?php if ($datasheet_file): ?>
								/ <a href="<?php echo $datasheet_file ?>" target="_blank">Ver Archivo Ficha Tecnica</a>
							<?php endif ?>
							<input type="file" class="file form-control form-control-sm" name="datasheet_file">
						</div>
						<div class="form-group col-md-4 float-left">
							<label class="control-label">Man Servicios</label>
							<?php if ($servicemanual_file): ?>
								/ <a href="<?php echo $servicemanual_file ?>" target="_blank">Ver Archivo Man Servicios</a>
							<?php endif ?>
							<input type="file" class="form-control form-control-sm" name="servicemanual_file">
						</div>
					</div>

				</div>
				<hr />

				<div class="row">
					<div class="col-md-12 border-right">
						<b class="text-muted">Pruebas de Recepción de Equipo <?php echo $state; ?></b>
						<br /><br />
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Acepto</label>
							<input type="radio" name="state" value="1"
								<?php if ($state == 1): ?> checked="true" <?php endif; ?> />&nbsp;&nbsp;
							<label for="" class="control-label">Rechazo</label>
							<input type="radio" name="state" value="0"
								<?php if ($state == 0): ?> checked="true" <?php endif; ?> />
						</div>
						<div class="form-group col-md-9 float-left">
							<label for="" class="control-label">Notas</label><br />
							<textarea name="comments" style="width: 100%; height: 120px"><?php echo $comments ?></textarea>

						</div>

					</div>
				</div>
				<hr />

				<div class="row">
					<div class="col-md-12 border-right">
						<b class="text-muted">Entrega de Equipo al área de usuario</b>
						<br /><br />
						<div class="form-group col-md-4 float-left">
							<label class="control-label">Departamento</label>
							<select name="department_id" class="custom-select custom-select-sm select2">

								<option value="1" <?php if ($department_id == 1): ?> selected="selected" <?php endif; ?>>Sistemas</option>
								<option value="2" <?php if ($department_id == 2): ?> selected="selected" <?php endif; ?>>Ventas</option>
							</select>
						</div>

						<div class="form-group col-md-4 float-left">
							<label class="control-label">Ubicación</label>
							<select name="location_id" class="custom-select custom-select-sm select2">
								<option value="1" <?php if ($location_id == 1): ?> selected="selected" <?php endif; ?>>Ubicación 1</option>
								<option value="2" <?php if ($location_id == 2): ?> selected="selected" <?php endif; ?>>Ubicación 2</option>
							</select>
						</div>
						<div class="form-group col-md-4 float-left">
							<label class="control-label">Cargo Responsable:</label>
							<select name="responsible_position" class="custom-select custom-select-sm select2">
								<option value="1" <?php if ($responsible_position == 1): ?> selected="selected" <?php endif; ?>>Cargo 1</option>
								<option value="2" <?php if ($responsible_position == 2): ?> selected="selected" <?php endif; ?>>Cargo 2</option>
							</select>
						</div>
						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Nombre Responsable:</label><br />
							<input type="text" name="responsible_name" class="form-control form-control-sm" required value="<?php echo isset($responsible_name) ? $responsible_name : '' ?>">
						</div>




						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Fecha </label><br />
							<input type="date" name="date" class="form-control form-control-sm" required value="<?php echo isset($date) ? date('Y-m-d', strtotime($date)) : '' ?>">
						</div>


						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Fecha Capacitación </label><br />
							<input type="date" name="date_training" class="form-control form-control-sm" required value="<?php echo isset($date_training) ? date('Y-m-d', strtotime($date_training)) : '' ?>">
						</div>

					</div>
				</div>
				<hr />
				<div class="row">

					<div class="col-md-12 border-left">
						<b class="text-muted">Responsable Resguardo Equipo</b>
						<br /><br />

						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Razón Social</label><br />
							<input type="text" name="business_name" class="form-control form-control-sm" required value="<?php echo isset($business_name) ? $business_name : '' ?>">
						</div>
						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Teléfono</label><br />
							<input type="text" name="phone" class="form-control form-control-sm" required value="<?php echo isset($phone) ? $phone : '' ?>">
						</div>
						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Email</label><br />
							<input type="text" name="email" class="form-control form-control-sm" required value="<?php echo isset($email) ? $email : '' ?>">
						</div>
						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Tiempo de Garantia</label><br />
							<input type="number" name="warranty_time" class="form-control form-control-sm" required value="<?php echo isset($warranty_time) ? $warranty_time : '' ?>">
						</div>
						<div class="form-group col-md-4 float-left">
							<label for="" class="control-label">Fecha De Adquisición</label><br />
							<input type="date" name="date_adquisition" class="form-control form-control-sm" required value="<?php echo isset($date_adquisition) ? date('Y-m-d', strtotime($date_adquisition)) : '' ?>">
						</div>
						<div class="form-group col-md-4 float-left">
							<label class="control-label">Rfc</label>
							<select name="rfc_id" class="custom-select custom-select-sm select2">
								<option value="1" <?php if ($rfc_id == 1): ?> selected="selected" <?php endif; ?>>Rfc 1</option>
								<option value="2" <?php if ($rfc_id == 2): ?> selected="selected" <?php endif; ?>>Rfc 2</option>
							</select>
						</div>

					</div>
				</div>

				<hr>
				<div class="col-lg-12 text-right justify-content-center d-flex">
					<button class="btn btn-primary mr-2">Guardar</button>
					<button class="btn btn-secondary" type="reset">Resetear</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$('[name="password"],[name="cpass"]').keyup(function() {
		var pass = $('[name="password"]').val()
		var cpass = $('[name="cpass"]').val()
		if (cpass == '' || pass == '') {
			$('#pass_match').attr('data-status', '')
		} else {
			if (cpass == pass) {
				$('#pass_match').attr('data-status', '1').html('<i class="text-success">Las contraseñas coinciden</i>')
			} else {
				$('#pass_match').attr('data-status', '2').html('<i class="text-danger">Las contraseñas no coinciden</i>')
			}
		}
	})

	$('.solonumeros').on('input', function() {
		this.value = this.value.replace(/[^0-9]/g, '');
	});

	$('.alfanumerico').on('input', function(e) {
		var alfanumerico = /^[a-zA-Z0-9]+$/;
		this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');

	})

	function displayImg(input, _this) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function(e) {
				$('#cimg').attr('src', e.target.result);
			}

			reader.readAsDataURL(input.files[0]);
		}
	}
	$('#manage_customer').submit(function(e) {
		e.preventDefault()
		start_load();

		var postData = new FormData($(this)[0]);
		/*
		$( ".file" ).each(function( index ) {
		  var file=$(this)[0].files[0];
		  var nameFile=$(this).attr('name');
		  postData.append(nameFile,file);
		});
		*/


		$.ajax({
			url: 'ajax.php?action=save_equipment',
			data: postData,
			cache: false,
			dataType: 'text',
			contentType: false,
			processData: false,
			enctype: 'multipart/form-data',
			method: 'POST',
			type: 'POST',
			success: function(resp) {
				if (resp == 1) {
					end_load();
					alert_toast('Datos guardados correctamente', "success");
					setTimeout(function() {
						location.replace('index.php?page=equipment_list')
					}, 750)
				} else if (resp == 2) {
					$('#msg').html("<div class='alert alert-danger'>Error al tratar de guardar el equipo.</div>");
					$('[name="email"]').addClass("border-danger")
					end_load()
				}
			}
		})
	})
</script>