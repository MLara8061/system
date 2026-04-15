# HOJA DE RUTA: MIGRACIÓN A SISTEMA DE PERMISOS GRANULAR

**Estado Actual**: Sistema hibrido (login_type + roles/permisos)  
**Objetivo**: Sistema de permisos 100% granular y consistente  
**Tiempo estimado**: 8-10 horas

---

## FASE 1: AUDITORÍA Y DOCUMENTACIÓN (Completada)

- ✅ Identificar vulnerabilidades críticas
- ✅ Crear auditoría de seguridad
- ✅ Documentar pruebas
- ✅ Proteger funciones críticas delete_*

---

## FASE 2: PROTECCIÓN INMEDIATA (Próximo - Crítico)

### Objetivo: Proteger el 100% de funciones delete_*

**Funciones a proteger** (19 total):

```
delete_user()           ✅ HECHO
delete_equipment()      ✅ HECHO
delete_customer()       ✅ HECHO
delete_staff()          ✅ HECHO
delete_supplier()       ✅ HECHO
delete_department()     ⏳ PENDIENTE
delete_branch()         ⏳ PENDIENTE
delete_ticket()         ⏳ PENDIENTE
delete_comment()        ⏳ PENDIENTE
delete_service()        ⏳ PENDIENTE
delete_inventory()      ⏳ PENDIENTE
delete_tool()           ✅ YA TIENE
delete_accessory()      ✅ YA TIENE
delete_acquisition_type() ⏳ PENDIENTE
delete_equipment_location() ⏳ PENDIENTE
delete_job_position()   ⏳ PENDIENTE
delete_service_category() ⏳ PENDIENTE
delete_ticket_attachment() ⏳ PENDIENTE
delete_avatar()         ⏳ PENDIENTE
```

**Patrón estándar a implementar**:

```php
function delete_MODULE() {
    extract($_POST);
    
    // 1. Validar permisos
    $login_type = (int)($_SESSION['login_type'] ?? 0);
    if ($login_type === 3) {  // Sin permisos para clientes
        error_log("SECURITY: Customer attempted to delete MODULE");
        return 0;
    }
    
    // 2. Validar que pertenece a su sucursal (si aplica)
    if ($login_type !== 1 && $active_branch_id > 0) {
        $stmt = $this->pdo->prepare("SELECT branch_id FROM modules WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['branch_id'] !== $active_branch_id) {
            error_log("SECURITY: Staff type {$login_type} attempted to delete MODULE from different branch");
            return 0;
        }
    }
    
    // 3. Usar can() si tipo de usuario está asignado con rol
    if (!can('delete', 'module_code')) {
        return 0;
    }
    
    // 4. Proceder con eliminación
    // ...
}
```

---

## FASE 3: MIGRACIÓN DE LOGIN_TYPE → ROLES (Medio plazo)

### 3.1 Tabla de Mapeo de Migración

**Estado actual**:
```
login_type = 1  → Admin
login_type = 2  → Staff/Jefe de Departamento
login_type = 3  → Customer/Cliente
```

**Estado futuro** (usar tabla `roles`):
```
role_id = 1  → Admin (is_admin = 1)
role_id = 2  → Manager (is_admin = 0)
role_id = 3  → Staff (is_admin = 0)
role_id = 4  → Customer (is_admin = 0)
role_id = 5  → Technician (is_admin = 0)
```

### 3.2 Script de Migración

```sql
-- 1. Crear roles nuevos si no existen
INSERT INTO roles (name, description, is_admin) VALUES
('Admin', 'Administrador del sistema', 1),
('Manager', 'Gerente de sucursal', 0),
('Staff', 'Personal de staff', 0),
('Customer', 'Cliente/Usuario final', 0)
ON DUPLICATE KEY UPDATE id = id;

-- 2. Mapear usuarios existentes
UPDATE users u 
SET u.role_id = CASE 
    WHEN u.login_type = 1 THEN (SELECT id FROM roles WHERE name = 'Admin')
    WHEN u.login_type = 2 THEN (SELECT id FROM roles WHERE name = 'Manager')
    WHEN u.login_type = 3 THEN (SELECT id FROM roles WHERE name = 'Customer')
    ELSE u.role_id
END
WHERE u.role_id IS NULL;

-- 3. Deprecated (mantener por retrocompatibilidad 1 mes)
ALTER TABLE users ADD COLUMN login_type_deprecated INT AFTER login_type;
UPDATE users SET login_type_deprecated = login_type;

-- 4. Set login_type a NULL o 0 tras verificar todo funciona
-- (NO hacer hasta que todo este probado en 2+ semanas)
```

---

## FASE 4: AUDITORÍA CENTRALIZADA

### 4.1 Crear tabla `permission_audit`

```sql
CREATE TABLE permission_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100),
    action VARCHAR(50),           -- 'view', 'create', 'delete', 'export'
    module VARCHAR(100),           -- 'equipments', 'users', 'suppliers'
    resource_id INT,
    resource_name VARCHAR(255),
    branch_id INT,
    old_value JSON,
    new_value JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    result VARCHAR(20),            -- 'success', 'denied', 'error'
    reason VARCHAR(255),           -- Por qué fue denegado
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_module (module),
    INDEX idx_timestamp (timestamp),
    INDEX idx_result (result)
) ENGINE=InnoDB;
```

### 4.2 Función central de auditoría

```php
function audit_action($message, $action, $module, $resource_id, $result, $reason = '') {
    global $conn;
    
    $user_id = (int)($_SESSION['login_id'] ?? 0);
    $username = $_SESSION['login_username'] ?? 'Unknown';
    $branch_id = (int)($_SESSION['login_active_branch_id'] ?? 0);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO permission_audit  
        (user_id, username, action, module, resource_id, branch_id, ip_address, user_agent, result, reason, timestamp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param('isssiisSs', 
        $user_id, $username, $action, $module, $resource_id,
        $branch_id, $ip, $user_agent, $result, $reason
    );
    
    $stmt->execute();
    $stmt->close();
    
    // Opcional: enviar a Sentry/monitoring
    error_log($message);
}
```

### 4.3 Usar en todas las operaciones críticas

```php
// Ejemplo de uso:
function delete_equipment() {
    // ... validaciones ...
    
    if (!$has_permission) {
        audit_action(
            "Intento de borrar equipo #{$id}",
            'delete',
            'equipments',
            $id,
            'denied',
            "User type {$login_type} no autorizado"
        );
        return 0;
    }
    
    // ... borrar ...
    
    audit_action(
        "Borró equipo #{$id} ({$equipment_name})",
        'delete',
        'equipments',  
        $id,
        'success'
    );
}
```

---

## FASE 5: VALIDACIÓN DUAL (FRONTEND + BACKEND)

### Regla de Oro
```
NUNCA confiar SOLO en validación del cliente
Frontend: Mostrar/ocultar UI según permisos
Backend: SIEMPRE verificar permisos en CADA operación
```

### 5.1 Ejemplo Correcto

**Frontend** (mostrar/ocultar botón):
```php
<?php if (can('delete', 'equipments')): ?>
    <button onclick="deleteEquipment(<?= $id ?>)">Eliminar</button>
<?php endif; ?>
```

**Backend** (SIEMPRE validar):
```php
function delete_equipment() {
    // VALIDAR PERMISOS INCLUSO SI EL BOTÓN NO SE MOSTRABA
    if (!can('delete', 'equipments')) {
        audit_action("Intento no autorizado", 'delete', 'equipments', $id, 'denied');
        return 0;
    }
    
    // Proceder...
}
```

---

## FASE 6: IMPLEMENTACIÓN POR MÓDULO

### Orden de Prioridad

| Fase | Módulo | Complejidad | Horas | Status |
|------|--------|-------------|-------|--------|
| 2 | Core (delete_*) | Alta | 4 | 🔴 CRÍTICO |
| 3 | Usuarios/Roles | Media | 2 | 🟡 ALTO |
| 4 | Auditoría | Media | 2 | 🟡 ALTO |
| 4 | Equipmentation | Media | 1.5 | 🟡 ALTO |
| 4 | Reportes | Media | 1 | 🟡 ALTO |
| 5 | Otros módulos | Baja | 3 | 🟢 BAJO |

---

## FASE 7: TESTING Y VALIDACIÓN

### Checklist de Validación

- [ ] Ejecutar pruebas de seguridad (PRUEBAS_SEGURIDAD_PERMISOS.md)
- [ ] Verificar que todos los botones de eliminar validan
- [ ] Revisar logs de auditoría para intentos fallidos
- [ ] Hacer pruebas con 3+ usuarios de diferentes tipos
- [ ] Verificar que no hay SQL injection en queries de validación

---

## MATRIZ DE COMPLETITUD ACTUAL vs OBJETIVO

| Área | Actual | Objetivo | % Completitud |
|------|--------|----------|---------------|
| delete_* functions | 5/19 protegidas | 19/19 protegidas | 26% |
| Roles/Permisos | Hibrido | 100% sistema can() | 40% |
| Auditoría centralizada | No existe | Tabla + logs | 0% |
| Validación dual | Parcial | 100% | 50% |
| Documentación | Audit + tests | Roadmap completo | 70% |

---

## PRÓXIMOS PASOS INMEDIATOS

1. ✅ Commit cambios de seguridad a TEST
2. ✅ Ejecutar pruebas de seguridad
3. ⏳ **Proteger funciones delete_* restantes** (14 funciones)
4. ⏳ Implementar tabla permission_audit
5. ⏳ Migrar usuarios a roles
6. ⏳ Reemplazar login_type con can()

---

**Matenido por**: GitHub Copilot  
**Última actualización**: 14/04/2026  
**Próxima revisión**: Después de FASE 2

