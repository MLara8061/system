<?php require_once 'config/config.php'; ?>

<!-- TARJETAS DE RESUMEN -->
<div class="row mb-4">
    <!-- TOTAL USUARIOS -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-users fa-2x text-primary mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Total Usuarios</h6>
                    <h4 class="mb-0"><?= $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ADMINISTRADORES -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-user-shield fa-2x text-success mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Administradores</h6>
                    <h4 class="mb-0"><?= $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 1")->fetch_assoc()['total'] ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- USUARIOS NORMALES -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-user fa-2x text-info mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Usuarios</h6>
                    <h4 class="mb-0"><?= $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 2")->fetch_assoc()['total'] ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- ÚLTIMO REGISTRO -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-calendar-plus fa-2x text-warning mr-3"></i>
                <div>
                    <h6 class="mb-0 text-muted">Último Creado</h6>
                    <?php 
                    $last = $conn->query("SELECT date_created FROM users ORDER BY date_created DESC LIMIT 1")->fetch_assoc();
                    $fecha = $last && $last['date_created'] > 0 
                        ? date('d/m/Y', $last['date_created']) 
                        : 'N/A';
                    ?>
                    <h4 class="mb-0"><?= $fecha ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE USUARIOS -->
<div class="col-lg-12">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
        <div class="card-header bg-white border-0">
            <div class="card-tools float-right">
                <a href="javascript:void(0)"
                   id="add-new-user-btn"
                   class="text-decoration-none d-flex align-items-center"
                   title="Añadir Nuevo Usuario"
                   aria-label="Añadir nuevo usuario">
                    <i class="fas fa-user-plus text-primary mr-2" style="font-size: 1.1rem;"></i>
                    <span class="text-muted" style="font-size: 0.9rem; font-weight: 900;">Añadir Usuario</span>
                </a>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped" id="user-table">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 60px;">Foto</th>
                        <th>Nombre Completo</th>
                        <th>Rol</th>
                        <th>Usuario</th>
                        <th>Creado</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM users ORDER BY lastname ASC, firstname ASC");
                    while ($row = $qry->fetch_assoc()):
                        $fullname = trim("{$row['firstname']} {$row['middlename']} {$row['lastname']}");
                        $role = $row['role'] == 1 ? '<span class="badge badge-primary">Administrador</span>' : '<span class="badge badge-secondary">Usuario</span>';
                        $avatar = !empty($row['avatar']) && file_exists('assets/avatars/'.$row['avatar']) 
                                ? 'assets/avatars/'.$row['avatar'] 
                                : 'assets/img/default-avatar.png';
                    ?>
                        <tr>
                            <td class="text-center">
                                <img src="<?= $avatar ?>" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #dee2e6;">
                            </td>
                            <td><?= ucwords($fullname) ?></td>
                            <td class="text-center"><?= $role ?></td>
                            <td><code><?= $row['username'] ?></code></td>
                            <td><small><?= date('d/m/Y', $row['date_created']) ?></small></td>

                            <!-- ACCIONES -->
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                            data-toggle="dropdown" title="Opciones">
                                        <i class="fas fa-cogs"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item edit-user" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-edit text-primary mr-2"></i> Editar
                                        </a>
                                        <a class="dropdown-item delete-user text-danger" href="javascript:void(0)" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-trash mr-2"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .card { transition: none !important; }
    .table th { font-weight: 600; font-size: 0.9rem; }
    #user-table .badge { font-size: 0.75rem; }
    code { font-size: 0.85rem; background: #f8f9fa; padding: 2px 6px; border-radius: 4px; }
    .modal-mid-large { max-width: 900px; }
</style>

<script>
$(document).ready(function() {
    const table = $('#user-table').DataTable({
        language: { url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" },
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 5] },
            { className: "text-center", targets: [0, 2, 4, 5] }
        ],
        info: false,
        lengthChange: false
    });

    // === MODAL ÚNICO (NO DUPLICADO) ===
    const $modal = $(`
        <div class="modal fade" id="userModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-mid-large">
                <div class="modal-content border-0 shadow-sm">
                    <div class="modal-header border-0 bg-white pb-0">
                        <h5 class="modal-title text-dark" id="modal-title">
                            <i class="fa fa-user-plus text-primary mr-2"></i> Nuevo Usuario
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">×</button>
                    </div>
                    <div class="modal-body pt-2" id="modal-user-content">
                        <div class="text-center p-4">
                            <i class="fa fa-spinner fa-spin fa-3x text-muted"></i>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-success btn-sm font-weight-bold" id="btn-save">
                            <i class="fas fa-save mr-1"></i> Crear Usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `);

    // Insertar modal una sola vez
    if ($('#userModal').length === 0) {
        $('body').append($modal);
    }

    // === ABRIR MODAL ===
    $(document).on('click', '#add-new-user-btn, .edit-user', function() {
        const isEdit = $(this).hasClass('edit-user');
        const id = isEdit ? $(this).data('id') : 0;
        const url = `manage_user_modal.php${id ? '?id=' + id : ''}`;

        // Actualizar título y botón
        $('#modal-title').html(`
            <i class="fa ${isEdit ? 'fa-edit' : 'fa-user-plus'} text-primary mr-2"></i>
            ${isEdit ? 'Editar Usuario' : 'Nuevo Usuario'}
        `);
        $('#btn-save').html(`
            <i class="fas fa-save mr-1"></i> ${isEdit ? 'Guardar Cambios' : 'Crear Usuario'}
        `);

        // Cargar contenido
        $('#modal-user-content').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-3x text-muted"></i></div>');
        $('#userModal').modal('show');

        $.get(url, function(data) {
            $('#modal-user-content').html(data);
            initializeUserForm(); // ← Inicializa el formulario
        }).fail(function() {
            $('#modal-user-content').html('<p class="text-danger">Error al cargar.</p>');
        });
    });

    // === INICIALIZAR FORMULARIO (SOLO CARGAR CONTENIDO) ===
    function initializeUserForm() {
        const $form = $('#manage-user-form');
        if ($form.length === 0) {
            setTimeout(initializeUserForm, 100); // Reintentar si el form aún no está en el DOM
            return;
        }
        // El formulario ya está inicializado en manage_user_modal.php
    }

    // === BOTÓN GUARDAR (SUBMIT EXPLÍCITO) ===
    $(document).on('click', '#btn-save', function() {
        console.log('Botón guardar clickeado');
        const $form = $('#manage-user-form');
        console.log('Formulario encontrado:', $form.length);
        
        if ($form.length === 0) {
            alert_toast("Error: Formulario no encontrado", 'error');
            return;
        }

        // Validar que el usuario no esté vacío
        const username = $form.find('input[name="username"]').val().trim();
        if (username.length < 3) {
            alert_toast("El usuario debe tener al menos 3 caracteres", 'error');
            return;
        }

        // Obtener datos del formulario
        const formData = new FormData($form[0]);
        console.log('Enviando formulario con datos:', Object.fromEntries(formData));

        start_load();
        $.ajax({
            url: 'ajax.php?action=save_user',
            method: 'POST',
            data: $form.serialize(),
            success: function(resp) {
                end_load();
                console.log('Respuesta del servidor:', resp);
                
                if (resp == 1) {
                    alert_toast("Usuario guardado", 'success');
                    setTimeout(() => {
                        $('#userModal').modal('hide');
                        location.reload();
                    }, 1000);
                } else if (resp == 2) {
                    alert_toast("El usuario ya existe", 'error');
                } else if (resp == 3) {
                    alert_toast("Campos requeridos vacíos", 'error');
                } else if (resp == 4) {
                    alert_toast("Contraseña requerida", 'error');
                } else {
                    alert_toast("Error al guardar: código " + resp, 'error');
                }
            },
            error: function(xhr, status, error) {
                end_load();
                console.error('AJAX Error:', status, error, xhr.responseText);
                alert_toast("Error de conexión: " + error, 'error');
            }
        });
    });

    // === ELIMINAR ===
    $(document).on('click', '.delete-user', function() {
        const id = $(this).data('id');
        _conf("¿Eliminar este usuario?", "delete_user", [id]);
    });

    window.delete_user = function(id) {
        start_load();
        $.ajax({
            url: 'ajax.php?action=delete_user',
            method: 'POST',
            data: { id: id },
            success: function(resp) {
                end_load();
                if (resp == 1) {
                    alert_toast("Usuario eliminado", 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast("Error al eliminar", 'error');
                }
            }
        });
    };
});
</script>