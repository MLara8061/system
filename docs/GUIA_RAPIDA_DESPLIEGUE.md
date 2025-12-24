# ============================================
# GUÍA RÁPIDA - Despliegue Multi-Tenant
# ============================================

## 🚀 Inicio Rápido (5 pasos)

### 1️⃣ Configuración Inicial
```powershell
# Ejecutar setup
.\setup-subdominios.ps1
```

### 2️⃣ En Panel de Hostinger
- Crear 3 bases de datos (sistema1, sistema2, sistema3)
- Crear 3 subdominios apuntando a sus carpetas
- Importar schema.sql en cada BD

### 3️⃣ Actualizar Contraseñas
```bash
# Editar cada .env con la contraseña real
nano domains/activosamerimed.com/public_html/sistema1/config/.env
nano domains/activosamerimed.com/public_html/sistema2/config/.env
nano domains/activosamerimed.com/public_html/sistema3/config/.env
```

### 4️⃣ Primer Despliegue
```powershell
.\deploy-multi.ps1
```

### 5️⃣ Crear Usuarios Admin
```sql
-- En cada base de datos
INSERT INTO users (username, password, name, type, branch_id) 
VALUES ('admin', MD5('admin123'), 'Admin', 1, 1);
```

---

## 📋 URLs de los Sistemas

| Sistema | URL | Branch ID | Base de Datos |
|---------|-----|-----------|---------------|
| Sistema 1 | https://sistema1.activosamerimed.com | 1 | u228864460_amerimed_s1 |
| Sistema 2 | https://sistema2.activosamerimed.com | 2 | u228864460_amerimed_s2 |
| Sistema 3 | https://sistema3.activosamerimed.com | 3 | u228864460_amerimed_s3 |

---

## 🔧 Comandos Más Usados

```powershell
# Desplegar a todos
.\deploy-multi.ps1

# Desplegar solo a sistema1
.\deploy-multi.ps1 -Targets sistema1

# Verificar estado sin desplegar
.\deploy-multi.ps1 -OnlyVerify

# Desplegar con mensaje personalizado
.\deploy-multi.ps1 -CommitMessage "fix: corrección de bugs"
```

---

## ❓ Solución Rápida de Problemas

| Problema | Solución |
|----------|----------|
| Error SSH | Verificar llave en `$SSH_KEY` |
| Error BD | Revisar credenciales en `.env` |
| 404 en subdominio | Verificar Document Root en Hostinger |
| Permisos uploads | `chmod -R 755 uploads/` |

---

## 📖 Documentación Completa
Ver: [DESPLIEGUE_MULTI_TENANT.md](DESPLIEGUE_MULTI_TENANT.md)
