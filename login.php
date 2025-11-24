<!DOCTYPE html>
<html lang="es">
<?php
session_start();
require_once 'config/config.php'; 
if (isset($_SESSION['login_id']))
    header("location:index.php?page=home");
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Acceder | Sistema de Activos</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8f9fa;
            color: #2d3748;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* === MOBILE FIRST === */
        .login-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 1rem;
            background: #f8f9fa;
        }

        .login-header {
            text-align: center;
            padding: 1.5rem 1rem;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 12px;
            color: white;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);
        }

        .login-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .login-header p {
            font-size: 0.85rem;
            opacity: 0.95;
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border: 1px solid #e9ecef;
        }

        .login-form-container {
            width: 100%;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: #2d3748;
            text-align: center;
        }

        #login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            color: #2d3748;
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            padding-right: 2.8rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.9rem;
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #2d3748;
        }

        .toggle-password i {
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1.5px solid #cbd5e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: white;
            color: #2d3748;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px #e7f1ff;
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper select {
            appearance: none;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        .select-wrapper::after {
            content: '▼';
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            pointer-events: none;
            font-size: 0.7rem;
        }

        .alert {
            padding: 0.65rem 0.9rem;
            border-radius: 8px;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 0.5rem;
            animation: slideDown 0.3s ease;
        }

        .alert-danger {
            background: #fff5f5;
            color: #f56565;
            border: 1.5px solid #feb2b2;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        #loader {
            position: fixed;
            inset: 0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        #loader.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f4f6;
            border-top-color: #007bff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* === TABLET === */
        @media (min-width: 576px) {
            .login-container {
                padding: 2rem;
                justify-content: center;
            }

            .login-header {
                padding: 2rem;
            }

            .login-header h1 {
                font-size: 1.6rem;
            }

            .login-card {
                max-width: 420px;
                margin: 0 auto;
                padding: 2rem 2rem;
            }

            .card-title {
                font-size: 1.3rem;
            }
        }

        /* === DESKTOP === */
        @media (min-width: 768px) {
            body {
                background: #f8f9fa !important;
                overflow-x: hidden;
            }

            .login-container {
                flex-direction: row;
                padding: 0;
                gap: 0;
                background: #f8f9fa !important;
                width: 100vw;
                height: 100vh;
            }

            .login-header {
                flex: 1;
                border-radius: 0;
                margin: 0;
                padding: 3rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
                color: #ffffff !important;
            }

            .login-header h1 {
                font-size: 2.2rem;
                margin-bottom: 0.5rem;
                color: #ffffff !important;
            }

            .login-header p {
                font-size: 1.1rem;
                color: #ffffff !important;
            }

            .login-card {
                flex: 1;
                max-width: none;
                border-radius: 0;
                background: #f8f9fa !important;
                border: none !important;
                padding: 3rem;
                display: flex;
                justify-content: center;
                align-items: center;
                position: relative;
            }

            .card-title {
                color: #2d3748 !important;
            }

            .login-card label {
                color: #2d3748 !important;
            }

            .login-form-container {
                max-width: 400px;
                width: 100%;
                background: #ffffff !important;
                padding: 2.5rem !important;
                border-radius: 16px !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05) !important;
                border: 1px solid #e9ecef !important;
            }
        }

        /* === LARGE DESKTOP === */
        @media (min-width: 1200px) {
            .login-header {
                padding: 4rem;
            }

            .login-card {
                padding: 4rem;
            }

            .login-header h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div id="loader">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <!-- Header compacto -->
        <header class="login-header">
            <h1>Sistema de Activos</h1>
            <p>Gestión eficiente de inventario</p>
        </header>

        <!-- Card del formulario -->
        <main class="login-card">
            <div class="login-form-container">
                <h2 class="card-title">Iniciar Sesión</h2>
                <form id="login-form">
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        <span class="btn-text">Acceder</span>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Ocultar loader suavemente
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader');
            loader.classList.add('hidden');
            setTimeout(() => loader.remove(), 300);
        });

        // Toggle mostrar/ocultar contraseña
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Cambiar icono
            const icon = this.querySelector('i');
            if (type === 'password') {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        // Login con AJAX y UX mejorada
        const form = document.getElementById('login-form');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('.btn-primary');
            const btnText = btn.querySelector('.btn-text');
            const originalText = btnText.textContent;
            
            // Deshabilitar botón y cambiar texto
            btn.disabled = true;
            btnText.textContent = 'Validando...';

            // Eliminar alertas previas
            const existingAlert = this.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }

            try {
                const response = await fetch('ajax.php?action=login', {
                    method: 'POST',
                    body: new FormData(this)
                });
                
                const result = await response.text();
                console.log('Login response:', result.trim());
                
                if (result.trim() === '1') {
                    // Login exitoso
                    btnText.textContent = '¡Acceso concedido!';
                    btn.style.background = 'var(--success)';
                    
                    setTimeout(() => {
                        location.href = 'index.php?page=home';
                    }, 500);
                } else {
                    // Login fallido
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger';
                    alert.textContent = 'Usuario o contraseña incorrectos';
                    form.insertBefore(alert, form.firstChild);
                    
                    // Auto-ocultar después de 4s
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.style.transition = 'opacity 0.3s ease';
                            alert.style.opacity = '0';
                            setTimeout(() => alert.remove(), 300);
                        }
                    }, 4000);
                }
            } catch (error) {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.textContent = 'Error de conexión. Intenta nuevamente.';
                form.insertBefore(alert, form.firstChild);
            } finally {
                btn.disabled = false;
                if (btnText.textContent === 'Validando...') {
                    btnText.textContent = originalText;
                }
            }
        });
    </script>
</body>
</html>