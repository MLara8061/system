<?php
require_once 'config/config.php';
$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$user = $is_edit ? $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc() : [];
?>

<form id="manage-user-form">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-group">
        <label for="firstname"><strong>Nombre</strong></label>
        <input type="text" name="firstname" id="firstname" class="form-control form-control-sm"
               value="<?= $user['firstname'] ?? '' ?>" required>
    </div>

    <div class="form-group">
        <label for="middlename"><strong>Segundo Nombre</strong></label>
        <input type="text" name="middlename" id="middlename" class="form-control form-control-sm"
               value="<?= $user['middlename'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label for="lastname"><strong>Apellido</strong></label>
        <input type="text" name="lastname" id="lastname" class="form-control form-control-sm"
               value="<?= $user['lastname'] ?? '' ?>" required>
    </div>

    <div class="form-group">
        <label for="username"><strong>Usuario</strong></label>
        <div class="input-group input-group-sm">
            <input type="text" name="username" id="username" class="form-control form-control-sm"
                   value="<?= $user['username'] ?? '' ?>" required>
            <div class="input-group-append">
                <span class="input-group-text bg-white border-0">
                    <i id="username-status-icon" class="fas"></i>
                </span>
            </div>
        </div>
        <small id="username-feedback" class="text-muted d-block mt-1"></small>
    </div>

    <div class="form-group">
        <label for="role"><strong>Rol</strong></label>
        <select name="role" id="role" class="form-control form-control-sm" required>
            <option value="">-- Seleccionar --</option>
            <option value="1" <?= ($user['role'] ?? '') == 1 ? 'selected' : '' ?>>Administrador</option>
            <option value="2" <?= ($user['role'] ?? '') == 2 ? 'selected' : '' ?>>Usuario</option>
        </select>
    </div>

    <div class="form-group">
        <label for="password"><strong>Contraseña</strong>
            <small class="text-muted">
                <?= $is_edit ? '(Dejar vacío para no cambiar)' : '(Requerida)' ?>
            </small>
        </label>
        <input type="password" name="password" id="password" class="form-control form-control-sm"
               placeholder="<?= $is_edit ? 'Nueva contraseña' : 'Contraseña segura' ?>"
               <?= !$is_edit ? 'required' : '' ?>>
    </div>
</form>

<script>
// El formulario se carga dinámicamente, así que ejecutamos directamente
(function() {
    console.log("Inicializando validación de usuario");
    
    const $form = $('#manage-user-form');
    if ($form.length === 0) {
        console.error("Formulario no encontrado!");
        return;
    }

    const $username = $('#username');
    const $feedback = $('#username-feedback');
    const $icon = $('#username-status-icon');
    const originalUsername = $username.val();
    const isEdit = parseInt($form.find('input[name="id"]').val()) > 0;
    let typingTimer;

    console.log("Validación inicializada. isEdit:", isEdit);

    // Validación en tiempo real del username
    $username.off('input').on('input', function() {
        clearTimeout(typingTimer);
        const val = $(this).val().trim();
        $icon.removeClass('fa-check-circle fa-times-circle text-success text-danger');
        $feedback.text('').removeClass('text-success text-danger');

        if (val.length < 3 || (isEdit && val === originalUsername)) return;

        typingTimer = setTimeout(() => {
            $.ajax({
                url: 'ajax.php?action=check_username',
                method: 'POST',
                data: { 
                    username: val, 
                    id: $form.find('input[name="id"]').val() 
                },
                success: function(resp) {
                    console.log('check_username respuesta:', resp);
                    if (resp == 1) {
                        $icon.addClass('fa-times-circle text-danger');
                        $feedback.text('No disponible').addClass('text-danger');
                        $username.addClass('is-invalid');
                    } else {
                        $icon.addClass('fa-check-circle text-success');
                        $feedback.text('Disponible').addClass('text-success');
                        $username.removeClass('is-invalid');
                    }
                },
                error: function() {
                    console.error('Error en check_username');
                }
            });
        }, 500);
    });
})();
</script>