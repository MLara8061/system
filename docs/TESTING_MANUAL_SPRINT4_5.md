# Guía de Testing Manual - SPRINT 4 & 5
## Validación en TEST - 2026-04-11

**URL TEST:** https://test.activosamerimed.com  
**Deployment:** Commit dba339b  
**Cobertura:** 2 épicas SPRINT 4 (E5.1-E5.2) + 8 épicas SPRINT 5 (E6.1-E7.3)  
**Estimado:** 45 minutos

---

## SPRINT 4: Insumos & Sustancias Peligrosas (90% Complete)

### E5.1 - Flag Sustancia Peligrosa + Documentación

**Test 1: Checkbox Sustancia Peligrosa en Inventario**
```
Steps:
1. Login como Admin
2. Ir a: Inventario > Ver Inventario
3. Seleccionar cualquier insumo
4. Buscar campo "¿Es Sustancia Peligrosa?" (checkbox)
5. Marcar checkbox → Guardar
6. Recargar página → Verificar checkbox persiste

Expected:
✅ Checkbox visible y funcional
✅ Cambios persisten en base de datos
✅ Campo "Clase Peligro" visible si está checked
```

**Test 2: Hoja de Seguridad (SDS) Upload**
```
Steps:
1. En mismo insumo (E5.1) con checkbox marcado
2. Buscar botón "Adjuntar Hoja de Seguridad"
3. Click → Modal de upload
4. Seleccionar archivo PDF/DOC (< 5MB)
5. Upload → Verificar archivo listado
6. Click en archivo → Descarga correcta

Expected:
✅ Modal abre sin errores
✅ Archivo sube a /uploads/safety_sheets/{id}/
✅ Se lista en tabla de adjuntos
✅ Descarga funciona
```

**Test 3: Validaciones**
```
Steps:
1. Intentar upload sin seleccionar archivo
2. Intentar upload tipo inválido (exe, zip)
3. Intentar upload > 5MB
4. Submit form vacío

Expected:
❌ Todos rechazados con mensaje claro
✅ Mensajes: "Archivo requerido", "Tipo no permitido", "Max 5MB"
```

---

### E5.2 - Módulo Sustancias Peligrosas

**Test 4: Acceso al Módulo**
```
Steps:
1. Login como Admin → Dashboard
2. Sidebar izquierdo: Buscar "Sustancias Peligrosas"
3. Click en menú

Expected:
✅ URL: ?page=hazardous_materials
✅ Carga sin errores
✅ Permisos validados
```

**Test 5: Tarjetas Resumen**
```
En página /hazardous_materials:
1. Visible 4 tarjetas: Total, Activos, Por Clase, Por Rama
2. Números coinciden con BD

Expected:
✅ Cards mostrando conteos correctos
✅ Formato: "X insumos" o similar
```

**Test 6: Tabla Insumos Peligrosos**
```
Steps:
1. En /hazardous_materials
2. Tabla listando solo WHERE is_hazardous=1
3. Columnas: ID, Nombre, Clase, Rama, Acción
4. Filtro rama funcional

Expected:
✅ Tabla carga sin errores
✅ Solo insumos peligrosos listados
✅ Filtro rama filtra correctamente
✅ Paginación si > 50 items
```

**Test 7: Exportar Excel**
```
Steps:
1. En /hazardous_materials, buscar botón "Exportar Excel"
2. Click → Descarga archivo .xlsx
3. Abrir en Excel/LibreOffice
4. Verificar:
   - Encabezados: ID, Nombre, Clase, SDS, Rama, Estado
   - Datos correctos
   - Formato: ancho columnas, colores

Expected:
✅ Descarga sin errores
✅ Archivo válido y abre correctamente
✅ Todos los insumos peligrosos incluidos
```

---

## SPRINT 5: Reportes & Branding (75% Complete)

### E6.1 - Consumo Eléctrico (kWh)

**Test 8: Reporte Consumo Eléctrico**
```
Steps:
1. Login → Reportes > Consumo Eléctrico
2. Buscar ruta: ?page=reports&section=energy_consumption
3. Tabla con columnas: Equipo, Potencia(W), Horas/Día, kWh/Mes, Costo
4. Filtro por Departamento (si aplica)

Expected:
✅ Tabla carga sin errores
✅ Cálculo kWh = (Potencia * 24 * 30) / 1000
✅ Filtro funciona correctamente
✅ Totales fila al pie
```

**Test 9: Gráfica Consumo (si implementada)**
```
Steps:
1. En consumo eléctrico
2. Buscar gráfica de barras
3. Eje X: Departamentos
4. Eje Y: kWh/Mes

Expected:
✅ Gráfica visible (Chart.js)
✅ Datos corresponden a tabla
✅ Hover muestra valores
```

**Test 10: Exportar Consumo**
```
Steps:
1. Botón "Exportar Excel"
2. Descarga archivo
3. Verificar columnas y fórmulas

Expected:
✅ Excel con fórmulas funcionales
✅ Formato PhpSpreadsheet (ancho, headers azules)
```

---

### E6.2 - Top Equipos Mayor Gasto Accesorios

**Test 11: Ranking Accesorios**
```
Steps:
1. Reportes > Top Equipos Accesorios (o en sprint5_reports)
2. Tabla Top 10: Equipo, Cantidad Accesorios, Gasto Total
3. Ordenado DESC por gasto

Expected:
✅ Top 10 listados correctamente
✅ Gasto = SUM(amount) donde equipment_id coincide
✅ Ordenamiento DESC funciona
```

**Test 12: Gráfica Top 10 (si implementada)**
```
Steps:
1. Gráfica barras horizontales
2. TOP 5-10 equipos con mayor gasto

Expected:
✅ Gráfica visible
✅ Valores corresponden a tabla
```

---

### E6.3 - Ranking Tickets/Reportes por Equipo

**Test 13: Dos Tabs - Más Tickets**
```
Steps:
1. Reportes > Ranking Equipos Tickets
2. Tab "Más Tickets Abiertos"
3. Tabla Top 20: Equipo, Contador Tickets, Último, Status

Expected:
✅ Tab y tabla visibles
✅ Datos correctos (COUNT(*) FROM tickets)
✅ Top 20 ordenado DESC
```

**Test 14: Tab Más Reportes**
```
Steps:
1. Mismo reporte, tab "Más Reportes"
2. Tabla Top 20: Equipo, Contador Reportes

Expected:
✅ Tab accesible
✅ Datos correctos
```

---

### E2.5 - Tickets + Tiempo Promedio Respuesta

**Test 15: Dashboard Tickets**
```
Steps:
1. Dashboard > Widget Tickets
2. Verificar contadores por estado: Abiertos, En Progreso, Cerrados

Expected:
✅ Contadores actualizados
✅ Click en contador filtra a tabla tickets
```

---

### E7.1 - Upload Logo

**Test 16: Página Configuración Branding**
```
Steps:
1. Admin > Configuración > Branding (si existe)
2. O: Ajustes > Logo Sistema
3. Buscar upload logo

Expected:
✅ Formulario visible
✅ Campo de subida (drag-n-drop o file picker)
```

**Test 17: Upload Logo**
```
Steps:
1. Seleccionar imagen PNG o SVG (< 2MB, 200x50px ideal)
2. Upload
3. Guardar

Expected:
✅ Archivo sube correctamente
✅ Imagen guardada en BD
✅ Preview visible en página
```

---

### E7.2 - Logo en PDFs

**Test 18: Generar PDF con Logo**
```
Steps:
1. Ir a cualquier reporte (Equipo, Ticket, Mantenimiento)
2. Botón "Generar PDF"
3. Abrir PDF descargado

Expected:
✅ PDF abre correctamente
✅ Logo visible en header/footer
✅ Logo correcto (el que subiste en E7.1)
✅ Tamaño proporcional, no distorsionado
```

---

### E7.3 - Auditoría de Exports

**Test 19: Verificar Exports Estandarizados**
```
Para cada export (accesible desde reportes/listas):

1. Equipos Bajas (si existe)
   - Botón Exportar Excel
   - Verificar columnas: ID, Nombre, Motivo, Fecha, Usuario

2. Calendario Mantenimiento
   - Botón Exportar Excel + PDF
   - Verificar formato: Blue header, color-coded status

3. Tickets (si existe export)
   - Botón Exportar Excel
   - Verificar: Totales fila, ancho columnas

Expected:
✅ Todos los exports funcionan
✅ Formato consistente (PhpSpreadsheet)
✅ Sin errores 500
✅ Descargas sin timeout
```

---

## Checklist Final

```
SPRINT 4 (E5.1-E5.2):
□ Checkbox sustancia peligrosa funciona
□ Upload SDS sin errores
□ Módulo accesible desde menú
□ Tarjetas resumen correctas
□ Tabla insumos peligrosos filtra bien
□ Excel export completo

SPRINT 5 (E6.1-E7.3):
□ Reporte consumo eléctrico calcula kWh
□ Ranking accesorios Top 10 correcto
□ Ranking tickets/reportes dos tabs
□ Dashboard tickets actualizado
□ Logo upload funciona
□ Logo en PDFs visible
□ Todos exports estandarizados
```

---

## Feedback Esperado

Por favor reportar cualquiera de:
- ❌ Errores 500 o páginas rotas
- ⚠️ Cálculos incorrectos
- 📊 Gráficas faltantes
- 🎨 Diseño inconsistente
- ⚡ Performance issues (carga lenta)
- 🔐 Problemas de permisos

**Formato:** Capture screenshot + descripción + URL si aplica

---

**Estimado:** 45 min  
**Fecha Planned:** 2026-04-11  
**Resultado esperado:** SPRINT 4 & 5 listos para producción o con minor tweaks identificados
