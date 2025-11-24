# Instrucciones de Despliegue - Sistema de Gestión de Equipos

## 🚨 IMPORTANTE: Configuración requerida en servidor de producción

### 1. Actualizar URL Base en `config/config.php`

Después de hacer `git pull` en el servidor de Hostinger, debes editar el archivo:

**Archivo:** `config/config.php`  
**Línea:** ~94

Buscar esta sección:
```php
if (ENVIRONMENT === 'production') {
    define('BASE_URL', getenv('BASE_URL') ?: 'https://tudominio.com');
}
```

**Cambiar** `'https://tudominio.com'` por tu URL real, por ejemplo:
- `'https://gestionequipos.hostinger.com'`
- `'https://miempresa.com'`
- `'https://sistema.midominio.com'`

### 2. Limpiar caché del navegador

Si después del despliegue no ves los cambios:

**Chrome/Edge:**
- Presiona `Ctrl + Shift + Delete`
- Selecciona "Imágenes y archivos en caché"
- Haz clic en "Borrar datos"
- O presiona `Ctrl + F5` para recargar sin caché

**Firefox:**
- Presiona `Ctrl + Shift + Delete`
- Selecciona "Caché"
- Haz clic en "Limpiar ahora"

### 3. Verificar cambios desplegados

#### Historial de Mantenimientos:
- ✅ Debe aparecer en `edit_equipment.php` (editar equipo)
- ✅ Debe aparecer en `view_equipment.php` (vista pública QR)
- ✅ Ubicación: Después de sección "Resguardo", antes del botón "Guardar Cambios"

#### Códigos QR:
- ✅ Deben apuntar a tu dominio de producción (no a localhost)
- ✅ Verificar que `view_equipment.php` sea accesible públicamente

## 📋 Cambios recientes (Commits)

### Commit `7cb1b58` - Historial de Mantenimientos
- Agregada tabla de historial en edit_equipment.php
- Agregada tabla de historial en view_equipment.php
- DataTable con búsqueda, ordenamiento y paginación
- Descarga de PDF de reportes de mantenimiento

### Commit `97a7679` - Fix manage_services.php
- Corregida función validate_image inexistente

### Commit actual - URL Base para QR
- Centralizada URL base en config.php
- Códigos QR ahora usan BASE_URL
- Eliminada dependencia de localhost

## 🔧 Comandos de despliegue en Hostinger

```bash
# 1. Conectar por SSH al servidor
ssh usuario@tuservidor.hostinger.com

# 2. Ir al directorio del proyecto
cd public_html/system  # o la ruta donde esté instalado

# 3. Hacer pull de los cambios
git pull origin main

# 4. Editar config.php con tu URL
nano config/config.php
# o usar el File Manager de Hostinger

# 5. Verificar permisos de uploads
chmod -R 755 uploads/
chmod -R 755 uploads/qrcodes/

# 6. Limpiar caché de PHP (si aplica)
# Algunos planes de Hostinger requieren esto:
# Ir a Panel de Control > PHP > Limpiar caché OPcache
```

## ✅ Checklist de verificación

- [ ] Git pull ejecutado exitosamente
- [ ] config/config.php actualizado con URL correcta
- [ ] Caché del navegador limpiado
- [ ] Página edit_equipment.php muestra sección "Historial de Mantenimientos"
- [ ] Botón "Guardar Cambios" visible
- [ ] Códigos QR apuntan a dominio de producción (no localhost)
- [ ] Vista pública (view_equipment.php) funciona correctamente

## 🆘 Problemas comunes

### No veo el historial de mantenimientos
- Verificar que hiciste git pull
- Limpiar caché del navegador (Ctrl + F5)
- Verificar en el código fuente del HTML que esté la sección

### Códigos QR siguen apuntando a localhost
- Verificar que BASE_URL en config.php esté correctamente configurada
- Regenerar los códigos QR (eliminar archivos en uploads/qrcodes/)

### Botón "Guardar Cambios" no aparece
- El botón usa `form="manage_equipment"` para vincularse al formulario
- Verificar que no haya errores de JavaScript en la consola del navegador

---

**Última actualización:** 24 de noviembre de 2025  
**Versión del sistema:** 2.0
