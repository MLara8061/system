# 🔐 Sistema de Permisos por Departamento - Guía de Implementación

## Descripción General

Este sistema permite:
- **Restricción por Departamento**: Cada usuario solo ve los activos de su departamento
- **Control Granular**: Administradores pueden asignar/revocar permisos específicos por rol
- **4 Niveles de Acceso**: Super Admin, Admin Departamento, Usuario, Solo Lectura
- **Permisos por Módulo**: Control fino sobre qué puede hacer cada rol en cada sección

---

## 📋 Pasos de Instalación

### 1. Ejecutar la Migración SQL

```bash
# En la base de datos local
mysql -u root -p < database/migrations/create_permissions_system.sql

# O desde PHP
php -r "require 'config/db.php'; \$pdo->exec(file_get_contents('database/migrations/create_permissions_system.sql'));"
```

**Esto creará**:
- Tabla `roles`
- Tabla `system_modules`
- Tabla `role_permissions`
- Vista `vw_user_permissions`
- 4 roles predeterminados con permisos básicos

### 2. Actualizar Archivos Existentes

#### A. Cargar helpers de permisos globalmente

Editar `config/config.php`, agregar después de la conexión a BD:

```php
// Cargar sistema de permisos
require_once ROOT . '/app/helpers/permissions.php';
```

#### B. Agregar opción al menú de configuración

Editar `app/views/layouts/sidebar.php` o donde esté el menú:

```php
<!-- En la sección de Configuración -->
<?php if (is_admin()): ?>
<li class="nav-item">
    <a href="index.php?page=permissions" class="nav-link">
        <i class="nav-icon fas fa-shield-alt"></i>
        <p>Permisos</p>
    </a>
</li>
<?php endif; ?>
```

#### C. Agregar ruta en routing

Editar `app/routing.php`:

```php
'permissions' => 'app/views/dashboard/permissions.php',
```

### 3. Proteger Módulos Existentes

Agregar verificación de permisos en cada archivo de módulo:

#### Ejemplo: Equipos

En `app/views/dashboard/equipment/list.php`:

```php
<?php
// Al inicio del archivo
require_once 'app/helpers/permissions.php';

// Verificar permiso de ver
if (!can('view', 'equipments')) {
    die('<div class="alert alert-danger">No tienes permisos para ver equipos</div>');
}

// Verificar permiso para botón crear
if (can('create', 'equipments')): ?>
    <button class="btn btn-primary" data-toggle="modal" data-target="#newEquipmentModal">
        <i class="fas fa-plus"></i> Nuevo Equipo
    </button>
<?php endif; ?>

// Verificar permiso para botón editar
<?php if (can('edit', 'equipments')): ?>
    <button class="btn btn-sm btn-warning" onclick="editEquipment(<?= $equipment['id'] ?>)">
        <i class="fas fa-edit"></i>
    </button>
<?php endif; ?>
```

#### Ejemplo: AJAX endpoints

En `public/ajax/action.php` o controladores:

```php
if ($action == 'save_equipment') {
    require_once '../../app/helpers/permissions.php';
    
    $is_edit = !empty($_POST['id']);
    $required_permission = $is_edit ? 'edit' : 'create';
    
    if (!can($required_permission, 'equipments')) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    // Continuar con el guardado...
}
```

### 4. Aplicar Filtro de Departamento

En las consultas SQL de listados:

#### Antes:
```php
$query = "SELECT * FROM equipments WHERE active = 1";
```

#### Después:
```php
$dept_filter = department_filter_sql('WHERE');
$query = "SELECT * FROM equipments WHERE active = 1 $dept_filter";
```

#### Para JOIN con equipment_delivery:
```php
$dept_filter = department_filter_sql('AND', 'ed', 'department_id');
$query = "SELECT e.*, ed.department_id 
          FROM equipments e
          INNER JOIN equipment_delivery ed ON e.id = ed.equipment_id
          WHERE e.active = 1 $dept_filter";
```

---

## 🎯 Uso de las Funciones

### Verificar Permisos

```php
// ¿Es administrador?
if (is_admin()) {
    // Código para admin
}

// ¿Puede ver equipos?
if (can('view', 'equipments')) {
    // Mostrar equipos
}

// ¿Puede crear herramientas?
if (can('create', 'tools')) {
    // Mostrar botón crear
}

// ¿Puede editar accesorios?
if (can('edit', 'accessories')) {
    // Mostrar botón editar
}

// ¿Puede eliminar?
if (can('delete', 'equipments')) {
    // Mostrar botón eliminar
}

// Requerir permiso (redirige si no tiene)
require_permission('edit', 'equipments');
```

### Filtros de Departamento

```php
// ¿Puede ver todos los departamentos?
if (can_view_all_departments()) {
    // No aplicar filtro
} else {
    // Aplicar filtro de departamento
}

// Obtener departamento del usuario
$user_dept = get_user_department();

// Generar SQL automáticamente
$filter = department_filter_sql('WHERE'); // WHERE department_id = X
$filter = department_filter_sql('AND');   // AND department_id = X
$filter = department_filter_sql('AND', 'ed'); // AND ed.department_id = X
```

---

## 📊 Gestión de Permisos (UI)

### Para Administradores:

1. Ir a **Configuración → Permisos**
2. Seleccionar el rol a configurar
3. Marcar/desmarcar los permisos deseados:
   - ✓ **Ver**: Puede ver el módulo
   - ✓ **Crear**: Puede crear nuevos registros
   - ✓ **Editar**: Puede modificar registros
   - ✓ **Eliminar**: Puede eliminar registros
   - ✓ **Exportar**: Puede exportar datos
4. Click en **Guardar Permisos**

---

## 🔄 Asignar Roles a Usuarios

### Opción 1: Desde SQL
```sql
UPDATE users 
SET role_id = 2,              -- Admin Departamento
    department_id = 5,        -- ID del departamento
    can_view_all_departments = 0
WHERE id = 10;
```

### Opción 2: Modificar formulario de usuarios

En `legacy/create_user.php` o `legacy/manage_user.php`:

```php
<!-- Agregar campos -->
<div class="form-group">
    <label>Rol</label>
    <select name="role_id" class="form-control" required>
        <?php
        $roles = $conn->query("SELECT * FROM roles ORDER BY name");
        while ($role = $roles->fetch_assoc()): ?>
            <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label>Departamento</label>
    <select name="department_id" class="form-control" required>
        <option value="">Seleccionar...</option>
        <?php
        $depts = $conn->query("SELECT * FROM departments ORDER BY name");
        while ($dept = $depts->fetch_assoc()): ?>
            <option value="<?= $dept['id'] ?>"><?= $dept['name'] ?></option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="view_all_depts" name="can_view_all_departments" value="1">
        <label class="custom-control-label" for="view_all_depts">
            Puede ver todos los departamentos
        </label>
    </div>
</div>
```

---

## 🚀 Despliegue a Producción

### Script de Despliegue:

```powershell
# 1. Ejecutar localmente para probar
php -r "require 'config/db.php'; \$pdo->exec(file_get_contents('database/migrations/create_permissions_system.sql'));"

# 2. Desplegar código
.\update-subdominios.ps1

# 3. Ejecutar SQL en producción (crear script PHP)
```

### Script PHP para ejecutar en servidor:

```php
<?php
// install_permissions_system.php
require_once 'config/db.php';

$sql = file_get_contents('database/migrations/create_permissions_system.sql');

try {
    $pdo->exec($sql);
    echo "OK: Sistema de permisos instalado correctamente\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
```

Ejecutar:
```bash
ssh -p 65002 u499728070@46.202.197.220 "cd /home/u499728070/domains/activosamerimed.com/public_html/biomedicacun && php install_permissions_system.php"
```

---

## 📝 Ejemplos de Implementación

### Ejemplo Completo: Listado de Equipos

```php
<?php
require_once 'config/config.php';
require_once 'app/helpers/permissions.php';

// Verificar permiso básico
if (!can('view', 'equipments')) {
    die('Sin permisos');
}

// Aplicar filtro de departamento
$dept_filter = department_filter_sql('WHERE');

// Consulta con filtro
$query = "SELECT e.*, ed.department_id, d.name as dept_name
          FROM equipments e
          INNER JOIN equipment_delivery ed ON e.id = ed.equipment_id
          LEFT JOIN departments d ON ed.department_id = d.id
          WHERE e.active = 1 $dept_filter
          ORDER BY e.number_inventory DESC";

$equipments = $conn->query($query);
?>

<!-- Botones según permisos -->
<div class="card-header">
    <h3>Equipos</h3>
    <?php if (can('create', 'equipments')): ?>
        <button class="btn btn-primary" onclick="newEquipment()">
            <i class="fas fa-plus"></i> Nuevo
        </button>
    <?php endif; ?>
    
    <?php if (can('export', 'equipments')): ?>
        <button class="btn btn-success" onclick="exportEquipments()">
            <i class="fas fa-file-excel"></i> Exportar
        </button>
    <?php endif; ?>
</div>

<!-- Tabla -->
<table class="table">
    <?php while ($eq = $equipments->fetch_assoc()): ?>
        <tr>
            <td><?= $eq['number_inventory'] ?></td>
            <td><?= $eq['name'] ?></td>
            <td><?= $eq['dept_name'] ?></td>
            <td>
                <?php if (can('edit', 'equipments')): ?>
                    <button class="btn btn-sm btn-warning" onclick="editEquipment(<?= $eq['id'] ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                <?php endif; ?>
                
                <?php if (can('delete', 'equipments')): ?>
                    <button class="btn btn-sm btn-danger" onclick="deleteEquipment(<?= $eq['id'] ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
</table>
```

---

## ⚠️ Consideraciones Importantes

1. **Cache de Permisos**: Los permisos se cachean en la sesión. Para forzar recarga:
   ```php
   clear_permission_cache();
   ```

2. **Admin Siempre Tiene Acceso**: Los administradores globales (`is_admin = 1`) siempre tendrán acceso total, independientemente de los permisos configurados.

3. **Compatibilidad**: El sistema es compatible con el filtro de sucursales (`branch_sql`) existente.

4. **Migración Gradual**: Puedes implementar el sistema gradualmente, módulo por módulo.

---

## 🎨 Personalización

### Agregar Nuevos Módulos

```sql
INSERT INTO system_modules (code, name, description, icon, `order`) 
VALUES ('invoices', 'Facturas', 'Gestión de facturas', 'fas fa-file-invoice', 100);
```

### Crear Nuevos Roles

```sql
INSERT INTO roles (name, description, is_admin) 
VALUES ('Técnico', 'Personal técnico de mantenimiento', 0);
```

---

## 📞 Soporte

Si tienes dudas durante la implementación:
1. Revisar logs en `logs/` o consola de PHP
2. Verificar que las tablas se crearon correctamente
3. Probar con un usuario de prueba primero

