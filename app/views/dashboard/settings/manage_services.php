<?php 
// Version: 2024-12-16-v4 - Avatar style image upload
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
	
	/* Service Image Container - Estilo Avatar */
	.service-img-container {
		position: relative;
		display: inline-block;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	}
	
	.service-img-container:hover .service-image {
		transform: scale(1.02);
		box-shadow: 0 8px 16px rgba(0,0,0,0.15);
	}
	
	.service-image {
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 8px rgba(0,0,0,0.1);
	}

	/* Botón Cámara */
	.service-camera-btn {
		position: absolute;
		bottom: 5px;
		right: 5px;
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: 3px solid #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
		z-index: 2;
		margin: 0;
	}
	
	.service-camera-btn:hover {
		transform: scale(1.1) rotate(5deg);
		box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
		background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
	}
	
	.service-camera-btn i {
		color: #fff;
		font-size: 1rem;
		margin: 0;
	}

	/* Botón Eliminar */
	.service-delete-btn {
		position: absolute;
		top: 0;
		right: 0;
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
		border: 3px solid #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(238, 90, 111, 0.4);
		z-index: 2;
		padding: 0;
	}
	
	.service-delete-btn:hover {
		transform: scale(1.15) rotate(-5deg);
		box-shadow: 0 6px 16px rgba(238, 90, 111, 0.6);
		background: linear-gradient(135deg, #ee5a6f 0%, #ff6b6b 100%);
	}
	
	.service-delete-btn i {
		color: #fff;
		font-size: 0.8rem;
		margin: 0;
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
			<label for="category_id">Categor�a</label>
			<select class="custom-select select2" name="category_id" id="category_id" required>
				<option value="">Selecciona una categor�a</option>
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
		<label for="service-img-upload">Imagen del Servicio</label>
		<div class="service-img-container position-relative d-inline-block">
			<img id="service-img-preview" 
				 src="<?php echo (isset($img_path) && !empty($img_path) && file_exists($img_path)) ? $img_path : 'uploads/default.png' ?>" 
				 alt="Imagen del Servicio" 
				 class="img-fluid rounded-circle"
				 style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #e8ecef;">
			<label for="service-img-upload" class="service-camera-btn" title="Cambiar imagen">
				<i class="fas fa-camera"></i>
			</label>
			<button type="button" class="service-delete-btn" id="delete-service-img-btn" title="Eliminar imagen" 
					style="<?php echo (!isset($img_path) || empty($img_path) || !file_exists($img_path)) ? 'display: none;' : '' ?>">
				<i class="fas fa-times"></i>
			</button>
		</div>
		<input type="file" id="service-img-upload" name="img" class="d-none" accept="image/png,image/jpeg,image/jpg,image/webp">
		
	</form>
</div>
<script>
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
		
		// Upload de imagen
		$('#service-img-upload').on('change', function(e) {
			if (this.files && this.files[0]) {
				const file = this.files[0];
				
				// Validar tipo de archivo
				if (!file.type.match('image/(jpeg|jpg|png|webp)')) {
					alert_toast('Por favor selecciona una imagen v�lida (JPG, PNG, WebP)', 'warning');
					return;
				}
				
				// Validar tama�o (5MB m�x)
				if (file.size > 5 * 1024 * 1024) {
					alert_toast('La imagen debe ser menor a 5MB', 'warning');
					return;
				}
				
				const reader = new FileReader();
				reader.onload = function(e) {
					$('#service-img-preview').attr('src', e.target.result);
					
					// Mostrar bot�n eliminar si no existe
					if ($('#delete-service-img-btn').length === 0) {
						$('.service-img-container').append(
							'<button type="button" class="service-delete-btn" id="delete-service-img-btn" title="Eliminar imagen">' +
							'<i class="fas fa-trash-alt"></i></button>'
						);
					}
				};
				reader.readAsDataURL(file);
			}
		});
		
		// Eliminar imagen
		$(document).on('click', '#delete-service-img-btn', function() {
			$('#service-img-preview').attr('src', 'uploads/default.png');
			$('#service-img-upload').val('');
			$(this).remove();
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
