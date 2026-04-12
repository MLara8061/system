# VALIDACIÓN EN TEST - SPRINT 1

## 🎯 Objetivo
Validar que todos los 8 problemas corregidos funcionan correctamente en:
**https://test.activosamerimed.com**

---

## ✅ Validación Inmediata

### 1️⃣ Acceso Básico (1 minuto)
```
Abre: https://test.activosamerimed.com
Si ves dashboard:
  ✅ PASS - Sistema funcionando
Si ves error blanco:
  ❌ FAIL - Verificar logs PHP
```

### 2️⃣ Consola del Navegador (1 minuto)
```
Presiona: F12
Pestaña: Console
Busca: Errores rojos o advertencias críticas
Si hay errores:
  ❌ FAIL - Reportar en BUG_REPORT_TEST.md
Si consola limpia:
  ✅ PASS
```

---

## 🧪 8 Tests Funcionales (~20 minutos)

### TEST 1: O.T Duplicada ⏱️ 2 min

**Procedimiento:**
```
1. Dashboard → Reportes de Mantenimiento
2. Click "Crear Nuevo Reporte"
3. Completar formulario mínimo
4. Click "GUARDAR"
5. Ver página guardada - anota O.T (ej: OS-2026-001)

6. Repetir pasos 2-5 dos veces más
7. Verificar O.T nuevas: OS-2026-002, OS-2026-003
```

**Expected:**
- ✅ Cada guardado = O.T único
- ✅ No repite números
- ✅ Formato: OS-YYYY-###

**Si falla:**
- Revisar: legacy/equipment_report_sistem_add.php
- Logs: error_log del servidor

---

### TEST 2: Contadores Tickets ⏱️ 1 min

**Procedimiento:**
```
1. Dashboard → Tickets
2. Ver números en cards:
   - Verde: "Abiertos"
   - Azul: "En Proceso"  
   - Rojo: "Finalizados"
3. Verificar que NO están todos en 0
```

**Expected:**
- ✅ Al menos uno muestra número > 0
- ✅ Si todos en 0, revisar BD

**Si falla:**
- Revisar: error_log (buscar [TICKET_DASHBOARD])
- Verificar: tabla tickets en BD

---

### TEST 3: Excel - Columnas ⏱️ 3 min

**Procedimiento:**
```
1. Ir a: Proveedores / Equipos / Tickets / Calendario
2. Encontrar botón "EXPORTAR"
3. Click "Exportar como Excel"
4. Abrir archivo descargado
5. Revisar columnas:
   - ¿Tienen ancho?
   - ¿Se leen los textos?
   - ¿Headers con fondo gris?
```

**Expected:**
- ✅ Columnas con espacio visible
- ✅ Textos legibles SIN ajuste
- ✅ Headers formateados (bold, gris)

**Si falla:**
- Revisar: app/helpers/export_*.php
- Buscar: setWidth() en código

---

### TEST 4: Fotos Adjuntos ⏱️ 2 min

**Procedimiento:**
```
1. Dashboard → Generar Reporte
2. Buscar sección: "Fotos"
3. Click "Agregar Foto"
4. Seleccionar imagen (JPG, PNG)
5. Esperar carga
6. Verificar foto aparece en lista
7. Guardar reporte
```

**Expected:**
- ✅ No error "No se permiten adjuntar..."
- ✅ Foto se carga sin problema
- ✅ Se guarda en /uploads/reports/

**Si falla:**
- Revisar: permisos carpeta /uploads/
- Logs: public/ajax/report_attachment.php

---

### TEST 5: Reportes NO Duplican ⏱️ 2 min

**Procedimiento:**
```
1. Crear y guardar reporte nuevo
2. Esperar a redirect
3. Verificar URL: ?page=maintenance_reports&msg=saved
4. Presionar F5 (REFRESH) en navegador
5. Presionar F5 dos veces más
6. Contar reportes en tabla
```

**Expected:**
- ✅ Redirect a dashboard (NO a report_pdf)
- ✅ Reporte aparece 1 sola vez
- ✅ F5 no duplica

**Si falla:**
- Revisar: legacy/generate_pdf.php redirect
- Buscar: maintenance_reports en URL

---

### TEST 6: Hoja Seguridad ⏱️ 2 min

**Procedimiento:**
```
1. Inventario → Insumos
2. Buscar insumo con "Hazardoso" = Sí
3. Verificar caso A: Si archivo existe
   → Botón "Ver Hoja" clickable
   → Abre PDF

4. Buscar otro insumo hazardoso sin archivo
   → Botón DISABLED
   → Text: "Archivo no disponible"
```

**Expected:**
- ✅ Archivo existe = Link funcional
- ✅ Archivo no existe = Botón disabled
- ✅ Sin errores 404

**Si falla:**
- Revisar: app/views/pages/view_inventory.php
- Buscar: file_exists() check

---

### TEST 7: Dashboard - Filtros Fecha ⏱️ 3 min

**Procedimiento:**
```
1. Dashboard (home)
2. Lado derecho, ver filtros:
   - Botones: 6M, 12M, Este Año, Todo
   - NUEVOS: "Desde" y "Hasta" (datepickers)
   
3. Click "6 Meses":
   → Gráficos se actualizan
   → Título muestra "Últimos 6 meses"

4. Ingresar fechas custom:
   Desde: 2026-01-01
   Hasta: 2026-04-01
   Click "Filtrar"
   → Gráficos se actualizan
   → Título: "Del 01/01/2026 al 01/04/2026"

5. Test validaciones:
   - Dejar vacía una fecha, click Filtrar
     → Alerta: "Selecciona ambas fechas"
   - Poner Desde > Hasta
     → Alerta: "Fecha inicio debe ser menor"
```

**Expected:**
- ✅ Botones predefindos funcionan
- ✅ Datepickers funcionan
- ✅ Gráficos se actualizan dinámicamente
- ✅ Validaciones presentes

**Si falla:**
- Revisar: app/views/dashboard/home.php
- Consultor: HTML datepicker, JS functions

---

### TEST 8: Bajas Equipos - Filtros + Export ⏱️ 5 min

**Procedimiento:**
```
1. Dashboard → Bajas de Equipos

2. Ver nuevos elementos:
   - Campo "Desde" (datepicker)
   - Campo "Hasta" (datepicker)
   - Botón "Filtrar" (azul)
   - Botón "Limpiar" (gris)
   - Botón "Excel" (verde)

3. Filtrar:
   Desde: 2026-01-01
   Hasta: 2026-04-01
   Click "Filtrar"
   → Tabla se actualiza
   → Solo muestra bajas en ese rango
   → Título actualiza

4. Click "Limpiar":
   → Campos se vacían
   → Tabla muestra TODAS las bajas

5. Click "Excel":
   → Inicia descarga: Bajas_Equipos_2026-04-11_19xx.xlsx

6. Abrir Excel:
   → Columnas: Folio, Equipo, N° Inv., etc.
   → Datos corresponden al rango filtrado
   → Anchos legibles
```

**Expected:**
- ✅ Filtros funcionan
- ✅ Export descarga archivo
- ✅ Excel tiene datos correctos
- ✅ Filtro + export juntos OK

**Si falla:**
- Revisar: app/views/dashboard/equipment/unsubscribe_report.php
- Archivo: app/helpers/export_equipment_bajas.php

---

## 📊 Puntuación Final

```
[ ] Test 1: O.T Duplicada           (1/8) ___/10 pts
[ ] Test 2: Contadores Tickets      (2/8) ___/10 pts
[ ] Test 3: Excel Columnas          (3/8) ___/10 pts
[ ] Test 4: Fotos Adjuntos          (4/8) ___/10 pts
[ ] Test 5: Reportes NO Duplican    (5/8) ___/10 pts
[ ] Test 6: Hoja Seguridad          (6/8) ___/10 pts
[ ] Test 7: Dashboard Fechas        (7/8) ___/10 pts
[ ] Test 8: Bajas + Export          (8/8) ___/10 pts

TOTAL: ___ / 80 pts

Escala:
  80 pts = 100% - ✅ LISTO PARA PRODUCCIÓN
  70-79  = 87%  - ⚠️ Revisar fallos menores
  60-69  = 75%  - 🔧 Necesita fixing
  <60    = <75% - ❌ No apto aún
```

---

## 🐛 Si Hay Fallos

### Opción 1: Reportar Bug
```
Crear archivo: docs/BUG_REPORT_TEST_[NUMERO].md

Incluir:
- Qué test falló (número)
- Pasos exactos para reproducir
- Error visible / logs relevantes
- Screenshot si aplica
- Navegador usado
- Version TEST URL
```

### Opción 2: Investigar Inmediatamente
```
1. Revisar error_log del servidor
2. Abrir F12 Console en navegador
3. Verificar archivo PHP relevante
4. Comparar con VALIDACION_ESTATICA_SPRINT1.md
```

### Opción 3: Rollback de Emergencia
```powershell
# Si TODOS los tests fallan:
.\deploy.ps1 -Target test  # Redeploy última versión
# O revertir commit:
git revert HEAD
.\deploy.ps1 -Target test
```

---

## ✅ Próximos Pasos

### Si 8/8 PASS (80 pts):
```
1. Documentar: "SPRINT 1 - TEST VALIDADO ✅"
2. Notificar cliente: "Cambios listos en TEST"
3. Programar despliegue a PRODUCCIÓN
4. Ejecutar: .\deploy.ps1 -Target prod
```

### Si <8/8 (hay fallos):
```
1. Investigar fallos específicos
2. Corregir código en DEV
3. Nuevo commit: "SPRINT 1 - Fix [problema]"
4. Redeployed a TEST: .\deploy.ps1 -Target test
5. Re-validar tests
```

---

## 📞 Contacto

**Entorno TEST:** https://test.activosamerimed.com
**Duración estimada:** 25-30 minutos
**Documentación:** docs/QUICK_TEST_SPRINT1.md

