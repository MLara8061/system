# Configuración de Despliegue SSH

## 1. Autorizar la llave pública en Hostinger

Copia esta llave pública y agrégala en tu panel de Hostinger:

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIEKrSiF+GvlqOZX4ob/3s6PXCc/oAQvaL9qk0b02wQW4 github-actions-deploy@hostinger
```

**Pasos en Hostinger:**
1. Entra a tu panel → **Advanced** → **SSH Access**
2. Click en **Manage SSH keys**
3. Pega la llave pública arriba
4. Guarda y verifica que SSH esté habilitado

## 2. Configurar secretos en GitHub

Ve a tu repositorio → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Crea estos secretos:

### `SSH_PRIVATE_KEY`
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACBCq0ohfhr5ajmV+KG/97Oj1wnP6AEL2i/apNG9NsEFuAAAAKiGXyX8hl8l
/AAAAAtzc2gtZWQyNTUxOQAAACBCq0ohfhr5ajmV+KG/97Oj1wnP6AEL2i/apNG9NsEFuA
AAAEDQyjquGPCeS84VeDyJcCJQ+yMhEcIkJSzdT44MbmAx70KrSiF+GvlqOZX4ob/3s6PX
Cc/oAQvaL9qk0b02wQW4AAAAH2dpdGh1Yi1hY3Rpb25zLWRlcGxveUBob3N0aW5nZXIBAg
MEBQY=
-----END OPENSSH PRIVATE KEY-----
```

### `SSH_HOST`
```
217.196.54.164
```

### `SSH_USER`
```
u228864460
```

### `SSH_PORT`
```
65002
```

### `REMOTE_PATH`
```
domains/indigo-porcupine-764368.hostingersite.com/public_html
```

### `DEPLOY_ENV_FILE` (Opcional)
Si quieres que el workflow genere tu `.env` en producción, pega el contenido completo:
```
DB_HOST=localhost
DB_NAME=u228864460_tu_base_datos
DB_USER=u228864460_tu_usuario
DB_PASS=tu_contraseña_segura
DB_CHARSET=utf8mb4
```

## 3. Probar el despliegue

Una vez configurados los secretos:

```bash
git add .
git commit -m "Setup SSH deployment"
git push origin main
```

Ve a **Actions** en GitHub para ver el progreso del despliegue.

## 4. Verificar en el servidor (Opcional)

Puedes conectarte manualmente para verificar:

```bash
ssh -i C:\Users\Arla.ALLIENWARE.001\.ssh\hostinger_deploy_ed25519 u228864460@ssh.hostinger.com
```

Y revisar archivos:
```bash
ls -la public_html/
```

## Notas de seguridad

- ✅ La llave privada NUNCA debe committearse al repo
- ✅ Solo existe en GitHub Secrets (encriptada)
- ✅ La llave pública es segura compartirla
- ✅ El workflow usa `rsync --delete` para sincronizar (borra archivos remotos que no existen en repo)
