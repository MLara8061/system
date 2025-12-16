<?php 
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}
require_once ROOT . '/config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id > 0){
	$qry = $conn->query("SELECT * FROM `services` where id = {$id}");
	foreach ($qry->fetch_array() as $key => $value) {
		if(!is_numeric($key))
			$$key = $value;
	}
}

?>
<style>
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: cover;
		border-radius: 100% 100%;
	}
</style>
<div class="container-fluid">
	<form action="" id="manage-service" enctype="multipart/form-data">
		<input type="hidden" name="id" value="<?php echo $id > 0 ? $id : '' ?>">
		<div class="form-group">
			<label for="category_id" class="control-label">Categoría</label>
			<select class="custom-select custom-select-sm select2" name="category_id" id="category_id" required>
				<option value="" readonly></option>
				<?php 
				$category = $conn->query("SELECT * FROM `services_category` order by `category` asc ");
				while($row = $category->fetch_assoc()):
				?>
					<option value="<?php echo $row['id'] ?>" <?php echo isset($category_id) && $category_id == $row['id'] ? "selected" : "" ?>><?php echo $row['clave'] ?> - <?php echo $row['category'] ?></option>
				<?php endwhile; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="service" class="control-label">Servicios</label>
			<input type="text" class="form-control form-control-sm" name="service" id="service" value="<?php echo isset($service) ? $service : "" ?>" required>
		</div>
		<div class="form-group">
			<label for="description" class="control-label">Descripción</label>
			<textarea type="text" style="resize: none" class="form-control" rows="3" name="description" id="description"  required><?php echo isset($description) ? $description : "" ?></textarea>
		</div>
		<div class="form-group">
				<label for="customFile" class="control-label">Imagen de Servicio</label>
				<div class="custom-file">
	              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
	              <label class="custom-file-label" for="customFile">Escoger archivo</label>
	            </div>
			</div>
			<hr>
			<?php
				$__default_img = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23f0f0f0\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'18\' dy=\'10\' font-weight=\'bold\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3ESin imagen%3C/text%3E%3C/svg%3E';
				$__img_src = $__default_img;
				if (isset($img_path) && !empty($img_path)) {
					$img_path_trim = trim((string)$img_path);

					// Si ya es URL pública, usarla tal cual
					if (preg_match('/^https?:\/\//i', $img_path_trim)) {
						$__img_src = $img_path_trim;
					} else {
						// Normalizar separadores
							$img_path_norm = str_replace("\0", '', $img_path_trim);
						$img_path_norm = str_replace('\\', '/', $img_path_norm);

						// Si viene como ruta absoluta de Windows (C:/...), no es servible por el navegador.
						// En ese caso, intentamos extraer la parte relativa a ROOT.
						if (preg_match('/^[A-Za-z]:\//', $img_path_norm)) {
							$root_norm = str_replace('\\', '/', (string)ROOT);
							$root_norm = rtrim($root_norm, '/');
							if (stripos($img_path_norm, $root_norm . '/') === 0) {
								$img_path_norm = substr($img_path_norm, strlen($root_norm) + 1);
							} else {
								$img_path_norm = basename($img_path_norm);
							}
						}

						$__rel = ltrim($img_path_norm, '/');
						$__fs = rtrim(ROOT, '/\\') . '/' . $__rel;
						if (file_exists($__fs)) {
							$__img_src = $__rel;
						}
					}
				}
			?>
			<div class="form-group d-flex justify-content-center">
				<img src="<?php echo htmlspecialchars($__img_src, ENT_QUOTES, 'UTF-8'); ?>" alt="" id="cimg" class="img-fluid img-thumbnail" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($__default_img, ENT_QUOTES, 'UTF-8'); ?>';">
			</div>

			<?php if (empty($__IN_UNI_MODAL)): ?>
			<div class="col-lg-12 d-flex justify-content-end btn-container-mobile" id="service-form-actions">
				<button class="btn btn-secondary" type="button" id="btn-cancel-service">Cancelar</button>
				<button class="btn btn-primary ml-2" type="submit">Guardar</button>
			</div>
			<?php endif; ?>
			
		
	</form>
</div>
<script>
	function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
	$(document).ready(function(){
		// Usar el footer estándar de #uni_modal (Cancelar/Guardar). El botón Guardar dispara submit().
		var inModal = <?php echo !empty($__IN_UNI_MODAL) ? 'true' : 'false'; ?>;

		$('#btn-cancel-service').on('click', function(){
			window.location.href = 'index.php?page=service_list';
		});

		$('.select2').select2();
		$('#service').keypress(function(){
			$(this).removeClass('border-danger');
		})
		$('#manage-service').submit(function(e){
			e.preventDefault();
			if($('.err_msg').length > 0){
				$('.err_msg').remove()
			}
			//start_loader();
			$.ajax({
				url:"public/ajax/action.php?action=save_service",
				dataType:'json',
				data: new FormData($(this)[0]),
		   		type: 'POST',
		   		method: 'POST',
			    cache: false,
			    contentType: false,
			    processData: false,
				error:err=>{
					console.log(err);
					alert_toast("A ocurrido un error","error");
					end_loader();
				},
				success:function(resp){
					if(!!resp.status && resp.status =='success'){
						alert_toast(" Datos guardados exitosamente","success");
						$('.modal').modal('hide');
						//end_loader()
						//load_data();
					}else if(!!resp.status && resp.status =='duplicate'){
						$('#manage-service').prepend('<div class="form-group err_msg"><div class="callout callout-danger"><span class="fa fa-exclamation-triangle"><b>Servicio ingresado exitosamente.</b></div></div>');
						$('#service').addClass('border-danger');
						$('#service').focus();
						//end_loader();
					}else{
						alert_toast("A ocurrido un error","error");
						//end_loader();
					}
				}
			})
		})
	})
</script>
