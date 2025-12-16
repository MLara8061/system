<?php 
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/../../../../'));
}
require_once ROOT . '/config/config.php';
if(isset($_GET['id'])){
	$qry = $conn->query("SELECT * FROM `services` where id = '{$_GET['id']}'");
	foreach ($qry->fetch_array() as $key => $value) {
		if(!is_numeric($key))
			$$key = $value;
	}
}

?>
<style>
	/* Modern Modal Styles */
	.modal-content {
		border-radius: 16px;
		border: none;
		box-shadow: 0 8px 32px rgba(0,0,0,0.12);
	}
	
	.modal-header {
		border-bottom: 1px solid #f0f0f0;
		padding: 1.5rem 2rem;
	}
	
	.modal-body {
		padding: 2rem;
	}
	
	.form-group label {
		font-weight: 600;
		color: #2c3e50;
		font-size: 0.875rem;
		margin-bottom: 0.5rem;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	
	.form-control, .custom-select {
		border-radius: 8px;
		border: 2px solid #e8ecef;
		padding: 0.75rem 1rem;
		transition: all 0.3s ease;
		font-size: 0.95rem;
	}
	
	.form-control:focus, .custom-select:focus {
		border-color: #007bff;
		box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
	}
	
	/* Image preview */
	#cimg {
		max-width: 200px;
		max-height: 200px;
		object-fit: contain;
		border-radius: 8px;
	}
	
	/* Modal Footer Styles */
	.modal-footer {
		border-top: 1px solid #f0f0f0;
		padding: 1.5rem 2rem;
	}
	
	/* Fix Select2 rendering */
	.select2-container {
		width: 100% !important;
	}
	
	.select2-container .select2-selection--single {
		height: calc(2.5rem + 4px) !important;
		border-radius: 8px;
		border: 2px solid #e8ecef;
		display: flex;
		align-items: center;
	}
	
	.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
		line-height: normal !important;
		padding-left: 0.75rem !important;
		padding-top: 0 !important;
		display: flex;
		align-items: center;
	}
	
	.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
		height: calc(2.5rem + 4px) !important;
		display: flex;
		align-items: center;
	}
	
	@media (max-width: 576px) {
		.modal-body {
			padding: 1.5rem;
		}
	}
</style>
<div class="container-fluid">
	<form action="" id="manage-service">
		<input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? $_GET['id'] :'' ?>">
		
		<div class="form-group">
			<label for="category_id">Categoría</label>
			<select class="custom-select select2" name="category_id" id="category_id" required>
				<option value="">Selecciona una categoría</option>
				<?php 
				$category = $conn->query("SELECT * FROM `services_category` order by `category` asc ");
				while($row = $category->fetch_assoc()):
				?>
					<option value="<?php echo $row['id'] ?>" <?php echo isset($category_id) && $category_id == $row['id'] ? "selected" : "" ?>><?php echo $row['clave'] ?> - <?php echo $row['category'] ?></option>
				<?php endwhile; ?>
			</select>
		</div>
		
		<div class="form-group">
			<label for="service">Nombre del Servicio</label>
			<input type="text" class="form-control" name="service" id="service" value="<?php echo isset($service) ? $service : "" ?>" placeholder="Ej. Reparación de equipos" required>
		</div>
		
		<div class="form-group">
			<label for="description">Descripción</label>
			<textarea class="form-control" rows="4" name="description" id="description" placeholder="Describe el servicio..." required><?php echo isset($description) ? $description : "" ?></textarea>
		</div>
		
		<div class="form-group">
		<label for="customFile">Imagen del Servicio</label>
		<div class="custom-file">
			<input type="file" class="custom-file-input" id="customFile" name="img" onchange="displayImg(this)" accept="image/png,image/jpeg,image/jpg,image/webp">
			<label class="custom-file-label" for="customFile">Elegir archivo (PNG, JPG, WebP)</label>
		</div>
	</div>
	<div class="form-group d-flex justify-content-center">
		<img src="<?php echo (isset($img_path) && !empty($img_path) && file_exists($img_path)) ? $img_path : '' ?>" alt="Imagen" id="cimg" class="img-fluid img-thumbnail" style="max-width: 200px; max-height: 200px;">
		
	</form>
</div>
<script>
	function displayImg(input) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) {
				$('#cimg').attr('src', e.target.result);
			}
			reader.readAsDataURL(input.files[0]);
		}
	}

	$(document).ready(function(){
		// Initialize Select2
		setTimeout(function() {
			$('.select2').select2({
				dropdownParent: $('#uni_modal'),
				theme: 'bootstrap4',
				placeholder: 'Selecciona una categoría',
				width: '100%'
			});
		}, 100);
		
		// Update custom file input label
		$('#customFile').on('change', function() {
			var fileName = $(this).val().split('\\').pop();
			$(this).next('.custom-file-label').html(fileName || 'Elegir archivo');
		});
		
		// Remove error styling on input
		$('#service').on('keypress', function() {
			$(this).removeClass('border-danger');
		});
		
		// Form submission
		$('#manage-service').submit(function(e){
			e.preventDefault();
			
			// Remove previous error messages
			if($('.err_msg').length > 0){
				$('.err_msg').remove();
			}
			
			// Show loading state on modal submit button
			const submitBtn = $('#uni_modal .modal-footer #submit');
			const originalText = submitBtn.html();
			submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...');
			
			$.ajax({
				url: "public/ajax/action.php?action=save_service",
				dataType: 'json',
				data: new FormData($(this)[0]),
				type: 'POST',
				method: 'POST',
				cache: false,
				contentType: false,
				processData: false,
				error: function(err) {
					console.log(err);
					alert_toast("Ha ocurrido un error al guardar", "error");
					submitBtn.prop('disabled', false).html(originalText);
				},
				success: function(resp) {
					if(!!resp.status && resp.status == 'success'){
						alert_toast("Servicio guardado exitosamente", "success");
						setTimeout(function() {
							$('#uni_modal').modal('hide');
							if (typeof load_data === 'function') {
								load_data();
							} else {
								location.reload();
							}
						}, 1500);
					} else if(!!resp.status && resp.status == 'duplicate'){
						$('#manage-service').prepend('<div class="alert alert-danger err_msg"><i class="fas fa-exclamation-triangle mr-2"></i>Este servicio ya existe en el sistema.</div>');
						$('#service').addClass('border-danger').focus();
						submitBtn.prop('disabled', false).html(originalText);
					} else {
						alert_toast("Ha ocurrido un error al guardar", "error");
						submitBtn.prop('disabled', false).html(originalText);
					}
				}
			});
		});
	});
</script>
