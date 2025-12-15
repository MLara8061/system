# Guía de Configuración del Sistema de Caché

## 1. Prueba Manual del Script de Caché

Primero ejecuta manualmente para verificar que funciona:

```bash
ssh -i "C:\Users\Arla.ALLIENWARE.001\Desktop\system\.ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && php utilities/update_dashboard_cache.php"
```

Deberías ver:
```
=== INICIO ACTUALIZACIÓN CACHE ===
1. Actualizando top proveedores...
   ✓ Top proveedores: 5 registros
2. Actualizando equipos recientes...
   ✓ Equipos recientes: 5 registros
...
=== FIN ACTUALIZACIÓN CACHE ===
```

## 2. Configurar Cron Job en Hostinger

### Opción A: Panel de Hostinger

1. Ingresa a: https://hpanel.hostinger.com
2. Ir a: **Avanzado** → **Cron Jobs**
3. Agregar nuevo cron:
   - **Comando:** `/usr/bin/php /home/u228864460/domains/indigo-porcupine-764368.hostingersite.com/public_html/utilities/update_dashboard_cache.php`
   - **Frecuencia:** Cada hora (`0 * * * *`)
   - **Email notificaciones:** (opcional) tu email

### Opción B: Línea de Comandos (crontab)

```bash
# Editar crontab
ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164
crontab -e

# Agregar esta línea:
0 * * * * /usr/bin/php /home/u228864460/domains/indigo-porcupine-764368.hostingersite.com/public_html/utilities/update_dashboard_cache.php >> /home/u228864460/domains/indigo-porcupine-764368.hostingersite.com/public_html/cache_update.log 2>&1

# Verificar crontab
crontab -l
```

## 3. Verificar que Funciona

Después de la primera ejecución del cron:

```bash
# Ver log del cache
ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "tail -50 domains/indigo-porcupine-764368.hostingersite.com/public_html/cache_update.log"

# Ver datos en caché
ssh -i ".ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && php -r \"require 'config/db_connect.php'; \\\$r = \\\$conn->query('SELECT cache_key, updated_at FROM dashboard_cache'); while(\\\$row = \\\$r->fetch_assoc()) echo \\\$row['cache_key'].' - '.\\\$row['updated_at'].\"\n\";\""
```

## 4. Desplegar el Sistema de Caché

```powershell
# Desde tu máquina local
.\deploy.ps1 "feat: Implementar sistema de caché para dashboard con cron"
```

## 5. Ejecutar Primera Actualización

```powershell
ssh -i "C:\Users\Arla.ALLIENWARE.001\Desktop\system\.ssh\deploy_id_nopass_new" -p 65002 u228864460@217.196.54.164 "cd domains/indigo-porcupine-764368.hostingersite.com/public_html && php utilities/update_dashboard_cache.php"
```

## 6. Recargar Dashboard

Recarga el dashboard y verifica que:
- ✅ Carga rápido (sin timeouts)
- ✅ Muestra datos reales de proveedores
- ✅ Muestra equipos recientes
- ✅ Gráficas con datos reales

## Ventajas del Sistema de Caché

1. **Dashboard siempre rápido** - Lee de cache, no ejecuta queries pesadas
2. **Datos actualizados cada hora** - Suficiente para estadísticas
3. **Cron tiene más tiempo** - 5 minutos vs 30 segundos de web
4. **Sin bloqueo de usuarios** - Cron ejecuta en background
5. **Fallback seguro** - Si cache falla, muestra array vacío (no crash)

## Monitoreo

### Ver edad del caché:
```php
<?php
require 'config/db_connect.php';
require 'app/helpers/cache_helper.php';

$keys = ['top_suppliers', 'recent_equipments', 'pie_suppliers', 'maintenance_counts'];
foreach ($keys as $key) {
    $age = get_cache_age($conn, $key);
    $fresh = cache_is_fresh($conn, $key, 60) ? '✓' : '✗';
    echo "$fresh $key: $age\n";
}
```

### Forzar actualización manual:
```bash
ssh ... "cd ... && php utilities/update_dashboard_cache.php"
```

## Ajustar Frecuencia

Si necesitas actualizar más seguido:

```bash
# Cada 30 minutos
*/30 * * * * /usr/bin/php /path/update_dashboard_cache.php

# Cada 15 minutos
*/15 * * * * /usr/bin/php /path/update_dashboard_cache.php

# Cada 6 horas (menos carga)
0 */6 * * * /usr/bin/php /path/update_dashboard_cache.php
```

## Troubleshooting

### Cache no actualiza
1. Verificar que cron está configurado: `crontab -l`
2. Verificar permisos: `chmod +x utilities/update_dashboard_cache.php`
3. Ver errores en log: `tail cache_update.log`

### Queries aún dan timeout en cron
1. Aumentar timeout en script: `set_time_limit(600);` (10 min)
2. Contactar Hostinger soporte
3. Usar Solución 2 (sin JOINs)

### Dashboard muestra datos viejos
1. Verificar última actualización: `SELECT * FROM dashboard_cache`
2. Ejecutar manualmente: `php utilities/update_dashboard_cache.php`
3. Ajustar frecuencia de cron
