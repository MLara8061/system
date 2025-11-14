<?php 
include 'db_connect.php';
$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$user = $is_edit ? $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc() : [];
$title = $is_edit ? 'Editar Usuario' : 'Nuevo Usuario';
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px;">
        <div class="card-header bg-white border-0 pb-0">
            <h5 class="modal-title text-dark mb-0">
                <i class="fa fa-user-plus text-primary mr-2"></i> <?= $title ?>
            </h5>
        </div>
        <div class="card-body pt-3">
            <form id="manage-user-form">
                <input type="hidden" name="id" value="<?= $id ?>">

                <!-- NOMBRE -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Nombre</strong></label>
                    <input type="text" name="firstname" class="form-control form-control-sm" 
                           value="<?= $user['firstname'] ?? '' ?>" required placeholder="Ej: Juan">
                </div>

                <!-- SEGUNDO NOMBRE -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Segundo Nombre</strong></label>
                    <input type="text" name="middlename" class="form-control form-control-sm" 
                           value="<?= $user['middlename'] ?? '' ?>" placeholder="Opcional">
                </div>

                <!-- APELLIDO -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Apellido</strong></label>
                    <input type="text" name="lastname" class="form-control form-control-sm" 
                           value="<?= $user['lastname'] ?? '' ?>" required placeholder="Ej: Pérez">
                </div>

                <!-- USUARIO -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Usuario</strong></label>
                    <input type="text" name="username" id="username" class="form-control form-control-sm" 
                           value="<?= $user['username'] ?? '' ?>" required placeholder="Nombre de acceso">
                    <small id="username-feedback" class="text-danger"></small>
                </div>

                <!-- ROL -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Rol</strong></label>
                    <select name="role" class="form-control form-control-sm" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="1" <?= ($user['role'] ?? '') == 1 ? 'selected' : '' ?>>Administrador</option>
                        <option value="2" <?= ($user['role'] ?? '') == 2 ? 'selected' : '' ?>>Usuario</option>
                    </select>
                </div>

                <!-- CONTRASEÑA -->
                <div class="form-group">
                    <label class="font-weight-bold">
                        <strong>Contraseña</strong> 
                        <small class="text-muted">
                            <?= $is_edit ? '(Dejar vacío para no cambiar)' : '(Requerida)' ?>
                        </small>
                    </label>
                    <input type="password" name="password" id="password" class="form-control form-control-sm" 
                           placeholder="<?= $is_edit ? 'Nueva contraseña' : 'Contraseña segura' ?>" 
                           <?= !$is_edit ? 'required' : '' ?>>
                    <small id="password-strength" class="text-muted"></small>
                </div>

                <!-- BOTONES -->
                <div class="form-group text-right mt-4">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="history.back()">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                        <i class="fas fa-save mr-1"></i> <?= $is_edit ? 'Guardar Cambios' : 'Crear Usuario' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card { max-width: 500px; margin: 0 auto; }
    .form-control-sm { border-radius: 8px; font-size: 0.9rem; }
    .form-group label { font-size: 0.9rem; color: #495057; }
    .btn { min-width: 120px; border-radius: 8px; }
    #username-feedback { font-size: 0.8rem; }
    #password-strength { font-size: 0.8rem; }
</style>

<script>
$(document).ready(function() {
    const $form = $('#manage-user-form');
    const $username = $('#username');
    const $password = $('#password');
    const isEdit = <?= $is_edit ? 'true' : 'false' ?>;

    // === VALIDAR USUARIO EN TIEMPO REAL ===
    let usernameTimeout;
    $username.on('input', function() {
        clearTimeout(usernameTimeout);
        const val = $(this).val().trim();
        if (val.length < 3) {
            $('#username-feedback').text('');
            return;
        }
        usernameTimeout = setTimeout(() => {
            $.ajax({
                url: 'ajax.php?action=check_username',
                method: 'POST',
                data: { username: val, id: <?= $id ?> },
                success: function(resp) {
                    if (resp == 1) {
                        $('#username-feedback').html('<i class="fas fa-times-circle"></i> Usuario ya existe');
                        $username.addClass('is-invalid');
                    } else {
                        $('#username-feedback').html('<i class="fas fa-check-circle text-success"></i> Disponible');
                        $username.removeClass('is-invalid');
                    }
                }
            });
        }, 500);
    });

    // === FUERZA DE CONTRASEÑA ===
    $password.on('input', function() {
        const val = $(this).val();
        let strength = '';
        if (val.length === 0) strength = '';
        else if (val.length < 6) strength = '<span class="text-danger">Débil</span>';
        else if (val.length < 10) strength = '<span class="text-warning">Media</span>';
        else strength = '<span class="text-success">Fuerte</span>';
        $('#password-strength').html(strength);
    });

    // === ENVIAR FORMULARIO ===
    $form.submit(function(e) {
        e.preventDefault();
        if ($username.hasClass('is-invalid')) {
            alert_toast("Corrige el usuario", 'danger');
            return;
        }
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_user',
            method: 'POST',
            data: $form.serialize(),
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Usuario guardado", 'success');
                    setTimeout(() => {
                        location.href = 'index.php?page=user_list';
                    }, 1000);
                } else if (resp == 2) {
                    alert_toast("El usuario ya existe", 'danger');
                    end_load();
                } else {
                    alert_toast("Error al guardar", 'danger');
                    end_load();
                }
            }
        });
    });
});
</script>