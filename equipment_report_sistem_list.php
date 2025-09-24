<?php include 'db_connect.php' ?>
<div class="col-lg-12">
	<div class="card">
		<div class="card-body">
			<table class="table tabe-hover table-bordered" id="list">
				<thead>
					<tr>
						<th>#</th>
						<th>Nombre</th>
						<th>Serie</th>
						<th>Model</th>
						<th>Marca</th>
						<th>Dia</th>
						<th>Mes</th>
						<th>Año</th>
						<th>Acciones</th>

					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					$qry = $conn->query("SELECT * FROM equipment_report_sistem order by id desc");
					while ($row = $qry->fetch_assoc()) :
					?>
						<tr>
							<th class="text-center"><?php echo ($row['id']) ?></th>
							<td><b><?php echo ($row['nombre']) ?></b></td>
							<td><b><?php echo ($row['serie']) ?></b></td>
							<td><b><?php echo $row['modelo'] ?></b></td>
							<td><b><?php echo $row['marca'] ?></b></td>
							<td><b><?php echo $row['dia'] ?></b></td>
                            <td><b><?php echo $row['mes'] ?></b></td>
                            <td><b><?php echo $row['yea'] ?></b></td>

							<td class="text-center">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
									Acción
								</button>
								<div class="dropdown-menu" style="">
								<a class="dropdown-item" href="./index.php?page=equipment_report_sistem_editar&id=<?php echo $row['id'] ?>">Editar</a>
									
									<div class="dropdown-divider"></div>
						

									<a class="dropdown-item" href="./index.php?page=equipment_report_sistem_editar&id=<?php echo $row['id'] ?>">Imprimir</a>
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
		$('.delete').click(function(e) {
			e.preventDefault();
			//alert('Deseas eliminar este equipo?');
			_conf("Deseas eliminar este equipo?", "delete_equipment", [$(this).attr('data-id')])
		})
	})

	function delete_equipment($id) {
		start_load()
		$.ajax({
			url: 'ajax.php?action=delete_equipment',
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