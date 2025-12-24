# 🚀 Guía de Despliegue Multi-Tenant

## Sistema de Gestión de Activos - AmeriMed

**Dominio principal:** `activosamerimed.com`  
**Subdominios:** 3 sistemas independientes con datos separados  
**Proveedor:** Hostinger MX  

---

## 📋 Resumen de la Arquitectura

### Estructura Multi-Tenant (Opción 1)

- ✅ **Un solo repositorio de código** compartido
- ✅ **3 bases de datos separadas** (una por subdominio)
- ✅ **Branch ID único** por sistema para segregación de datos
- ✅ **Subdominios independientes** con configuración propia

```text
activosamerimed.com/
├── biomedicacun/      → https://biomedicacun.activosamerimed.com
│   ├── config/.env    → Branch ID: 1, BD: biomedicacun
│   └── (código)
├── sistemascun/       → https://sistemascun.activosamerimed.com
│   ├── config/.env    → Branch ID: 2, BD: sistemascun
│   └── (código)
└── manttocun/         → https://manttocun.activosamerimed.com
    ├── config/.env    → Branch ID: 3, BD: manttocun
    └── (código)
```

---

## 🛠️ Scripts Disponibles

### 1. `setup-subdominios.ps1` - Configuración Inicial

Crea la estructura de directorios y prepara el entorno.

```powershell
# Prueba sin hacer cambios
.\setup-subdominios.ps1 -DryRun

# Ejecutar configuración completa
.\setup-subdominios.ps1
```

### 2. `deploy-multi.ps1` - Despliegue Automatizado

Despliega código a los 3 subdominios simultáneamente.

```powershell
# Desplegar a todos
.\deploy-multi.ps1

# Desplegar solo a biomedicacun y sistemascun
.\deploy-multi.ps1 -Targets biomedicacun,sistemascun

# Verificar estado sin desplegar
.\deploy-multi.ps1 -OnlyVerify

# Desplegar sin hacer commit local
.\deploy-multi.ps1 -SkipCommit
```

---

## 📝 Proceso de Instalación Completa

### FASE 1: Preparación Local (En tu PC)

#### Paso 1.1: Actualizar configuración de SSH

Edita `setup-subdominios.ps1` y `deploy-multi.ps1`:

```powershell
$SSH_KEY = "ruta/a/tu/llave_ssh"
$SSH_HOST = "tu_usuario@tu_servidor"
$SSH_PORT = "puerto_ssh"
```

#### Paso 1.2: Actualizar URL del repositorio

En `setup-subdominios.ps1`, línea 13:

```powershell
$REPO_URL = "https://github.com/tu-usuario/amerimed-system.git"
```

#### Paso 1.3: Ejecutar setup inicial

```powershell
.\setup-subdominios.ps1
```

Esto crea:

- ✅ Estructura de carpetas en servidor
- ✅ Clona el repositorio 3 veces
- ✅ Copia archivos `.env` iniciales
- ✅ Configura permisos

---

### FASE 2: Configuración en Hostinger

#### Paso 2.1: Crear bases de datos

Ir a **Panel Hostinger → Bases de datos MySQL**

Crear 3 bases de datos:

| Sistema        | Nombre BD                  | Usuario                | Contraseña         |
| -------------- | -------------------------- | ---------------------- | ------------------ |
| biomedicacun   | `u499728070_biomedicacun`  | `u499728070_biomedica` | [Generar segura]   |
| sistemascun    | `u499728070_sistemascun`   | `u499728070_sistemas`  | [Generar segura]   |
| manttocun      | `u499728070_manttocun`     | `u499728070_mantto`    | [Generar segura]   |

**💡 Tip:** Guarda las contraseñas en un gestor seguro.

#### Paso 2.2: Importar esquema de base de datos

Para cada base de datos creada:

```bash
# Conectar por SSH
ssh tu_usuario@tu_servidor -p puerto

# Ir al directorio
cd domains/activosamerimed.com/public_html/biomedicacun

# Importar esquema
mysql -u u499728070_biomedica -p u499728070_biomedicacun < database/migrations/schema.sql
```

O usa **phpMyAdmin**:

1. Seleccionar base de datos
2. Pestaña "Importar"
3. Subir `database/migrations/schema.sql`

#### Paso 2.3: Configurar subdominios

Ir a **Panel Hostinger → Dominios → Crear Subdominio**

Para cada sistema:

| Subdominio     | Document Root                                                            |
| -------------- | ------------------------------------------------------------------------ |
| `biomedicacun` | `/home/u499728070/domains/activosamerimed.com/public_html/biomedicacun` |
| `sistemascun`  | `/home/u499728070/domains/activosamerimed.com/public_html/sistemascun`  |
| `manttocun`    | `/home/u499728070/domains/activosamerimed.com/public_html/manttocun`    |

**Pasos:**

1. Click en "Crear subdominio"
2. Escribir prefijo: `biomedicacun`
3. Seleccionar dominio principal: `activosamerimed.com`
4. Establecer Document Root correspondiente
5. Guardar y esperar propagación DNS (5-30 min)

#### Paso 2.4: Actualizar archivos .env

Conectar por SSH y editar cada archivo:

```bash
# Sistema Biomédica
nano /home/u499728070/domains/activosamerimed.com/public_html/biomedicacun/config/.env
```

Actualizar estas líneas con las contraseñas reales:

```env
DB_PASS_PROD=TuPasswordReal123!
```

Repetir para `sistemascun` y `manttocun`.

#### Paso 2.5: Configurar permisos finales

```bash
cd /home/u499728070/domains/activosamerimed.com/public_html

# Para cada sistema
chmod -R 755 biomedicacun/uploads/ biomedicacun/logs/
chmod -R 755 sistemascun/uploads/ sistemascun/logs/
chmod -R 755 manttocun/uploads/ manttocun/logs/
```

---

### FASE 3: Verificación y Primer Despliegue

#### Paso 3.1: Verificar conexión

```powershell
.\deploy-multi.ps1 -OnlyVerify
```

Debe mostrar:

- ✅ Conexión SSH OK
- ✅ Archivos .env presentes
- ✅ Sin cambios pendientes

#### Paso 3.2: Primer despliegue

```powershell
.\deploy-multi.ps1 -CommitMessage "feat: configuración inicial multi-tenant"
```

#### Paso 3.3: Probar cada subdominio

Abrir en navegador:

- `https://biomedicacun.activosamerimed.com`
- `https://sistemascun.activosamerimed.com`
- `https://manttocun.activosamerimed.com`

**Verificar:**

- ✅ Página de login carga correctamente
- ✅ Sin errores de base de datos
- ✅ Sin errores de permisos

---

## 🔐 Configuración de Usuarios

### Crear usuario admin en cada sistema

Conectar a cada base de datos y ejecutar:

```sql
-- Biomédica CUN
USE u499728070_biomedicacun;
INSERT INTO users (username, password, name, type, branch_id) 
VALUES ('admin', MD5('admin123'), 'Administrador Biomédica', 1, 1);

-- Sistemas CUN
USE u499728070_sistemascun;
INSERT INTO users (username, password, name, type, branch_id) 
VALUES ('admin', MD5('admin123'), 'Administrador Sistemas', 1, 2);

-- Mantenimiento CUN
USE u499728070_manttocun;
INSERT INTO users (username, password, name, type, branch_id) 
VALUES ('admin', MD5('admin123'), 'Administrador Mantenimiento', 1, 3);
```

**⚠️ IMPORTANTE:** Cambia las contraseñas después del primer login.

---

## 🔄 Workflow de Desarrollo y Despliegue

### Desarrollo Local

1. Trabajar en tu máquina con BD local
2. Probar cambios en `localhost`
3. Hacer commits descriptivos

### Despliegue a Producción

```powershell
# Desplegar cambios a los 3 sistemas
.\deploy-multi.ps1 -CommitMessage "feat: nueva funcionalidad X"
```

El script automáticamente:

1. ✅ Hace commit de cambios locales
2. ✅ Push a GitHub
3. ✅ Pull en cada subdominio
4. ✅ Verifica permisos
5. ✅ Muestra resumen de estado

### Despliegue Selectivo

```powershell
# Solo actualizar biomedicacun y sistemascun
.\deploy-multi.ps1 -Targets biomedicacun,sistemascun
```

---

## 🐛 Solución de Problemas

### Error: "No se puede conectar por SSH"

**Causa:** Llave SSH incorrecta o permisos

**Solución:**

```powershell
# Verificar llave
ls "C:\Users\Arla.ALLIENWARE.001\Desktop\system\.ssh\"

# Verificar permisos (solo lectura para ti)
icacls "ruta\a\llave_ssh"
```

### Error: "git pull failed"

**Causa:** Cambios locales en servidor

**Solución:** El script lo resuelve automáticamente con `git reset --hard`

### Error: "Database connection failed"

**Causa:** Credenciales incorrectas en `.env`

**Solución:**

```bash
# Verificar credenciales
nano config/.env

# Probar conexión manualmente
mysql -u usuario -p nombre_bd
```

### Error: "Permission denied" en uploads/

**Causa:** Permisos incorrectos

**Solución:**

```bash
chmod -R 755 uploads/
chown -R usuario:usuario uploads/
```

### Subdominios no cargan

**Causa:** DNS no propagado o Document Root incorrecto

**Solución:**

1. Esperar 30 min para propagación DNS
2. Verificar Document Root en panel Hostinger
3. Verificar que existe `index.php` en la ruta

---

## 📊 Monitoreo y Mantenimiento

### Verificar estado de todos los sistemas

```powershell
.\deploy-multi.ps1 -OnlyVerify
```

### Ver logs de errores

```bash
# Biomédica CUN
tail -f /home/u499728070/domains/activosamerimed.com/public_html/biomedicacun/logs/error.log

# Todos los sistemas
tail -f /home/u499728070/domains/activosamerimed.com/public_html/*/logs/error.log
```

### Backup de bases de datos

```bash
# Script de backup
mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d).sql

# Backup de los 3 sistemas
mysqldump -u u499728070_biomedica -p u499728070_biomedicacun > biomedica_backup.sql
mysqldump -u u499728070_sistemas -p u499728070_sistemascun > sistemas_backup.sql
mysqldump -u u499728070_mantto -p u499728070_manttocun > mantto_backup.sql
```

---

## 🔧 Configuración Avanzada

### Usar API de Hostinger

El sistema está preparado para usar la API de Hostinger.

**Configurar API Token:**

```powershell
$env:API_TOKEN = "tu_token_de_hostinger"
```

**Listar sitios:**

```powershell
.\tasks\Hostinger_List_Websites.ps1
```

### Automatizar con Cron

Crear tarea programada para backups automáticos:

```bash
# Editar crontab
crontab -e

# Backup diario a las 2 AM
0 2 * * * /ruta/a/backup-script.sh
```

---

## 📁 Estructura de Archivos Clave

```text
system/
├── deploy-multi.ps1                    # Script principal de despliegue
├── setup-subdominios.ps1               # Setup inicial de infraestructura
├── sync-subdominios.ps1                # Sincronización a subdominios existentes
├── config/
│   ├── .env.biomedicacun.example      # Plantilla para Biomédica
│   ├── .env.sistemascun.example       # Plantilla para Sistemas
│   ├── .env.manttocun.example         # Plantilla para Mantenimiento
│   └── config.php                     # Configuración compartida
├── database/
│   ├── migrations/
│   │   └── schema.sql                 # Esquema de BD a importar
│   └── seeds/                         # Datos iniciales (opcional)
└── docs/
    └── DESPLIEGUE_MULTI_TENANT.md     # Esta guía
```

---

## ✅ Checklist de Implementación

### Configuración Inicial

- [ ] Scripts actualizados con tus credenciales SSH
- [ ] URL del repositorio configurada
- [ ] `setup-subdominios.ps1` ejecutado exitosamente
- [ ] 3 bases de datos creadas en Hostinger
- [ ] Esquema SQL importado en cada BD
- [ ] 3 subdominios configurados en Hostinger
- [ ] Archivos `.env` actualizados con contraseñas reales
- [ ] Permisos de carpetas configurados (755)

### Verificación

- [ ] `deploy-multi.ps1 -OnlyVerify` sin errores
- [ ] Los 3 subdominios cargan correctamente
- [ ] Login funciona en cada sistema
- [ ] Usuarios admin creados
- [ ] Sin errores en logs

### Producción

- [ ] Primer despliegue exitoso
- [ ] Contraseñas de admin cambiadas
- [ ] Backups configurados
- [ ] Certificados SSL activos (Hostinger los da gratis)

---

## 🆘 Soporte

### Recursos

- **Hostinger Docs:** <https://support.hostinger.com>
- **Panel Hostinger:** <https://hpanel.hostinger.com>
- **GitHub del proyecto:** (tu repositorio)

### Comandos Útiles

```powershell
# Ver ayuda de scripts
Get-Help .\deploy-multi.ps1 -Full
Get-Help .\setup-subdominios.ps1 -Full

# Ver últimos commits
git log --oneline -10

# Estado de repositorio
git status
```

---

## 🎯 Próximos Pasos Recomendados

1. **Personalización:** Ajustar nombres y branding por sistema
2. **Usuarios:** Crear cuentas de usuario para cada departamento
3. **Datos:** Importar inventario inicial de equipos
4. **Monitoreo:** Configurar alertas de errores
5. **Backups:** Automatizar respaldos diarios
6. **SSL:** Verificar certificados HTTPS activos

---

## 📞 Contacto y Mantenimiento

**Desarrollado para:** AmeriMed  
**Fecha implementación:** Diciembre 2025  
**Proveedor hosting:** Hostinger MX  
**Arquitectura:** Multi-Tenant con BD separadas  

---

¡Sistema listo para producción! 🚀
