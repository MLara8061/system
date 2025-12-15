# Estado del Dashboard - 15 Diciembre 2025

## ✅ PROBLEMA RESUELTO
El dashboard home ahora **carga correctamente** en producción.

## 🔧 Cambios Realizados

### 1. Refactorización de home.php
- **Problema original:** home.php era un documento HTML completo con `<!doctype>`, `<html>`, `<head>`, `<body>` siendo incluido dentro de index.php que ya tenía esa estructura
- **Solución:** Convertido a vista parcial que solo genera el contenido interno
- **Líneas:** Reducido de 956 a ~488 líneas

### 2. Corrección de Sintaxis
- **Error:** `endwhile` usado con `foreach`
- **Solución:** Cambiado a `endforeach` (líneas 323 y 364)

### 3. Inicialización de ApexCharts
- **Problema:** Scripts ejecutándose antes de que DOM esté listo
- **Solución:** Envuelto en `DOMContentLoaded` event listener

### 4. Función safe_query()
```php
function safe_query($conn, $sql) {
    if ($conn->real_query($sql)) {
        return $conn->store_result();
    }
    return false;
}
```
Usa `real_query()` + `store_result()` en lugar de `query()` directo que se colgaba.

## ⚠️ WORKAROUNDS TEMPORALES

### Queries Comentadas (causan timeout en producción)
Las siguientes queries están temporalmente deshabilitadas con datos dummy:

1. **accessories (EPP)**
   - `SELECT COUNT(*) FROM accessories`
   - `SELECT SUM(cost) FROM accessories`
   - **Valor actual:** 0

2. **tools (Herramientas)**
   - `SELECT COUNT(*) FROM tools`
   - `SELECT SUM(costo) FROM tools`
   - **Valor actual:** 0

3. **maintenance_reports**
   - Gráfica de tipos de servicio
   - Gráfica mensual de ejecución
   - **Valores dummy:** MP=5, MC=3 por mes

4. **branches**
   - Query principal comentada
   - Datos estáticos: `[['id' => 1, 'name' => 'Sede Principal']]`

5. **Equipos recientes y Top proveedores**
   - Tablas vacías (sin queries por timeout)

### Queries que SÍ Funcionan
✅ `equipments` - Total equipos: 252
✅ `equipments` - Valor total: $4,033,562 MXN

## 📊 Estado de Gráficas
Todas las gráficas se renderizan correctamente pero con datos de ejemplo:
- **Sales Chart:** Línea plana (valores dummy: 8 equipos/$15,000 cada mes)
- **Pie Chart:** Proveedores dummy (Proveedor A, B, Sin Proveedor)
- **Service Type Chart:** MP=10, MC=5 (dummy)
- **Execution Monthly Chart:** MP=5, MC=3 cada mes (dummy)

## 🐛 PROBLEMA RAÍZ: Timeout en Queries MySQL

**Causa identificada:** Queries a ciertas tablas (accessories, tools, maintenance_reports, branches) se cuelgan indefinidamente en producción.

### Diagnóstico Realizado
1. ✅ Tabla `branches` existe y tiene 1 fila
2. ✅ `CHECK TABLE branches` → OK
3. ✅ `REPAIR TABLE branches` → OK
4. ✅ No hay locks activos (`SHOW PROCESSLIST`)
5. ✅ No hay índices corruptos (`ANALYZE TABLE`)
6. ❌ Queries simples como `SELECT id, name FROM branches LIMIT 1` se cuelgan

### Comportamiento Extraño
- `$conn->query()` → timeout infinito
- `real_query()` + `store_result()` → **también timeout**
- `mysqli_query()` funcional → **también timeout**
- Queries a `equipments` → **funcionan perfectamente**

## 🔍 PRÓXIMOS PASOS

### Opción A: Investigar Configuración del Servidor
1. Revisar `my.cnf` / configuración MySQL en Hostinger
2. Verificar `max_execution_time`, `connect_timeout`, `net_read_timeout`
3. Revisar logs de MySQL: `/var/log/mysql/error.log`
4. Verificar si hay queries bloqueadas a nivel de servidor

### Opción B: Usar PDO en lugar de mysqli
```php
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
```

### Opción C: Optimizar Queries Problemáticas
- Simplificar queries con múltiples JOINs
- Agregar índices específicos
- Usar LIMIT más agresivos

### Opción D: Mantener Workaround con Datos Reales Limitados
- Ejecutar queries problemáticas en background job (cron)
- Cachear resultados en archivo/Redis
- Actualizar cada 5-10 minutos

## 📝 Archivos Modificados
- `app/views/dashboard/home.php` - Refactorizado completo
- `index.php` - Rutas de mantenimiento actualizadas
- `app/views/auth/login.php` - Rutas de mantenimiento actualizadas
- `.htaccess` - Rutas de mantenimiento actualizadas
- `config/maintenance_config.php` - Movido desde raíz
- `components/maintenance.php` - Movido desde raíz
- `utilities/maintenance_allow_ip.php` - Movido desde raíz + path actualizado

## 🎯 Estado Actual del Sitio
- **URL:** https://indigo-porcupine-764368.hostingersite.com
- **Estado:** ✅ FUNCIONANDO
- **Modo mantenimiento:** ❌ DESACTIVADO
- **Dashboard home:** ✅ CARGA CORRECTAMENTE
- **Datos:** ⚠️ PARCIALES (equipos OK, resto dummy)

## 💡 Recomendación
Para restaurar funcionalidad completa:
1. **Urgente:** Contactar soporte Hostinger sobre timeout en queries MySQL
2. **Temporal:** Usar workaround actual hasta resolver problema de servidor
3. **Largo plazo:** Considerar migración a PDO + implementar caché de datos
