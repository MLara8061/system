<?php
if (!defined('ROOT')) {
	define('ROOT', dirname(__DIR__, 4));
}
require_once ROOT . '/config/config.php';
?>
<?php
$department_id = 0;
$name = '';
$selected_locations = [];
$selected_positions = [];

if (isset($_GET['id'])) {
	$department_id = intval($_GET['id']);
	$qry = $conn->query("SELECT * FROM departments WHERE id = $department_id")->fetch_array();
	if($qry) {
		foreach ($qry as $k => $v) {
			$$k = $v;
		}
		
		// Obtener ubicaciones asignadas
		$loc_qry = $conn->query("SELECT id FROM locations WHERE department_id = $department_id");
		while($loc = $loc_qry->fetch_assoc()) {
			$selected_locations[] = $loc['id'];
		}
		
		// Obtener puestos asignados
		$pos_qry = $conn->query("SELECT id FROM job_positions WHERE department_id = $department_id");
		while($pos = $pos_qry->fetch_assoc()) {
			$selected_positions[] = $pos['id'];
		}
	}
}
?>
<div class="container-fluid">
	<div id="department-msg"></div>
	<form action="" id="manage-department-form">
		<input type="hidden" name="id" value="<?php echo $department_id ?>">
		
		<div class="form-group">
			<label for="department-name" class="control-label">Nombre del Departamento</label>
			<input type="text" class="form-control form-control-sm" id="department-name" name="name" value='<?php echo htmlspecialchars($name) ?>' required>
		</div>
		
		<div class="form-group">
			<label for="department-locations" class="control-label">Ubicaciones</label>
			<select id="department-locations" name="locations[]" class="form-control select2" multiple="multiple" style="width: 100%">
				<?php
				$locations = $conn->query("SELECT * FROM locations ORDER BY name ASC");
				while($row = $locations->fetch_assoc()):
				?>
					<option value="<?php echo $row['id'] ?>" <?php echo in_array($row['id'], $selected_locations) ? 'selected' : '' ?>>
						<?php echo ucwords($row['name']) ?>
					</option>
				<?php endwhile; ?>
			</select>
			<small class="form-text text-muted">Selecciona las ubicaciones que pertenecen a este departamento</small>
		</div>
		
		<div class="form-group">
			<label for="department-positions" class="control-label">Puestos de Trabajo</label>
			<select id="department-positions" name="positions[]" class="form-control select2" multiple="multiple" style="width: 100%">
				<?php
				$positions = $conn->query("SELECT * FROM job_positions ORDER BY name ASC");
				while($row = $positions->fetch_assoc()):
				?>
					<option value="<?php echo $row['id'] ?>" <?php echo in_array($row['id'], $selected_positions) ? 'selected' : '' ?>>
						<?php echo ucwords($row['name']) ?>
					</option>
				<?php endwhile; ?>
			</select>
			<small class="form-text text-muted">Selecciona los puestos que pertenecen a este departamento</small>
		</div>
	</form>
</div>
<script>
	$(function(){
		$('#manage-department-form .select2').select2({
			placeholder: 'Seleccionar...',
			allowClear: true,
			width: '100%'
		});
	});

	$(document).off('submit', '#manage-department-form').on('submit', '#manage-department-form', function(e) {
		e.preventDefault()
		$(this).find('input').removeClass("border-danger")
		start_load()
		$('#department-msg').html('')
		
		// Preparar FormData
		var formData = new FormData($(this)[0]);
		
		$.ajax({
			url: 'public/ajax/action.php?action=save_department',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			method: 'POST',
			type: 'POST',
			success: function(resp) {
				if (resp == 1) {
					alert_toast('Datos guardados correctamente', "success");
					setTimeout(function() {
						location.replace('index.php?page=department_list')
					}, 750)
				} else if (resp == 2) {
					$('#department-msg').html("<div class='alert alert-danger'>Departamento ya existe</div>");
					$('#manage-department-form').find('[name="name"]').addClass("border-danger")
					end_load()
				} else {
					$('#department-msg').html("<div class='alert alert-danger'>Error al guardar: " + resp + "</div>");
					end_load()
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.error('Response:', xhr.responseText);
				$('#department-msg').html("<div class='alert alert-danger'>Error del servidor. Revisa la consola.</div>");
				end_load()
			}
		})
	})
</script>

