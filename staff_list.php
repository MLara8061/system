<?php require_once 'config/config.php'; ?>
<div class="col-lg-12">
	<div class="card">
		<div class="card-body">
			<table class="table tabe-hover table-bordered" id="list">
				<thead>
					<tr>
						<th>#</th>
						<th>Nombre</th>
						<th>N Contacto</th>
						<th>Dirección</th>
						<th>Correo</th>
						<th>Acción</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					$qry = $conn->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM staff order by concat(lastname,', ',firstname,' ',middlename) asc");
					while ($row = $qry->fetch_assoc()) :
					?>
						<tr>
							<th class="text-center"><?php echo $i++ ?></th>
							<td><b><?php echo ucwords($row['name']) ?></b></td>
							<td><b><?php echo $row['contact'] ?></b></td>
							<td><b><?php echo $row['address'] ?></b></td>
							<td><b><?php echo $row['email'] ?></b></td>
							<td class="text-center">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
									Acción
								</button>
								<div class="dropdown-menu" style="">
									<a class="dropdown-item" href="./index.php?page=edit_staff&id=<?php echo $row['id'] ?>">Editar</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item delete_staff" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Eliminar</a>
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
		$('.delete_staff').click(function() {
			const staffId = $(this).attr('data-id');
			confirm_toast(
				'¿Estás seguro de eliminar este empleado? Esta acción no se puede deshacer.',
				function() { delete_staff(staffId); }
			);
		})
	})

	function delete_staff($id) {
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_staff',
			method: 'POST',
			data: {
				id: $id
			},
			success: function(resp) {
				if (resp == 1) {
					alert_toast("Datos eliminados correctamente", 'success')
					setTimeout(function() {
						location.reload()
					}, 1500)

				}
			}
		})
	}
</script>