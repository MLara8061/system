<!DOCTYPE html>
<html lang="es">
<?php
session_start();
include('./db_connect.php');
?>
<head>
	<meta charset="utf-8">
	<meta content="width=device-width, initial-scale=1.0" name="viewport">
	<title>Acceder | Sistema de Administración de Activos</title>

	<?php include('./header.php'); ?>
	<?php
	if (isset($_SESSION['login_id']))
		header("location:index.php?page=home");
	?>
	<style>
		/* === Fondo general === */
		body {
			width: 100%;
			height: 100vh;
			margin: 0;
			background: #f4f4f4; /* gris claro */
			display: flex;
			align-items: center;
			justify-content: center;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}

		/* === Contenedor principal === */
		#login-center {
			background: none !important; /* eliminamos bg-warning */
			width: 100%;
			max-width: 400px;
		}

		/* === Tarjeta de login === */
		.card {
			border: none;
			border-radius: 10px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
			background: #fff;
		}

		.card-body {
			padding: 2rem;
		}

		h4 {
			margin-bottom: 2rem;
			color: #333;
			font-weight: 600;
			text-align: center;
		}

		/* === Botón moderno === */
		.btn-custom {
			background: #333;
			color: #fff;
			border: none;
			padding: 0.6rem;
			transition: all 0.3s ease;
		}

		.btn-custom:hover {
			background: #555;
		}
	</style>
</head>

<body>
	<main id="main">
		<div id="login-center">
			<h4><b>Sistema de Administración de Activos</b></h4>
			<div class="row justify-content-center">
				<div class="card col-md-12">
					<div class="card-body">
						<form id="login-form">
							<div class="form-group">
								<label for="username">Usuario</label>
								<input type="text" id="username" name="username" class="form-control form-control-sm">
							</div>
							<div class="form-group">
								<label for="password">Contraseña</label>
								<input type="password" id="password" name="password" class="form-control form-control-sm">
							</div>
							<div class="form-group">
								<label for="type">Tipo de Usuario</label>
								<select class="custom-select custom-select-sm" name="type">
									<option value="3">Cliente</option>
									<option value="2">Staff</option>
									<option value="1">Admin</option>
								</select>
							</div>
							<center>
								<button class="btn btn-custom btn-block col-md-6">Acceder</button>
							</center>
						</form>
					</div>
				</div>
			</div>
		</div>
	</main>

	<a href="#" class="back-to-top"><i class="icofont-simple-up"></i></a>

	<script>
		$('#login-form').submit(function(e) {
			e.preventDefault()
			$('#login-form button').attr('disabled', true).html('Ingresando...');
			if ($(this).find('.alert-danger').length > 0)
				$(this).find('.alert-danger').remove();
			$.ajax({
				url: 'ajax.php?action=login',
				method: 'POST',
				data: $(this).serialize(),
				error: err => {
					console.log(err)
					$('#login-form button').removeAttr('disabled').html('Acceder');
				},
				success: function(resp) {
					if (resp == 1) {
						location.href = 'index.php?page=home';
					} else {
						$('#login-form').prepend('<div class="alert alert-danger">Usuario o Contraseña Incorrecta</div>')
						$('#login-form button').removeAttr('disabled').html('Acceder');
					}
				}
			})
		})
	</script>
</body>
</html>
