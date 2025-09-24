<?php
include 'db_connect.php';
$qry = $conn->query("SELECT * FROM equipments where id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_reception where equipment_id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_delivery where equipment_id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_safeguard where equipment_id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_control_documents where equipment_id = ".$_GET['id'])->fetch_array();
foreach($qry as $k => $v){
	$$k = $v;
}
//Seteamos el id de nuevo por toda las consultas anteriores;
$id=$_GET['id'];

?>
<div class="col-lg-12">
	<div class="card">
		<div class="card-body">
			<form action="" id="manage_customer">
				<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
				<input type="hidden" name="equipment_id" value="<?php echo isset($id) ? $id : '' ?>">
					<div class="row">
					<div class="col-md-12 border-right">
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Fecha Revision</label>
							<input  type="date" name="date_revision" class="form-control form-control-sm" required value="<?php echo isset($date_revision) ? date('Y-m-d',strtotime($date_revision)) : '' ?>">
						</div>

						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Nro Inventario </label>
							<input disabled="true" type="text" name="number_inventory" class="form-control form-control-sm solonumeros" required value="<?php echo isset($number_inventory) ? $number_inventory : '' ?>">
						</div>
						
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Serie </label>
							<input disabled="true"  type="text" name="serie" class="form-control form-control-sm alfanumerico" required value="<?php echo isset($serie) ? $serie : '' ?>">
						</div>

						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Equipo</label>
							<input disabled="true" type="text" name="name" class="form-control form-control-sm" required value="<?php echo isset($name) ? $name : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Marca</label>
							<input disabled="true" type="text" name="brand" class="form-control form-control-sm" value="<?php echo isset($brand) ? $brand : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Modelo</label>
							<input disabled="true" type="text" name="model" class="form-control form-control-sm" required value="<?php echo isset($model) ? $model : '' ?>">
						</div>
						
						<div class="form-group col-md-6 float-left">
							<label for="" class="control-label">Caracteristicas</label><br/>
							<textarea disabled="true" disabled="true" name="characteristics"  style="width: 100%; height: 80px"><?php echo $characteristics ?></textarea>
						</div>

						<div class="form-group  col-md-3 float-left">
							<label for="" class="control-label">Valor del Equipo</label>
							<input disabled="true" type="text" name="amount" class="form-control form-control-sm solonumeros" required value="<?php echo isset($amount) ? $amount : '' ?>">
						</div>
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Disciplina</label>
							<input disabled="true" type="text" name="discipline" class="form-control form-control-sm" required value="<?php echo isset($discipline) ? $discipline : '' ?>">
						</div>
						
						<div class="form-group col-md-3 float-left">
							<label for="" class="control-label">Tipo De Adquisición</label>
							<select disabled="true" name="acquisition_type" class="custom-select custom-select-sm select2">
								<option value="1">Compra</option>
							</select>
						</div>

						<div class="form-group col-md-3 float-left">
							<label class="control-label">Frecuencia</label>
							<select  name="frecuencia" class="custom-select custom-select-sm select2">
								<option value="7">Semanal</option>
								<option value="15">Quincenal</option>
								<option value="30">Mensual</option>
								<option value="60">BiMensual</option>
								<option value="90">Trimestral</option>
								<option value="180">Semestral</option>
								<option value="365">Anual</option>


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

	$('.solonumeros').on('input', function () { 
            this.value = this.value.replace(/[^0-9]/g,'');
    });
    
	$('.alfanumerico').on('input',function(e){
		var alfanumerico = /^[a-zA-Z0-9]+$/;
		this.value = this.value.replace(/[^a-zA-Z0-9]/g,'');

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

		var postData=new FormData($(this)[0]);
		/*
		$( ".file" ).each(function( index ) {
		  var file=$(this)[0].files[0];
		  var nameFile=$(this).attr('name');
		  postData.append(nameFile,file);
		});
		*/

	
		$.ajax({
			url: 'ajax.php?action=save_equipment_revision',
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