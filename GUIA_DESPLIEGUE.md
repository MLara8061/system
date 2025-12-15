# Guía de Despliegue

## ❌ PROBLEMA IDENTIFICADO

El servidor de producción tiene un repositorio `.git` conectado a GitHub. Cada vez que haces `git push`, alguien/algo puede estar haciendo `git pull` en producción, sobrescribiendo tus cambios manuales con SCP.

**Evidencia:**
- Producción estaba en commit `1e485d6` (viejo)
- Tu local estaba en `a091f65` (nuevo con queries optimizadas)
- Archivos subidos con SCP se perdían después de push

## ✅ SOLUCIÓN

### Opción 1: Despliegue Automatizado (RECOMENDADO)

Usa el script `deploy.ps1` que hace todo el proceso:

```powershell
.\deploy.ps1 "fix: corregir bug en home"
```

El script hace:
1. ✅ Git add + commit local
2. ✅ Git push a GitHub
3. ✅ Git pull en producción (automático)
4. ✅ Verificación de estado

**Ventajas:**
- Un solo comando
- Siempre sincronizado
- Detecta y resuelve conflictos automáticamente

### Opción 2: Proceso Manual

Si prefieres control manual:

```powershell
# 1. Commit y push local
git add -A
git commit -m "tu mensaje"
git push origin main

# 2. Pull en producción
ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && git pull origin main"

# 3. Verificar
ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && git log --oneline -1"
```

### Opción 3: Desconectar Git en Producción (NO RECOMENDADO)

Si quieres volver a SCP manual:

```bash
# EN PRODUCCIÓN (vía SSH)
cd domains/indigo-porcupine-764368.hostingersite.com/public_html
rm -rf .git .github

# Ahora puedes usar SCP sin que se sobrescriba
```

**Desventaja:** Pierdes control de versiones en producción.

## 🔍 Verificación

Después de cualquier despliegue, verifica que el commit sea el correcto:

```powershell
ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && git log --oneline -3"
```

Debe mostrar tus últimos commits, empezando por:
```
a091f65 feat: Restaurar datos reales en dashboard usando queries optimizadas
c020659 fix: Solucionar carga del dashboard home con workarounds...
```

## 🚨 Si el Home Vuelve a Fallar

1. **Verificar commit en producción:**
   ```powershell
   ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && git log --oneline -1"
   ```

2. **Si está desactualizado, hacer pull:**
   ```powershell
   ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && git pull origin main"
   ```

3. **Si hay conflictos, resetear a origin:**
   ```powershell
   ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && git reset --hard origin/main"
   ```

## 📋 Resumen

| Método | Velocidad | Control | Sincronización | Recomendación |
|--------|-----------|---------|----------------|---------------|
| `deploy.ps1` | ⚡⚡⚡ | ✅ | ✅ Automática | ⭐⭐⭐⭐⭐ |
| Manual 3 pasos | ⚡⚡ | ✅✅ | ⚠️ Manual | ⭐⭐⭐⭐ |
| SCP (ya no funciona) | ⚡ | ✅✅✅ | ❌ Se pierde | ❌ |

## 🎯 Workflow Recomendado

```powershell
# Editar archivos en VS Code...
# Guardar cambios...

# Desplegar
.\deploy.ps1 "feat: agregar nueva funcionalidad"

# Verificar en el navegador
# https://indigo-porcupine-764368.hostingersite.com
```

**Eso es todo. Un comando, despliegue completo.**
