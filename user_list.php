<style>
	.img-avatar {
		width: 50px;
		height: 50px;
		object-fit: cover;
		border: 1px solid #ddd;
		border-radius: 50%;
	}
	.dropdown-item { font-size: 0.9rem; }
</style>

<div class="container-fluid">
	<div class="card card-outline card-primary shadow-sm">
		<div class="card-header">
			<h5 class="card-title">Lista de Usuarios</h5>
			<div class="card-tools">
				<button class="btn btn-success btn-sm" id="new_user">
					<i class="fa fa-plus"></i> Nuevo Usuario
				</button>
			</div>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered table-striped" id="user-table">
					<colgroup>
						<col width="5%">
						<col width="15%">
						<col width="25%">
						<col width="35%">
						<col width="20%">
					</colgroup>
					<thead>
						<tr>
							<th>#</th>
							<th>Avatar</th>
							<th>Nombre</th>
							<th>Usuario</th>
							<th class="text-center">Acción</th>
						</tr>
					</thead>
					<tbody>
						<!-- Datos se cargan con AJAX -->
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<!-- MODAL DE CONFIRMACIÓN -->
<div class="modal fade" id="confirm_modal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Confirmar</h5>
				<button type="button" class="close" data-dismiss="modal">×</button>
			</div>
			<div class="modal-body">
				<p>¿Estás seguro de eliminar este usuario?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" id="confirm_delete">Eliminar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
			</div>
		</div>
	</div>
</div>

<script>
	// === CARGAR USUARIOS ===
	function load_users() {
		start_load(); // Muestra loader
		$.ajax({
			url: 'ajax.php?action=load_users',
			method: 'GET',
			dataType: 'json',
			success: function(resp) {
				if (resp.status === 'success' && resp.data.length > 0) {
					let tbody = '';
					resp.data.forEach((user, i) => {
						tbody += `
						<tr>
							<td class="text-center">${i + 1}</td>
							<td class="text-center">
								<img src="${user.avatar || 'assets/img/avatar.png'}" 
									 class="img-avatar" alt="Avatar">
							</td>
							<td><b>${user.name || 'Sin nombre'}</b></td>
							<td><b>${user.username}</b></td>
							<td class="text-center">
								<div class="btn-group">
									<button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" 
											data-toggle="dropdown">
										<i class="fa fa-cogs"></i>
									</button>
									<div class="dropdown-menu">
										<a class="dropdown-item edit_user" href="javascript:void(0)" 
										   data-id="${user.id}">
											<i class="fa fa-edit text-primary"></i> Editar
										</a>
										<div class="dropdown-divider"></div>
										<a class="dropdown-item delete_user text-danger" href="javascript:void(0)" 
										   data-id="${user.id}">
											<i class="fa fa-trash"></i> Eliminar
										</a>
									</div>
								</div>
							</td>
						</tr>`;
					});
					$('#user-table tbody').html(tbody);
				} else {
					$('#user-table tbody').html('<tr><td colspan="5" class="text-center">No hay usuarios</td></tr>');
				}
				end_load();
				init_data_events();
			},
			error: function() {
				alert_toast('Error al cargar usuarios', 'error');
				end_load();
			}
		});
	}

	// === EVENTOS DINÁMICOS ===
	function init_data_events() {
		$('.edit_user').click(function() {
			location.href = 'index.php?page=manage_user&id=' + $(this).data('id');
		});

		$('.delete_user').click(function() {
			_conf('¿Eliminar este usuario?', 'delete_user', [$(this).data('id')]);
		});
	}

	// === ELIMINAR USUARIO ===
	function delete_user(id) {
		start_load();
		$.ajax({
			url: 'ajax.php?action=delete_user',
			method: 'POST',
			data: { id: id },
			dataType: 'json',
			success: function(resp) {
				if (resp.status === 'success') {
					alert_toast('Usuario eliminado', 'success');
					load_users();
				} else {
					alert_toast('Error al eliminar', 'error');
				}
				end_load();
			},
			error: function() {
				alert_toast('Error de conexión', 'error');
				end_load();
			}
		});
	}

	// === INICIALIZAR ===
	$(document).ready(function() {
		load_users();

		$('#new_user').click(function() {
			location.href = 'index.php?page=manage_user';
		});
	});
</script>