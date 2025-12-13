# Estructura de Directorios - Sistema de Equipos

## 📁 Estructura Organizada

```
system/
├── app/                           # Aplicación principal
│   ├── models/                    # 12 Modelos de dominio (MVC Pattern)
│   │   ├── DataStore.php          # Base class para acceso a datos
│   │   ├── User.php
│   │   ├── Equipment.php
│   │   ├── Customer.php
│   │   ├── Department.php
│   │   ├── Ticket.php
│   │   ├── Category.php
│   │   ├── Location.php
│   │   ├── Supplier.php
│   │   ├── Service.php
│   │   ├── Tool.php
│   │   ├── Accessory.php
│   │   └── Inventory.php
│   │
│   ├── controllers/               # 12 Controllers (Lógica de negocio)
│   │   ├── UserController.php
│   │   ├── EquipmentController.php
│   │   ├── CustomerController.php
│   │   ├── DepartmentController.php
│   │   ├── TicketController.php
│   │   ├── CategoryController.php
│   │   ├── LocationController.php
│   │   ├── SupplierController.php
│   │   ├── ServiceController.php
│   │   ├── ToolController.php
│   │   ├── AccessoryController.php
│   │   └── InventoryController.php
│   │
│   ├── views/                     # Vistas (Presentación)
│   │   ├── layouts/              # Componentes reutilizables
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   ├── sidebar.php
│   │   │   └── topbar.php
│   │   ├── pages/                # Páginas principales
│   │   │   ├── home.php
│   │   │   ├── profile.php
│   │   │   ├── view_equipment.php
│   │   │   ├── view_ticket.php
│   │   │   ├── view_inventory.php
│   │   │   └── activity_log.php
│   │   ├── auth/                 # Autenticación
│   │   │   ├── login.php
│   │   │   └── logout.php
│   │   └── dashboard/            # Dashboard y módulos
│   │       ├── users/
│   │       ├── equipment/
│   │       ├── customers/
│   │       ├── tickets/
│   │       ├── suppliers/
│   │       ├── staff/
│   │       ├── settings/
│   │       ├── reports/
│   │       └── inventory/
│   │
│   ├── helpers/                  # Funciones auxiliares
│   │   ├── auth.php
│   │   ├── validation.php
│   │   ├── pdf_generator.php
│   │   ├── excel_exporter.php
│   │   └── qr_generator.php
│   │
│   └── routing.php               # Router centralizado
│
├── config/                        # Configuración
│   ├── database.php              # Conexión a BD
│   ├── session.php               # Manejo de sesiones
│   ├── constants.php             # Constantes de la app
│   └── db_connect.example.php    # Ejemplo de conexión
│
├── public/                        # Archivos públicos
│   ├── ajax/                      # 13 Endpoints AJAX
│   │   ├── user.php
│   │   ├── equipment.php
│   │   ├── customer.php
│   │   ├── department.php
│   │   ├── ticket.php
│   │   ├── category.php
│   │   ├── location.php
│   │   ├── supplier.php
│   │   ├── service.php
│   │   ├── tool.php
│   │   ├── accessory.php
│   │   ├── inventory.php
│   │   └── login.php
│   │
│   ├── css/                       # Estilos
│   ├── js/                        # JavaScript
│   │   ├── jquery/
│   │   ├── datatables/
│   │   ├── adminlte/
│   │   └── custom/
│   │
│   ├── images/
│   ├── uploads/
│   ├── downloads/                 # Archivos de descargas
│   │   ├── descargar_manual.php
│   │   └── download_template.php
│   │
│   └── index.php                 # Punto de entrada de la app
│
├── legacy/                        # Archivos antiguos (86 archivos)
│   ├── admin_class.php
│   ├── ajax_*.php
│   ├── manage_*.php
│   ├── edit_*.php
│   ├── new_*.php
│   └── ... (otros archivos legacy)
│
├── docs/                          # Documentación
│   ├── INSTALACION_GUIA_COMPLETA.md
│   ├── INSTRUCCIONES_DESPLIEGUE.md
│   ├── LEEME_CARGA_MASIVA.md
│   ├── README_CARGA_MASIVA.md
│   ├── PHASE4_STATUS.md
│   └── PHASE4_AJAX_REFERENCE.md
│
├── .github/
│   └── workflows/
│       └── deploy.yml             # GitHub Actions (auto-deploy)
│
└── README.md                      # Información del proyecto
```

## 🏗️ Arquitectura

**Patrón MVC:**
- **Models** (12): Lógica de datos, validaciones de negocio
- **Controllers** (12): Lógica de aplicación, procesamiento
- **Views**: Presentación (layouts + pages)
- **AJAX Endpoints** (13): API para frontend

## 🔒 Seguridad

✅ Sesiones hardened
✅ Prepared statements en todas las queries
✅ Validación en Controllers
✅ Password hashing (bcrypt)
✅ CSRF protection
✅ Input sanitization

## 📊 Estadísticas

- **12 Modelos** completos
- **12 Controllers** con validación
- **13 AJAX endpoints** funcionales
- **~8,500 líneas de código nuevo**
- **86 archivos legacy organizados**
- **100% MVC pattern consistency**

## 🚀 Despliegue

Auto-deploy a Hostinger vía GitHub Actions en cada commit
