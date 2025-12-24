# Mejoras en Sistema de Permisos y Notificaciones

## Cambios Realizados - 24 de Diciembre 2025

### 1. **Nuevo Sistema de Notificaciones (`assets/js/notification.js`)**

Se creó un componente de notificaciones personalizado que reemplaza las alertas nativas de JavaScript:

#### Características:
- **Tipos de notificación**: Success, Error, Warning, Info
- **Animaciones suaves**: Deslizamiento desde la derecha
- **Cierre automático**: Las notificaciones desaparecen después de un tiempo configurable
- **Gradientes visuales**: Cada tipo tiene un color y gradiente único
- **Botón de cierre manual**: Los usuarios pueden cerrar manualmente
- **Mejor UX**: Notificaciones no intrusivas que no bloquean la interacción

#### Uso:
```javascript
// Éxito
notification.success('Guardado correctamente');

// Error
notification.error('No se pudo guardar');

// Advertencia
notification.warning('Por favor verifica los datos');

// Información
notification.info('Operación completada');

// Con duración personalizada (en milisegundos)
notification.success('Mensaje', 3000);

// Sin cierre automático (0 = nunca se cierra automáticamente)
notification.error('Error crítico', 0);
```

### 2. **Mejora del Filtrado de Equipos por Departamento**

#### Archivo: `app/models/Equipment.php` - Método `listWithFilters()`

**Cambios:**
- Se agregó validación de departamento del usuario en tiempo de consulta
- Ahora filtra automáticamente equipos según el departamento del usuario
- Si el usuario tiene `can_view_all_departments = 0`, solo ve equipos de su departamento
- Los administradores globales y usuarios con `can_view_all_departments = 1` ven todos

**Lógica:**
```php
// Si usuario NO es admin y NO puede ver todos los departamentos
if (!$can_view_all && $user_dept_id) {
    // Solo mostrar equipos del departamento del usuario
    $sql .= " AND (e.department_id = {$dept_id} OR e.department_id IS NULL)";
}
```

**Beneficio:** Los equipos de otros departamentos no aparecerán en la lista si el usuario está restringido

### 3. **Validación Mejorada de Permisos de Departamento**

#### Archivos Modificados:
- `app/views/dashboard/equipment/edit.php`
- `app/views/pages/view_equipment.php`

**Cambios:**
- Reemplazó sistema antiguo de sucursales por sistema de departamentos
- Integraron la lógica de `can_view_all_departments`
- Reemplazaron `alert()` nativo con `notification.error()`
- Validación más robusta que verifica: user_id, department_id, is_admin

**Nueva validación:**
```php
// Si el usuario NO es admin y NO puede ver todos los departamentos
if (!$is_admin && !$can_view_all && $equipment_dept_id && $equipment_dept_id != $user_dept_id) {
    notification.error('No tiene permiso para ver equipos de este departamento');
    // Redirigir a lista
}
```

### 4. **Script de Notificaciones Agregado a Layout**

#### Archivo: `public/index.php`

Se agregó la carga del script:
```html
<script src="/assets/js/notification.js"></script>
```

Esto garantiza que el objeto global `notification` esté disponible en toda la aplicación.

## Flujo de Control Mejorado

### Antes:
1. Usuario intenta ver equipo de otro departamento
2. JavaScript `alert()` nativo aparece sin estilo
3. Mensaje no personalizado
4. UX pobre

### Después:
1. Usuario intenta ver equipo de otro departamento
2. Validación en backend verifica permisos
3. Notificación personalizada aparece con estilo
4. Redirección suave a lista
5. UX mejorada con animaciones

## Validaciones Implementadas

### En el Listado de Equipos:
- ✅ Solo muestra equipos del departamento del usuario
- ✅ Admin global ve todos
- ✅ Usuario con `can_view_all_departments=1` ve todos
- ✅ Usuario con `can_view_all_departments=0` ve solo su departamento

### En Vista Detallada:
- ✅ Valida que el equipo pertenezca al departamento del usuario
- ✅ Rechaza acceso con notificación personalizada
- ✅ Redirige automáticamente a lista

## Próximos Pasos Recomendados

1. Aplicar misma validación a otros módulos (Tickets, Servicios, etc.)
2. Reemplazar todos los `alert()` nativos con `notification`
3. Agregar confirmaciones personalizadas con el nuevo sistema
4. Crear componentes de diálogos personalizados
5. Aplicar estilos consistentes en toda la aplicación

## Testing Recomendado

```
1. Loguear como usuario limitado a un departamento
   - Verificar que solo ve equipos de su departamento
   - Intentar acceder directamente a equipo de otro departamento
   - Confirmar notificación personalizada

2. Loguear como usuario con can_view_all_departments=1
   - Verificar que ve todos los equipos
   - Acceder a equipos de cualquier departamento

3. Loguear como administrador
   - Verificar acceso completo
   - Validar que filtros funcionan

4. Probar notificaciones
   - Éxito: Guardar cambios
   - Error: Intentar operación no permitida
   - Información: Operaciones generales
```
