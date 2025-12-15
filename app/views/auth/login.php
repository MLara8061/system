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
    </style>
</head>
<body>
    <div class="auth-wrap bg-light">
        <div class="auth-box">
            <div class="auth-brand">
                <div class="title h4 text-dark">Sistema de Activos</div>
                <div class="subtitle text-muted">Accede para continuar</div>
            </div>

            <div class="card auth-card shadow-sm">
                <div class="card-body">
                    <div class="h5 mb-3 text-dark">Iniciar sesión</div>

                    <form id="login-form" novalidate>
                        <div id="login-alert-slot"></div>

                        <div class="form-group mb-3">
                            <label for="username" class="small font-weight-bold mb-1">Usuario</label>
                            <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="username">
                        </div>

                        <div class="form-group mb-3">
                            <label for="password" class="small font-weight-bold mb-1">Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                        </div>

                        <div class="auth-actions">
                            <button type="submit" class="btn btn-primary btn-block">
                                <span class="btn-text">Acceder</span>
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
        // Login con AJAX y UX mejorada
        const form = document.getElementById('login-form');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const btnText = btn.querySelector('.btn-text');
            const originalText = btnText.textContent;
            
            // Deshabilitar botón y cambiar texto
            btn.disabled = true;
            btnText.textContent = 'Validando...';

            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');

            // Eliminar alertas previas
            const slot = document.getElementById('login-alert-slot');
            slot.innerHTML = '';

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
                console.log('Login response:', result.trim());
                console.log('Response status:', response.status);
                
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
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger py-2';
                    
                    // Mensajes más específicos
                    if (result.trim() === '2') {
                        alert.textContent = 'Usuario no encontrado';
                    } else if (result.trim() === '3') {
                        alert.textContent = 'Contraseña incorrecta';
                    } else if (result.includes('error') || result.includes('Error')) {
                        alert.textContent = 'Error del servidor. Intenta más tarde.';
                        console.error('Server error:', result);
                    } else {
                        alert.textContent = 'Usuario o contraseña incorrectos';
                    }

                    slot.appendChild(alert);
                }
            } catch (error) {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger py-2';
                alert.textContent = 'Error de conexión. Intenta nuevamente.';
                document.getElementById('login-alert-slot').appendChild(alert);
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