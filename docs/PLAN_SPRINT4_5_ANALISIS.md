# PLAN CONSOLIDADO - SPRINT 4 & SPRINT 5

**Estado:** Análisis de código preexistente

---

## 🔍 SPRINT 4 - Insumos & Sustancias Peligrosas

### E5.1 - Flag Sustancia Peligrosa + Upload Documentación

**Preexistente:**
- ✅ Migración: `database/migrations/018_hazardous_inventory.sql`
- ✅ Campos en `inventory`: is_hazardous, hazard_class, safety_data_sheet
- ✅ Tabla: `inventory_documents` (structure complete)
- ✅ UI parcial: Checkbox en `app/views/pages/view_inventory.php` (línea 151-158)
- ✅ Toggle JS: `$('#edit_is_hazardous').on('change', ...)` (línea 403)
- ✅ Permisos integrados: `legacy/admin_class.php` (línea 3612+)

**Status:** ~80% completado, solo falta finalizar upload de documentos

---

### E5.2 - Módulo Sustancias Peligrosas

**Preexistente:**
- ✅ Vista: `app/views/dashboard/settings/hazardous_materials.php` (existe)
- ✅ Ruta: `app/routing.php` (línea 138: hazardous_materials)
- ✅ Menú: `app/views/layouts/sidebar.php` (línea 124-128 con permisos check)
- ✅ Migración: `database/migrations/018_hazardous_inventory.sql` (insert module)

**Status:** ~90% completado, estructura lista

---

## 🔍 SPRINT 5 - Reportes & Branding

### E6.1 - Consumo Eléctrico (kWh)

**Preexistente:**
- ✅ Vista: `app/views/dashboard/reports/sprint5_reports.php` (existe)
- ✅ Cálculo kWh: Fórmula implementada en queries (kwh_monthly)
- ✅ Agrupación por departamento: Logic en vista (línea 68)
- ✅ Export: `app/helpers/export_sprint5_reports.php` (existe)
- ✅ Tabla de datos con kWh calculado

**Status:** ~85% completado, ajustes finales

---

### E6.2 - Top Equipos Mayor Gasto Accesorios

**Preexistente:**
- ✅ Query con kWh: Ya calcula gasto
- ✅ Ranking: Implementado en sprint5_reports.php
- ✅ Gráficas: Chart.js presente en vista

**Status:** ~75% completado, necesita refinamiento de accesorios

---

### E6.3 - Ranking Equipos Más Tickets/Reportes

**Preexistente:**
- ✅ Queries: Agrupación por equipment_id
- ✅ Tablas: `tickets`, `maintenance_reports` linked
- ✅ Vista: Parte de `sprint5_reports.php`

**Status:** ~75% completado

---

### E7.1 - Upload Logo Organización

**Preexistente:**
- ✅ Tabla: `system_info` (tiene meta_field, meta_value)
- ✅ Campo: Logo guardado en meta_value

**Status:** ~60% completado, necesita UI/controller

---

### E7.2 - Logo Dinámico en PDFs

**Preexistente:**
- ✅ Header system: Puede inyectar logo en FPDF
- ✅ PDFs: `app/helpers/equipment_report_pdf.php` + otros

**Status:** ~50% completado, necesita integración

---

### E7.3 - Auditar Exports Excel

**Preexistente:**
- ✅ Exports: 5+ helpers creados (export_*.php)
- ✅ PhpSpreadsheet: Ya integrado
- ✅ Estandarización: Parcialmente hecha

**Status:** ~70% completado, auditoría final

---

## 📊 Resumen Implementación

| SPRINT | Status | Archivos Listos | Falta |
|--------|--------|-----------------|-------|
| 4 | 85% | 6 archivos | Finalizar uploads |
| 5 | 73% | 7 archivos | Refinamiento + UI |

---

## 🎯 Estrategia

1. **Documentar estado actual** de código preexistente
2. **Crear migraciones** para tablas pendientes (si aplica)
3. **Completar funcionalidad** con endpoints AJAX faltantes
4. **Finalizar UI** administrativa
5. **Git commit + Deploy** a TEST (como SPRINT 3)
6. **Documentación completa** de testing

---

**Próximo paso:** Comenzar SPRINT 4 full implementation
