<!DOCTYPE html>
<html lang="es">
<?php
// Definir ROOT si no está definido
if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__DIR__))));
}

// === VERIFICAR MODO MANTENIMIENTO PRIMERO ===
$maintenanceConfig = require ROOT . '/config/maintenance_config.php';
if ($maintenanceConfig['maintenance_enabled']) {
    $userIP = $_SERVER['REMOTE_ADDR'] ?? '';
    $isAllowedIP = in_array($userIP, $maintenanceConfig['allowed_ips']);
    
    if (!$isAllowedIP) {
        require ROOT . '/components/maintenance.php';
        exit();
    }
}

require_once ROOT . '/config/session.php';
require_once ROOT . '/config/config.php'; 

if (isset($_SESSION['login_id']))
    header("location: ../../../index.php?page=home");
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Acceder | Sistema de Activos</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">

    <!-- AdminLTE / Bootstrap (para diseño consistente y minimalista) -->
    <link rel="stylesheet" href="/css/adminlte.min.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        html, body {
            height: 100%;
        }

        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .auth-box {
            width: 100%;
            max-width: 420px;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 1.25rem;
        }

        .auth-brand .title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .auth-brand .subtitle {
            font-size: 0.9rem;
        }

        .auth-card {
            border-radius: 12px;
        }

        .auth-card .card-body {
            padding: 1.5rem;
        }

        .auth-actions {
            margin-top: 1rem;
        }

        /* Floating labels (sin colores nuevos; usa tipografía/colores del tema) */
        .floating-field {
            position: relative;
        }
        .floating-field .form-control {
            padding-top: 1.1rem;
            padding-bottom: 0.65rem;
        }
        .floating-field label {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            margin: 0;
            pointer-events: none;
            transition: transform 120ms ease, opacity 120ms ease;
            transform-origin: left top;
            opacity: .85;
        }
        .floating-field .form-control:focus + label,
        .floating-field .form-control:not(:placeholder-shown) + label {
            transform: translateY(-0.55rem) scale(0.85);
            opacity: .7;
        }
        .floating-field .field-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-25%);
            opacity: .65;
        }

        /* Microinteracción: shake en error */
        @keyframes shakeX {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .shake {
            animation: shakeX 320ms ease-in-out;
        }
    </style>
</head>
<body>
    <div class="auth-wrap bg-light">
        <div class="auth-box">
            <div class="auth-brand">
                <div class="title h4 text-dark">Sistema de Activos</div>
                <div class="subtitle text-muted">Accede para continuar</div>
            </div>

            <div class="card auth-card shadow-sm" id="auth-card">
                <div class="card-body">
                    <div class="h5 mb-3 text-dark">Iniciar sesión</div>

                    <form id="login-form" novalidate>
                        <div id="login-alert-slot"></div>

                        <div class="floating-field mb-3">
                            <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="username" placeholder=" ">
                            <label for="username" class="small">Usuario</label>
                            <span class="field-icon"><i class="fa-regular fa-user"></i></span>
                            <small class="invalid-feedback" id="username-error">El usuario es requerido.</small>
                        </div>

                        <div class="floating-field mb-2">
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password" placeholder=" ">
                            <label for="password" class="small">Contraseña</label>
                            <span class="field-icon"><i class="fa-solid fa-lock"></i></span>
                            <small class="invalid-feedback" id="password-error">La contraseña es requerida.</small>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="remember_me" name="remember_me" value="1">
                                <label class="custom-control-label" for="remember_me">Recordarme</label>
                            </div>
                            <a href="#" class="small" id="forgot-link">¿Olvidaste la contraseña?</a>
                        </div>

                        <div class="auth-actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btn-spinner"></span>
                                <span class="btn-text" id="btn-text">Acceder</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center text-muted small mt-3">
                <span>© <?= date('Y') ?></span>
            </div>
        </div>
    </div>

    <script>
        // Login con AJAX + validación en tiempo real + microinteracciones
        const form = document.getElementById('login-form');
        const card = document.getElementById('auth-card');
        const slot = document.getElementById('login-alert-slot');
        const usernameEl = document.getElementById('username');
        const passwordEl = document.getElementById('password');
        const forgotLink = document.getElementById('forgot-link');

        const btn = form.querySelector('button[type="submit"]');
        const btnText = document.getElementById('btn-text');
        const btnSpinner = document.getElementById('btn-spinner');
        const originalText = btnText.textContent;

        function clearAlerts(){
            slot.innerHTML = '';
        }

        function showAlert(type, message){
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} py-2`;
            alert.textContent = message;
            slot.appendChild(alert);
        }

        function shake(){
            card.classList.remove('shake');
            // reflow
            void card.offsetWidth;
            card.classList.add('shake');
        }

        function setLoading(isLoading){
            btn.disabled = isLoading;
            if (isLoading){
                btnSpinner.classList.remove('d-none');
                btnText.textContent = 'Validando...';
            } else {
                btnSpinner.classList.add('d-none');
                if (btnText.textContent === 'Validando...') btnText.textContent = originalText;
            }
        }

        function validateUsername(){
            const value = (usernameEl.value || '').trim();
            const ok = value.length >= 2;
            usernameEl.classList.toggle('is-invalid', !ok);
            usernameEl.setAttribute('aria-invalid', ok ? 'false' : 'true');
            return ok;
        }

        function validatePassword(){
            const value = (passwordEl.value || '');
            const ok = value.length >= 1;
            passwordEl.classList.toggle('is-invalid', !ok);
            passwordEl.setAttribute('aria-invalid', ok ? 'false' : 'true');
            return ok;
        }

        usernameEl.addEventListener('input', validateUsername);
        passwordEl.addEventListener('input', validatePassword);

        forgotLink.addEventListener('click', function(e){
            e.preventDefault();
            clearAlerts();
            showAlert('info', 'Si olvidaste tu contraseña, contacta al administrador del sistema para restablecerla.');
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            clearAlerts();
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');

            const okUser = validateUsername();
            const okPass = validatePassword();
            if (!okUser || !okPass){
                shake();
                return;
            }

            setLoading(true);

            try {
                const response = await fetch('../../../public/ajax/login.php', {
                    method: 'POST',
                    body: new FormData(this)
                });
                
                // Verificar si la respuesta es exitosa
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.text();
                
                if (result.trim() === '1') {
                    // Login exitoso
                    btnText.textContent = '¡Acceso concedido!';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                    
                    setTimeout(() => {
                        location.href = '../../../index.php?page=home';
                    }, 500);
                } else {
                    // Login fallido
                    if (result.trim() === '2') {
                        showAlert('danger', 'Usuario no encontrado');
                    } else if (result.trim() === '3') {
                        showAlert('danger', 'Contraseña incorrecta');
                    } else if (result.includes('error') || result.includes('Error')) {
                        showAlert('danger', 'Error del servidor. Intenta más tarde.');
                    } else {
                        showAlert('danger', 'Usuario o contraseña incorrectos');
                    }

                    shake();
                }
            } catch (error) {
                showAlert('danger', 'Error de conexión. Intenta nuevamente.');
                shake();
            } finally {
                setLoading(false);
            }
        });
    </script>
</body>
</html>