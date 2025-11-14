<?php 
include 'db_connect.php';
$id = $_GET['id'] ?? 0;
$is_edit = $id > 0;
$user = $is_edit ? $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc() : [];
?>

<div class="container-fluid">
    <form id="manage-user-form">
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- NOMBRE COMPLETO -->
        <div class="form-group">
            <label><strong>Nombre</strong></label>
            <input type="text" name="firstname" class="form-control" value="<?= $user['firstname'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label><strong>Segundo Nombre</strong></label>
            <input type="text" name="middlename" class="form-control" value="<?= $user['middlename'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label><strong>Apellido</strong></label>
            <input type="text" name="lastname" class="form-control" value="<?= $user['lastname'] ?? '' ?>" required>
        </div>

        <!-- USUARIO Y ROL -->
        <div class="form-group">
            <label><strong>Usuario</strong></label>
            <input type="text" name="username" class="form-control" value="<?= $user['username'] ?? '' ?>" required>
        </div>

        <div class="form-group">
            <label><strong>Rol</strong></label>
            <select name="role" class="form-control" required>
                <option value="1" <?= ($user['role'] ?? '') == 1 ? 'selected' : '' ?>>Administrador</option>
                <option value="2" <?= ($user['role'] ?? '') == 2 ? 'selected' : '' ?>>Usuario</option>
            </select>
        </div>

        <!-- CONTRASEÑA -->
        <div class="form-group">
            <label><strong>Contraseña</strong> <small class="text-muted">(Dejar vacío para no cambiar)</small></label>
            <input type="password" name="password" class="form-control" placeholder="Nueva contraseña">
        </div>
    </form>
</div>

<script>
$('#manage-user-form').submit(function(e) {
    e.preventDefault();
    start_load();
    $.ajax({
        url: 'ajax.php?action=save_user',
        method: 'POST',
        data: $(this).serialize(),
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Usuario guardado", 'success');
                setTimeout(() => {
                    $('#editUserModal').modal('hide');
                    location.reload();
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
</script>