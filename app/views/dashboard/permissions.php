<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/permissions.php';

if (!is_admin()) {
    echo '<div class="alert alert-danger">Acceso denegado.</div>';
    exit;
}

$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$selected_role_id = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;

$roles = [];
$roles_qry = $conn->query("SELECT id, name, description, is_admin FROM roles ORDER BY is_admin DESC, name ASC");
if ($roles_qry) {
    while ($row = $roles_qry->fetch_assoc()) {
        $roles[] = $row;
    }
}

$users = [];
$selected_user = null;
$user_sql = "SELECT u.id, u.username, u.firstname, u.middlename, u.lastname, u.role_id, u.role, u.department_id, u.can_view_all_departments, d.name as department_name 
             FROM users u 
             LEFT JOIN departments d ON u.department_id = d.id 
             ORDER BY u.lastname ASC, u.firstname ASC";
$user_qry = $conn->query($user_sql);
if ($user_qry) {
    while ($row = $user_qry->fetch_assoc()) {
        $fullname = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
        if ($fullname === '') {
            $fullname = $row['username'];
        }
        $row['fullname'] = $fullname;
        $row['resolved_role_id'] = (int)($row['role_id'] ?: $row['role'] ?: 0);
        $users[] = $row;

        if ($row['id'] == $selected_user_id) {
            $selected_user = $row;
        }
    }
}

if (!$selected_role_id) {
    if ($selected_user && $selected_user['resolved_role_id'] > 0) {
        $selected_role_id = $selected_user['resolved_role_id'];
    } elseif (!empty($roles)) {
        $selected_role_id = (int)$roles[0]['id'];
    }
}

$selected_role = null;
foreach ($roles as $role) {
    if ((int)$role['id'] === (int)$selected_role_id) {
        $selected_role = $role;
        break;
    }
}

$modules = [];
$modules_qry = $conn->query("SELECT code, name, description, icon FROM system_modules WHERE active = 1 ORDER BY `order` ASC, name ASC");
if ($modules_qry) {
    while ($row = $modules_qry->fetch_assoc()) {
        $modules[] = $row;
    }
}

// Obtener todos los departamentos para los selectores
$departments = [];
$dept_qry = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
if ($dept_qry && $dept_qry->num_rows > 0) {
    while ($row = $dept_qry->fetch_assoc()) {
        $departments[] = $row;
    }
}

$current_permissions = [];
if ($selected_role) {
    if ((int)$selected_role['is_admin'] === 1) {
        foreach ($modules as $module) {
            $current_permissions[$module['code']] = [
                'can_view' => 1,
                'can_create' => 1,
                'can_edit' => 1,
                'can_delete' => 1,
                'can_export' => 1
            ];
        }
    } else {
        $perm_sql = "SELECT module_code, can_view, can_create, can_edit, can_delete, can_export FROM role_permissions WHERE role_id = $selected_role_id";
        $perm_qry = $conn->query($perm_sql);
        if ($perm_qry) {
            while ($row = $perm_qry->fetch_assoc()) {
                $current_permissions[$row['module_code']] = [
                    'can_view' => (int)$row['can_view'],
                    'can_create' => (int)$row['can_create'],
                    'can_edit' => (int)$row['can_edit'],
                    'can_delete' => (int)$row['can_delete'],
                    'can_export' => (int)$row['can_export']
                ];
            }
        }
    }
}
?>
<style>
.permissions-page h2 {
    font-weight: 700;
}

.permissions-table th, .permissions-table td {
    vertical-align: middle;
}

.permissions-table th {
    font-weight: 600;
    font-size: 0.95rem;
}

.permissions-table td {
    font-size: 0.93rem;
}

.permission-checkbox {
    transform: scale(1.15);
    cursor: pointer;
}

.permission-checkbox:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.module-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #e9f2fb;
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-size: 1rem;
}

.role-pill {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.role-pill.admin {
    background: #ffe5e0;
    color: #c53030;
}

.role-pill.normal {
    background: #e8f5e9;
    color: #1b5e20;
}
</style>

<div class="container-fluid permissions-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Roles y Permisos</h2>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold mb-2"><i class="fas fa-user mr-1"></i> Seleccionar Usuario</label>
                    <select id="user-selector" class="form-control form-control-lg">
                        <option value="">-- Selecciona un usuario --</option>
                        <?php foreach ($users as $user): ?>
                            <option 
                                value="<?= $user['id'] ?>"
                                data-role-id="<?= $user['resolved_role_id'] ?>"
                                data-department="<?= htmlspecialchars($user['department_name'] ?: 'Sin departamento') ?>"
                                data-view-all="<?= (int)($user['can_view_all_departments'] ?? 0) ?>"
                                <?= $selected_user_id === (int)$user['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($user['fullname']) ?>
                                (<?= htmlspecialchars($user['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_user): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="small font-weight-bold mb-1">
                                        <i class="fas fa-building mr-1"></i> Departamento
                                    </label>
                                    <select id="user-department" name="user_department_id" class="form-control form-control-sm">
                                        <option value="">Sin asignar</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>" <?= (int)($selected_user['department_id'] ?? 0) === (int)$dept['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="small font-weight-bold mb-1">
                                        <i class="fas fa-eye mr-1"></i> Acceso multi-departamental
                                    </label>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="user-view-all" 
                                               name="user_can_view_all_departments" value="1"
                                               <?= (int)($selected_user['can_view_all_departments'] ?? 0) === 1 ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="user-view-all">
                                            Puede ver todos los departamentos
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold mb-2"><i class="fas fa-user-tag mr-1"></i> Rol</label>
                    <select id="role-selector" class="form-control form-control-lg">
                        <?php foreach ($roles as $role): ?>
                            <option 
                                value="<?= $role['id'] ?>" 
                                <?= (int)$role['id'] === (int)$selected_role_id ? 'selected' : '' ?>
                                data-description="<?= htmlspecialchars($role['description']) ?>"
                                data-admin="<?= (int)$role['is_admin'] ?>"
                            >
                                <?= htmlspecialchars($role['name']) ?> <?= $role['is_admin'] ? '(Administrador Global)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($selected_role): ?>
                        <div class="mt-2">
                            <span class="role-pill <?= (int)$selected_role['is_admin'] === 1 ? 'admin' : 'normal' ?>">
                                <?= (int)$selected_role['is_admin'] === 1 ? 'Admin global' : 'Rol normal' ?>
                            </span>
                            <?php if (!empty($selected_role['description'])): ?>
                                <div class="text-muted small mt-1"><?= htmlspecialchars($selected_role['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($selected_role && (int)$selected_role['is_admin'] === 1): ?>
        <div class="alert alert-info border-0 shadow-sm">
            <i class="fas fa-info-circle mr-1"></i> Los administradores globales tienen acceso total. Los permisos especificos no son editables.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form id="permissions-form" method="POST" action="public/ajax/permissions.php?action=save">
                <input type="hidden" name="role_id" value="<?= $selected_role_id ?>">
                <?php if ($selected_user): ?>
                    <input type="hidden" name="user_id" value="<?= $selected_user['id'] ?>">
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table permissions-table mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width: 32%;">Modulo</th>
                                <th class="text-center" style="width: 13%;">Ver</th>
                                <th class="text-center" style="width: 13%;">Crear</th>
                                <th class="text-center" style="width: 13%;">Editar</th>
                                <th class="text-center" style="width: 13%;">Eliminar</th>
                                <th class="text-center" style="width: 13%;">Exportar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): 
                                $perms = $current_permissions[$module['code']] ?? [
                                    'can_view' => 0,
                                    'can_create' => 0,
                                    'can_edit' => 0,
                                    'can_delete' => 0,
                                    'can_export' => 0
                                ];
                                $is_admin_role = $selected_role && (int)$selected_role['is_admin'] === 1;
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="module-icon">
                                                <i class="<?= htmlspecialchars($module['icon']) ?>"></i>
                                            </span>
                                            <div>
                                                <div class="font-weight-bold mb-0"><?= htmlspecialchars($module['name']) ?></div>
                                                <?php if (!empty($module['description'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($module['description']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <?php foreach (['can_view','can_create','can_edit','can_delete','can_export'] as $field): ?>
                                        <td class="text-center">
                                            <input 
                                                type="checkbox"
                                                class="permission-checkbox"
                                                name="permissions[<?= $module['code'] ?>][<?= $field ?>]"
                                                value="1"
                                                <?= !empty($perms[$field]) || $is_admin_role ? 'checked' : '' ?>
                                                <?= $is_admin_role ? 'disabled' : '' ?>
                                            >
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($selected_role && (int)$selected_role['is_admin'] !== 1): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary btn-lg mr-2" onclick="location.reload();">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save mr-1"></i> Guardar permisos
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    const $user = $('#user-selector');
    const $role = $('#role-selector');

    $user.on('change', function() {
        const userId = $(this).val();
        const roleId = $(this).find(':selected').data('role-id') || $role.val();
        let url = 'index.php?page=permissions';
        if (roleId) url += '&role_id=' + roleId;
        if (userId) url += '&user_id=' + userId;
        window.location.href = url;
    });

    $role.on('change', function() {
        const roleId = $(this).val();
        const userId = $user.val();
        let url = 'index.php?page=permissions';
        if (roleId) url += '&role_id=' + roleId;
        if (userId) url += '&user_id=' + userId;
        window.location.href = url;
    });

    $('#permissions-form').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado exitoso',
                    text: 'Permisos, departamento y acceso actualizados correctamente.',
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'No se pudieron guardar los cambios.'
                });
            }
        }).fail(function(xhr, status, error) {
            console.log('XHR Response:', xhr.responseText);
            console.log('Status:', xhr.status);
            
            let errorMessage = 'Error ' + xhr.status;
            let errorHtml = '<div style="text-align:left;">';
            
            // Intentar parsear JSON
            try {
                const response = JSON.parse(xhr.responseText);
                errorHtml += '<p><strong>Mensaje:</strong> ' + (response.message || 'Sin mensaje') + '</p>';
                
                if (response.debug) {
                    errorHtml += '<p><strong>Información de debug:</strong></p>';
                    errorHtml += '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;font-size:11px;max-height:300px;overflow:auto;">';
                    errorHtml += JSON.stringify(response.debug, null, 2);
                    errorHtml += '</pre>';
                }
            } catch (e) {
                errorHtml += '<p><strong>Respuesta del servidor:</strong></p>';
                errorHtml += '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;font-size:11px;max-height:300px;overflow:auto;">';
                errorHtml += xhr.responseText || 'Sin respuesta';
                errorHtml += '</pre>';
            }
            
            errorHtml += '<p style="margin-top:10px;"><small>Status: ' + status + ' | Error: ' + error + '</small></p>';
            errorHtml += '</div>';
            
            Swal.fire({
                icon: 'error',
                title: errorMessage,
                html: errorHtml,
                width: '700px',
                customClass: {
                    htmlContainer: 'text-left'
                }
            });
        });
    });
});
</script>
