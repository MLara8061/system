# Filtrado de Equipos por Departamento - Correcciones Implementadas

## Resumen

Se corrigieron varios problemas en la implementación del filtrado de equipos por departamento:

1. **Relación de Departamento Incorrecta**: El código estaba intentando usar `equipments.department_id` que no existe.
2. **Fuente de Verdad**: La relación departamento-equipo está en `equipment_delivery.department_id`.
3. **JOINs Incorrectos**: Se usaban JOINs a `departments` desde `locations`.

## Cambios Realizados

### 1. Modelo Equipment.php (`app/models/Equipment.php`)

**Antes (Incorrecto)**:
```php
FROM equipment e
LEFT JOIN departments d ON l.department_id = d.id  // ❌ Departamento está en delivery, no en locations
WHERE ...
AND (l.department_id = :user_dept_id OR l.department_id IS NULL)  // ❌ Filtro en tabla incorrecta
```

**Después (Correcto)**:
```php
FROM equipment e
LEFT JOIN equipment_delivery ed ON e.id = ed.equipment_id  // ✅ Join correcto
LEFT JOIN departments d ON ed.department_id = d.id  // ✅ Departamento desde delivery
WHERE ...
AND (ed.department_id = :user_dept_id OR ed.department_id IS NULL)  // ✅ Filtro correcto
```

### 2. Vista `app/views/pages/view_equipment.php`

**Antes (Incorrecto)**:
```php
$qry = $conn->query("SELECT e.*, d.name as department_name FROM equipments e 
                     LEFT JOIN departments d ON e.department_id = d.id 
                     WHERE e.id = $equipment_id");  // ❌ equipments.department_id no existe
```

**Después (Correcto)**:
```php
$qry = $conn->query("SELECT e.*, ed.department_id FROM equipments e 
                     LEFT JOIN equipment_delivery ed ON e.id = ed.equipment_id 
                     WHERE e.id = $equipment_id");  // ✅ Correcto
```

### 3. Vista `app/views/dashboard/equipment/edit.php`

Mismo cambio que `view_equipment.php` para consultar correctamente la relación de departamento.

## Cómo Funciona Ahora

1. **Consulta Base**: El modelo Equipment obtiene equipos con sus departamentos desde `equipment_delivery`.
2. **Filtro de Usuario**: Si el usuario NO es admin y NO tiene `can_view_all_departments = 1`:
   - Se añade filtro: `AND (ed.department_id = :user_dept_id OR ed.department_id IS NULL)`
   - Solo ve equipos entregados a su departamento o sin departamento asignado.
3. **Vista de Detalle**: Valida que el usuario tenga permiso para ver ese equipo específico.

## Verificación de la Estructura de Base de Datos

**Tabla: equipments**
- `id` (PK)
- `name`, `serial_number`, `category_id`, etc.
- NO tiene `department_id` ❌

**Tabla: equipment_delivery**
- `id` (PK)
- `equipment_id` (FK → equipments.id)
- `department_id` (FK → departments.id) ✅
- `location_id`, `responsible_position`, etc.

## Migración Incluida

Se creó `database/migrations/004_add_department_id_to_equipments.sql` que:
- Agrega columna `department_id` a `equipments` (para uso futuro)
- Crea índice para consultas rápidas
- Agrega constraint de integridad referencial

Esta migración es opcional y se proporciona para futura compatibilidad en caso de refactorizar el modelo de datos.

## Resultados Esperados

✅ **Usuarios limitados a un departamento**:
- No ven equipos de otros departamentos en listados
- No pueden ver detalles de equipos de otros departamentos
- Solo ven equipos entregados a su departamento

✅ **Administradores o usuarios con `can_view_all_departments = 1`**:
- Ven todos los equipos sin restricción

## Testing Recomendado

1. Crear usuario en Departamento A con `can_view_all_departments = 0`
2. Crear usuario en Departamento B con `can_view_all_departments = 0`
3. Asignar equipos a cada departamento
4. Verificar que cada usuario solo ve sus equipos
