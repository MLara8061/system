<?php require_once 'config/config.php'; ?>
<div class="col-lg-12">
	<div class="card card-outline card-primary">
		<div class="card-header">
			<div class="card-tools">
				<button class="btn btn-sm btn-primary btn-block" type='button' id="new_department"><i class="fa fa-plus"></i> Agregar Departamento</button>
			</div>
		</div>
		<div class="card-body">
			<table class="table tabe-hover table-bordered" id="list">
				<thead>
					<tr>
						<th>#</th>
						<th>Nombre</th>
						<th>Descripción</th>
						<th>Acción</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					$qry = $conn->query("SELECT * FROM departments order by  name asc");
					while ($row = $qry->fetch_assoc()) :
					?>
						<tr>
							<th class="text-center"><?php echo $i++ ?></th>
							<td><b><?php echo ucwords($row['name']) ?></b></td>
							<td><b><?php echo $row['description'] ?></b></td>
							<td class="text-center ">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
									Acción
								</button>
								<div class="dropdown-menu" style="">
									<a class="dropdown-item edit_department" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Editar</a>
									<div class="dropdown-divider"></div>
									<a class="dropdown-item delete_department" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Eliminar</a>
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
		$('#new_department').click(function() {
			uni_modal("Agregar Departamento", "manage_department.php")
		})
		$('.edit_department').click(function() {
			uni_modal("Editar Departmento", "manage_department.php?id=" + $(this).attr('data-id'))
		})
		$('.delete_department').click(function() {
			const deptId = $(this).attr('data-id');
			confirm_toast(
				'¿Estás seguro de eliminar este departamento? Esta acción no se puede deshacer.',
				function() { delete_department(deptId); }
			);
		})

	})

	function delete_department($id) {
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_department',
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