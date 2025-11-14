<?php
include 'db_connect.php';
$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$user = $is_edit ? $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc() : [];
?>

<div class="container-fluid">
    <form id="manage-user-form">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- NOMBRE -->
        <div class="form-group">
            <label class="font-weight-bold"><strong>Nombre</strong></label>
            <input type="text" name="firstname" class="form-control form-control-sm"
                value="<?= $user['firstname'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label class="font-weight-bold"><strong>Segundo Nombre</strong></label>
            <input type="text" name="middlename" class="form-control form-control-sm"
                value="<?= $user['middlename'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label class="font-weight-bold"><strong>Apellido</strong></label>
            <input type="text" name="lastname" class="form-control form-control-sm"
                value="<?= $user['lastname'] ?? '' ?>" required>
        </div>

        <!-- USUARIO CON VALIDACIÓN VISUAL -->
        <div class="form-group">
            <label class="font-weight-bold"><strong>Usuario</strong></label>
            <div class="input-group input-group-sm">
                <input type="text" name="username" id="username" class="form-control form-control-sm"
                    value="<?= $user['username'] ?? '' ?>" required placeholder="Nombre de acceso">
                <div class="input-group-append">
                    <span class="input-group-text bg-white border-0">
                        <i id="username-status-icon" class="fas"></i>
                    </span>
                </div>
            </div>
            <small id="username-feedback" class="text-muted d-block mt-1"></small>
        </div>

        <!-- ROL -->
        <div class="form-group">
            <label class="font-weight-bold"><strong>Rol</strong></label>
            <select name="role" class="form-control form-control-sm" required>
                <option value="">-- Seleccionar --</option>
                <option value="1" <?= (isset($user['role']) && $user['role'] == 1) ? 'selected' : '' ?>>Administrador</option>
                <option value="2" <?= (isset($user['role']) && $user['role'] == 2) ? 'selected' : '' ?>>Usuario</option>
            </select>
        </div>

        <!-- CONTRASEÑA CON OJO -->
        <div class="form-group">
            <label class="font-weight-bold">
                <strong>Contraseña</strong>
                <small class="text-muted">
                    <?= $is_edit ? '(Dejar vacío para no cambiar)' : '(Requerida)' ?>
                </small>
            </label>
            <div class="input-group input-group-sm">
                <input type="password" name="password" id="password" class="form-control form-control-sm"
                    placeholder="<?= $is_edit ? 'Nueva contraseña' : 'Contraseña segura' ?>"
                    <?= !$is_edit ? 'required' : '' ?>>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <small id="password-strength" class="text-muted d-block mt-1"></small>
        </div>
    </form>
</div>

<style>
    .form-control-sm {
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .form-group label {
        font-size: 0.9rem;
        color: #495057;
    }

    #username-feedback,
    #password-strength {
        font-size: 0.8rem;
        min-height: 1.2rem;
    }

    .input-group-text {
        background: transparent !important;
    }

    #toggle-password {
        border-radius: 0 8px 8px 0;
    }

    #toggle-password i {
        font-size: 0.9rem;
    }
</style>

<script>
    $(document).ready(function() {
        const $form = $('#manage-user-form');
        const $username = $('#username');
        const $usernameFeedback = $('#username-feedback');
        const $usernameIcon = $('#username-status-icon');
        const $password = $('#password');
        const $passwordStrength = $('#password-strength');
        const $toggleBtn = $('#toggle-password');
        const isEdit = <?= $is_edit ? 'true' : 'false' ?>;
        const originalUsername = '<?= $user['username'] ?? '' ?>';

        // === MOSTRAR/OCULTAR CONTRASEÑA ===
        $toggleBtn.click(function() {
            const type = $password.attr('type') === 'password' ? 'text' : 'password';
            $password.attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        // === VALIDAR USUARIO EN TIEMPO REAL ===
        let usernameTimeout;
        $username.on('input', function() {
            clearTimeout(usernameTimeout);
            const val = $(this).val().trim();

            // Reset
            $usernameIcon.removeClass('fa-check-circle fa-times-circle text-success text-danger');
            $usernameFeedback.text('').removeClass('text-success text-danger');
            $username.removeClass('is-invalid');

            if (isEdit && val === originalUsername) return;

            if (val.length < 3) return;

            usernameTimeout = setTimeout(() => {
                $.ajax({
                    url: 'ajax.php?action=check_username',
                    method: 'POST',
                    data: {
                        username: val,
                        id: <?= $id ?>
                    },
                    success: function(resp) {
                        if (resp == 1) {
                            $usernameIcon.removeClass().addClass('fas fa-times-circle text-danger');
                            $usernameFeedback.html('Usuario no disponible').removeClass('text-success').addClass('text-danger');
                            $username.addClass('is-invalid');
                        } else {
                            $usernameIcon.removeClass().addClass('fas fa-check-circle text-success');
                            $usernameFeedback.html('Usuario disponible').removeClass('text-danger').addClass('text-success');
                        }
                    }
                });
            }, 500);
        });

        // === FUERZA DE CONTRASEÑA ===
        $password.on('input', function() {
            const val = $(this).val();
            let strength = '';
            if (isEdit && val.length === 0) {
                strength = '<span class="text-info">Sin cambios</span>';
            } else if (val.length > 0 && val.length < 6) {
                strength = '<span class="text-danger">Débil</span>';
            } else if (val.length < 10) {
                strength = '<span class="text-warning">Media</span>';
            } else if (val.length >= 10) {
                strength = '<span class="text-success">Fuerte</span>';
            }
            $passwordStrength.html(strength);
        });

        // === ENVIAR FORMULARIO ===
        $form.submit(function(e) {
            e.preventDefault();
            if ($username.hasClass('is-invalid')) {
                alert_toast("El usuario no está disponible", 'danger');
                return;
            }
            start_load();
            $.ajax({
                url: 'ajax.php?action=save_user',
                method: 'POST',
                data: $form.serialize(),
                success: function(resp) {
                    if (resp == 1) {
                        alert_toast("Usuario guardado correctamente", 'success');
                        setTimeout(() => {
                            $('#userModal').modal('hide');
                            location.reload();
                        }, 1000);
                    } else if (resp == 2) {
                        alert_toast("El usuario ya existe", 'error'); 
                    } else {
                        alert_toast("Error al guardar", 'error'); 
                    }
                }
            });
        });
    });
</script>