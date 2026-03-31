<?php 
require_once 'config/config.php';
if (!isset($_SESSION['login_id'])) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/app/views/auth/login.php');
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
                             class="rounded-circle avatar-image" style="width: 140px; height: 140px; object-fit: cover; border: 3px solid #e9ecef;">
                        
                        <!-- Botón Cámara -->
                        <label for="avatar-upload" class="avatar-camera-btn" title="Cambiar foto">
                            <i class="fas fa-camera"></i>
                        </label>
                        
                        <!-- Botón Eliminar -->
                        <?php if (!empty($user['avatar'])): ?>
                        <button type="button" class="avatar-delete-btn" id="delete-avatar-btn" title="Eliminar foto">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="avatar-upload" accept="image/jpeg,image/png,image/jpg,image/webp" class="d-none">
                    <p class="text-muted mt-3 mb-0" style="font-size: 0.875rem;">Haz clic en <i class="fas fa-camera text-primary"></i> para cambiar</p>
                </div>

                <!-- NOMBRE -->
                <div class="form-group">
                    <label for="prof-firstname" class="font-weight-bold"><strong>Nombre</strong></label>
                    <input type="text" name="firstname" id="prof-firstname" class="form-control form-control-sm" 
                           value="<?= $user['firstname'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="prof-middlename" class="font-weight-bold"><strong>Segundo Nombre</strong></label>
                    <input type="text" name="middlename" id="prof-middlename" class="form-control form-control-sm" 
                           value="<?= $user['middlename'] ?>">
                </div>

                <div class="form-group">
                    <label for="prof-lastname" class="font-weight-bold"><strong>Apellido</strong></label>
                    <input type="text" name="lastname" id="prof-lastname" class="form-control form-control-sm" 
                           value="<?= $user['lastname'] ?>" required>
                </div>

                <!-- USUARIO -->
                <div class="form-group">
                    <label for="username" class="font-weight-bold"><strong>Usuario</strong></label>
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
                <button type="button" class="close" data-dismiss="modal">&times;</button>
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
<link rel="stylesheet" href="assets/plugins/cropperjs/css/cropper.min.css">
<style>
    /* === AVATAR CONTAINER === */
    .avatar-container {
        position: relative;
        display: inline-block;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .avatar-container:hover .avatar-image {
        transform: scale(1.02);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .avatar-image {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* === BOTÓN CÁMARA (Moderno y Compacto) === */
    .avatar-camera-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: 3px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        z-index: 2;
    }
    .avatar-camera-btn:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }
    .avatar-camera-btn i {
        color: #fff;
        font-size: 1rem;
        margin: 0;
    }

    /* === BOTÓN ELIMINAR (Icono Basura Rojo) === */
    .avatar-delete-btn {
        position: absolute;
        top: 0;
        right: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        border: 3px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(238, 90, 111, 0.4);
        z-index: 2;
        padding: 0;
    }
    .avatar-delete-btn:hover {
        transform: scale(1.15) rotate(-5deg);
        box-shadow: 0 6px 16px rgba(238, 90, 111, 0.6);
        background: linear-gradient(135deg, #ee5a6f 0%, #ff6b6b 100%);
    }
    .avatar-delete-btn i {
        color: #fff;
        font-size: 0.8rem;
        margin: 0;
    }

    /* === CROPPER === */
    #crop-image { max-width: 100%; height: auto; }
    .cropper-container { max-height: 400px; }

    /* === RESPONSIVE (MÓVIL) === */
    @media (max-width: 768px) {
        .avatar-image {
            width: 120px !important;
            height: 120px !important;
        }
        .avatar-camera-btn {
            width: 36px;
            height: 36px;
            bottom: 3px;
            right: 3px;
        }
        .avatar-camera-btn i {
            font-size: 0.9rem;
        }
        .avatar-delete-btn {
            width: 28px;
            height: 28px;
            top: -2px;
            right: -2px;
        }
        .avatar-delete-btn i {
            font-size: 0.7rem;
        }
    }

    @media (max-width: 576px) {
        .avatar-image {
            width: 100px !important;
            height: 100px !important;
        }
        .avatar-camera-btn {
            width: 32px;
            height: 32px;
        }
        .avatar-camera-btn i {
            font-size: 0.8rem;
        }
        .avatar-delete-btn {
            width: 26px;
            height: 26px;
        }
        .avatar-delete-btn i {
            font-size: 0.65rem;
        }
    }
</style>

<script src="assets/plugins/cropperjs/js/cropper.min.js"></script>

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

    // === VALIDAR FORMATO DE IMAGEN ===
    $avatarUpload.on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const validFormats = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!validFormats.includes(ext)) {
                alert_toast('Formato no permitido. Solo se aceptan archivos JPG, PNG y WebP', 'error');
                $(this).val('');
                return false;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert_toast('La imagen es muy grande. Máximo 5MB', 'error');
                $(this).val('');
                return false;
            }
        }
    });

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
                url: 'public/ajax/action.php?action=upload_avatar',
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
                        location.reload();
                    } else {
                        alert_toast("Error al subir foto", 'error');
                    }
                    end_load();
                },
                error: function() {
                    alert_toast("Error de conexión", 'error');
                    end_load();
                }
            });
        }, 'image/jpeg', 0.95);
    });

    // === ELIMINAR AVATAR ===
    $('#delete-avatar-btn').click(function() {
        if (!confirm('¿Estás seguro de eliminar tu foto de perfil?')) return;
        
        start_load();
        $.ajax({
            url: 'public/ajax/action.php?action=delete_avatar',
            method: 'POST',
            data: { id: <?= $user_id ?> },
            success: function(resp) {
                if (resp == 1) {
                    alert_toast("Foto eliminada correctamente", 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    alert_toast("Error al eliminar la foto", 'error');
                    end_load();
                }
            },
            error: function() {
                alert_toast("Error de conexión", 'error');
                end_load();
            }
        });
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
                url: 'public/ajax/action.php?action=check_username',
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
            alert_toast("El usuario no está disponible", 'error');
            return;
        }

        start_load();
        $.ajax({
            url: 'public/ajax/action.php?action=save_user',
            method: 'POST',
            data: $form.serialize(),
            success: function(resp) {
                // resp = 1 → éxito, 2 → usuario duplicado, 0 → error
                if (resp == 1) {
                    alert_toast("Perfil actualizado correctamente", 'success');
                    setTimeout(() => location.reload(), 1200);
                } else if (resp == 2) {
                    alert_toast("El nombre de usuario ya está en uso", 'error');
                    end_load();
                } else {
                    alert_toast("Error al guardar los cambios", 'error');
                    end_load();
                }
            },
            error: function(xhr, status, error) {
                alert_toast("Error de conexión: " + error, 'error');
                end_load();
            }
        });
    });
});
</script>
