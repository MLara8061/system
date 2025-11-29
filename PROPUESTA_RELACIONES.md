# 📋 Propuesta de Estructura de Relaciones

## 🎯 Objetivo
Relacionar Departamentos → Ubicaciones → Puestos para filtrado en cascada

## 📊 Estructura Propuesta

```
┌─────────────────┐
│  DEPARTMENTS    │
│  (Departamentos)│
├─────────────────┤
│ id              │
│ name            │
└────────┬────────┘
         │
         │ Un departamento tiene
         │ múltiples ubicaciones
         │
         ▼
┌─────────────────┐
│   LOCATIONS     │
│  (Ubicaciones)  │
├─────────────────┤
│ id              │
│ name            │
│ department_id ◄─┘ (FK a departments)
└────────┬────────┘
         │
         │ Una ubicación tiene
         │ múltiples puestos
         │
         ▼
┌─────────────────┐
│ JOB_POSITIONS   │
│    (Puestos)    │
├─────────────────┤
│ id              │
│ name            │
│ location_id   ◄─┘ (FK a locations)
│ department_id   │ (FK a departments - redundante pero útil)
└─────────────────┘
```

## 🔄 Flujo de Filtrado en Cascada

### Formulario de Equipos:

1. **Usuario selecciona DEPARTAMENTO**
   ```
   → AJAX: Obtener ubicaciones WHERE department_id = X
   → Cargar select de Ubicaciones
   ```

2. **Usuario selecciona UBICACIÓN**
   ```
   → AJAX: Obtener puestos WHERE location_id = Y
   → Cargar select de Puestos
   ```

### Vista de Departamentos:

```
┌────────────────┬──────────────────┬──────────────────┬──────────┐
│ Departamento   │ Ubicaciones      │ Puestos          │ Acción   │
├────────────────┼──────────────────┼──────────────────┼──────────┤
│ Sistemas       │ [Select Multiple]│ [Select Multiple]│ [Editar] │
│                │ ☑ Oficina Central│ ☑ Desarrollador  │          │
│                │ ☑ Sala Servidores│ ☑ Analista       │          │
├────────────────┼──────────────────┼──────────────────┼──────────┤
│ Recursos       │ [Select Multiple]│ [Select Multiple]│ [Editar] │
│ Humanos        │ ☑ Oficina RH     │ ☑ Reclutador     │          │
│                │                  │ ☑ Capacitador    │          │
└────────────────┴──────────────────┴──────────────────┴──────────┘
```

## ⚙️ Cambios Necesarios

### 1. Base de Datos
- ✅ Ejecutar `migration_department_relations.sql`
- ✅ Migrar datos de `location_positions` a las nuevas columnas

### 2. Backend (PHP)
- ✅ Actualizar `manage_department.php` para manejar ubicaciones y puestos
- ✅ Crear endpoints AJAX:
  - `get_locations_by_department`
  - `get_positions_by_location`
  - `get_positions_by_department`

### 3. Frontend
- ✅ Modificar vista `department_list.php` (eliminar descripción, agregar ubicaciones/puestos)
- ✅ Actualizar formularios de equipos con triple cascada
- ✅ JavaScript para manejar los selects dependientes

## 🚀 Ventajas

✅ **Jerarquía clara y lógica**
✅ **Filtrado automático y preciso**
✅ **Menos errores de asignación**
✅ **Más escalable**
✅ **Mejor experiencia de usuario**

## ⚠️ Consideraciones

1. **Migración de datos existentes**: Los equipos actuales que tienen `department_id`, `location_id` y `responsible_position` seguirán funcionando
2. **Compatibilidad**: Mantener la estructura actual hasta confirmar que la nueva funciona
3. **Validación**: Asegurar que las relaciones sean consistentes

## 📝 Siguiente Paso

¿Quieres que implemente esta estructura? Necesitarías:
1. Ejecutar el SQL de migración
2. Actualizar la vista de Departamentos
3. Actualizar los formularios de equipos
