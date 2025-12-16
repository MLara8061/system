<?php
if (!defined('ROOT')) {
	define('ROOT', dirname(__DIR__, 4));
}
require_once ROOT . '/config/config.php';

// Asegurar tabla para evitar errores en instalaciones nuevas
@$conn->query("CREATE TABLE IF NOT EXISTS `equipment_categories` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`clave` VARCHAR(3) NOT NULL,
	`description` VARCHAR(255) NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uniq_equipment_categories_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$clave = '';
$description = '';

if ($id > 0) {
	$qry = $conn->query("SELECT * FROM equipment_categories WHERE id = {$id} LIMIT 1");
	if ($qry && $qry->num_rows > 0) {
		$row = $qry->fetch_assoc();
		$clave = $row['clave'] ?? '';
		$description = $row['description'] ?? '';
	}
}
?>
<div class="container-fluid">
	<form id="manage_equipment_category">
		<input type="hidden" name="id" value="<?php echo $id; ?>">

		<div class="form-group">
			<label class="control-label">CLAVE (2 o 3 caracteres)</label>
			<?php if ($id > 0): ?>
				<input type="text" class="form-control" value="<?php echo htmlspecialchars($clave); ?>" disabled>
				<input type="hidden" name="clave" value="<?php echo htmlspecialchars($clave); ?>">
				<small class="text-muted">La CLAVE no se puede modificar.</small>
			<?php else: ?>
				<input type="text" name="clave" class="form-control" required maxlength="3" placeholder="Ej: EQ" value="<?php echo htmlspecialchars($clave); ?>">
			<?php endif; ?>
		</div>

		<div class="form-group">
			<label class="control-label">Descripción</label>
			<input type="text" name="description" class="form-control" required placeholder="Ej: Equipo médico" value="<?php echo htmlspecialchars($description); ?>">
		</div>
	</form>
</div>

<script>
	// Fallbacks por si el layout no define estos helpers
	if (typeof window.start_loader !== 'function') window.start_loader = function() {};
	if (typeof window.end_loader !== 'function') window.end_loader = function() {};

	$('#manage_equipment_category [name="clave"]').on('input', function(){
		this.value = this.value.replace(/[^a-zA-Z0-9]/g,'').toUpperCase();
	})

	$('#manage_equipment_category').submit(function(e){
		e.preventDefault();
		start_loader();
		$.ajax({
			url:'public/ajax/action.php?action=save_equipment_category',
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
				}else if(resp && resp.status == 'duplicate_clave'){
					alert_toast('La CLAVE ya existe','warning');
					end_loader();
				}else{
					alert_toast('No se pudo guardar','error');
					end_loader();
				}
			}
		})
	})
</script>

