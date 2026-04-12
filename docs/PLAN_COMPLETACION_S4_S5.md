# SPRINT 4 & 5: Plan de Completación Rápida

**Tiempo Estimado:** 2-3 horas total  
**Estrategia:** Documentar lo preexistente + pequeños ajustes + Deploy

---

## ✅ SPRINT 4: INSUMOS & SUSTANCIAS PELIGROSAS

### Estado Actual: ~90% COMPLETO

**Archivos Funcionales:**
- ✅ `app/views/dashboard/settings/hazardous_materials.php` - Vista completa
- ✅ `database/migrations/018_hazardous_inventory.sql` - Migración lista
- ✅ `app/views/pages/view_inventory.php` - Checkbox is_hazardous
- ✅ `legacy/admin_class.php` - Lógica de guardado
- ✅ `app/views/layouts/sidebar.php` - Menú integrado

**Funcionalidad Verificada:**
- ✅ Tarjetas de resumen (Total, Sin SDS)
- ✅ Toggle "Sustancia Peligrosa" en formulario insumo
- ✅ Listado filtrado WHERE is_hazardous = 1
- ✅ Permisos: can('view', 'hazardous_materials')
- ✅ Multi-branch support con branch_sql()

**Falta (Menor):**
- ⏳ Botón "Exportar a Excel" en vista hazardous
- ⏳ Modal upload documentación (SDS, certificados)
- ⏳ Galería de documentos adjuntos

**Esfuerzo Faltante:** ~30-45 min (agregar export + upload)

---

## ✅ SPRINT 5: REPORTES & BRANDING  

### E6.1 - Consumo Eléctrico: ~80% COMPLETO

**Funcionalidad Lista:**
- ✅ Vista: `sprint5_reports.php`
- ✅ Cálculo kWh: `(power_w * usage_hours * 30) / 1000`
- ✅ Agrupación: Por departamento
- ✅ Filtros: Department select
- ✅ Tabla: Equipos com kWh ordenados DESC
- ✅ Export: `app/helpers/export_sprint5_reports.php`

**Falta:**
- ⏳ Gráfica de barras por departamento (Chart.js)
- ⏳ Tabla de resumen total kWh por depto

**Esfuerzo:** ~20-30 min

---

### E6.2 - Top Accesorios: ~70% COMPLETO

**Funcionalidad:**
- ✅ Query grouping accesorios
- ✅ Ranking por monto gasto
- ✅ Integrado en `sprint5_reports.php`

**Falta:**
- ⏳ Refinamiento de query si `accessories.equipment_id` existe
- ⏳ Gráfica Top 10

**Esfuerzo:** ~20 min

---

### E6.3 - Ranking Tickets/Reportes: ~75% COMPLETO

**Funcionalidad:**
- ✅ Queries implementadas
- ✅ Tablas de datos
- ✅ Integrado en sprint5_reports.php

**Falta:**
- ⏳ Gráficas horizontales

**Esfuerzo:** ~15 min

---

### E7.1 - Upload Logo: ~60% COMPLETO

**Funcionalidad:**
- ✅ Tabla `system_info` existe
- ✅ Campo meta_value para guardar logo

**Falta:**
- ⏳ UI: Formulario con file upload
- ⏳ Controller: Procesar upload
- ⏳ Preview del logo actual

**Esfuerzo:** ~40-50 min (crear UI nueva)

---

### E7.2 - Logo en PDFs: ~50% COMPLETO

**Funcionalidad:**
- ✅ FPDF integrado
- ✅ Helpers de PDF existen

**Falta:**
- ⏳ Lógica: Recuperar logo desde BD
- ⏳ Inyectar en encabezado de PDFs
- ⏳ Testing en 3+ PDFs (maintenance, equipment, etc.)

**Esfuerzo:** ~40-60 min

---

### E7.3 - Auditoría Exports: ~70% COMPLETO

**Funcionalidad:**
- ✅ 5+ helpers export creados
- ✅ PhpSpreadsheet estandarizado
- ✅ Columnas con widths

**Falta:**
- ⏳ Auditoría final en cada export
- ⏳ Estandarizar header/footer
- ⏳ Validar permisos can_export

**Esfuerzo:** ~30 min

---

## 🎯 Orden de Trabajo Recomendado

### Fase 1: Documentación (15 min)
- [ ] Crear estado de SPRINT 4
- [ ] Crear estado de SPRINT 5  
- [ ] Documentar lo que está en TEST

### Fase 2: Completar SPRINT 4 (30-45 min)
- [ ] Agregar export Excel a hazardous_materials
- [ ] Agregar modal upload documentos
- [ ] Testing básico

### Fase 3: Completar SPRINT 5 (120-150 min)
- [ ] E6.1: Agregar gráficas de barras
- [ ] E6.2: Refinar query, gráfica Top 10
- [ ] E6.3: Agregar gráficas
- [ ] E7.1: Crear UI upload logo
- [ ] E7.2: Integrar logo en 3 PDFs
- [ ] E7.3: Auditoría final

### Fase 4: Deploy (15 min)
- [ ] Git commit todo
- [ ] Git push
- [ ] Deploy a TEST (deploy.ps1)

### Fase 5: Documentación Testing (30 min)
- [ ] Crear guía manual testing S4+S5
- [ ] Script automatizado
- [ ] Reporte

**Tiempo Total Estimado:** 3-4 horas

---

## 🚀 Mi Recomendación

Dado que el usuario solicita "finalizar todo", sugiero:

1. **Completar lo que falta** de SPRINT 4 (40 min de trabajo)
2. **Completar lo que falta** de SPRINT 5 (100-120 min)
3. **Git commit + Deploy** (20 min)
4. **Documentar testing** (30 min)

**Total: ~4 horas**

El usuario luego hace testing manual + recopila feedback que podría resultar en ajustes menores.

---

**¿Procedo con completación de SPRINT 4 & 5?**
