<?php
$root = defined('ROOT') ? ROOT : realpath(__DIR__ . '/../../../../');
require_once $root . '/config/config.php';
?>
<div class="col-lg-12">
	<div class="card card-outline card-primary">
		<div class="card-header">
			<div class="card-tools">
				<button class="btn btn-sm btn-primary btn-block" type='button' id="new_branch"><i class="fa fa-plus"></i> Agregar Sucursal</button>
			</div>
		</div>
		<div class="card-body">
			<table class="table tabe-hover table-bordered" id="list">
				<thead>
					<tr>
						<th class="text-center" style="width: 5%">#</th>
						<th style="width: 20%">CÃ³digo</th>
						<th style="width: 45%">Nombre</th>
						<th class="text-center" style="width: 15%">Estado</th>
						<th class="text-center" style="width: 15%">AcciÃ³n</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					$has_active = false;
					$col = $conn->query("SHOW COLUMNS FROM branches LIKE 'active'");
					if ($col && $col->num_rows > 0) $has_active = true;

					$qry = $conn->query("SELECT id, code, name" . ($has_active ? ", active" : "") . " FROM branches ORDER BY name ASC");
					while ($row = $qry->fetch_assoc()) :
						$active = $has_active ? intval($row['active']) : 1;
					?>
						<tr>
							<th class="text-center"><?php echo $i++ ?></th>
							<td><b><?php echo htmlspecialchars($row['code'] ?? '') ?></b></td>
							<td><?php echo htmlspecialchars($row['name'] ?? '') ?></td>
							<td class="text-center">
								<?php if ($active === 1) : ?>
									<span class="badge badge-success">Activa</span>
								<?php else : ?>
									<span class="badge badge-secondary">Inactiva</span>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
									AcciÃ³n
								</button>
								<div class="dropdown-menu">
									<a class="dropdown-item edit_branch" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Editar</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item delete_branch" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Eliminar</a>
								</div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function() {
		$('#list').dataTable()

		$('#new_branch').click(function() {
			uni_modal("Agregar Sucursal", "app/views/dashboard/settings/manage_branch.php")
		})

		$('.edit_branch').click(function() {
			uni_modal("Editar Sucursal", "app/views/dashboard/settings/manage_branch.php?id=" + $(this).attr('data-id'))
		})

		$('.delete_branch').click(function() {
			const branchId = $(this).attr('data-id');
			confirm_toast(
				'Â¿EstÃ¡s seguro de eliminar esta sucursal? Esta acciÃ³n no se puede deshacer.',
				function() {
					delete_branch(branchId);
				}
			);
		})
	})

	function delete_branch(id) {
		start_load()
		$.ajax({
			url: 'public/ajax/action.php?action=delete_branch',
			method: 'POST',
			data: { id: id },
			success: function(resp) {
				if (resp == 1) {
					alert_toast("Sucursal eliminada correctamente", 'success')
					setTimeout(function() {
						location.reload()
					}, 750)
				} else if (resp == 2) {
					alert_toast("No se puede eliminar: la sucursal estÃ¡ en uso", 'warning')
					end_load()
				} else {
					alert_toast("Error al eliminar la sucursal", 'danger')
					end_load()
				}
			}
		})
	}
</script>

