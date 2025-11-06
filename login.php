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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceder | Sistema de Activos</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef1ff;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --radius: 16px;
            --shadow: 0 10px 25px -3px rgba(0,0,0,0.07), 0 4px 6px -2px rgba(0,0,0,0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow: hidden;
        }

        /* === MOBILE FIRST === */
        .login-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, #5d7bff 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: var(--bg);
            border-radius: 50% 50% 0 0 / 30px 30px 0 0;
            transform: scaleX(1.5);
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            font-size: 0.9rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .login-card {
            flex: 1;
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-inner {
            background: var(--card);
            border-radius: var(--radius);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .card-title {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .btn-primary:hover {
            background: #3b56d3;
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper select {
            appearance: none;
            padding-right: 2.5rem;
        }

        .select-wrapper::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        /* === TABLET & DESKTOP === */
        @media (min-width: 768px) {
            .login-wrapper {
                flex-direction: row;
            }

            .login-header {
                flex: 1;
                padding: 3rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
                border-radius: 0 var(--radius) var(--radius) 0;
            }

            .login-header h1 {
                font-size: 2.2rem;
            }

            .login-header p {
                font-size: 1.1rem;
            }

            .login-card {
                flex: 1;
                padding: 3rem;
            }

            .card-inner {
                padding: 2.5rem;
            }
        }

        /* Loader */
        #loader {
            position: fixed;
            inset: 0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div id="loader">
        <div class="spinner"></div>
    </div>

    <div class="login-wrapper">
        <!-- Header / Hero -->
        <header class="login-header">
            <h1>Sistema de Administración de Activos</h1>
            <p>Gestión eficiente y moderna de tu inventario</p>
        </header>

        <!-- Login Form -->
        <section class="login-card">
            <div class="card-inner">
                <h2 class="card-title">Iniciar Sesión</h2>
                <form id="login-form">
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" class="form-control" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Tipo de Usuario</label>
                        <div class="select-wrapper">
                            <select id="type" name="type" class="form-control" required>
                                <option value="2">Staff</option>
                                <option value="1">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        <span class="btn-text">Acceder</span>
                    </button>
                </form>
            </div>
        </section>
    </div>

    <script>
        // Ocultar loader
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            loader.style.opacity = '0';
            setTimeout(() => loader.style.display = 'none', 300);
        });

        // Login con AJAX
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('.btn-primary');
            const btnText = btn.querySelector('.btn-text');
            const originalText = btnText.textContent;
            
            btn.disabled = true;
            btnText.textContent = 'Validando...';

            // Limpiar errores previos
            const existingAlert = this.querySelector('.alert');
            if (existingAlert) existingAlert.remove();

            fetch('ajax.php?action=login', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.text())
            .then(resp => {
                if (resp.trim() === '1') {
                    location.href = 'index.php?page=home';
                } else {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger';
                    alert.textContent = 'Usuario o contraseña incorrectos';
                    this.insertBefore(alert, this.firstChild);
                }
            })
            .catch(() => {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.textContent = 'Error de conexión';
                this.insertBefore(alert, this.firstChild);
            })
            .finally(() => {
                btn.disabled = false;
                btnText.textContent = originalText;
            });
        });
    </script>
</body>
</html>