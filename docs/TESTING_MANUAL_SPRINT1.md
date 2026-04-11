# GUÍA DE TESTING - PROBLEMAS CORREGIDOS

## ✅ PROBLEMA 1: Orden de Trabajo Duplicada

**Archivo Modificado:** `legacy/equipment_report_sistem_add.php`

### Cambio Realizado:
- Lógica de generación de O.T movida del GET al POST
- Se genera al guardar, NO en cada carga de página

### Procedimiento de Test:

1. **Ir a:** Sistema > Generar Reporte de Mantenimiento
2. **Completar formulario** con datos válidos
3. **Guardar reporte** (POST)
4. **Verificar O.T:**
   - Debe generar: `OS-2026-001` (o número siguiente)
   - Cada guardar genera un número ÚNICO
5. **Repetir 3 veces** en misma sesión
6. **Esperado:** O.T diferentes: `OS-2026-001`, `OS-2026-002`, `OS-2026-003`
7. **No debe repetir** número si solo recarga la página

---

## ✅ PROBLEMA 2: Contadores de Tickets en 0

**Archivo Modificado:** `app/views/dashboard/tickets/list.php`

### Cambio Realizado:
- Agregada auditoría con logging para diagnosticar causa raíz
- Si contadores = 0, se guarda en error_log para debug

### Procedimiento de Test:

1. **Ir a:** Dashboard > Tickets
2. **Ver contadores:** Abiertos, En Proceso, Finalizados
3. **Si alguno = 0:**
   - Revisar error_log del servidor
   - Buscar línea: `[TICKET_DASHBOARD] WARNING`
   - Mostrará la query y resultado
4. **Verificar que:**
   - Los números se actualizan correctamente
   - No hay errores de BD

**Nota:** Si el contador sigue en 0, revisar:
- Tabla `tickets` existe en BD
- Campo `status` tiene tipo correcto (tinyint)
- Hay datos en tabla

---

## ✅ PROBLEMA 3: Columnas Comprimidas en Excel

**Archivos Modificados (5):**
- `app/helpers/export_suppliers.php`
- `app/helpers/export_equipment.php`
- `app/helpers/export_tickets_report.php`
- `app/helpers/export_maintenance_calendar.php`
- `app/helpers/export_sprint5_reports.php`

### Cambio Realizado:
- Agregados anchos dinámicos de columnas según tipo de dato
- Cada columna tiene ancho mínimo (80-180px)

### Procedimiento de Test:

1. **Exportar desde cada módulo:**
   - Proveedores → Export → Excel
   - Equipos → Export → Excel
   - Tickets → Export → Excel
   - Calendario → Export → Excel

2. **Abrir archivo Excel descargado**

3. **Verificar:**
   - ✅ Columnas tienen ANCHO visible (NO comprimidas)
   - ✅ Textos se leen completos sin ajuste
   - ✅ Números están centrados (si aplica)
   - ✅ Headers tienen fondo gris

4. **Comparar antes/después:**
   - Antes: Columnas juntas, difícil leer
   - Después: Espaciado correcto

---

## ✅ PROBLEMA 4: Fotos NO se Adjuntan

**Archivo Modificado:** `public/ajax/report_attachment.php`

### Cambio Realizado:
- Creación automática de directorio `/uploads/reports/` si no existe
- Si falta directorio, `mkdir()` lo crea con permisos 755

### Procedimiento de Test:

1. **Ir a:** Generar Reporte de Mantenimiento
2. **Adjuntar foto:**
   - Click en botón "Agregar Foto"
   - Seleccionar imagen (máx 5MB)
   - Esperar carga
3. **Verify:**
   - ✅ No muestra error "No se permiten adjuntar fotos"
   - ✅ Foto se carga correctamente
   - ✅ Aparece en lista de adjuntos
4. **Verificar en servidor:**
   - Carpeta `/uploads/reports/` debe existir
   - Archivos fotos ahí con nombres hash

**Si falla:**
- Revisar permisos de `/uploads/`
- Debe ser writable (755 mínimo)

---

## ✅ PROBLEMA 5: Redirect tras Guardar Reporte

**Archivo Modificado:** `legacy/generate_pdf.php`

### Cambio Realizado:
- Al guardar reporte, redirecciona a `maintenance_reports` dashboard
- ANTES: Iba a `report_pdf` (permitía re-envío duplicado)
- AHORA: Va a lista de reportes (previene duplicados)

### Procedimiento de Test:

1. **Generar Reporte:**
   - Completar formulario
   - Click "Guardar"

2. **Verificar redirect:**
   - ✅ NO regresa a formulario vacío
   - ✅ Va a Dashboard de Reportes
   - ✅ Muestra mensaje "msg=saved" en URL
   - ✅ Reporte aparece en lista

3. **Presionar F5 (refresh):**
   - ✅ NO duplica reporte
   - ✅ Solo recarga la página correctamente

4. **Test crítico:**
   - Generar reporte
   - Esperar a see en dashboard
   - Presionar F5 varias veces
   - **Esperado:** Reporte no se duplica

---

## ✅ PROBLEMA 6: Hoja de Seguridad Error

**Archivo Modificado:** `app/views/pages/view_inventory.php`

### Cambio Realizado:
- Validación de existencia de archivo antes de crear link
- Si no existe: botón disabled + mensaje "Archivo no disponible"

### Procedimiento de Test:

1. **Ir a:** Inventario > Insumos
2. **Buscar insumo con "Hoja de Seguridad":**
   - Marcar que tiene hazardoso (is_hazardous = 1)
   - Que se haya cargado archivo PDF

3. **Verificar caso 1 (archivo existe):**
   - ✅ Link "Ver Hoja de Seguridad" clickable
   - ✅ Abre PDF en navegador o descarga

4. **Verificar caso 2 (archivo NOT existe):**
   - Insumo hazardoso sin archivo cargado
   - ✅ Botón "Ver Hoja de Seguridad" DESHABILITADO
   - ✅ Tooltip o texto dice "Archivo no disponible"
   - ✅ NO lanza error

---

## ✅ PROBLEMA 7: Dashboard - Filtros de Fecha

**Archivo Modificado:** `app/views/dashboard/home.php`

### Cambio Realizado:
- Agregados campos datepicker para "Desde" y "Hasta"
- Nueva función `applyCustomPeriod()` 
- Gráficos dinámicos según rango custom

### Procedimiento de Test:

1. **Ir a:** Dashboard

2. **Ver filtro de período (lado derecho):**
   - Botones predefinidos: 6M, 12M, Este Año, Todo
   - **Nuevo:** Campos de fecha "Desde" y "Hasta"

3. **Test 1 - Períodos predefinidos:**
   - Click "6 Meses" → Gráficos se actualizan
   - Click "12 Meses" → Gráficos se actualizan
   - Click "Este Año" → Gráficos se actualizan
   - ✅ Título muestra período

4. **Test 2 - Período Custom:**
   - Ingresar "Desde": 2026-01-01
   - Ingresar "Hasta": 2026-04-10
   - Click "Filtrar"
   - ✅ Gráficos se actualizan
   - ✅ Título muestra: "Del 01/01/2026 al 10/04/2026"

5. **Test 3 - Validaciones:**
   - Dejar vacío "Desde", click "Filtrar"
   - ✅ Muestra alerta: "Por favor, selecciona ambas fechas"
   - Poner "Desde" > "Hasta"
   - ✅ Muestra alerta: "La fecha inicio debe ser menor"

6. **Gráficos afectados:**
   - "Reportes por Tipo de Servicio" → Se actualiza
   - "Reportes Mensuales por Tipo de Ejecución" → Se actualiza

---

## ✅ PROBLEMA 8: Bajas Equipos - Filtros + Export

**Archivos Modificados:**
- `app/views/dashboard/equipment/unsubscribe_report.php`
- `app/helpers/export_equipment_bajas.php` (NUEVO)

### Cambio Realizado:
- Filtros de fecha inicio/fin
- Botón "Excel" para exportar datos filtrados
- Funciones JS: `applyBajasFilter()`, `clearBajasFilter()`, `exportBajasEquiposExcel()`

### Procedimiento de Test:

1. **Ir a:** Dashboard > Bajas de Equipos

2. **Ver nuevos elementos:**
   - ✅ Campos "Desde" y "Hasta"
   - ✅ Botón "Filtrar"
   - ✅ Botón "Limpiar"
   - ✅ Botón "Excel" (verde)

3. **Test 1 - Filtrar por fecha:**
   - Ingresar rango: 2026-01-01 a 2026-04-01
   - Click "Filtrar"
   - ✅ Tabla se actualiza
   - ✅ Muestra solo bajas en ese rango
   - ✅ Título actualiza: "...del 01/01/2026 al 01/04/2026"

4. **Test 2 - Limpiar filtro:**
   - Click "Limpiar"
   - ✅ Campos se vacían
   - ✅ Tabla muestra TODAS las bajas

5. **Test 3 - Export a Excel:**
   - Seleccionar rango de fechas
   - Click "Filtrar"
   - Click "Excel"
   - ✅ Inicia descarga "Bajas_Equipos_YYYY-MM-DD_HHMM.xlsx"

6. **Test 4 - Validar archivo Excel:**
   - Abrir Excel descargado
   - ✅ Tiene columnas: Folio, Equipo, N° Inv., Marca, Modelo, Fecha, Usuario, Responsable, Destino, Dictamen, Causas, Mantenimientos
   - ✅ Datos corresponden al rango filtrado
   - ✅ Anchos de columna son legibles
   - ✅ Headers tienen fondo gris

7. **Test 5 - Export sin filtro:**
   - Click "Limpiar"
   - Click "Excel"
   - ✅ Descarga TODAS las bajas

---

## RESUMEN DE TESTING

| Problema | Paso Crítico | Señal de Éxito |
|----------|--------------|-----------------|
| O.T Duplicada | Generar 3x | O.T diferentes |
| Contadores 0 | Ver dashboard | Números visibles, no 0 |
| Excel Comprimido | Exportar | Columnas se ven bien |
| Fotos | Adjuntar en reporte | Foto cargada sin error |
| Redirect | Guardar reporte | Va a dashboard, no duplica |
| Hoja Seguridad | Ver insumo | Botón OK o disabled |
| Dashboard Fecha | Ingresar rango | Gráficos se actualizan |
| Bajas + Export | Filtrar + Excel | Descarga datos correctos |

---

## 🔍 CHECKLIST FINAL

- [ ] Problema 1: O.T generadas únicas
- [ ] Problema 2: Contadores visibles en dashboard
- [ ] Problema 3: Exports Excel con columnas legibles
- [ ] Problema 4: Fotos adjuntadas en reportes
- [ ] Problema 5: Reportes NO duplicados tras guardar
- [ ] Problema 6: Hoja seguridad sin errores
- [ ] Problema 7: Dashboard con filtros custom
- [ ] Problema 8: Bajas con export a Excel

**Si todos están ✅: SPRINT 1 COMPLETADO Y VALIDADO**

