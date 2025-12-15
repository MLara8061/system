<?php
$root = defined('ROOT') ? ROOT : realpath(__DIR__ . '/../../../../');
require_once $root . '/config/config.php';
?>
<?php
$branch_id = 0;
$code = '';
$name = '';
$active = 1;

$has_active = false;
$col = $conn->query("SHOW COLUMNS FROM branches LIKE 'active'");
if ($col && $col->num_rows > 0) $has_active = true;

if (isset($_GET['id'])) {
	$branch_id = intval($_GET['id']);
	$fields = "id, code, name" . ($has_active ? ", active" : "");
	$qry = $conn->query("SELECT $fields FROM branches WHERE id = $branch_id");
	if ($qry && $row = $qry->fetch_assoc()) {
		$code = $row['code'] ?? '';
		$name = $row['name'] ?? '';
		$active = $has_active ? intval($row['active']) : 1;
	}
}
?>
<div class="container-fluid">
	<div id="msg"></div>
	<form action="" id="manage-branch">
		<input type="hidden" name="id" value="<?php echo $branch_id ?>">

		<div class="form-group">
			<label for="" class="control-label">Código</label>
			<input type="text" class="form-control form-control-sm" name="code" value="<?php echo htmlspecialchars($code) ?>" required>
			<small class="form-text text-muted">Ej: HAC</small>
		</div>

		<div class="form-group">
			<label for="" class="control-label">Nombre</label>
			<input type="text" class="form-control form-control-sm" name="name" value="<?php echo htmlspecialchars($name) ?>" required>
		</div>

		<?php if ($has_active): ?>
			<div class="form-group">
				<div class="custom-control custom-switch">
					<input type="checkbox" class="custom-control-input" id="branch_active" name="active" value="1" <?php echo ($active == 1 ? 'checked' : '') ?>>
					<label class="custom-control-label" for="branch_active">Sucursal activa</label>
				</div>
			</div>
		<?php endif; ?>
	</form>
</div>
<script>
	$('#manage-branch').submit(function(e) {
		e.preventDefault()
		$('input').removeClass("border-danger")
		start_load()
		$('#msg').html('')

		var formData = new FormData($(this)[0]);

		$.ajax({
			url: 'public/ajax/action.php?action=save_branch',
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
						location.replace('/index.php?page=branches')
					}, 750)
				} else if (resp == 2) {
					$('#msg').html("<div class='alert alert-danger'>El código ya existe</div>");
					$('[name="code"]').addClass("border-danger")
					end_load()
				} else if (resp == 3) {
					$('#msg').html("<div class='alert alert-danger'>El nombre ya existe</div>");
					$('[name="name"]').addClass("border-danger")
					end_load()
				} else {
					$('#msg').html("<div class='alert alert-danger'>Error al guardar: " + resp + "</div>");
					end_load()
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', status, error);
				console.error('Response:', xhr.responseText);
				$('#msg').html("<div class='alert alert-danger'>Error del servidor. Revisa la consola.</div>");
				end_load()
			}
		})
	})
</script>

