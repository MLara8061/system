<?php
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 4));
}
require_once ROOT . '/config/db.php';
/** @var \PDO $pdo */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;
$user = [];
if ($is_edit) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch() ?: [];
}
?>

<div class="container-fluid">
    <form id="manage-user-form">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- NOMBRE COMPLETO -->
        <div class="form-group">
            <label for="firstname"><strong>Nombre</strong></label>
            <input type="text" name="firstname" id="firstname" class="form-control" value="<?= $user['firstname'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label for="middlename"><strong>Segundo Nombre</strong></label>
            <input type="text" name="middlename" id="middlename" class="form-control" value="<?= $user['middlename'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label for="lastname"><strong>Apellido</strong></label>
            <input type="text" name="lastname" id="lastname" class="form-control" value="<?= $user['lastname'] ?? '' ?>" required>
        </div>

        <!-- USUARIO CON VALIDACIÓN -->
        <div class="form-group">
            <label for="username"><strong>Usuario</strong></label>
            <div class="input-group">
                <input type="text" name="username" id="username" class="form-control" 
                       value="<?= $user['username'] ?? '' ?>" required>
                <div class="input-group-append">
                    <span class="input-group-text"><i id="check-icon" class="fa"></i></span>
                </div>
            </div>
            <small id="username-msg" class="text-danger"></small>
        </div>

        <!-- ROL -->
        <div class="form-group">
            <label for="role"><strong>Rol</strong></label>
            <select name="role" id="role" class="form-control" required>
                <option value="1" <?= ($user['role'] ?? '') == 1 ? 'selected' : '' ?>>Administrador</option>
                <option value="2" <?= ($user['role'] ?? '') == 2 ? 'selected' : '' ?>>Usuario</option>
            </select>
        </div>

        <!-- DEPARTAMENTO -->
        <div class="form-group">
            <label for="department_id"><strong>Departamento</strong></label>
            <select name="department_id" id="department_id" class="form-control">
                <option value="">Sin asignar</option>
                <?php
                $dept_stmt = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
                while ($dept = $dept_stmt->fetch()):
                ?>
                    <option value="<?= $dept['id'] ?>" <?= ($user['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small class="text-muted">Area a la que pertenece el usuario</small>
        </div>

        <!-- ACCESO MULTI-DEPARTAMENTAL -->
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="can_view_all_departments" 
                       name="can_view_all_departments" value="1"
                       <?= ($user['can_view_all_departments'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label class="custom-control-label" for="can_view_all_departments">
                    <strong>Puede ver todos los departamentos</strong>
                </label>
            </div>
            <small class="text-muted">Si esta activo, vera informacion de todas las areas</small>
        </div>

        <!-- CONTRASEÑA -->
        <div class="form-group">
            <label for="password"><strong>Contraseña</strong> 
                <small class="text-muted">
                    <?= $is_edit ? '(Dejar vacío para no cambiar)' : '(Requerida)' ?>
                </small>
            </label>
            <input type="password" name="password" id="password" class="form-control" 
                   placeholder="<?= $is_edit ? 'Nueva contraseña' : 'Contraseña segura' ?>"
                   <?= !$is_edit ? 'required' : '' ?>>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    const $form = $('#manage-user-form');
    const $username = $('#username');
    const $msg = $('#username-msg');
    const $icon = $('#check-icon');
    const original = '<?= $user['username'] ?? '' ?>';
    let typingTimer;

    // Validar usuario en tiempo real
    $username.on('input', function() {
        clearTimeout(typingTimer);
        const val = $(this).val().trim();
        $icon.removeClass('fa-check text-success fa-times text-danger');
        $msg.text('');

        if (val.length < 3) return;

        typingTimer = setTimeout(() => {
            $.ajax({
                url: 'public/ajax/action.php?action=check_username',
                method: 'POST',
                data: { username: val, id: <?= $id ?> },
                success: function(resp) {
                    if (resp == 1 && val !== original) {
                        $icon.addClass('fa-times text-danger');
                        $msg.text('Usuario no disponible');
                        $username.addClass('is-invalid');
                    } else {
                        $icon.addClass('fa-check text-success');
                        $msg.text('Disponible');
                        $username.removeClass('is-invalid');
                    }
                }
            });
        }, 500);
    });

    // Enviar formulario
    $form.submit(function(e) {
        e.preventDefault();
        if ($username.hasClass('is-invalid')) {
            alert_toast("El usuario no está disponible", 'error');
            return;
        }
        start_load();
        $.ajax({
            url: 'public/ajax/action.php?action=save_user',
            method: 'POST',
            data: $form.serialize(),
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Usuario guardado", 'success');
                    setTimeout(() => {
                        $('#editUserModal').modal('hide');
                        location.reload();
                    }, 1000);
                } else if (resp == 2) {
                    alert_toast("El usuario ya existe", 'error');
                    end_load();
                } else if (resp == 4) {
                    alert_toast("La contraseña es requerida", 'error');
                    end_load();
                } else {
                    alert_toast("Error: " + resp, 'error');
                    end_load();
                }
            }
        });
    });
});
</script>
