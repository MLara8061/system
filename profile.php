<?php 
include 'db_connect.php';
if (!isset($_SESSION['login_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['login_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$avatar_path = !empty($user['avatar']) ? 'assets/avatars/'.$user['avatar'] : 'assets/img/default-avatar.png';
?>

<div class="container-fluid">
    <div class="card shadow-sm border-0" style="border-radius: 16px; max-width: 600px; margin: 20px auto;">
        <div class="card-header bg-white border-0 pb-0">
            <h5 class="modal-title text-dark mb-0">
                <i class="fa fa-user-edit text-primary mr-2"></i> Mi Perfil
            </h5>
        </div>
        <div class="card-body pt-3">
            <form id="update-my-profile" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $user_id ?>">
                <input type="hidden" name="avatar" id="avatar-input" value="<?= $user['avatar'] ?? '' ?>">

                <!-- FOTO DE PERFIL -->
                <div class="text-center mb-4">
                    <div class="avatar-container position-relative d-inline-block">
                        <img src="<?= $avatar_path ?>" alt="Avatar" id="avatar-preview" 
                             class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #e9ecef;">
                        <label for="avatar-upload" class="btn btn-primary btn-sm rounded-circle position-absolute" 
                               style="bottom: 0; right: 0; width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <input type="file" id="avatar-upload" accept="image/*" class="d-none">
                    <p class="text-muted mt-2 mb-0">Haz clic para cambiar foto</p>
                </div>

                <!-- NOMBRE -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Nombre</strong></label>
                    <input type="text" name="firstname" class="form-control form-control-sm" 
                           value="<?= $user['firstname'] ?>" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold"><strong>Segundo Nombre</strong></label>
                    <input type="text" name="middlename" class="form-control form-control-sm" 
                           value="<?= $user['middlename'] ?>">
                </div>

                <div class="form-group">
                    <label class="font-weight-bold"><strong>Apellido</strong></label>
                    <input type="text" name="lastname" class="form-control form-control-sm" 
                           value="<?= $user['lastname'] ?>" required>
                </div>

                <!-- USUARIO -->
                <div class="form-group">
                    <label class="font-weight-bold"><strong>Usuario</strong></label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="username" id="username" class="form-control form-control-sm" 
                               value="<?= $user['username'] ?>" required>
                        <div class="input-group-append">
                            <span class="input-group-text bg-white border-0">
                                <i id="username-status-icon" class="fas"></i>
                            </span>
                        </div>
                    </div>
                    <small id="username-feedback" class="text-muted d-block mt-1"></small>
                </div>

                <!-- CONTRASEÑA -->
                <div class="form-group">
                    <label class="font-weight-bold">
                        <strong>Nueva Contraseña</strong> 
                        <small class="text-muted">(Dejar vacío para no cambiar)</small>
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="password" name="password" id="password" class="form-control form-control-sm">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <small id="password-strength" class="text-muted d-block mt-1"></small>
                </div>

                <!-- BOTONES -->
                <div class="form-group text-right mt-4">
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DE RECORTE -->
<div class="modal fade" id="cropModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recortar Imagen</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-body text-center">
                <img id="crop-image" src="" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="crop-save">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- CROP CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<style>
    .avatar-container { transition: all 0.2s; }
    .avatar-container:hover { transform: scale(1.05); }
    #crop-image { max-width: 100%; height: auto; }
    .cropper-container { max-height: 400px; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

<script>
$(document).ready(function() {
    let cropper;
    const $form = $('#update-my-profile');
    const $avatarUpload = $('#avatar-upload');
    const $avatarPreview = $('#avatar-preview');
    const $avatarInput = $('#avatar-input');
    const $cropModal = $('#cropModal');
    const $cropImage = $('#crop-image');
    const $cropSave = $('#crop-save');
    const $username = $('#username');
    const $usernameFeedback = $('#username-feedback');
    const $usernameIcon = $('#username-status-icon');
    const $password = $('#password');
    const $passwordStrength = $('#password-strength');
    const $toggleBtn = $('#toggle-password');
    const originalUsername = '<?= $user['username'] ?>';

    // === SUBIR Y RECORTAR IMAGEN ===
    $avatarUpload.on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            $cropImage.attr('src', e.target.result);
            $cropModal.modal('show');
            setTimeout(() => {
                if (cropper) cropper.destroy();
                cropper = new Cropper($cropImage[0], {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 0.8,
                    responsive: true
                });
            }, 100);
        };
        reader.readAsDataURL(file);
    });

    $cropSave.click(function() {
        if (!cropper) return;
        cropper.getCroppedCanvas({ width: 300 }).toBlob(function(blob) {
            const formData = new FormData();
            formData.append('avatar', blob, 'avatar.jpg');
            formData.append('id', <?= $user_id ?>);

            start_load();
            $.ajax({
                url: 'ajax.php?action=upload_avatar',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp) {
                    if (resp) {
                        const timestamp = new Date().getTime();
                        $avatarPreview.attr('src', resp + '?t=' + timestamp);
                        $avatarInput.val(resp.split('/').pop());
                        $cropModal.modal('hide');
                        alert_toast("Foto actualizada", 'success');
                    } else {
                        alert_toast("Error al subir foto", 'danger');
                    }
                    end_load();
                },
                error: function() {
                    alert_toast("Error de conexión", 'danger');
                    end_load();
                }
            });
        }, 'image/jpeg', 0.95);
    });

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

        $usernameIcon.removeClass('fa-check-circle fa-times-circle text-success text-danger');
        $usernameFeedback.text('').removeClass('text-success text-danger');

        if (val === originalUsername || val.length < 3) return;

        usernameTimeout = setTimeout(() => {
            $.ajax({
                url: 'ajax.php?action=check_username',
                method: 'POST',
                data: { username: val, id: <?= $user_id ?> },
                success: function(resp) {
                    if (resp == 1) {
                        $usernameIcon.addClass('fa-times-circle text-danger');
                        $usernameFeedback.html('No disponible').addClass('text-danger');
                    } else {
                        $usernameIcon.addClass('fa-check-circle text-success');
                        $usernameFeedback.html('Disponible').addClass('text-success');
                    }
                }
            });
        }, 500);
    });

    // === FUERZA DE CONTRASEÑA ===
    $password.on('input', function() {
        const val = $(this).val();
        let strength = '';
        if (val.length === 0) {
            strength = '<span class="text-info">Sin cambios</span>';
        } else if (val.length < 6) {
            strength = '<span class="text-danger">Débil</span>';
        } else if (val.length < 10) {
            strength = '<span class="text-warning">Media</span>';
        } else {
            strength = '<span class="text-success">Fuerte</span>';
        }
        $passwordStrength.html(strength);
    });

    // === GUARDAR PERFIL (CORREGIDO) ===
    $form.submit(function(e) {
        e.preventDefault();

        // Solo bloquear si el usuario cambió y está duplicado
        const currentVal = $username.val().trim();
        if (currentVal !== originalUsername && $usernameFeedback.hasClass('text-danger')) {
            alert_toast("El usuario no está disponible", 'danger');
            return;
        }

        start_load();
        $.ajax({
            url: 'ajax.php?action=save_user',
            method: 'POST',
            data: $form.serialize(),
            success: function(resp) {
                // resp = 1 → éxito, 2 → usuario duplicado, 0 → error
                if (resp == 1) {
                    alert_toast("Perfil actualizado correctamente", 'success');
                    setTimeout(() => location.reload(), 1200);
                } else if (resp == 2) {
                    alert_toast("El nombre de usuario ya está en uso", 'danger');
                    end_load();
                } else {
                    alert_toast("Error al guardar los cambios", 'danger');
                    end_load();
                }
            },
            error: function(xhr, status, error) {
                alert_toast("Error de conexión: " + error, 'danger');
                end_load();
            }
        });
    });
});
</script>