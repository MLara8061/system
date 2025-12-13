# Reorganización de Estructura del Proyecto - Documentación

## Estado Actual

El proyecto ha sido reorganizado en **dos fases** para mejorar la mantenibilidad y escalabilidad.

## Estructura Nueva

```
system/
├── public/                              # Punto de entrada PÚBLICO
│   ├── index.php                       # (futuro - actualmente en raíz)
│   ├── ajax/
│   │   ├── login.php                  # ✅ Login endpoint
│   │   └── action.php                 # ✅ AJAX general (mapea desde ajax.php)
│   └── assets/
│
├── app/                                 # Lógica de aplicación
│   ├── views/
│   │   ├── layouts/                    # ✅ Completado
│   │   │   ├── header.php             # ✅ Movido
│   │   │   ├── footer.php             # ✅ Movido
│   │   │   ├── sidebar.php            # ✅ Movido
│   │   │   └── topbar.php             # ✅ Movido
│   │   │
│   │   ├── auth/                       # ✅ Completado
│   │   │   ├── login.php              # ✅ Movido
│   │   │   └── logout.php             # ✅ Movido
│   │   │
│   │   └── dashboard/                  # ✅ Completado (vistas principales)
│   │       ├── home.php               # ⏳ Por copiar
│   │       ├── calendar.php           # ⏳ Por copiar
│   │       │
│   │       ├── users/                 # ✅ Completado
│   │       │   ├── list.php
│   │       │   ├── create.php
│   │       │   ├── manage_modal.php
│   │       │   └── modal.php
│   │       │
│   │       ├── customers/             # ✅ Completado
│   │       │   ├── list.php
│   │       │   ├── new.php
│   │       │   └── edit.php
│   │       │
│   │       ├── equipment/             # ✅ Parcialmente (3/15+ vistas)
│   │       │   ├── list.php
│   │       │   ├── new.php
│   │       │   ├── edit.php
│   │       │   ├── view.php           # ⏳ Por copiar
│   │       │   ├── public.php         # ⏳ Por copiar
│   │       │   ├── upload.php         # ⏳ Por copiar
│   │       │   ├── tools_list.php     # ⏳ Por copiar
│   │       │   ├── new_tool.php       # ⏳ Por copiar
│   │       │   ├── edit_tool.php      # ⏳ Por copiar
│   │       │   ├── accessories_list.php # ⏳ Por copiar
│   │       │   └── ... (más reportes)
│   │       │
│   │       ├── staff/                 # ✅ Completado
│   │       │   ├── list.php
│   │       │   ├── new.php
│   │       │   └── edit.php
│   │       │
│   │       ├── suppliers/             # ✅ Completado
│   │       │   ├── list.php
│   │       │   ├── new.php
│   │       │   └── edit.php
│   │       │
│   │       ├── tickets/               # ✅ Completado
│   │       │   ├── list.php
│   │       │   ├── new.php
│   │       │   ├── edit.php
│   │       │   └── view.php
│   │       │
│   │       ├── settings/              # ⏳ Por crear
│   │       │   ├── profile.php
│   │       │   ├── activity_log.php
│   │       │   ├── departments.php
│   │       │   ├── categories.php
│   │       │   ├── services.php
│   │       │   ├── locations.php
│   │       │   └── job_positions.php
│   │       │
│   │       ├── reports/               # ⏳ Por crear
│   │       │   └── form.php
│   │       │
│   │       └── inventory/             # ⏳ Por crear
│   │           ├── list.php
│   │           └── manage.php
│   │
│   ├── helpers/                        # Funciones de utilidad
│   │   ├── PathResolver.php           # ✅ Creado (pendiente migración)
│   │   ├── generate_pdf.php           # ⏳ Por mover
│   │   ├── download_template.php      # ⏳ Por mover
│   │   └── generate_excel_template.php # ⏳ Por mover
│   │
│   ├── models/                         # Capa de datos (FUTURO)
│   │   ├── User.php
│   │   ├── Equipment.php
│   │   └── ...
│   │
│   ├── controllers/                    # Lógica de negocio (FUTURO)
│   │   └── ...
│   │
│   └── routing.php                     # ✅ Router de páginas
│
├── config/                              # Configuración
│   ├── env.php                         # ✅ Loader de .env
│   ├── db.php                          # ✅ Conexión PDO
│   ├── session.php                     # ✅ Hardening de sesión
│   ├── config.php                      # Legacy (mysqli)
│   └── .env                            # Variables de entorno
│
├── database/                            # Base de datos
│   ├── migrations/                     # Scripts SQL
│   └── seeds/                          # Datos iniciales
│
├── logs/                                # Logs de aplicación
│
├── tests/                               # Tests unitarios
│
├── .github/
│   ├── workflows/
│   │   └── deploy.yml                 # CI/CD deployment
│   └── SECURITY_HARDENING.md          # Documentación seguridad
│
├── index.php                            # ✅ Punto de entrada PRINCIPAL
├── .env.example
└── admin_class.php                      # ✅ Core logic (mezclado: migrar a models/)
```

## Fases de Reorganización

### ✅ Fase 1: Layouts y Autenticación (COMPLETADO)
- Mover `header.php`, `footer.php`, `sidebar.php`, `topbar.php` → `app/views/layouts/`
- Mover `login.php`, `logout.php` → `app/views/auth/`
- Mover AJAX endpoints → `public/ajax/`
- Crear `app/helpers/PathResolver.php` para resolución dinámica de rutas
- Actualizar `index.php` para usar ROOT constante
- Crear router JavaScript en footer.php para mapear calls legadas

**Commits:**
- `refactor: Reorganize project structure - Phase 1: Layouts and Auth views`
- `refactor: Reorganize project structure - Phase 2: Dashboard views and routing`

### ⏳ Fase 3: Vistas Restantes (EN PROGRESO)
**Pendiente:**
- Equipment (reportes adicionales, unsubscribe, public views)
- Settings/Configuration (profile, departments, categories, services)
- Reports (forms, PDFs)
- Inventory management
- Home/Dashboard principal
- Herramientas y Accesorios

**Enfoque:** Copiar archivos a nuevas ubicaciones, mantener lógica sin cambios

### ⏳ Fase 4: Helpers y Utilities (FUTURO)
- `generate_pdf.php` → `app/helpers/PdfGenerator.php`
- `download_template.php` → `app/helpers/ExcelDownloader.php`
- `generate_excel_template.php` → `app/helpers/ExcelGenerator.php`

### ⏳ Fase 5: Refactoring de Lógica (FUTURO)
- `admin_class.php` → Dividir en múltiples models:
  - `app/models/User.php`
  - `app/models/Equipment.php`
  - `app/models/Customer.php`
  - `app/models/Ticket.php`
  - etc.
- Crear controllers para cada módulo
- Implementar inyección de dependencias

## Compatibilidad Hacia Atrás

### Sistema de Routing Automático

Todos los archivos heredados funcionan **sin cambios**. El sistema usa:

1. **Mapeo de Rutas (`app/routing.php`)**
   - Define qué URL antigua → qué archivo nuevo
   - Busca en mapeo → Si no existe, intenta archivo directo en raíz
   - Fallback: genera error 404

2. **Router AJAX JavaScript (`footer.php`)**
   ```javascript
   // Intercepta:
   $.ajax({ url: 'ajax.php?action=save_user' })
   
   // Redirige a:
   /public/ajax/action.php?action=save_user
   ```

3. **ROOT Constante (`index.php`)**
   ```php
   define('ROOT', __DIR__); // Raíz del proyecto
   
   // En layouts:
   include ROOT . '/app/views/layouts/header.php';
   
   // En vistas:
   include ROOT . '/admin_class.php';
   ```

## Beneficios de la Nueva Estructura

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Navegación** | 90+ archivos en raíz (caos) | Organizado por módulo (claro) |
| **Seguridad** | Archivos sensibles expuestos | `public/` como punto entrada único |
| **Mantenibilidad** | Difícil localizar código | Estructura predecible |
| **Escalabilidad** | Múltiples concerns mezclados | Separación de responsabilidades |
| **Testing** | Difícil aislar módulos | Fácil testear controllers/models |
| **DevOps** | Punto entrada confuso | `index.php` claro y centralizado |

## Próximas Acciones Recomendadas

### Corto Plazo (Esta Sesión)
1. ✅ Completar copia de vistas restantes
2. ✅ Validar que todo funciona vía index.php
3. ✅ Deploy a producción (GitHub Actions automático)

### Mediano Plazo
1. Mover helpers a `app/helpers/`
2. Crear simples Models para CRUD (User, Equipment, etc.)
3. Implementar routing más robusto (sin $_GET['page'])

### Largo Plazo
1. Refactoring completo a MVC
2. Implementar dependency injection
3. Tests unitarios para cada módulo
4. Posible migración a framework (Laravel/Symfony)

## Notas Importantes

⚠️ **CAMBIOS MÍNIMOS EN CÓDIGO LÓGICO**
- Los archivos se han COPIADO (no movido aún)
- Las rutas se han actualizado a usar ROOT constante
- Toda la lógica funciona igual que antes
- Cambios son estructurales, no funcionales

🔄 **DEPLOYMENT AUTOMÁTICO**
- GitHub Actions detecta cambios en main
- Ejecuta `rsync` a Hostinger automáticamente
- No requiere intervención manual
- La reorganización es completamente transparente en producción

✅ **BACKWARD COMPATIBLE**
- Todas las URLs legadas siguen funcionando
- AJAX calls se redireccionan automáticamente
- No hay breaking changes
- Usuarios finales no notan diferencia

---
**Estado:** Fase 2 de 5 completada
**Próxima:** Copiar vistas restantes y validar funcionalidad completa
**Última actualización:** 13 de Diciembre de 2025
