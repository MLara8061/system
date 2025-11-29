# Cambios Implementados: Relación Departamentos-Ubicaciones-Puestos

## 📋 Resumen de Cambios

Se ha reestructurado el sistema para establecer relaciones directas entre Departamentos, Ubicaciones y Puestos de Trabajo, eliminando la dependencia de tablas intermedias y mejorando el flujo de datos en cascada.

## 🗄️ Cambios en la Base de Datos

### Estructura Anterior
- `departments`: Tabla aislada con `id`, `name`, `description`
- `locations`: Tabla aislada sin relación con departamentos
- `job_positions`: Relacionado con ubicaciones mediante tabla intermedia `location_positions`

### Estructura Nueva (Implementada)
```sql
departments (id, name)
├── locations (id, name, department_id) → FK a departments
└── job_positions (id, name, location_id, department_id) → FK a departments y locations
```

### Script de Migración
**Archivo:** `migration_department_relations.sql`

**Pasos para aplicar:**
```sql
-- 1. Ejecutar el script completo en MySQL
-- 2. Verificar con las queries de validación incluidas
-- 3. Los datos existentes se migran automáticamente desde location_positions
```

**⚠️ IMPORTANTE:** Debes ejecutar este script SQL antes de que los cambios funcionen completamente.

## 📂 Archivos Modificados

### 1. **ajax.php** (Líneas 320-415)
**Cambios:**
- ✅ Actualizado `get_job_positions_by_location`: Ahora consulta primero `job_positions.location_id`, luego hace fallback a `location_positions` para compatibilidad
- ✅ Nuevo endpoint `get_locations_by_department`: Retorna ubicaciones filtradas por department_id
- ✅ Nuevo endpoint `get_positions_by_department`: Retorna puestos filtrados por department_id

**Ejemplo de uso:**
```javascript
// Obtener ubicaciones de un departamento
$.ajax({
    url: 'ajax.php?action=get_locations_by_department',
    method: 'POST',
    data: { department_id: 5 },
    dataType: 'json'
});
```

### 2. **department_list.php**
**Cambios:**
- ❌ Eliminada columna "Descripción"
- ✅ Agregada columna "Ubicaciones" (muestra ubicaciones relacionadas)
- ✅ Agregada columna "Puestos" (muestra puestos relacionados)
- Query actualizado para traer datos relacionados vía FK

**Vista previa:**
```
Nombre          | Ubicaciones                    | Puestos
----------------|---------------------------------|------------------
Recursos Humanos| Oficina RH, Recepción          | Gerente, Asistente
Sistemas        | Sala Servidores, Data Center   | Administrador, Técnico
```

### 3. **manage_department.php**
**Cambios:**
- ❌ Eliminado campo textarea "Descripción"
- ✅ Agregado campo multi-select "Ubicaciones" (Select2)
- ✅ Agregado campo multi-select "Puestos" (Select2)
- Al editar, carga ubicaciones y puestos ya asignados al departamento

**Funcionalidad:**
- Puedes seleccionar múltiples ubicaciones
- Puedes seleccionar múltiples puestos
- Los cambios actualizan las columnas `department_id` en `locations` y `job_positions`

### 4. **admin_class.php**

#### `save_department()` (Líneas 282-340)
**Reescrito completamente:**
```php
// Antes: Guardaba name + description
// Ahora:
// 1. Guarda solo el name del departamento
// 2. UPDATE locations SET department_id = X WHERE id IN (...)
// 3. UPDATE locations SET department_id = NULL WHERE id NOT IN (...)
// 4. Lo mismo para job_positions
```

#### `delete_department()` (Líneas 342-360)
**Actualizado:**
- Primero limpia relaciones (SET department_id = NULL)
- Luego elimina el departamento

#### `save_job_position()` (Líneas 1120-1155)
**Reescrito completamente:**
```php
// Antes: Solo insertaba en job_positions + location_positions
// Ahora:
// 1. INSERT/UPDATE en job_positions con location_id y department_id
// 2. Mantiene compatibilidad insertando también en location_positions
```

### 5. **manage_job_position.php**
**Cambios:**
- ✅ Agregado campo "Departamento" (dropdown)
- Campo ubicación ahora muestra solo ubicaciones del departamento seleccionado
- Query actualizado para leer `location_id` y `department_id` directamente desde `job_positions`

**Nueva estructura del formulario:**
```
1. Nombre del Puesto
2. Departamento [Select]
3. Ubicación [Select - filtrado por departamento]
```

### 6. **manage_equipment_location.php**
**Cambios:**
- ✅ Agregado campo "Departamento" (dropdown opcional)
- Permite asignar una ubicación a un departamento al crearla/editarla
- Campo opcional: puede dejarse "Sin departamento"

### 7. **new_equipment.php**
**Cambios - Sección "Entrega del Equipo":**
- ✅ Filtro en cascada triple implementado:
  1. **Departamento** → Habilita "Ubicación"
  2. **Ubicación** → Habilita "Cargo Responsable"
- Selectores inicialmente deshabilitados excepto Departamento
- JavaScript actualizado para manejar 2 eventos AJAX en cascada

**Flujo de usuario:**
```
1. Usuario selecciona "Recursos Humanos" en Departamento
   → AJAX carga ubicaciones de RH
2. Usuario selecciona "Oficina RH" en Ubicación
   → AJAX carga cargos de esa ubicación
3. Usuario selecciona "Gerente de RH" en Cargo
```

### 8. **edit_equipment.php**
**Cambios:**
- ✅ Misma lógica de cascada triple que `new_equipment.php`
- Al cargar equipo existente:
  - Muestra departamento, ubicación y cargo guardados
  - Carga opciones filtradas según los valores actuales
- JavaScript con fallback para compatibilidad con estructura anterior

## 🔄 Flujo de Cascada Triple

### Diagrama de Flujo
```
┌──────────────┐
│ Departamento │ (Siempre habilitado)
└──────┬───────┘
       │ onChange → AJAX get_locations_by_department
       ▼
┌──────────────┐
│  Ubicación   │ (Se habilita después de seleccionar Depto)
└──────┬───────┘
       │ onChange → AJAX get_job_positions_by_location
       ▼
┌──────────────┐
│Cargo Respons.│ (Se habilita después de seleccionar Ubicación)
└──────────────┘
```

### Endpoints AJAX Utilizados
1. **get_locations_by_department**
   - Input: `department_id`
   - Output: Array de objetos `[{id, name}, ...]`
   
2. **get_job_positions_by_location**
   - Input: `location_id`
   - Output: Array de objetos `[{id, name}, ...]`

## 🔧 Compatibilidad con Estructura Anterior

### Sistema de Fallback
El código mantiene compatibilidad con la estructura anterior:

1. **En `ajax.php` - get_job_positions_by_location:**
   ```php
   // Primero intenta nueva estructura (location_id en job_positions)
   $query = "SELECT id, name FROM job_positions WHERE location_id = $location_id";
   
   // Si no hay resultados, usa tabla intermedia location_positions
   if(empty($positions)){
       $query = "SELECT j.id, j.name FROM job_positions j 
                 INNER JOIN location_positions lp ...";
   }
   ```

2. **En `admin_class.php` - save_job_position:**
   ```php
   // Guarda en job_positions con las nuevas columnas
   UPDATE job_positions SET location_id = X, department_id = Y;
   
   // También mantiene location_positions actualizado
   INSERT IGNORE INTO location_positions (location_id, job_position_id);
   ```

## ✅ Tareas Completadas

- [x] Crear script de migración SQL
- [x] Actualizar endpoints AJAX
- [x] Modificar vistas de listado de departamentos
- [x] Modificar formulario de gestión de departamentos
- [x] Reescribir funciones save/delete de departamentos
- [x] Actualizar formulario de puestos de trabajo
- [x] Reescribir función save de puestos
- [x] Agregar campo departamento a ubicaciones
- [x] Implementar cascada triple en new_equipment.php
- [x] Implementar cascada triple en edit_equipment.php
- [x] Documentar todos los cambios

## 📝 Pasos Siguientes (Manual del Usuario)

### 1. Ejecutar Migración de Base de Datos
```bash
mysql -u usuario -p nombre_base_datos < migration_department_relations.sql
```

### 2. Configurar Relaciones
1. Ir a "Departamentos"
2. Editar cada departamento
3. Seleccionar ubicaciones correspondientes
4. Seleccionar puestos correspondientes
5. Guardar

### 3. Verificar Puestos de Trabajo
1. Ir a "Puestos de Trabajo"
2. Editar cada puesto
3. Asignar departamento y ubicación
4. Guardar

### 4. Probar Creación de Equipos
1. Ir a "Nuevo Equipo"
2. En sección "Entrega del Equipo":
   - Seleccionar Departamento
   - Verificar que se cargan ubicaciones
   - Seleccionar Ubicación
   - Verificar que se cargan cargos
   - Seleccionar Cargo
3. Completar formulario y guardar

### 5. Probar Edición de Equipos
1. Abrir un equipo existente
2. Cambiar departamento
3. Verificar que ubicación y cargo se actualizan correctamente

## 🐛 Solución de Problemas

### Problema: "No hay ubicaciones en este departamento"
**Solución:** Editar el departamento y asignar ubicaciones desde el formulario de gestión

### Problema: "No hay cargos para esta ubicación"
**Solución:** Editar los puestos de trabajo y asignarles la ubicación correspondiente

### Problema: Los selectores no se habilitan
**Solución:** 
1. Verificar consola del navegador (F12)
2. Confirmar que ajax.php responde correctamente
3. Verificar que la migración SQL se ejecutó

### Problema: Error al guardar departamento
**Solución:** Verificar que las columnas `department_id` existan en `locations` y `job_positions`

## 📊 Ventajas de la Nueva Estructura

### Antes
❌ Tabla intermedia `location_positions` propensa a inconsistencias  
❌ No había relación entre departamentos y ubicaciones  
❌ Difícil de mantener y sincronizar  
❌ Queries complejos con múltiples JOINs  

### Ahora
✅ Relaciones directas mediante Foreign Keys  
✅ Cascada de datos consistente  
✅ Más fácil de entender y mantener  
✅ Queries más simples y rápidos  
✅ Integridad referencial garantizada  

## 👨‍💻 Notas Técnicas

- **Compatibilidad:** El sistema mantiene compatibilidad con `location_positions` durante el período de transición
- **Performance:** Las queries ahora usan índices en las FK para mejor rendimiento
- **Validación:** Los formularios validan que las selecciones sean consistentes
- **UX:** Los selectores se deshabilitan/habilitan automáticamente según el flujo lógico

---

**Fecha de implementación:** <?= date('Y-m-d') ?>  
**Versión:** 2.0  
**Desarrollador:** Sistema de Gestión de Equipos
