# Documentación Técnica Completa
## Sistema de Gestión de Activos Médicos - AmeriMed

---

**Versión:** 2.0  
**Fecha:** Diciembre 2025  
**Arquitectura:** Multi-Tenant (3 Sucursales)  
**Tecnología:** PHP 7.4+, MySQL 8.0, AdminLTE 3

---

## Tabla de Contenidos

1. [Visión General del Sistema](#1-visión-general-del-sistema)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Patrones de Diseño](#3-patrones-de-diseño)
4. [Estructura de Directorios](#4-estructura-de-directorios)
5. [Lógica de Negocio](#5-lógica-de-negocio)
6. [Base de Datos](#6-base-de-datos)
7. [Seguridad](#7-seguridad)
8. [Multi-Tenancy](#8-multi-tenancy)
9. [Flujos de Trabajo](#9-flujos-de-trabajo)
10. [APIs y Endpoints](#10-apis-y-endpoints)
11. [Deployment](#11-deployment)

---

## 1. Visión General del Sistema

### 1.1 Propósito

Sistema de gestión integral de activos médicos hospitalarios diseñado para AmeriMed con arquitectura multi-tenant. Gestiona equipamiento biomédico, mantenimientos preventivos/correctivos, inventarios, tickets de soporte, y reportes de cumplimiento normativo.

### 1.2 Objetivos

- **Trazabilidad completa:** Registro desde adquisición hasta baja del equipo
- **Mantenimiento programado:** Alertas automáticas basadas en calendarios
- **Cumplimiento normativo:** Documentación para auditorías (COFEPRIS, ISO)
- **Multi-sucursal:** Aislamiento de datos por branch_id
- **Generación de QR:** Códigos QR para acceso público de reportes

### 1.3 Usuarios del Sistema

| Rol | Permisos | Casos de Uso |
|-----|----------|--------------|
| **Admin** | Acceso total, gestión usuarios, configuración | Supervisión general, reportes ejecutivos |
| **Técnico Biomédico** | CRUD equipos, mantenimientos, tickets | Operaciones diarias de mantenimiento |
| **Staff** | Consulta equipos, crear tickets | Reportar fallas, consultar disponibilidad |
| **Público (QR)** | Crear tickets anónimos | Reportar problemas sin autenticación |

---

## 2. Arquitectura del Sistema

### 2.1 Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                      CAPA DE PRESENTACIÓN                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Web Browser │  │  Mobile QR   │  │  PDF Export  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   CAPA DE APLICACIÓN (PHP)                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              Router (routing.php)                     │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │   Models     │  │    Views     │      │
│  │   (MVC)      │  │  (Business)  │  │   (UI)       │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE DATOS (MySQL)                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ biomedicacun │  │ sistemascun  │  │  manttocun   │      │
│  │  (Branch 1)  │  │  (Branch 2)  │  │  (Branch 3)  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Modelo MVC (Model-View-Controller)

#### **Controllers**
Ubicación: `/app/controllers/`

Responsabilidades:
- Recibir requests HTTP (GET/POST)
- Validar entrada del usuario
- Invocar lógica de negocio (Models)
- Retornar respuestas (Views o JSON)

Ejemplo: `EquipmentController.php`
```php
class EquipmentController {
    public function create($data) {
        // 1. Validar datos
        // 2. Llamar modelo Equipment
        // 3. Retornar respuesta JSON
    }
}
```

#### **Models**
Ubicación: `/app/models/`

Responsabilidades:
- Abstracción de base de datos
- Lógica de negocio pura
- Validaciones de integridad
- Relaciones entre entidades

Herencia: Todos heredan de `DataStore` (Active Record Pattern)

#### **Views**
Ubicación: `/app/views/`

Responsabilidades:
- Renderizado HTML
- Templates con datos inyectados desde Controllers
- Componentes reutilizables (navbar, sidebar, footer)

### 2.3 Flujo de Request

```
1. Usuario accede: index.php?page=equipment_list
   ▼
2. index.php carga routing.php
   ▼
3. Router mapea: equipment_list → app/views/dashboard/equipment/list.php
   ▼
4. Vista carga EquipmentController
   ▼
5. Controller obtiene datos via Equipment Model
   ▼
6. Model ejecuta query con filtro branch_id
   ▼
7. Vista renderiza HTML con datos
```

---

## 3. Patrones de Diseño

### 3.1 Active Record (DataStore)

**Ubicación:** `/app/models/DataStore.php`

**Propósito:** Abstracción de operaciones CRUD sobre tablas MySQL

**Características:**
- Métodos `insert()`, `update()`, `delete()`, `getById()`, `getAll()`
- Query builder simplificado
- Filtros automáticos por `branch_id` en queries
- Prepared statements para prevenir SQL Injection

**Ejemplo de uso:**
```php
$equipment = new Equipment();
$data = ['name' => 'Ventilador', 'brand' => 'Medtronic'];
$id = $equipment->save($data); // INSERT automático
```

### 3.2 Front Controller (index.php)

**Propósito:** Punto de entrada único para todas las requests

**Responsabilidades:**
- Cargar configuración (`config/config.php`)
- Iniciar sesión segura (`session.php`)
- Verificar modo mantenimiento
- Enrutar requests via `routing.php`
- Manejo de errores fatales

### 3.3 Repository Pattern (Partial)

Los modelos actúan como repositorios encapsulando acceso a datos:
- `Equipment::getWithRelations($id)` - Carga equipo + categoría + proveedor
- `Ticket::getByEquipment($equipmentId)` - Tickets relacionados

### 3.4 Strategy Pattern (Branch Selection)

**Implementación:** Configuración por `.env` define branch_id activo

Cada subdominio tiene su propio `.env`:
```env
# biomedicacun/.env
BRANCH_ID=1

# sistemascun/.env
BRANCH_ID=2
```

Queries automáticamente filtran por:
```php
$stmt = $pdo->prepare("SELECT * FROM equipments WHERE branch_id = ?");
$stmt->execute([$_ENV['BRANCH_ID']]);
```

### 3.5 Factory Pattern (PDF Generation)

**Ubicación:** `/app/helpers/generate_pdf.php`

Crea diferentes tipos de reportes PDF:
- Reporte de equipo individual
- Reporte de mantenimiento mensual
- Reporte de baja de equipos
- Reporte de responsable por área

```php
$pdfFactory = new PDFGenerator();
$pdf = $pdfFactory->createEquipmentReport($equipmentId);
$pdf->output();
```

---

## 4. Estructura de Directorios

```
system/
├── app/
│   ├── controllers/          # Controladores MVC
│   │   ├── EquipmentController.php
│   │   ├── TicketController.php
│   │   ├── UserController.php
│   │   └── ...
│   ├── models/              # Modelos de datos
│   │   ├── DataStore.php    # Clase base Active Record
│   │   ├── Equipment.php
│   │   ├── Ticket.php
│   │   └── ...
│   ├── views/               # Vistas HTML/PHP
│   │   ├── dashboard/       # Vistas protegidas (autenticadas)
│   │   │   ├── equipment/
│   │   │   ├── tickets/
│   │   │   └── users/
│   │   └── pages/           # Vistas públicas
│   ├── helpers/             # Funciones auxiliares
│   │   ├── generate_pdf.php
│   │   ├── qr_generator.php
│   │   └── excel_export.php
│   └── routing.php          # Mapeo de rutas
│
├── config/                  # Configuración del sistema
│   ├── .env                 # Variables de entorno (NO en Git)
│   ├── config.php           # Configuración principal
│   ├── db.php               # Conexión PDO
│   ├── db_connect.php       # Conexión MySQLi (legacy)
│   ├── session.php          # Manejo de sesiones seguras
│   ├── maintenance_config.php  # Modo mantenimiento
│   └── access_guard.php     # Middleware de autenticación
│
├── assets/                  # Recursos estáticos
│   ├── css/
│   ├── js/
│   ├── img/
│   ├── avatars/
│   └── plugins/             # AdminLTE, DataTables, etc.
│
├── components/              # Componentes reutilizables
│   ├── navbar.php
│   ├── sidebar.php
│   ├── footer.php
│   └── maintenance.php
│
├── public/                  # Endpoints AJAX públicos
│   └── ajax/
│       ├── login.php        # Autenticación
│       ├── equipment_crud.php
│       └── ticket_crud.php
│
├── uploads/                 # Archivos subidos por usuarios
│   ├── equipment_images/
│   ├── staff_images/
│   └── qrcodes/
│
├── logs/                    # Logs del sistema
│   ├── php_fatal.log
│   └── activity.log
│
├── database/
│   ├── migrations/          # Scripts de migración SQL
│   └── seeds/               # Datos iniciales
│
├── scripts/                 # Scripts de mantenimiento
│   ├── deploy.sh
│   └── backup.sh
│
├── utilities/               # Herramientas admin
│   ├── rebuild_inventory_numbers.php
│   ├── update_dashboard_cache.php
│   └── migration_*.sql
│
├── legacy/                  # Código heredado (deprecado)
│   └── admin_class.php
│
├── docs/                    # Documentación
│   ├── PROJECT_STRUCTURE.md
│   ├── INSTALACION_GUIA_COMPLETA.md
│   └── DOCUMENTACION_TECNICA_COMPLETA.md
│
├── index.php                # Punto de entrada principal
├── .htaccess                # Configuración Apache (rewrite rules)
├── .gitignore
├── update-subdominios.ps1   # Script de deployment
└── DB_CREDENTIALS.txt       # Credenciales bases de datos
```

### 4.1 Descripción de Carpetas Críticas

#### `/app/controllers/`
**12 Controladores:**
- `EquipmentController` - CRUD de equipos biomédicos
- `TicketController` - Sistema de tickets de soporte
- `UserController` - Gestión de usuarios y roles
- `SupplierController` - Proveedores
- `ToolController` - Herramientas del departamento
- `AccessoryController` - Accesorios médicos
- Otros: Customers, Departments, Locations, Services, Categories

#### `/config/`
**Archivos sensibles (NO en repositorio):**
- `.env` - Credenciales de base de datos por branch
- `maintenance_config.php` - IPs autorizadas durante mantenimiento

#### `/uploads/`
**Persistente en servidor:**
- Imágenes de equipos (preservar en updates)
- QR codes generados dinámicamente
- Avatares de usuarios

---

## 5. Lógica de Negocio

### 5.1 Gestión de Equipos

#### **Ciclo de Vida del Equipo**

```
┌────────────┐
│ Adquisición│ → Número de inventario generado (ACT-CATEG-0001)
└─────┬──────┘
      │
      ▼
┌────────────┐
│  Recepción │ → Inspección técnica, documentación
└─────┬──────┘
      │
      ▼
┌────────────┐
│   Entrega  │ → Asignación a departamento/responsable
└─────┬──────┘
      │
      ▼
┌────────────┐
│ Operación  │ → Mantenimientos preventivos programados
└─────┬──────┘   ↓ Tickets de fallas/correctivos
      │          ↓
      ▼
┌────────────┐
│    Baja    │ → Obsolescencia, daño irreparable, donación
└────────────┘
```

#### **Generación de Números de Inventario**

**Formato:** `{CODIGO_ADQUISICION}-{CODIGO_CATEGORIA}-{SECUENCIA}`

Ejemplo: `DON-EMG-0042`
- `DON` - Donación
- `EMG` - Equipo médico general
- `0042` - Secuencia autoincremental por branch

**Implementación:**
Tabla `inventory_config` almacena secuencias por:
- `branch_id` (sucursal)
- `acquisition_type_id` (DON, COM, ARR)
- `equipment_category_id` (EMG, LAB, IMG)

```php
function getNextInventoryNumber($branchId, $acquisitionTypeId, $categoryId) {
    // 1. Obtener prefijos de códigos
    // 2. Incrementar secuencia en inventory_config
    // 3. Retornar: DON-EMG-0043
}
```

### 5.2 Sistema de Mantenimientos

#### **Tipos de Mantenimiento**

| Tipo | Periodicidad | Trigger | Responsable |
|------|-------------|---------|-------------|
| **Preventivo** | Programado (30/60/90/180/365 días) | Calendario automático | Biomédica |
| **Correctivo** | On-demand | Ticket de falla | Biomédica |
| **Predictivo** | Basado en métricas | Análisis de tendencias | Ingeniería |
| **Calibración** | Anual/Semestral | Normativa COFEPRIS | Metrólogo certificado |

#### **Alertas Automáticas**

Sistema de notificaciones en dashboard:
- 🟡 **7 días antes:** Mantenimiento próximo
- 🔴 **Vencido:** Mantenimiento atrasado
- ⚫ **15 días después:** Equipo bloqueado (no operable)

**Query de alertas:**
```sql
SELECT e.*, m.fecha_programada
FROM equipments e
JOIN mantenimientos m ON m.equipo_id = e.id
WHERE m.estatus = 'pendiente'
  AND m.fecha_programada BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
  AND e.branch_id = ?
ORDER BY m.fecha_programada ASC
```

### 5.3 Sistema de Tickets

#### **Flujo de Ticket**

```
Usuario reporta falla → Ticket creado (status: PENDING)
    ↓
Técnico asigna → Status: IN_PROGRESS
    ↓
Diagnóstico → Agregar comentarios técnicos
    ↓
Reparación → Registrar partes usadas
    ↓
Validación → Pruebas funcionales
    ↓
Cierre → Status: CLOSED (equipo funcional)
```

#### **Tickets Públicos (QR)**

Característica única: Usuarios sin cuenta pueden reportar fallas escaneando QR en equipo.

**Flujo QR:**
1. Usuario escanea QR en equipo → URL: `equipment_public.php?qr=ACT-EMG-0042`
2. Sistema carga datos del equipo (nombre, ubicación, foto)
3. Formulario público: reportar problema sin login
4. Ticket creado con `is_public=1`, campos: `reporter_name`, `reporter_email`, `reporter_phone`
5. Confirmación enviada por email

**Ventaja:** Enfermeras/médicos reportan fallas sin necesidad de cuenta en el sistema.

### 5.4 Reportes y Exportación

#### **Tipos de Reportes**

1. **Reporte de Equipo Individual (PDF)**
   - Ficha técnica completa
   - Historial de mantenimientos
   - Responsable actual
   - Estado operativo

2. **Reporte Mensual de Mantenimientos (PDF)**
   - Todos los mantenimientos del mes por categoría
   - Gráficas de cumplimiento
   - Equipos atrasados

3. **Reporte de Baja de Equipos (PDF)**
   - Justificación técnica
   - Aprobaciones (Jefe de Biomédica, Dirección)
   - Destino (donación, venta, desecho)
   - Fotos del equipo

4. **Exportación Excel**
   - Inventario completo con filtros
   - Formato para auditorías COFEPRIS
   - Campos: No. Inventario, Nombre, Categoría, Ubicación, Último Mantenimiento

---

## 6. Base de Datos

### 6.1 Esquema General

**41 Tablas** organizadas en 6 módulos:

#### **Módulo Core**
- `users` - Usuarios del sistema
- `branches` - Sucursales/Hospitales
- `activity_log` - Auditoría de acciones

#### **Módulo Equipos**
- `equipments` - Equipos biomédicos (tabla central)
- `equipment_categories` - Categorías (EMG, LAB, IMG)
- `equipment_reception` - Proceso de recepción
- `equipment_delivery` - Entrega a departamentos
- `equipment_revision` - Mantenimientos realizados
- `equipment_unsubscribe` - Bajas de equipos
- `equipment_control_documents` - Documentos PDF (manual, factura)
- `equipment_power_specs` - Especificaciones eléctricas

#### **Módulo Mantenimiento**
- `mantenimientos` - Mantenimientos programados
- `maintenance_reports` - Reportes detallados
- `maintenance_periods` - Periodicidades (30, 60, 90 días)

#### **Módulo Tickets**
- `tickets` - Tickets de soporte
- `ticket_comment` - Comentarios en tickets
- `comments` - Sistema heredado (deprecado)

#### **Módulo Inventario**
- `accessories` - Accesorios médicos
- `tools` - Herramientas del taller
- `inventory` - Insumos consumibles
- `inventory_config` - Configuración de secuencias

#### **Módulo Configuración**
- `departments` - Departamentos hospitalarios
- `locations` - Ubicaciones físicas
- `job_positions` - Puestos de trabajo
- `responsibles` - Responsables de equipos
- `suppliers` - Proveedores
- `acquisition_type` - Tipos de adquisición

### 6.2 Relaciones Clave

#### **Equipments (Tabla Central)**
```
equipments (id)
├── → acquisition_type (id)         [Tipo adquisición: DON, COM, ARR]
├── → equipment_categories (id)     [Categoría: EMG, LAB, IMG]
├── → suppliers (id)                [Proveedor]
├── → maintenance_periods (id)      [Periodicidad mantenimiento]
├── → branches (id)                 [Sucursal - MULTI-TENANT]
│
├── ← mantenimientos (equipo_id)    [1:N - Mantenimientos programados]
├── ← tickets (equipment_id)        [1:N - Tickets de soporte]
├── ← equipment_delivery (equipment_id) [1:1 - Entrega actual]
└── ← responsibles (equipment_id)   [1:N - Responsables históricos]
```

#### **Multi-Tenancy (branch_id)**

Tablas con campo `branch_id` (filtrado automático):
- `equipments`
- `mantenimientos`
- `maintenance_reports`
- `accessories`
- `tools`
- `inventory`
- `inventory_config`

**Query automática en DataStore:**
```php
protected function applyBranchFilter($query) {
    if (isset($_ENV['BRANCH_ID'])) {
        $query .= " WHERE branch_id = " . (int)$_ENV['BRANCH_ID'];
    }
    return $query;
}
```

### 6.3 Índices y Optimización

#### **Índices Principales**

```sql
-- equipments (búsquedas frecuentes)
CREATE INDEX idx_branch ON equipments(branch_id);
CREATE INDEX idx_category ON equipments(equipment_category_id);
CREATE INDEX idx_date_created ON equipments(date_created);
CREATE INDEX idx_number_inventory ON equipments(number_inventory);

-- mantenimientos (alertas dashboard)
CREATE INDEX idx_equipo_fecha ON mantenimientos(equipo_id, fecha_programada);
CREATE INDEX idx_estatus ON mantenimientos(estatus);

-- tickets (filtros de estado)
CREATE INDEX idx_status ON tickets(status);
CREATE INDEX idx_equipment_id ON tickets(equipment_id);
CREATE INDEX idx_is_public ON tickets(is_public);
```

#### **Consultas Optimizadas**

Dashboard principal carga datos con JOIN optimizado:
```sql
SELECT 
    e.id, e.name, e.number_inventory,
    ec.description AS category,
    m.fecha_programada AS next_maintenance
FROM equipments e
LEFT JOIN equipment_categories ec ON e.equipment_category_id = ec.id
LEFT JOIN mantenimientos m ON m.equipo_id = e.id AND m.estatus = 'pendiente'
WHERE e.branch_id = ?
ORDER BY m.fecha_programada ASC
LIMIT 10
```

**Explicación:**
- `LEFT JOIN` para traer categoría sin excluir equipos sin mantenimiento
- Filtro `m.estatus = 'pendiente'` reduce dataset
- `ORDER BY` directo en query (no en PHP)
- `LIMIT 10` para paginación

### 6.4 Caché de Dashboard

Tabla `dashboard_cache` almacena métricas precalculadas:

```json
{
  "cache_key": "dashboard_stats_branch_1",
  "cache_data": {
    "total_equipments": 312,
    "active_equipments": 298,
    "pending_maintenances": 15,
    "open_tickets": 8
  },
  "updated_at": "2025-12-18 10:30:00"
}
```

**Invalidación:**
- Cada INSERT/UPDATE en `equipments` → regenerar caché
- Script cron: `/utilities/update_dashboard_cache.php` (cada hora)

---

## 7. Seguridad

### 7.1 Autenticación

#### **Almacenamiento de Contraseñas**

**Hashing:** MD5 (⚠️ LEGACY - Migrar a bcrypt)

```php
// Actual (inseguro)
$password_hash = md5($password);

// Recomendado (migración futura)
$password_hash = password_hash($password, PASSWORD_BCRYPT);
```

#### **Sesiones Seguras**

Archivo: `/config/session.php`

Configuración hardened:
```php
ini_set('session.cookie_httponly', 1);    // No accesible desde JavaScript
ini_set('session.cookie_secure', 1);      // Solo HTTPS
ini_set('session.use_strict_mode', 1);    // Validar session IDs
session_regenerate_id(true);              // Regenerar ID en cada login
```

### 7.2 Prevención de Ataques

#### **SQL Injection**

✅ **Prepared Statements en PDO:**
```php
$stmt = $pdo->prepare("SELECT * FROM equipments WHERE id = ?");
$stmt->execute([$id]);
```

❌ **NUNCA concatenar variables:**
```php
// VULNERABLE
$query = "SELECT * FROM users WHERE username = '$username'";
```

#### **XSS (Cross-Site Scripting)**

✅ **Escapar salida HTML:**
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

Funciones auxiliares:
```php
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
```

Uso en vistas:
```html
<input value="<?= e($equipment['name']) ?>">
```

#### **CSRF (Cross-Site Request Forgery)**

**Tokens en formularios:**
```php
// Generar token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Incluir en form
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validar en POST
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF validation failed');
}
```

### 7.3 Control de Acceso

#### **Middleware de Autenticación**

Archivo: `/config/access_guard.php`

```php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($role) {
    if ($_SESSION['user_role'] !== $role) {
        http_response_code(403);
        die('Acceso denegado');
    }
}
```

Uso en páginas protegidas:
```php
require_once 'config/access_guard.php';
requireLogin();
requireRole('admin'); // Solo admin
```

#### **IPs Permitidas (Modo Mantenimiento)**

Archivo: `/config/maintenance_config.php`

```php
return [
    'maintenance_enabled' => false,
    'allowed_ips' => [
        '192.168.1.100',  // Oficina TI
        '10.0.0.5',       // VPN Soporte
    ]
];
```

---

## 8. Multi-Tenancy

### 8.1 Arquitectura Multi-Tenant

**Estrategia:** Base de Datos Separadas (Database-per-Tenant)

```
activosamerimed.com/
├── biomedicacun/          → DB: u499728070_biomedicacun
├── sistemascun/           → DB: u499728070_sistemascun
└── manttocun/             → DB: u499728070_manttocun
```

**Ventajas:**
- ✅ Aislamiento total de datos (seguridad)
- ✅ Backups independientes
- ✅ Escalabilidad (agregar sucursales sin modificar código)
- ✅ Cumplimiento GDPR/HIPAA (datos médicos)

**Desventajas:**
- ❌ Reportes consolidados requieren queries multi-DB
- ❌ Migraciones deben ejecutarse 3 veces

### 8.2 Configuración por Subdominio

Cada subdominio tiene su `.env`:

**biomedicacun/.env:**
```env
DB_HOST=localhost
DB_USER=u499728070_biomedica
DB_PASS=gR1Zh9Ll2jcawp35
DB_NAME=u499728070_biomedicacun
BRANCH_ID=1
BASE_URL=https://biomedicacun.activosamerimed.com
```

**sistemascun/.env:**
```env
DB_HOST=localhost
DB_USER=u499728070_sistemas
DB_PASS=IJLd0R3hqBCbxKHW
DB_NAME=u499728070_sistemascun
BRANCH_ID=2
BASE_URL=https://sistemascun.activosamerimed.com
```

### 8.3 Tabla Branches (Referencia Cruzada)

Aunque cada DB es independiente, todas tienen tabla `branches` sincronizada:

```sql
INSERT INTO branches (id, code, name) VALUES
(1, 'HAC', 'Hospital Amerimed Cancún'),
(2, 'HAP', 'Hospital Amerimed Playa del Carmen'),
(3, 'HAM', 'Hospital Amerimed Mérida');
```

**Uso:**
- Selector de sucursal en dashboard (aunque solo muestra datos del branch activo)
- Transferencias de equipos (futuro) entre branches

### 8.4 Migraciones Multi-Tenant

Script de deployment ejecuta migraciones en las 3 BDs:

```bash
#!/bin/bash
for DB in biomedicacun sistemascun manttocun; do
    mysql -u u499728070_${DB:0:9} -p < migrations/001_add_column.sql
done
```

---

## 9. Flujos de Trabajo

### 9.1 Flujo: Registro de Equipo Nuevo

```
1. ADQUISICIÓN
   └─→ Admin selecciona: Tipo adquisición (Compra/Donación/Arrendamiento)
       └─→ Sistema asigna número inventario automático: DON-EMG-0042

2. RECEPCIÓN TÉCNICA
   └─→ Técnico inspecciona equipo
       ├─→ Registra estado físico (funcional/dañado)
       ├─→ Sube fotos del equipo
       ├─→ Adjunta documentos (factura, manual, garantía)
       └─→ Valida especificaciones eléctricas (voltaje, amperaje)

3. ENTREGA A DEPARTAMENTO
   └─→ Admin asigna:
       ├─→ Departamento (Urgencias, Quirófano, UCI)
       ├─→ Ubicación física (Consultorio 3, Piso 2)
       ├─→ Responsable (Dr. Juan Pérez - Cardiólogo)
       └─→ Fecha de capacitación

4. PROGRAMACIÓN MANTENIMIENTO
   └─→ Sistema calcula próximo mantenimiento:
       └─→ Si periodicidad = 90 días → Fecha = Hoy + 90 días

5. GENERACIÓN QR
   └─→ Sistema genera QR code único
       └─→ Impresión de etiqueta adhesiva para equipo
```

### 9.2 Flujo: Mantenimiento Preventivo

```
DÍA -7: ALERTA AUTOMÁTICA
   └─→ Dashboard muestra: "🟡 Mantenimiento próximo: Ventilador #ACT-EMG-0042"

DÍA 0: MANTENIMIENTO VENCIDO
   └─→ Técnico accede a lista de pendientes
       └─→ Clic en equipo → Formulario de mantenimiento
           ├─→ Tipo servicio: Preventivo
           ├─→ Actividades realizadas (checklist):
           │   ├─ Limpieza de filtros
           │   ├─ Revisión de conexiones eléctricas
           │   ├─ Calibración de sensores
           │   └─ Pruebas funcionales
           ├─→ Partes usadas: [Filtro HEPA - $50, Fusible 5A - $3]
           ├─→ Observaciones: "Equipo operando óptimamente"
           └─→ Status final: FUNCIONAL / STAND BY / SIN REPARACIÓN

GUARDADO
   └─→ Sistema actualiza:
       ├─→ Fecha último mantenimiento: HOY
       ├─→ Próximo mantenimiento: HOY + 90 días
       └─→ Genera PDF de reporte (firma digital)
```

### 9.3 Flujo: Ticket de Falla (Usuario Público)

```
USUARIO ESCANEA QR EN EQUIPO
   └─→ URL: equipment_public.php?qr=ACT-EMG-0042
       └─→ Sistema carga:
           ├─→ Foto del equipo
           ├─→ Nombre: "Ventilador Medtronic"
           ├─→ Ubicación: "UCI - Piso 3"
           └─→ Formulario de reporte sin login

USUARIO REPORTA PROBLEMA
   └─→ Campos:
       ├─→ Tu nombre: "Enfermera María López"
       ├─→ Email: maria.lopez@amerimed.com
       ├─→ Teléfono: 998-123-4567
       ├─→ Tipo de falla: "No enciende"
       └─→ Descripción: "Pantalla apagada, no responde al botón power"

TICKET CREADO
   └─→ Sistema asigna:
       ├─→ Ticket #: TKT-2025-0156
       ├─→ Status: PENDING
       ├─→ is_public: 1
       └─→ Email confirmación enviado a: maria.lopez@amerimed.com

TÉCNICO ATIENDE
   └─→ Dashboard muestra: "🔴 Ticket TKT-2025-0156 - Ventilador UCI"
       └─→ Técnico asigna ticket a sí mismo → Status: IN_PROGRESS
           ├─→ Diagnóstico: "Fusible fundido"
           ├─→ Reparación: Reemplazo de fusible
           ├─→ Pruebas: Equipo funcional
           └─→ Cierre: Status → CLOSED

NOTIFICACIÓN
   └─→ Email automático a: maria.lopez@amerimed.com
       "Tu reporte TKT-2025-0156 ha sido resuelto. El equipo está operativo."
```

---

## 10. APIs y Endpoints

### 10.1 Endpoints AJAX (JSON)

Ubicación: `/public/ajax/`

#### **Autenticación**

**POST /public/ajax/login.php**
```json
Request:
{
  "username": "admin",
  "password": "admin123"
}

Response (Success):
{
  "status": "success",
  "user_id": 14,
  "username": "admin",
  "role": 1,
  "branch_id": 1
}

Response (Error):
{
  "status": "error",
  "message": "Usuario o contraseña incorrectos"
}
```

#### **CRUD de Equipos**

**POST /public/ajax/equipment_crud.php?action=create**
```json
Request:
{
  "name": "Ventilador Medtronic",
  "brand": "Medtronic",
  "model": "PB840",
  "serie": "SN123456",
  "acquisition_type": 1,
  "equipment_category_id": 2,
  "amount": 150000,
  "supplier_id": 5
}

Response:
{
  "status": "success",
  "equipment_id": 313,
  "inventory_number": "COM-EMG-0313"
}
```

**GET /public/ajax/equipment_crud.php?action=get&id=313**
```json
Response:
{
  "id": 313,
  "name": "Ventilador Medtronic",
  "number_inventory": "COM-EMG-0313",
  "category": "Equipo Médico General",
  "supplier": "Medtronic Inc.",
  "next_maintenance": "2026-03-18"
}
```

#### **CRUD de Tickets**

**POST /public/ajax/ticket_crud.php?action=create_public**
```json
Request:
{
  "equipment_id": 313,
  "reporter_name": "Enfermera María",
  "reporter_email": "maria@hospital.com",
  "reporter_phone": "998-123-4567",
  "issue_type": "No enciende",
  "description": "Equipo no responde"
}

Response:
{
  "status": "success",
  "ticket_number": "TKT-2025-0157",
  "ticket_id": 24
}
```

### 10.2 Generación de PDFs

**GET /app/helpers/equipment_report_pdf.php?id=313**

Genera PDF con:
- Ficha técnica del equipo
- Historial de mantenimientos (tabla)
- Responsable actual
- QR code para acceso público

**GET /app/helpers/maintenance_report_pdf.php?month=12&year=2025**

Reporte mensual consolidado:
- Todos los mantenimientos del mes
- Gráfica de cumplimiento
- Equipos atrasados (tabla)

### 10.3 Exportación Excel

**GET /app/helpers/excel_export.php?type=equipments&branch_id=1**

Genera archivo `.xlsx` con:
- Todos los equipos del branch
- Columnas: No. Inventario, Nombre, Categoría, Ubicación, Último Mantenimiento, Estado

Librería: PhpSpreadsheet

---

## 11. Deployment

### 11.1 Infraestructura Hostinger

**Hosting Compartido MX:**
- IP: 46.202.197.220
- Puerto SSH: 65002
- Usuario: u499728070
- Document Root: `/home/u499728070/domains/activosamerimed.com/public_html/`

**Subdominios:**
```
biomedicacun.activosamerimed.com → /public_html/biomedicacun/
sistemascun.activosamerimed.com  → /public_html/sistemascun/
manttocun.activosamerimed.com    → /public_html/manttocun/
```

### 11.2 Proceso de Deployment

**Script:** `update-subdominios.ps1`

Pasos automatizados:
1. Comprimir código local (excluir .git, node_modules, logs)
2. Subir tar.gz al servidor vía SCP
3. Extraer en 3 subdominios preservando `.env`
4. Configurar permisos 755 en uploads/, logs/, cache/
5. Limpiar archivos temporales

Ejecución:
```powershell
.\update-subdominios.ps1
```

Tiempo estimado: 30 segundos

### 11.3 Credenciales de Bases de Datos

Archivo: `DB_CREDENTIALS.txt`

```
=== Base de Datos: biomedicacun ===
Usuario: u499728070_biomedica
Contraseña: gR1Zh9Ll2jcawp35
Base de datos: u499728070_biomedicacun
Branch ID: 1

=== Base de Datos: sistemascun ===
Usuario: u499728070_sistemas
Contraseña: IJLd0R3hqBCbxKHW
Base de datos: u499728070_sistemascun
Branch ID: 2

=== Base de Datos: manttocun ===
Usuario: u499728070_mantto
Contraseña: JhNsx6MVlkQzLC4g
Base de datos: u499728070_manttocun
Branch ID: 3

Usuario Admin (los 3 sistemas):
Username: admin
Contraseña: admin123
```

### 11.4 Backups

**Estrategia de Respaldo:**

1. **Base de Datos (Diaria):**
```bash
mysqldump -u u499728070_biomedica -p u499728070_biomedicacun > backup_$(date +%Y%m%d).sql
```

2. **Uploads (Semanal):**
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

3. **Código (Git):**
- Commits diarios en repositorio privado
- Tags de versión en releases

### 11.5 Modo Mantenimiento

Activar:
```php
// config/maintenance_config.php
return [
    'maintenance_enabled' => true,
    'allowed_ips' => ['192.168.1.100']
];
```

Página mostrada: `/components/maintenance.php`

Mensaje personalizable:
> "Sistema en mantenimiento programado. Regresaremos en 2 horas. Disculpa las molestias."

---

## 12. Stack Tecnológico

### 12.1 Backend

| Tecnología | Versión | Propósito |
|-----------|---------|-----------|
| PHP | 7.4+ | Lenguaje servidor |
| MySQL / MariaDB | 8.0+ | Base de datos relacional |
| PDO | - | Capa de abstracción DB |
| MySQLi | - | Conexiones legacy |

### 12.2 Frontend

| Tecnología | Versión | Propósito |
|-----------|---------|-----------|
| AdminLTE | 3.2 | Theme admin dashboard |
| Bootstrap | 4.6 | Framework CSS |
| jQuery | 3.6 | Manipulación DOM |
| DataTables | 1.11 | Tablas interactivas |
| Chart.js | 3.9 | Gráficas estadísticas |
| Select2 | 4.1 | Dropdowns mejorados |
| SweetAlert2 | 11.0 | Modales elegantes |

### 12.3 Librerías PHP

| Librería | Propósito |
|---------|-----------|
| FPDF | Generación de PDFs |
| PhpSpreadsheet | Exportación Excel |
| PHPQRCode | Generación códigos QR |
| PHPMailer | Envío de emails |

### 12.4 DevOps

| Herramienta | Propósito |
|------------|-----------|
| PowerShell | Scripts de deployment |
| Git | Control de versiones |
| SSH | Acceso remoto servidor |
| SCP | Transferencia archivos |

---

## 13. Métricas del Sistema

### 13.1 Complejidad

- **Líneas de código:** ~25,000
- **Archivos PHP:** 180+
- **Controladores:** 12
- **Modelos:** 13
- **Vistas:** 60+
- **Tablas DB:** 41

### 13.2 Performance

- **Tiempo carga dashboard:** < 2 segundos
- **Generación PDF:** < 3 segundos
- **Query promedio:** < 100ms
- **Tamaño deployment:** 24 MB (comprimido)

---

## 14. Roadmap Futuro

### 14.1 Mejoras Técnicas

1. **Migración a bcrypt:** Reemplazar MD5 en contraseñas
2. **API RESTful:** Endpoints documentados con Swagger
3. **Tests automatizados:** PHPUnit para cobertura 80%+
4. **Docker:** Contenedorización para desarrollo local
5. **CI/CD:** GitHub Actions para deployment automático

### 14.2 Funcionalidades

1. **Dashboard en tiempo real:** WebSockets para alertas push
2. **App móvil:** React Native para técnicos en campo
3. **Reportes consolidados:** Vista ejecutiva multi-branch
4. **Integración IoT:** Sensores en equipos críticos
5. **Machine Learning:** Predicción de fallas basada en historial

---

## 15. Contacto y Soporte

**Documentación actualizada:** Diciembre 2025  
**Repositorio:** GitHub privado  
**Soporte técnico:** Departamento de TI - AmeriMed

---

**Fin del Documento**
