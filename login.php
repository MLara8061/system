<!DOCTYPE html>
<html lang="es">
<?php
session_start();
include('./db_connect.php');
if (isset($_SESSION['login_id']))
    header("location:index.php?page=home");
?>
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Acceder | Sistema de Administración de Activos</title>

    <?php include('./header.php'); ?>
    <style>
        /* === Reset y fondo general === */
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        /* === Contenedor principal dividido === */
        #login-container {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* === Lado izquierdo con título === */
        #login-left {
            flex: 1;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
        }

        #login-left h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        #login-left h2 {
            font-size: 1.2rem;
            font-weight: 400;
            opacity: 0.8;
        }

        /* Decoración suave */
        #login-left::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50% 50% 0 0;
        }

        /* === Lado derecho con formulario === */
        #login-right {
            flex: 1;
            background: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
        }

        .login-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s forwards 0.3s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card h4 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .btn-custom {
            background: #4facfe;
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.6rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background: #00f2fe;
        }

        /* Inputs minimalistas */
        .form-control {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 0.5rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #4facfe;
            box-shadow: 0 0 5px rgba(79,172,254,0.3);
        }

        /* Responsive */
        @media(max-width: 768px) {
            #login-container {
                flex-direction: column;
            }
            #login-left, #login-right {
                flex: none;
                width: 100%;
                height: 50vh;
            }
        }

        /* === Pantalla de carga === */
        #loader {
            position: fixed;
            top:0;
            left:0;
            width: 100%;
            height: 100%;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        #loader .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #4facfe;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg);}
            100% { transform: rotate(360deg);}
        }
    </style>
</head>

<body>
    <!-- Loader -->
    <div id="loader">
        <div class="spinner"></div>
    </div>

    <!-- Contenedor principal -->
    <div id="login-container">
        <div id="login-left">
            <h1>Sistema de Administración de Activos</h1>
            <h2>V2</h2>
        </div>

        <div id="login-right">
            <div class="login-card">
                <h4>Acceso al Sistema</h4>
                <form id="login-form">
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="type">Tipo de Usuario</label>
                        <select class="custom-select" name="type">
                            <option value="3">Cliente</option>
                            <option value="2">Staff</option>
                            <option value="1">Admin</option>
                        </select>
                    </div>
                    <button class="btn btn-custom mt-3">Acceder</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Loader fade out
        window.addEventListener("load", function(){
            document.getElementById("loader").style.display = "none";
        });

        // Login AJAX
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
