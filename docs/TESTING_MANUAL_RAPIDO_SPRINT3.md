# ⚡ TESTING MANUAL RÁPIDO - SPRINT 3

**URL Base:** https://test.activosamerimed.com  
**Tiempo Total:** ~25 minutos  
**Resultado Esperado:** 5/5 PASS

---

## 🧪 TEST 1: Periodos CRUD (5 min)

### Acceder
👉 **Ir a:** https://test.activosamerimed.com/index.php?page=maintenance_periods

### Validar Tabla
- [ ] Tabla carga con periodos existentes
- [ ] Mínimo 5 periodos visibles

### Crear Nuevo
1. Clickear **"Nuevo Periodo"**
2. Ingresar:
   - Nombre: `Test Sprint3`
   - Intervalo: `88`
3. Clickear **"Guardar"**
4. [ ] Toast verde: "Periodo creado"
5. [ ] Nuevo período aparece en tabla

### Editar
1. Clickear **"Editar"** en el período que creaste
2. Cambiar intervalo a: `90`
3. Guardar
4. [ ] Tabla actualiza con nuevo valor

### Eliminar
1. Clickear **"Eliminar"** en el período
2. Confirmar en popup
3. [ ] Desaparece de tabla

✅ **TEST 1 RESULTADO:** [ ] PASS [ ] FAIL

---

## 🧪 TEST 2: Exportar Excel (5 min)

### Acceder
👉 **Ir a:** https://test.activosamerimed.com/index.php?page=calendar

### Ubicar Botón
- [ ] Buscar botón verde **"Exportar"**
- [ ] Hacer click en dropdown

### Exportar
1. Verificar date inputs:
   - [ ] "Desde" prefilled con 1° del mes
   - [ ] "Hasta" prefilled con último día del mes
2. Clickear **"Excel"** (botón verde)
3. [ ] Se descarga archivo `calendario_mantenimiento_YYYY-MM-DD.xlsx`

### Verificar Contenido
1. Abrir archivo en Excel/LibreOffice
2. [ ] Encabezado principal: "CALENDARIO DE MANTENIMIENTO"
3. [ ] Subtitle con período
4. [ ] Columnas: #, Fecha, Hora, Equipo, Num. Inv., Tipo, Depto, Estatus
5. [ ] Datos populated
6. [ ] Encabezado azul marino (color importado)
7. [ ] Filas alternadas con colores
8. [ ] Fila de TOTALES al final con sumas
9. [ ] Freeze panes en fila 4 (puede scrollear)

✅ **TEST 2 RESULTADO:** [ ] PASS [ ] FAIL

---

## 🧪 TEST 3: Exportar PDF (3 min)

### Acceder (mismo sitio)
👉 En la misma página `?page=calendar`

### Exportar
1. Clickear **"Exportar"** dropdown nuevamente
2. Clickear **"PDF"** (botón rojo)
3. [ ] Se abre nueva ventana/tab

### Verificar Estructura
- [ ] Encabezado azul marino con título
- [ ] Línea: "Periodo: dd/mm/yyyy — dd/mm/yyyy"
- [ ] 3 Tarjetas: Programados | Completados | Pendientes
- [ ] Tabla con datos
- [ ] Colores por tipo de mantenimiento
- [ ] Badges de estado (Completado verde, Pendiente naranja)
- [ ] Footer con info del sistema

### Imprimir
1. Presionar **CTRL+P**
2. [ ] Preview de impresión se abre
3. [ ] Diseño es legible en vista de impresión
4. [ ] Estilos de color se mantienen visibles

✅ **TEST 3 RESULTADO:** [ ] PASS [ ] FAIL

---

## 🧪 TEST 4: Campos Personalizados Admin (5 min)

### Acceder
👉 **Ir a:** https://test.activosamerimed.com/index.php?page=custom_fields

### Crear Campo
1. Clickear **"Nuevo Campo"**
2. Modal abre, ingresar:
   - Tipo de entidad: `Equipo` (select)
   - Nombre interno: `modelo_alternativo`
   - Etiqueta: `Modelo Alternativo`
   - Tipo de campo: `text`
   - [ ] Requerido: UNCHECKED
3. Clickear **"Crear"**
4. [ ] Toast verde: "Campo creado"
5. [ ] Campo aparece en tabla

### Editar
1. Clickear **"Editar"** en el campo creado
2. Cambiar etiqueta a: `Modelo Alternativo v2`
3. Clickear **"Guardar"**
4. [ ] Tabla actualiza etiqueta

### Crear Segundo Campo
1. **"Nuevo Campo"** nuevamente
2. Ingresar:
   - Entidad: `Equipo`
   - Nombre: `garantia_anos`
   - Etiqueta: `Garantía (años)`
   - Tipo: `number`
   - [ ] Requerido: CHECKED ✓
3. Crear
4. [ ] Aparece en tabla
5. [ ] Respeta sort_order (orden de creación)

### Eliminar
1. Clickear **"Eliminar"** en el primer campo
2. Confirmar eliminación
3. [ ] Desaparece de tabla
4. [ ] Segundo campo se mantiene

✅ **TEST 4 RESULTADO:** [ ] PASS [ ] FAIL

---

## 🧪 TEST 5: Campos en Formulario Equipo (7 min) - OPCIONAL PERO RECOMENDADO

### Crear Equipo
👉 **Ir a:** https://test.activosamerimed.com/index.php?page=equipment_new

### Scroll al Final
1. Llenar campos básicos (Nombre, Marca, etc.)
2. [ ] Scroll DOWN hasta el final del formulario
3. [ ] Bloque **"Campos adicionales"** es visible

### Verificar Campos
- [ ] Se ven los campos custom creados en TEST 4
- [ ] Input type correcto:
  - `Modelo Alternativo v2` → tipo TEXT
  - `Garantía (años)` → tipo NUMBER

### Llenar Valores
1. Ingresar valores en los dos campos custom
2. Clickear **"Guardar"** o **"Crear Equipo"**

### Verificar Recuperación
1. Buscar el equipo creado
2. Clickear para editar: `?page=view_equipment&id=[ID]`
3. [ ] Bloque "Campos adicionales" aparece nuevamente
4. [ ] Los valores guardados están presentes
5. [ ] Se pueden editar nuevamente

✅ **TEST 5 RESULTADO:** [ ] PASS [ ] FAIL (OPCIONAL)

---

## 📊 RESUMEN TESTING

| Test | Pasos | Resultado |
|------|-------|-----------|
| 1. Periodos CRUD | 4 sub-tests | [ ] PASS |
| 2. Export Excel | 9 verificaciones | [ ] PASS |
| 3. Export PDF | 8 verificaciones | [ ] PASS |
| 4. Custom Fields Admin | 9 pasos | [ ] PASS |
| 5. Custom Fields UI | 8 pasos (opcional) | [ ] PASS |

---

## ✅ RESULTADO FINAL

**Completado:** ___ de 5 tests

**Si 5/5 PASS:** ✅ **SPRINT 3 APROBADO PARA PRODUCCIÓN**

**Si <5/5 PASS:** 🔧 Investigar failures específicas

---

## 🔗 Links Útiles

| Item | Link |
|------|------|
| Periodos | https://test.activosamerimed.com/index.php?page=maintenance_periods |
| Calendario | https://test.activosamerimed.com/index.php?page=calendar |
| Custom Fields | https://test.activosamerimed.com/index.php?page=custom_fields |
| Crear Equipo | https://test.activosamerimed.com/index.php?page=equipment_new |

---

## 💡 Tips

- Abre los 4 links en pestañas separadas para navegar rápido
- Los toasts (mensajes verdes/rojos) confirman acciones
- F12 para abrir DevTools si hay errores
- CTRL+SHIFT+K en navegador para limpiar caché si algo se vuelve raro

---

**Fecha Inicio:** _______________  
**Responsable:** _______________  
**Hora Fin:** _______________  
**Notas:** 

