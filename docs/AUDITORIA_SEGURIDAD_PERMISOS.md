# AUDITORÍA DE SEGURIDAD: SISTEMA DE ROLES Y PERMISOS

**Fecha**: 14/04/2026  
**Crítico**: Sí - Se encontraron vulnerabilidades de seguridad

---

## 1. RESUMEN EJECUTIVO

### Hallazgos Críticos Identificados: 5

1. ✅ **CRÍTICO**: Funciones de eliminación sin validación de permisos
2. ✅ **CRÍTICO**: Sistema de permisos híbrido e inconsistente
3. ✅ **ALTO**: Validación solo en frontend, no en backend
4. ✅ **ALTO**: Falta implementación de permisos en algunos módulos
5. ⚠️ **MEDIO**: Falta de auditoría de cambios centralizados

---

## 2. ANÁLISIS DETALLADO

### 2.1 VULNERABILIDADES EN FUNCIONES DELETE_*

#### Antes (INSEGURO):
```php
function delete_equipment() {
    extract($_POST);
    $id = (int)$id;
    // BORRAR DIRECTAMENTE, SIN VALIDACIÓN
    $stmt = $this->pdo->prepare("DELETE FROM equipments WHERE id = ?");
    $stmt->execute([$id]);
}
```

#### Después (SEGURO - IMPLEMENTADO):
```php
function delete_equipment() {
    extract($_POST);
    
    // Validación de permisos
    $login_type = (int)($_SESSION['login_type'] ?? 0);
    $active_branch_id = (int)($_SESSION['login_active_branch_id'] ?? 0);
    
    if ($login_type === 3) {
        error_log("SECURITY: Customer attempted delete");
        return 2;  // Denegado
    }
    
    if ($login_type !== 1 && $active_branch_id > 0) {
        // Verificar sucursal
        $stmt = $this->pdo->prepare("SELECT branch_id FROM equipments WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['branch_id'] !== $active_branch_id) {
            return 2;  // No pertenece a su sucursal
        }
    }
}
```

#### Funciones Corregidas en esta Auditoría:
- ✅ `delete_user()` - Solo admin
- ✅ `delete_equipment()` - Validación de sucursal
- ✅ `delete_customer()` - Solo admin
- ✅ `delete_staff()` - Solo admin
- ✅ `delete_supplier()` - Admin o staff

#### Funciones Pendientes de Revisar (NEXT):
- [ ] `delete_department()` - línea 802
- [ ] `delete_branch()` - línea 873
- [ ] `delete_ticket()` - línea 973
- [ ] `delete_inventory()` - línea 3721
- [ ] `delete_acquisition_type()` - línea 1952

---

### 2.2 SISTEMA HÍBRIDO DE PERMISOS

#### **Problema**: Dos sistemas conviven y no hablan entre sí

| Sistema | Ubicación | Uso | Problema |
|---------|-----------|-----|----------|
| **Nuevov (Roles/Permisos)** | `app/helpers/permissions.php` | Función `can()` | No se usa en funciones delete_* |
| **Antiguo** (`login_type`) | `$_SESSION['login_type']` | Directo en funciones | Numérico (1=admin, 2=staff, 3=customer) |

#### Sistema **can()** - Diseñado Correctamente:
```php
can('delete', 'equipments')  // ✅ Correcto
can('view', 'reports')        // ✅ Correcto
can('export', 'inventory')    // ✅ Correcto
```

#### Inconsistencia Encontrada:
```php
// En sidebar.php línea 122 - USANDO login_type directo
$can_hazardous = ((int)($_SESSION['login_type'] ?? 0) === 1);

// Debería ser:
$can_hazardous = can('view', 'hazardous_materials');
```

---

### 2.3 VALIDACIÓN SOLO EN FRONTEND

#### Problema: Botones ocultos en UI, pero backend sin validación

```php
// app/views/layouts/sidebar.php línea 122 - FRONTEND
if ($can_hazardous) {
    echo '<li><a href="...">Sustancias Peligrosas</a></li>';  // Botón oculto
}

// legacy/admin_class.php - BACKEND (antes de fixes)
function delete_hazmat() {  // SIN VALIDACIÓN - Vulnerable!
    $stmt->execute(["DELETE FROM hazardous_materials WHERE id = ?", $id]);
}
```

#### Diagrama de Vulnerabilidad:

```
User es Staff (logout_type=2)
  ↓
UI: Botón OCULTO (no ve nada)  ✅
  ↓
Pero puede enviar AJAX manual:  POST /action.php?action=delete_hazmat&id=1
  ↓
Backend: NO valida permisos  ❌
  ↓
BORRADO EXITOSO - BREACH!
```

---

### 2.4 MÓDULOS SIN PERMISOS IMPLEMENTADOS

Módulos listados en `app/routing.php` pero sin validación granular:

| Módulo | Ubicación Vista | Tiene `can()` | Status |
|--------|--|---|---|
| Equipments | `app/views/dashboard/equipment/` | Parcial | ⚠️ Incompleto |
| Reports | `app/views/dashboard/reports/` | Parcial | ⚠️ Incompleto |
| Inventory | `legacy/inventory_list.php` | No | ❌ Falta |
| Tools | `legacy/tools_list.php` | No | ❌ Falta |
| Suppliers | `legacy/suppliers.php` | No | ❌ Falta |
| Accessories | `legacy/accessories_list.php` | No | ❌ Falta |

---

### 2.5 FALTA AUDITORÍA CENTRALIZADA

#### Situación Actual:
- Cada función tiene `$this->audit()` inconsistente
- No existe logging centralizado de cambios de permisos
- No existe dashboard de "quién hizo qué"

#### Lo que Falta:
```php
// Tabla: permission_audit
CREATE TABLE permission_audit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50),           // 'create', 'delete', 'edit'
    module VARCHAR(100),           // 'equipments', 'users'
    resource_id INT,              // ID del recurso afectado
    old_value JSON,
    new_value JSON,
    ip_address VARCHAR(45),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    result VARCHAR(10)             // 'success', 'denied'
);
```

---

## 3. MATRIZ DE RIESGOS

| Riesgo | Severidad | Usuario | Impacto | Estado |
|--------|-----------|---------|---------|--------|
| Staff borra equipment de otra sucursal | **CRÍTICO** | login_type=2 | Pérdida de datos | ✅ FIXED |
| Customer borra usuarios | **CRÍTICO** | login_type=3 | Ataque de negación | ✅ FIXED |
| Staff borra clientes | **CRÍTICO** | login_type=2 | Pérdida de datos | ✅ FIXED |
| Todos ven módulos no autorizados (UI) | ALTO | Todos | Desconfiguración | ⚠️ Parcial |
| No hay rastro de cambios críticos | ALTO | Todos | Imposible auditar | ❌ No fixed |

---

## 4. RECOMENDACIONES INMEDIATAS

### 4.1 Migracion a Sistema `can()`

**Prioridad**: CRÍTICO - 2 horas

Reemplazar todos los `if ($login_type === 1)` con:

```php
// Reemplazar esto:
if ((int)($_SESSION['login_type'] ?? 0) !== 1) {
    return 0;
}

// Con esto (una vez que roles estén bien configurados):
if (!can('delete', 'module_name')) {
    return 0;
}
```

### 4.2 Implementar Auditoría Centralizada

**Prioridad**: ALTO - 3 horas

Crear tabla `permission_audit` y función central:

```php
function audit_action($user_id, $action, $module, $resource_id, $result) {
    // Registrar en BD
    // Enviar a log externo (Sentry/CloudWatch)
}
```

### 4.3 Proteger TODAS las funciones delete_*

**Prioridad**: CRÍTICO - 4 horas

Recorrer todo `admin_class.php` y agregar validaciones.

### 4.4 Validación Dual (Frontend + Backend)

**Prioridad**: ALTO - 1 hora

La regla de oro:
```
NUNCA confiar en validaciones del cliente
SIEMPRE validar en el servidor
```

---

## 5. VERIFICACIÓN DE FIXES APLICADOS

```
✅ delete_user() - Agregada validación (solo admin)
✅ delete_equipment() - Agregada validación (verifica sucursal)
✅ delete_customer() - Agregada validación (solo admin)
✅ delete_staff() - Agregada validación (solo admin)
✅ delete_supplier() - Agregada validación (solo admin o staff)
```

**Pendientes**:
- [ ] delete_department()
- [ ] delete_branch()
- [ ] delete_ticket()
- [ ] delete_inventory()
- [ ] delete_service_category()

---

## 6. PRÓXIMOS PASOS

1. **Ahora**: Commitear los 5 fixes de seguridad
2. **Después**: Implementar auditoría centralizada
3. **Luego**: Proteger funciones delete_* restantes
4. **Final**: Migrar 100% a sistema `can()`, eliminar `login_type` directo

---

**Auditoría realizada por**: GitHub Copilot  
**Commit**: Pendiente de enviar a TEST/PROD
