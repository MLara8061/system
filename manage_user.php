<?php
include('db_connect.php');

$utype = array('', 'users', 'staff', 'customers');

$user_table = $utype[$_SESSION['login_type']] ?? 'users';

if (isset($_GET['id'])) {
    $user = $conn->query("SELECT * FROM $user_table WHERE id = " . $_GET['id']);
    if ($user && $user->num_rows > 0) {
        foreach ($user->fetch_array() as $k => $v) {
            $meta[$k] = $v;
        }
    }
}
?>
<div class="container-fluid">
    <div id="msg"></div>

    <form id="manage-user">
        <input type="hidden" name="id" value="<?= $meta['id'] ?? '' ?>">
        <input type="hidden" name="table" value="<?= $user_table ?>">

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="firstname" class="form-control" value="<?= $meta['firstname'] ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label>Segundo Nombre</label>
            <input type="text" name="middlename" class="form-control" value="<?= $meta['middlename'] ?? '' ?>">
        </div>
        <div class="form-group">
            <label>Apellido</label>
            <input type="text" name="lastname" class="form-control" value="<?= $meta['lastname'] ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label>Usuario/Correo</label>
            <input type="text" name="username" class="form-control" value="<?= $meta['username'] ?? '' ?>" required>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
        </div>

        <div class="form-group text-right">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php?page=user_list" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
$('#manage-user').submit(function(e) {
    e.preventDefault();
    start_load();
    $.ajax({
        url: 'ajax.php?action=save_user',
        method: 'POST',
        data: $(this).serialize(),
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Usuario guardado", 'success');
                setTimeout(() => location.href = 'index.php?page=user_list', 1500);
            } else if (resp == 2) {
                $('#msg').html('<div class="alert alert-danger">Usuario ya existe</div>');
                end_load();
            } else {
                alert_toast("Error", 'error');
                end_load();
            }
        }
    });
});
</script>