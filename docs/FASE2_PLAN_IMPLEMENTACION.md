# FASE 2 -- Plan de Implementacion

**Fecha:** 19 de marzo de 2026  
**Total de requerimientos:** 22  
**Agrupacion:** 7 epicas / sprints logicos  
**Criterios de prioridad:** impacto en operacion > dependencias tecnicas > esfuerzo

---

## Resumen Ejecutivo

| # | Epica | Items | Prioridad | Esfuerzo estimado |
|---|-------|-------|-----------|-------------------|
| E1 | Auditoria y Logging | 3 | CRITICA | Medio |
| E2 | Tickets y Comunicacion | 5 | ALTA | Alto |
| E3 | Equipos y Catalogo | 4 | ALTA | Medio |
| E4 | Mantenimiento y Calendario | 2 | ALTA | Medio |
| E5 | Insumos y Sustancias Peligrosas | 3 | MEDIA | Alto |
| E6 | Reportes y Exportaciones | 3 | MEDIA | Medio |
| E7 | Branding y Configuracion | 2 | BAJA | Bajo |

---

## E1 -- AUDITORIA Y LOGGING (Prioridad: CRITICA)

> Se implementa primero porque los interceptores de auditoria deben estar activos antes de construir el resto de modulos. Asi todo lo nuevo queda registrado desde el dia uno.

### E1.1 -- Tabla `audit_logs` + interceptores automaticos en controllers

**Estado actual:** Existe `activity_log` con columnas basicas (user_id, action, table_name, record_id, ip_address, created_at).  
**Diferencia con lo solicitado:** Falta old_values / new_values (delta de cambios), modulo, user_agent y tipo de accion estandarizado (creo, actualizo, elimino, movio).

**Migracion SQL:**
```sql
CREATE TABLE audit_logs (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    module        VARCHAR(60)  NOT NULL COMMENT 'equipment, ticket, inventory...',
    action        ENUM('create','update','delete','move','login','logout','export') NOT NULL,
    table_name    VARCHAR(100) NOT NULL,
    record_id     INT          DEFAULT NULL,
    old_values    JSON         DEFAULT NULL,
    new_values    JSON         DEFAULT NULL,
    ip_address    VARCHAR(45)  DEFAULT NULL,
    user_agent    VARCHAR(255) DEFAULT NULL,
    branch_id     INT UNSIGNED DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module     (module),
    INDEX idx_action     (action),
    INDEX idx_user       (user_id),
    INDEX idx_table      (table_name),
    INDEX idx_created    (created_at),
    INDEX idx_branch     (branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion recomendada:**
1. Crear `app/helpers/AuditLogger.php` -- clase estatica con metodo `log($module, $action, $table, $recordId, $oldValues, $newValues)`.
2. Inyectar llamada en `DataStore::insert()`, `DataStore::update()`, `DataStore::delete()` para interceptar automaticamente.
3. En cada Controller, solo pasar `$module` al constructor del Model; el resto es transparente.
4. Mantener `activity_log` existente por compatibilidad; usar `audit_logs` para todo lo nuevo.

**Archivos a crear/modificar:**
- `database/migrations/010_create_audit_logs.sql` (nuevo)
- `app/helpers/AuditLogger.php` (nuevo)
- `app/models/DataStore.php` (modificar: hooks de auditoria)

---

### E1.2 -- Vista filtrable del registro de auditoria

**Ruta:** `?page=audit_logs`  
**Vista:** `app/views/dashboard/settings/audit_logs.php`

**Filtros requeridos:**
- Modulo (select: equipment, ticket, inventory, user...)
- Usuario (select con busqueda)
- Tipo de accion (create, update, delete, move, export)
- Rango de fechas (date-range picker)
- Sucursal (branch)

**Implementacion:**
1. Crear modelo `app/models/AuditLog.php` (extiende DataStore) con metodo `listFiltered($filters, $page, $perPage)`.
2. Crear controlador `app/controllers/AuditLogController.php` con accion `list`.
3. Crear endpoint `public/ajax/audit_log.php`.
4. Vista con DataTable server-side (los logs pueden crecer rapido).
5. Registrar modulo `audit_logs` en `system_modules` y asignar permiso solo a admins.

---

### E1.3 -- Exportacion del registro de auditoria a Excel

**Implementacion:**
- Accion `export` en `AuditLogController.php`.
- Reutilizar PhpSpreadsheet (ya incluida en `lib/PhpSpreadsheet-1.29.0/`).
- Aplicar los mismos filtros de la vista.
- Maximo 10,000 filas por exportacion para evitar timeout.

---

## E2 -- TICKETS Y COMUNICACION (Prioridad: ALTA)

> Impacto directo en operacion diaria del hospital. Estas mejoras reducen tiempo de atencion y cierran el loop de comunicacion.

### E2.1 -- Adjuntar y visualizar fotos al crear/editar un ticket

**Migracion SQL:**
```sql
CREATE TABLE ticket_attachments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT NOT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(500) NOT NULL,
    file_type   VARCHAR(50)  NOT NULL COMMENT 'image/jpeg, image/png, application/pdf',
    file_size   INT UNSIGNED NOT NULL COMMENT 'bytes',
    uploaded_by INT          NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    CONSTRAINT fk_ticket_att_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion:**
1. Componente reutilizable `assets/js/file-uploader.js` (drag & drop + preview, max 5 MB por archivo, tipos: jpg, png, pdf).
2. Upload AJAX a `public/ajax/ticket.php?action=upload_attachment`.
3. Almacenar en `uploads/tickets/{ticket_id}/`.
4. Galeria lightbox en la vista de detalle del ticket.
5. Validar tipo MIME en servidor (no confiar solo en extension).

**Archivos:**
- `database/migrations/011_create_ticket_attachments.sql`
- `app/views/dashboard/tickets/edit.php` (modificar)
- `app/views/dashboard/tickets/new.php` (modificar)
- `public/ajax/ticket.php` (agregar acciones upload/delete attachment)

---

### E2.2 -- Notificaciones in-app + email al cambiar estado

**Migracion SQL:**
```sql
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    type        VARCHAR(50)  NOT NULL COMMENT 'ticket_status, maintenance_due, etc.',
    title       VARCHAR(255) NOT NULL,
    message     TEXT         NOT NULL,
    link        VARCHAR(500) DEFAULT NULL,
    is_read     TINYINT(1)  DEFAULT 0,
    created_at  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    read_at     TIMESTAMP   NULL DEFAULT NULL,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion:**
1. `app/helpers/NotificationService.php` -- metodos: `notify($userId, $type, $title, $message, $link)`, `notifyByEmail(...)`, `markRead($id)`, `getUnread($userId)`.
2. Envio de email con `mail()` nativo o PHPMailer (preferir PHPMailer para SMTP con credenciales en `.env`).
3. Widget de campana en navbar con contador badge y dropdown de ultimas 10.
4. Polling cada 30s via AJAX o, mejor, long-polling ligero.
5. Disparar notificacion cuando `tickets.status` cambie (dentro de `TicketController::changeStatus()`).

**Estados del ticket:** 0 = Recibido, 1 = En atencion, 2 = Cerrado.

---

### E2.3 -- Historico completo de cambios de estado (timeline)

**Migracion SQL:**
```sql
CREATE TABLE ticket_status_history (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id     INT          NOT NULL,
    old_status    TINYINT      DEFAULT NULL,
    new_status    TINYINT      NOT NULL,
    changed_by    INT          NOT NULL,
    comment       TEXT         DEFAULT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    CONSTRAINT fk_tsh_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion:**
1. Insertar registro automaticamente cuando se actualice `tickets.status` (en TicketController).
2. Componente visual tipo timeline vertical con AdminLTE (ya tiene `.timeline` CSS).
3. Mostrar en la vista de detalle del ticket, debajo de los comentarios.

---

### E2.4 -- Modulo de respuestas: hilo de conversacion tecnico/usuario

**Estado actual:** Existen tablas `comments` y `ticket_comment`. Ambas almacenan comentarios pero con diferente estructura.

**Recomendacion:** Estandarizar en `ticket_comment` y agregar campo `is_internal` para notas privadas del tecnico.

**Migracion SQL:**
```sql
ALTER TABLE ticket_comment
    ADD COLUMN is_internal  TINYINT(1) DEFAULT 0 COMMENT '1=nota privada tecnico',
    ADD COLUMN parent_id    INT DEFAULT NULL COMMENT 'para respuestas anidadas',
    ADD COLUMN attachments  JSON DEFAULT NULL COMMENT 'array de paths a archivos adjuntos';
```

**Implementacion:**
1. Vista tipo chat/hilo en `app/views/dashboard/tickets/view.php`.
2. Diferenciar visualmente mensajes del tecnico (azul, alineados a la derecha) vs usuario (gris, alineados a la izquierda).
3. Notas internas visibles solo para roles con permiso.
4. AJAX para enviar comentarios sin recargar pagina.
5. Al agregar respuesta, disparar notificacion (E2.2) al otro participante.

---

### E2.5 -- Reportes: tickets atendidos y tiempo promedio de respuesta

**Implementacion:**
1. Query que calcule: total por estado, tiempo promedio (date_created -> primer cambio a status=1), tiempo de cierre (date_created -> status=2).
2. Filtros: rango de fechas, departamento, tecnico asignado.
3. Vista con graficas (Chart.js ya incluido en AdminLTE).
4. Exportar a Excel/PDF.

**Archivos:**
- `app/views/dashboard/reports/tickets_report.php`
- `app/controllers/TicketController.php` (agregar metodo `getStatistics`)
- Ruta: `?page=tickets_report`

---

## E3 -- EQUIPOS Y CATALOGO (Prioridad: ALTA)

### E3.1 -- Numero de serie unico con validacion AJAX en tiempo real

**Estado actual:** La columna `equipments.serie` existe pero NO tiene indice UNIQUE.

**Migracion SQL:**
```sql
-- Solo agregar indice unique (si hay duplicados, limpiar primero)
ALTER TABLE equipments ADD UNIQUE INDEX idx_serie_unique (serie);
```

**Implementacion:**
1. Endpoint `public/ajax/equipment.php?action=check_serie&serie=XXX&exclude_id=YYY`.
2. En el formulario de new/edit equipment: evento `blur` o `keyup` con debounce de 500ms.
3. Mostrar indicador verde (disponible) / rojo (duplicado) junto al campo.
4. Validacion tambien en servidor al guardar (doble proteccion).

**Archivos:**
- `database/migrations/012_unique_serie_equipment.sql`
- `public/ajax/equipment.php` (agregar accion check_serie)
- `app/views/dashboard/equipment/new.php` (agregar JS validacion)
- `app/views/dashboard/equipment/edit.php` (agregar JS validacion)

---

### E3.2 -- Campos personalizados en Equipos, Herramientas, Accesorios e Insumos

**Migracion SQL:**
```sql
CREATE TABLE custom_field_definitions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type  ENUM('equipment','tool','accessory','inventory') NOT NULL,
    field_name   VARCHAR(100) NOT NULL,
    field_label  VARCHAR(150) NOT NULL,
    field_type   ENUM('text','number','date','select','textarea','checkbox') NOT NULL DEFAULT 'text',
    options      JSON DEFAULT NULL COMMENT 'opciones para tipo select: ["Op1","Op2"]',
    is_required  TINYINT(1) DEFAULT 0,
    sort_order   INT DEFAULT 0,
    active       TINYINT(1) DEFAULT 1,
    branch_id    INT UNSIGNED DEFAULT NULL COMMENT 'NULL = global, con valor = solo esa sucursal',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_entity_field (entity_type, field_name, branch_id),
    INDEX idx_entity (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custom_field_values (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    definition_id   INT UNSIGNED NOT NULL,
    entity_type     ENUM('equipment','tool','accessory','inventory') NOT NULL,
    entity_id       INT UNSIGNED NOT NULL,
    field_value     TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_field_entity (definition_id, entity_type, entity_id),
    INDEX idx_entity (entity_type, entity_id),
    CONSTRAINT fk_cfv_definition FOREIGN KEY (definition_id) REFERENCES custom_field_definitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion:**
1. UI administrativa para definir campos (`?page=custom_fields`) -- CRUD con sortable drag & drop.
2. Helper `app/helpers/CustomFieldRenderer.php` que genere el HTML del formulario dinamicamente.
3. En cada vista de create/edit de equipment, tool, accessory e inventory, inyectar los campos custom despues de los campos nativos.
4. Guardar valores en `custom_field_values` via AJAX junto con el save principal.

---

### E3.3 -- Formato "NOMBRE #INVENTARIO" en seccion datos del equipo

**Implementacion:**
- Cambio puramente de presentacion en las vistas de equipo.
- Donde se muestra el nombre del equipo, concatenar: `{name} #{number_inventory}`.
- Aplicar en: list.php, edit.php, view.php, report PDFs, selects de equipo en tickets.
- Crear helper `formatEquipmentDisplay($name, $inventory)`.

**Archivos a modificar:**
- `app/views/dashboard/equipment/list.php`
- `app/views/dashboard/equipment/edit.php`
- Selectores de equipo en tickets, mantenimientos

---

### E3.4 -- Adjuntar fotos en reportes tecnicos + galeria en PDF

**Migracion SQL:**
```sql
CREATE TABLE report_attachments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id   INT NOT NULL,
    report_type ENUM('maintenance','unsubscribe','sistem') NOT NULL DEFAULT 'maintenance',
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(500) NOT NULL,
    file_type   VARCHAR(50)  NOT NULL,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report (report_id, report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion:**
1. Reutilizar componente de upload (E2.1).
2. En el formulario de reporte de mantenimiento, seccion para adjuntar fotos (max 10).
3. Al generar PDF con FPDF/TCPDF, agregar pagina extra "Evidencia Fotografica" con grid de imagenes.
4. Almacenar en `uploads/reports/{report_id}/`.

---

## E4 -- MANTENIMIENTO Y CALENDARIO (Prioridad: ALTA)

### E4.1 -- Periodos de mantenimiento libres: CRUD con UI administrativa

**Estado actual:** Tabla `maintenance_periods` existe con (id, name, days_interval). Los datos estan hardcoded en seeds.

**Implementacion:**
1. Vista `?page=maintenance_periods` con tabla editable.
2. Modal para crear/editar: nombre + intervalo en dias.
3. Validar que no se elimine un periodo que este en uso por algun equipo.
4. Agregar al menu de Configuracion.

**Archivos:**
- `app/views/dashboard/settings/maintenance_periods.php` (nuevo)
- `app/controllers/MaintenancePeriodController.php` (nuevo, simple CRUD via DataStore)
- `public/ajax/maintenance_period.php` (nuevo)
- `app/routing.php` (agregar ruta)
- `components/sidebar.php` (agregar al menu)

---

### E4.2 -- Descarga del calendario de mantenimiento como lista (Excel / PDF)

**Implementacion:**
1. Boton "Exportar" en la vista de calendario (`?page=calendar`) con dropdown: Excel | PDF.
2. Para Excel: query de `mantenimientos` con JOIN a `equipments`, filtrado por mes/rango visible. Generar con PhpSpreadsheet.
3. Para PDF: tabla con columnas (Fecha, Equipo, #Inventario, Tipo, Estado, Departamento). Generar con FPDF/TCPDF.
4. Endpoint: `public/ajax/maintenance.php?action=export&format=excel&from=2026-03-01&to=2026-03-31`.

---

## E5 -- INSUMOS Y SUSTANCIAS PELIGROSAS (Prioridad: MEDIA)

### E5.1 -- Flag sustancia peligrosa + upload de documentacion PDF/JPG

**Estado actual:** Tabla `inventory` ya existe con campos basicos.

**Migracion SQL:**
```sql
ALTER TABLE inventory
    ADD COLUMN is_hazardous     TINYINT(1) DEFAULT 0 COMMENT '1=sustancia peligrosa',
    ADD COLUMN hazard_class     VARCHAR(100) DEFAULT NULL COMMENT 'clase de peligro (inflamable, corrosivo, toxico...)',
    ADD COLUMN safety_data_sheet VARCHAR(500) DEFAULT NULL COMMENT 'path a hoja de seguridad PDF',
    ADD INDEX idx_hazardous (is_hazardous);

CREATE TABLE inventory_documents (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_id  INT UNSIGNED NOT NULL,
    document_type ENUM('safety_data_sheet','certificate','photo','other') NOT NULL,
    file_name     VARCHAR(255) NOT NULL,
    file_path     VARCHAR(500) NOT NULL,
    file_type     VARCHAR(50)  NOT NULL,
    uploaded_by   INT          NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory (inventory_id),
    CONSTRAINT fk_invdoc_inventory FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Implementacion:**
1. Toggle/switch "Sustancia peligrosa" en formulario de insumo.
2. Al activar, mostrar campos adicionales: clase de peligro (select) + zona de upload para hoja de seguridad.
3. Icono de advertencia visible en listados cuando `is_hazardous = 1`.
4. Almacenar en `uploads/inventory/{id}/docs/`.

---

### E5.2 -- Modulo y permiso exclusivo para visualizar sustancias peligrosas

**Implementacion:**
1. Registrar nuevo modulo en `system_modules`:
   ```sql
   INSERT INTO system_modules (code, name, description, icon, `order`, active)
   VALUES ('hazardous_materials', 'Sustancias Peligrosas', 'Visualizacion de insumos clasificados como sustancia peligrosa', 'fas fa-exclamation-triangle', 85, 1);
   ```
2. Vista dedicada `?page=hazardous_materials` con listado filtrado de `inventory WHERE is_hazardous = 1`.
3. Incluir columnas: nombre, clase de peligro, hoja de seguridad (link), stock actual, ubicacion.
4. Solo visible para roles con `can_view` en este modulo.
5. Exportar a Excel con icono de peligro.

---

### E5.3 -- Campos personalizados en Insumos

Ya cubierto por E3.2 (entity_type = 'inventory'). No requiere trabajo adicional separado.

---

## E6 -- REPORTES Y EXPORTACIONES (Prioridad: MEDIA)

### E6.1 -- Consumo electrico (kWh) agrupado por departamento y equipo

**Estado actual:** Tabla `equipment_power_specs` tiene voltage, amperage, frequency_hz, power_w. No tiene campo de horas de uso.

**Migracion SQL:**
```sql
ALTER TABLE equipment_power_specs
    ADD COLUMN daily_usage_hours DECIMAL(4,1) DEFAULT 8.0 COMMENT 'horas estimadas de uso diario',
    ADD COLUMN kwh_monthly       DECIMAL(10,2) GENERATED ALWAYS AS (power_w * daily_usage_hours * 30 / 1000) STORED COMMENT 'kWh mensual estimado';
```

**Implementacion:**
1. Vista `?page=energy_consumption` con tabla agrupada:
   - Nivel 1: Departamento (suma kWh).
   - Nivel 2: Equipo (detalle individual).
2. Grafica de barras (Chart.js) por departamento.
3. Query con JOIN: `equipment_power_specs` -> `equipments` -> `equipment_delivery` -> `departments`.
4. Filtros: sucursal, departamento.
5. Exportar a Excel.

---

### E6.2 -- Top equipos con mayor gasto en accesorios (piezas y monto)

**Implementacion:**
1. Query que agrupe accesorios por equipo (via `accessories.area_id` o una relacion equipment-accessory).
2. **Nota importante:** Actualmente `accessories` no tiene FK directa a `equipments`. Se necesita establecer la relacion:
   ```sql
   ALTER TABLE accessories ADD COLUMN equipment_id INT UNSIGNED DEFAULT NULL;
   ALTER TABLE accessories ADD INDEX idx_equipment (equipment_id);
   ```
   O alternativamente usar `report_items` que enlaza reportes con piezas.
3. Vista tipo ranking con: posicion, equipo (nombre + #inventario), total piezas, monto total.
4. Grafica Top 10.

---

### E6.3 -- Ranking de equipos con mas reportes / tickets generados

**Implementacion:**
1. Query: `SELECT equipment_id, COUNT(*) as total FROM tickets WHERE equipment_id IS NOT NULL GROUP BY equipment_id ORDER BY total DESC LIMIT 20`.
2. Similar para `maintenance_reports`.
3. Vista con dos tablas/tabs: "Mas tickets" y "Mas mantenimientos".
4. Graficas de barras horizontales.

---

## E7 -- BRANDING Y CONFIGURACION (Prioridad: BAJA)

### E7.1 -- Subir logo de organizacion desde el panel de configuracion

**Estado actual:** `system_info` tiene fila con `meta_field = 'logo'` y `meta_value = 'uploads/...'`.

**Implementacion:**
1. En la vista de configuracion de empresa (`?page=company_config`), agregar campo de upload para logo.
2. Preview del logo actual + boton para cambiar.
3. Almacenar en `uploads/logos/` y actualizar `system_info WHERE meta_field = 'logo'`.
4. Validar: solo jpg/png, max 2MB, redimensionar a max 400x200px.
5. Opcionalmente agregar logo por sucursal en `company_config`.

---

### E7.2 -- Insercion dinamica del logo en todos los PDFs del sistema

**Implementacion:**
1. Crear `app/helpers/PdfHeader.php` con metodo `addHeader($pdf)` que:
   - Lea el logo de `system_info` o `company_config` segun `branch_id`.
   - Lo inserte en la esquina superior izquierda de cada PDF.
   - Agregue nombre de empresa y datos del membrete.
2. Refactorizar los generadores de PDF existentes para usar este helper.
3. PDFs afectados:
   - `equipment_report_pdf.php` (reporte de equipo)
   - `equipment_unsubscribe_pdf.php` (baja)
   - `equipment_report_sistem_pdf.php` (reporte de sistema)
   - `generate_pdf.php` (reporte de mantenimiento)
   - Cualquier nuevo PDF de reportes de tickets.

---

### E7.3 -- Auditar y corregir botones de exportacion a Excel en todo el sistema

**Implementacion:**
1. Hacer inventario de todas las vistas que tienen (o deberian tener) boton de exportar.
2. Verificar que cada una use PhpSpreadsheet (no metodos obsoletos).
3. Estandarizar a un helper: `app/helpers/ExcelExporter.php` con metodo `export($headers, $data, $filename)`.
4. Lista de vistas a auditar:
   - Equipos, Herramientas, Accesorios, Insumos
   - Tickets, Proveedores, Usuarios, Staff
   - Reportes de mantenimiento, Calendario
   - Registro de auditoria (E1.3)
5. Asegurar que el permiso `can_export` se verifique en cada caso.

---

## Orden de Implementacion Recomendado

```
Sprint 1 (Fundamentos) -- COMPLETADO
  |-- E1.1  Audit logs + interceptores          [DONE]
  |-- E1.2  Vista de auditoria                  [DONE]
  |-- E1.3  Exportacion auditoria Excel         [DONE]
  |-- E3.1  Serie unico + validacion AJAX       [DONE]
  |-- E3.3  Formato NOMBRE #INVENTARIO          [DONE]

Sprint 2 (Tickets & Comunicacion) -- COMPLETADO
  |-- E2.1  Adjuntar fotos en tickets           [DONE]
  |-- E2.2  Notificaciones in-app + email       [DONE]
  |-- E2.3  Timeline de estados                 [DONE]
  |-- E2.4  Hilo de conversacion                [DONE]

Sprint 3 (Mantenimiento & Equipos)
  |-- E4.1  CRUD periodos mantenimiento
  |-- E4.2  Exportar calendario Excel/PDF
  |-- E3.4  Fotos en reportes tecnicos
  |-- E3.2  Campos personalizados

Sprint 4 (Insumos & Sustancias)
  |-- E5.1  Flag sustancia peligrosa + docs
  |-- E5.2  Modulo sustancias peligrosas

Sprint 5 (Reportes & Branding)
  |-- E6.1  Consumo electrico kWh
  |-- E6.2  Top gasto en accesorios
  |-- E6.3  Ranking equipos con mas tickets
  |-- E2.5  Reporte tickets + tiempo promedio
  |-- E7.1  Upload logo organizacion
  |-- E7.2  Logo dinamico en PDFs
  |-- E7.3  Auditar exports Excel
```

---

## Migraciones SQL Consolidadas

Orden de ejecucion:
1. `010_create_audit_logs.sql`           [EJECUTADA]
2. `012_unique_serie_equipment.sql`      [EJECUTADA]
3. `013_sprint2_tickets.sql`             [EJECUTADA] (ticket_attachments, notifications, ticket_status_history, alter comments is_internal)
4. `016_create_custom_fields.sql`
5. `017_create_report_attachments.sql`
6. `018_alter_inventory_hazardous.sql`
7. `019_create_inventory_documents.sql`
8. `020_alter_power_specs_kwh.sql`
9. `021_alter_accessories_equipment_id.sql`
10. `022_insert_hazardous_module.sql`

---

## Patrones Tecnicos a Seguir

1. **Modelos:** Extender `DataStore.php` para cada entidad nueva. Los interceptores de auditoria van en la clase base.
2. **Controllers:** Validacion de entrada + logica de negocio. Nunca SQL directo ahi.
3. **AJAX:** Un endpoint por entidad en `public/ajax/`. Verificar sesion y permisos al inicio.
4. **Uploads:** Siempre validar MIME type en servidor, generar nombre unico (hash), limitar tamano.
5. **Exportaciones:** Centralizar en `app/helpers/ExcelExporter.php` y `app/helpers/PdfHeader.php`.
6. **Notificaciones:** Centralizar en `app/helpers/NotificationService.php`, disparar desde controllers.
7. **Frontend:** jQuery + AdminLTE widgets. No introducir frameworks JS nuevos.

---

## Nuevas Tablas Resumen

| Tabla | Proposito |
|-------|-----------|
| `audit_logs` | Registro detallado de cambios con old/new values |
| `ticket_attachments` | Archivos adjuntos en tickets |
| `ticket_status_history` | Timeline de cambios de estado |
| `notifications` | Notificaciones in-app para usuarios |
| `custom_field_definitions` | Definicion de campos personalizados |
| `custom_field_values` | Valores de campos personalizados por entidad |
| `report_attachments` | Fotos/documentos adjuntos en reportes tecnicos |
| `inventory_documents` | Documentacion de insumos (hojas de seguridad) |

## Tablas Modificadas

| Tabla | Cambio |
|-------|--------|
| `equipments` | UNIQUE INDEX en `serie` |
| `ticket_comment` | +is_internal, +parent_id, +attachments |
| `inventory` | +is_hazardous, +hazard_class, +safety_data_sheet |
| `equipment_power_specs` | +daily_usage_hours, +kwh_monthly (generated) |
| `accessories` | +equipment_id (FK a equipments) |

---

**Ultima actualizacion:** 19 de marzo de 2026
