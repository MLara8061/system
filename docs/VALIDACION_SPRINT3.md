# VALIDACIÓN FUNCIONAL - SPRINT 3

**Entorno:** TEST  
**URL Base:** https://test.activosamerimed.com  
**Fecha de Inicio:** 11 de abril de 2026  
**Duración Estimada:** 30-40 minutos

---

## Procedimiento de Validación

### 🧪 TEST 1: Periodos de Mantenimiento - CRUD

**Objetivo:** Verificar que la UI permite crear, editar y eliminar periodos.

**Pasos:**
1. Acceder a: `https://test.activosamerimed.com/index.php?page=maintenance_periods`
2. Verificar que se carga tabla con periodos existentes (mínimo 5)
3. Hacer clic en **"Nuevo Periodo"**
4. Ingresar:
   - Nombre: `Test Trimestral`
   - Intervalo: `92`
5. Hacer clic en **"Guardar"**
6. Verificar toast: "Periodo creado"
7. Buscar en tabla el nuevo período
8. Hacer clic en **"Editar"** sobre el período creado
9. Cambiar intervalo a `95` y guardar
10. Verificar actualización en tabla
11. Hacer clic en **"Eliminar"**
12. Confirmar eliminación
13. Verificar que desaparece de tabla

**Resultados Esperados:**
- ✅ Tabla carga con periodos existentes
- ✅ Modal se abre correctamente
- ✅ Toast de éxito después de crear
- ✅ Nuevo período aparece en tabla
- ✅ Edición actualiza valores
- ✅ Eliminación remueve de tabla
- ✅ No hay errores de consola

**Status:** [ ] PASS [ ] FAIL

---

### 🧪 TEST 2: Exportar Calendario - Excel

**Objetivo:** Validar exportación a Excel del calendario de mantenimiento.

**Pasos:**
1. Acceder a: `https://test.activosamerimed.com/index.php?page=calendar`
2. Buscar sección con botón **"Exportar"**
3. Hacer clic en dropdown **"Exportar"**
4. Verificar que aparecen campos:
   - Date input "Desde" (prefilled con 1° del mes)
   - Date input "Hasta" (prefilled con último día del mes)
   - Botón "Excel" (verde)
   - Botones "PDF" (rojo)
5. Hacer clic en **"Excel"**
6. Esperar descarga de archivo `calendario_mantenimiento_YYYY-MM-DD.xlsx`
7. Abrir archivo en Excel/LibreOffice
8. Verificar estructura:
   - Encabezado principal: "CALENDARIO DE MANTENIMIENTO"
   - Subtitle: "Periodo: dd/mm/yyyy — dd/mm/yyyy"
   - Columnas: #, Fecha, Hora, Equipo, Num. Inventario, Tipo, Departamento, Estatus
   - Datos populated correctamente
   - Colores aplicados (encabezado azul marino, filas alternadas)
   - Fila de totales al final

**Resultados Esperados:**
- ✅ Dropdown se abre sin errores
- ✅ Campos de fecha están prefilled
- ✅ Archivo descarga exitosamente
- ✅ Formato Excel válido (no corrupto)
- ✅ Encabezados con estilos correctos
- ✅ Datos visibles y legibles
- ✅ Totales calculados correctamente

**Status:** [ ] PASS [ ] FAIL

---

### 🧪 TEST 3: Exportar Calendario - PDF

**Objetivo:** Validar exportación a PDF del calendario.

**Pasos:**
1. En la misma página `?page=calendar`
2. Abrir dropdown **"Exportar"** nuevamente
3. Cambiar rango de fechas a:
   - Desde: 1 mes atrás
   - Hasta: hoy
4. Hacer clic en **"PDF"**
5. Se abre nueva ventana con HTML imprimible
6. Verificar estructura:
   - Encabezado azul con título
   - Campos Meta: Generado, Por, Total
   - Summary Cards: 3 tarjetas (Programados, Completados, Pendientes)
   - Tabla con datos
   - Footer con info del sistema
   - Estilos de color aplicados (text-color por tipo)
7. Ejecutar `CTRL+P` para imprimir
8. Verificar preview de impresión

**Resultados Esperados:**
- ✅ Nueva ventana se abre sin errores
- ✅ HTML se renderiza correctamente
- ✅ Colores visibles (no solo blanco/negro)
- ✅ Tabla legible y formateada
- ✅ Preview de impresión funciona
- ✅ No hay errores de consola

**Status:** [ ] PASS [ ] FAIL

---

### 🧪 TEST 4: Campos Personalizados - UI Administrativa

**Objetivo:** Verificar que se pueden crear, editar, eliminar campos personalizados.

**Pasos:**
1. Acceder a: `https://test.activosamerimed.com/index.php?page=custom_fields`
2. Verificar que tabla carga (puede estar vacía inicialmente)
3. Hacer clic en **"Nuevo Campo"**
4. Ingresar:
   - Tipo de entidad: `Equipo`
   - Nombre interno: `modelo_alternativo`
   - Etiqueta: `Modelo Alternativo`
   - Tipo de campo: `text`
   - Requerido: ☐ (unchecked)
5. Hacer clic en **"Crear"**
6. Verificar que aparece en tabla
7. Hacer clic en **"Editar"** sobre el campo creado
8. Cambiar etiqueta a `Modelo Alternativo Updated`
9. Guardar
10. Verificar actualización en tabla
11. Crear otro campo:
    - Nombre: `garantia_meses`
    - Entidad: `Equipo`
    - Tipo: `number`
    - Requerido: ✓ (checked)
12. Guardar
13. Verificar orden en tabla (debe respetarse sort_order)
14. Eliminar uno de los campos creados
15. Confirmar eliminación

**Resultados Esperados:**
- ✅ UI carga sin errores
- ✅ Modal de creación funciona
- ✅ Nuevo campo aparece en tabla
- ✅ Validación: nombre requerido, etiqueta requerida
- ✅ Edición actualiza tabla
- ✅ Eliminación remueve registro
- ✅ Sort order se respeta
- ✅ Campos del mismo entity_type se agrupan

**Status:** [ ] PASS [ ] FAIL

---

### 🧪 TEST 5: Unidad Integrada - Renderizar Campos Personalizados (OPCIONAL - Avanzado)

**Objetivo:** Verificar que campos personalizados aparecen en formularios de equipos.

**Pasos:**
1. Desde la prueba anterior, tener 2+ campos custom creados para `Equipo`
2. Acceder a formulario de crear nuevo equipo: `?page=equipment_new`
3. Hacer scroll hasta el final de formulario
4. Verificar que existe bloque **"Campos adicionales"** con:
   - Los campos creados con sus etiquetas
   - Inputs correctos (text para modelo, number para garantía)
5. Llenar los campos personalizados
6. Guardar equipo
7. Volver a editar el equipo: `?page=view_equipment&id=[ID]`
8. Verificar que sección "Campos adicionales" contiene valores guardados

**Resultados Esperados:**
- ✅ Bloque "Campos adicionales" visible
- ✅ Campos custom renderizados correctamente
- ✅ Inputs tienen tipos correctos
- ✅ Valores se guardan en BD
- ✅ Valores se recuperan al editar
- ✅ No hay errores de formulario

**Status:** [ ] PASS [ ] FAIL (opcional)

---

## 📊 Resumen de Testing

| Test | Resultado | Notas |
|------|-----------|-------|
| 1. Periodos CRUD | [ ] OK [ ] FAIL | |
| 2. Exportar Excel | [ ] OK [ ] FAIL | |
| 3. Exportar PDF | [ ] OK [ ] FAIL | |
| 4. Custom Fields Admin | [ ] OK [ ] FAIL | |
| 5. Custom Fields Integración | [ ] OK [ ] FAIL | OPCIONAL |

**Resultado Final:** [ ] 4/4 PASS (Aprobar Deploy) [ ] < 4 PASS (Investigar)

---

## 🔧 Troubleshooting

### Problema: "Página no encontrada" al acceder a ?page=maintenance_periods
**Solución:**
- Verificar que `app/routing.php` tiene entrada para `maintenance_periods`
- Regenerar caché si aplica
- Ejecutar `git pull` para traer últimos cambios

### Problema: Tabla de periodos vacía
**Solución:**
- Esto es normal si no hay registros. Crear uno nuevo con el botón "Nuevo Periodo"
- Si debe haber datos, verificar BD directamente: `SELECT * FROM maintenance_periods;`

### Problema: Export Excel descarga pero no abre
**Solución:**
- Verificar que `vendor/autoload.php` existe
- Revisar permisos de carpeta `uploads/`
- Ver logs: `php error_log` en servidor

### Problema: Campos personalizados no aparecen en formulario
**Solución:**
- Verificar que campos están `active = 1` en BD
- Confirmar que `entity_type` coincide (case-sensitive)
- Revisar permisos en módulo `custom_fields`

### Problema: Error 403 en endpoints AJAX
**Solución:**
- Verificar que está logueado como admin (login_type = 1)
- Confirmar que sesión no ha expirado
- Ver logs de servidor para detalles

---

## ✅ Checklist Final

Antes de aprobar para producción:

- [ ] Test 1: Periodos CRUD - 4/4 pasos OK
- [ ] Test 2: Exportar Excel - 7/7 verificaciones OK
- [ ] Test 3: Exportar PDF - 7/7 verificaciones OK
- [ ] Test 4: Custom Fields - 9/9 pasos OK
- [ ] No hay errores en consola del navegador (F12)
- [ ] No hay errores en logs del servidor
- [ ] Todos los botones son interactivos
- [ ] Todos los modales abren/cierran correctamente
- [ ] Paginación en datatables funciona (si aplica)
- [ ] Mobile responsiveness OK (si aplica)

---

**Responsable:** [Nombre]  
**Firma/Checklist:** _______________  
**Fecha de Validación:** _______________
