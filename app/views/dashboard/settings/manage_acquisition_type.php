<?php
require_once 'config/config.php';

// Asegurar columnas necesarias (sin romper instalaciones existentes)
@$conn->query("CREATE TABLE IF NOT EXISTS `acquisition_type` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`code` VARCHAR(3) NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Agregar columnas si faltan
$col = @$conn->query("SHOW COLUMNS FROM acquisition_type LIKE 'code'");
if (!$col || $col->num_rows === 0) {
	@$conn->query("ALTER TABLE acquisition_type ADD COLUMN code VARCHAR(3) NULL AFTER name");
}
$col = @$conn->query("SHOW COLUMNS FROM acquisition_type LIKE 'active'");
if (!$col || $col->num_rows === 0) {
	@$conn->query("ALTER TABLE acquisition_type ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER code");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$code = '';
$name = '';

if ($id > 0) {
	$fields = 'id, name';
	$has_code = false;
	$col = @$conn->query("SHOW COLUMNS FROM acquisition_type LIKE 'code'");
	if ($col && $col->num_rows > 0) {
		$has_code = true;
		$fields .= ', code';
	}
	$qry = @$conn->query("SELECT {$fields} FROM acquisition_type WHERE id = {$id} LIMIT 1");
	if ($qry && $qry->num_rows > 0) {
		$row = $qry->fetch_assoc();
		$code = $has_code ? ($row['code'] ?? '') : '';
		$name = $row['name'] ?? '';
	}
}
?>
<div class="container-fluid">
	<form id="manage_acquisition_type">
		<input type="hidden" name="id" value="<?php echo $id; ?>">

		<div class="form-group">
			<label class="control-label">CLAVE (2 o 3 caracteres)</label>
			<input type="text" name="code" class="form-control" required maxlength="3" placeholder="Ej: ADQ" value="<?php echo htmlspecialchars($code); ?>">
		</div>

		<div class="form-group">
			<label class="control-label">Descripción</label>
			<input type="text" name="name" class="form-control" required placeholder="Ej: Adquisición" value="<?php echo htmlspecialchars($name); ?>">
		</div>
	</form>
</div>

<script>
	if (typeof window.start_loader !== 'function') window.start_loader = function() {};
	if (typeof window.end_loader !== 'function') window.end_loader = function() {};

	$('#manage_acquisition_type [name="code"]').on('input', function(){
		this.value = this.value.replace(/[^a-zA-Z0-9]/g,'').toUpperCase();
	})

	$('#manage_acquisition_type').submit(function(e){
		e.preventDefault();
		start_loader();
		$.ajax({
			url:'public/ajax/action.php?action=save_acquisition_type',
			method:'POST',
			data: $(this).serialize(),
			dataType:'json',
			error:err=>{
				console.log(err)
				alert_toast('Ha ocurrido un error','error');
				end_loader();
			},
			success:function(resp){
				if(resp && resp.status == 'success'){
					alert_toast('Guardado correctamente','success');
					$('.modal').modal('hide');
					load_data();
				}else if(resp && resp.status == 'duplicate_code'){
					alert_toast('La CLAVE ya existe','warning');
					end_loader();
				}else if(resp && resp.status == 'in_use'){
					alert_toast('No se puede cambiar la CLAVE: está en uso','warning');
					end_loader();
				}else{
					alert_toast('No se pudo guardar','error');
					end_loader();
				}
			}
		})
	})
</script>

