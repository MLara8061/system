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
	
	/* Drag & Drop Image Upload - Modern Style */
	.image-upload-container {
		position: relative;
		margin-top: 0.5rem;
	}
	
	.image-drop-zone {
		border: 3px dashed #cbd5e0;
		border-radius: 16px;
		padding: 2.5rem 1.5rem;
		text-align: center;
		background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
	}
	
	.image-drop-zone:hover {
		border-color: #007bff;
		background: linear-gradient(135deg, #e7f3ff 0%, #ffffff 100%);
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(0,123,255,0.15);
	}
	
	.image-drop-zone.dragover {
		border-color: #28a745;
		background: linear-gradient(135deg, #e8f5e9 0%, #ffffff 100%);
		transform: scale(1.02);
	}
	
	.drop-icon {
		font-size: 3rem;
		color: #a0aec0;
		margin-bottom: 1rem;
		transition: all 0.3s ease;
	}
	
	.image-drop-zone:hover .drop-icon {
		color: #007bff;
		transform: scale(1.1);
	}
	
	.drop-text {
		color: #4a5568;
		font-size: 1rem;
		font-weight: 500;
		margin-bottom: 0.5rem;
	}
	
	.drop-subtext {
		color: #a0aec0;
		font-size: 0.875rem;
	}
	
	#service-img-input {
		display: none;
	}
	
	.image-preview-container {
		margin-top: 1.5rem;
		text-align: center;
		display: none;
	}
	
	.image-preview-container.active {
		display: block;
	}
	
	#service-img-preview {
		max-width: 100%;
		width: 180px;
		height: 180px;
		object-fit: cover;
		border-radius: 16px;
		box-shadow: 0 4px 16px rgba(0,0,0,0.1);
		border: 4px solid #ffffff;
	}
	
	.remove-image-btn {
		position: absolute;
		top: -10px;
		right: 50%;
		transform: translateX(calc(50% + 90px));
		background: #dc3545;
		color: white;
		border: none;
		width: 32px;
		height: 32px;
		border-radius: 50%;
		cursor: pointer;
		font-size: 1.2rem;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 2px 8px rgba(220,53,69,0.3);
		transition: all 0.2s ease;
	}
	
	.remove-image-btn:hover {
		background: #c82333;
		transform: translateX(calc(50% + 90px)) scale(1.1);
	}
	
	/* Button Styles */
	.btn-save-service {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: none;
		padding: 0.875rem 2.5rem;
		font-weight: 600;
		font-size: 0.95rem;
		border-radius: 10px;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(102,126,234,0.3);
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	
	.btn-save-service:hover {
		transform: translateY(-2px);
		box-shadow: 0 6px 20px rgba(102,126,234,0.4);
	}
	
	.btn-save-service:active {
		transform: translateY(0);
	}
	
	.btn-cancel-service {
		background: #ffffff;
		border: 2px solid #e2e8f0;
		color: #718096;
		padding: 0.875rem 2rem;
		font-weight: 600;
		font-size: 0.95rem;
		border-radius: 10px;
		transition: all 0.3s ease;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		margin-right: 1rem;
	}
	
	.btn-cancel-service:hover {
		background: #f7fafc;
		border-color: #cbd5e0;
		color: #4a5568;
		transform: translateY(-2px);
	}
	
	.modal-footer {
		border-top: 1px solid #f0f0f0;
		padding: 1.5rem 2rem;
		display: flex;
		justify-content: flex-end;
		gap: 1rem;
	}
	
	@media (max-width: 576px) {
		.modal-body {
			padding: 1.5rem;
		}
		
		.modal-footer {
			flex-direction: column-reverse;
		}
		
		.btn-save-service, .btn-cancel-service {
			width: 100%;
			margin: 0;
			margin-bottom: 0.5rem;
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
			<label>Imagen del Servicio</label>
			<div class="image-upload-container">
				<div class="image-drop-zone" id="service-drop-zone">
					<i class="fas fa-cloud-upload-alt drop-icon"></i>
					<div class="drop-text">Arrastra y suelta una imagen aquí</div>
					<div class="drop-subtext">o haz clic para seleccionar (PNG, JPG - Máx. 2MB)</div>
					<input type="file" id="service-img-input" name="img" accept="image/png,image/jpeg,image/jpg">
				</div>
				<div class="image-preview-container" id="service-preview-container">
					<button type="button" class="remove-image-btn" id="remove-service-img">
						<i class="fas fa-times"></i>
					</button>
					<img id="service-img-preview" src="<?php echo (isset($img_path) && !empty($img_path) && file_exists($img_path)) ? $img_path : '' ?>" alt="Preview">
				</div>
			</div>
		</div>
		
		<div class="modal-footer">
			<button type="button" class="btn btn-cancel-service" data-dismiss="modal">Cancelar</button>
			<button type="submit" class="btn btn-primary btn-save-service">
				<i class="fas fa-save mr-2"></i>Guardar Servicio
			</button>
		</div>
			
		
	</form>
</div>
<script>
	$(document).ready(function(){
		// Initialize Select2
		$('.select2').select2({
			theme: 'bootstrap4',
			placeholder: 'Selecciona una categoría',
			width: '100%'
		});
		
		// Drag & Drop Image Upload
		const dropZone = $('#service-drop-zone');
		const fileInput = $('#service-img-input');
		const previewContainer = $('#service-preview-container');
		const previewImg = $('#service-img-preview');
		const removeBtn = $('#remove-service-img');
		
		// Check if there's an existing image
		if (previewImg.attr('src') && previewImg.attr('src') !== '') {
			previewContainer.addClass('active');
			dropZone.hide();
		}
		
		// Click to select file
		dropZone.on('click', function() {
			fileInput.click();
		});
		
		// File input change
		fileInput.on('change', function(e) {
			handleFiles(this.files);
		});
		
		// Drag & Drop events
		dropZone.on('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).addClass('dragover');
		});
		
		dropZone.on('dragleave', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('dragover');
		});
		
		dropZone.on('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).removeClass('dragover');
			
			const files = e.originalEvent.dataTransfer.files;
			if (files.length > 0) {
				fileInput[0].files = files;
				handleFiles(files);
			}
		});
		
		// Handle file selection
		function handleFiles(files) {
			if (files && files[0]) {
				const file = files[0];
				
				// Validate file type
				if (!file.type.match('image/(png|jpeg|jpg)')) {
					alert_toast('Por favor selecciona una imagen PNG o JPG', 'warning');
					return;
				}
				
				// Validate file size (2MB max)
				if (file.size > 2 * 1024 * 1024) {
					alert_toast('La imagen debe ser menor a 2MB', 'warning');
					return;
				}
				
				const reader = new FileReader();
				reader.onload = function(e) {
					previewImg.attr('src', e.target.result);
					dropZone.fadeOut(300, function() {
						previewContainer.addClass('active').fadeIn(300);
					});
				};
				reader.readAsDataURL(file);
			}
		}
		
		// Remove image
		removeBtn.on('click', function() {
			fileInput.val('');
			previewImg.attr('src', '');
			previewContainer.removeClass('active').fadeOut(300, function() {
				dropZone.fadeIn(300);
			});
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
			
			// Show loading state
			const submitBtn = $('.btn-save-service');
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
							$('.modal').modal('hide');
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
