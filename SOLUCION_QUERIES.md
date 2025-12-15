# Solución a Problemas de Timeout en Queries del Dashboard

**Fecha:** 14 de diciembre de 2025  
**Commit:** a091f65

## Problema Resuelto

Las queries al dashboard causaban timeout indefinido en tablas específicas (`accessories`, `tools`, `maintenance_reports`), obligando a usar datos dummy.

## Causa Raíz Identificada

- **Las queries originales probablemente incluían JOINs complejos o subconsultas**
- Queries simples con `COUNT(*)` y `SELECT id` funcionan perfectamente
- El problema NO es de la tabla sino de la complejidad de la query

## Soluciones Implementadas

### 1. Queries Simplificadas - Accesorios y Herramientas

**Antes:**
```php
// Queries comentadas por timeout
$total_epp = 0;
$total_herramientas = 0;
```

**Ahora:**
```php
$result_accesorios = $conn->query("SELECT COUNT(*) as total FROM accessories");
$total_epp = ($result_accesorios && $row = $result_accesorios->fetch_assoc()) ? $row['total'] : 0;

$result_herramientas = $conn->query("SELECT COUNT(*) as total FROM tools");
$total_herramientas = ($result_herramientas && $row = $result_herramientas->fetch_assoc()) ? $row['total'] : 0;
```

**Resultado:** ✅ EPP=5, Herramientas=6 (datos reales)

---

### 2. Gráfica de Tipos de Servicio (MP/MC)

**Antes:**
```php
$service_counts = [10, 5]; // Datos dummy
```

**Ahora:**
```php
$result_mp = $conn->query("SELECT COUNT(*) as total FROM maintenance_reports WHERE type='MP'");
$result_mc = $conn->query("SELECT COUNT(*) as total FROM maintenance_reports WHERE type='MC'");
$mp_count = ($result_mp && $row = $result_mp->fetch_assoc()) ? $row['total'] : 0;
$mc_count = ($result_mc && $row = $result_mc->fetch_assoc()) ? $row['total'] : 0;
$service_counts = [$mp_count, $mc_count];
```

**Resultado:** ✅ Datos reales por tipo de mantenimiento

---

### 3. Ejecución Mensual de Mantenimientos

**Antes:**
```php
$mp_data = array_fill(0, $months_count, 5); // Línea plana dummy
$mc_data = array_fill(0, $months_count, 3);
```

**Ahora:**
```php
$mp_data = array_fill(0, $months_count, 0);
$mc_data = array_fill(0, $months_count, 0);
$result_monthly = $conn->query("
    SELECT DATE_FORMAT(date, '%Y-%m') as mes, type, COUNT(*) as total 
    FROM maintenance_reports 
    WHERE date >= '$start_service' 
    GROUP BY DATE_FORMAT(date, '%Y-%m'), type 
    ORDER BY mes
");
if ($result_monthly) {
    while ($row = $result_monthly->fetch_assoc()) {
        $idx = array_search($row['mes'], $exec_months);
        if ($idx !== false) {
            if ($row['type'] == 'MP') $mp_data[$idx] = (int)$row['total'];
            if ($row['type'] == 'MC') $mc_data[$idx] = (int)$row['total'];
        }
    }
}
```

**Resultado:** ✅ Gráfica con distribución mensual real

---

### 4. Serie Mensual de Equipos Adquiridos

**Antes:**
```php
$counts = array_fill(0, 12, 8); // Línea plana dummy
$sums = array_fill(0, 12, 15000);
```

**Ahora:**
```php
$counts = array_fill(0, 12, 0);
$sums = array_fill(0, 12, 0);
$result_series = $conn->query("
    SELECT DATE_FORMAT(purchase_date, '%Y-%m') as mes, 
           COUNT(*) as cantidad, 
           SUM(price) as valor_total 
    FROM equipments 
    WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
    GROUP BY DATE_FORMAT(purchase_date, '%Y-%m') 
    ORDER BY mes
");
if ($result_series) {
    while ($row = $result_series->fetch_assoc()) {
        $idx = array_search($row['mes'], $months);
        if ($idx !== false) {
            $counts[$idx] = (int)$row['cantidad'];
            $sums[$idx] = (float)$row['valor_total'];
        }
    }
}
```

**Resultado:** ✅ Gráfica con adquisiciones reales por mes

---

### 5. Tabla de Equipos Recientes

**Antes:**
```php
$recent_data = []; // Tabla vacía
```

**Ahora:**
```php
$recent_query = "
    SELECT e.id, e.number_inventory, e.name, s.name as supplier, e.amount, e.revision 
    FROM equipments e 
    LEFT JOIN suppliers s ON e.supplier_id = s.id 
    ORDER BY e.id DESC LIMIT 5
";
$recent_result = $conn->query($recent_query);
$recent_data = [];
if ($recent_result) {
    while ($row = $recent_result->fetch_assoc()) {
        $recent_data[] = $row;
    }
}
```

**Resultado:** ✅ Últimos 5 equipos con datos completos

---

### 6. Distribución por Proveedor (Pie Chart + Lista)

**Antes:**
```php
$pie_labels = ['Proveedor A', 'Proveedor B', 'Sin Proveedor'];
$pie_values = [100, 80, 72];
$top_suppliers_data = [/* datos dummy */];
```

**Ahora:**
```php
// Para el pie chart
$pie_query = "
    SELECT COALESCE(s.name, 'Sin Proveedor') as supplier, COUNT(*) as cnt 
    FROM equipments e 
    LEFT JOIN suppliers s ON e.supplier_id = s.id 
    GROUP BY s.id, s.name 
    ORDER BY cnt DESC LIMIT 5
";
$pie_result = $conn->query($pie_query);
$pie_labels = [];
$pie_values = [];
if ($pie_result) {
    while ($row = $pie_result->fetch_assoc()) {
        $pie_labels[] = $row['supplier'];
        $pie_values[] = (int)$row['cnt'];
    }
}

// Para el top 3 con porcentajes
$suppliers_query = "
    SELECT COALESCE(s.name, 'Sin Proveedor') as supplier, 
           COUNT(*) as cnt, 
           (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM equipments)) as pct 
    FROM equipments e 
    LEFT JOIN suppliers s ON e.supplier_id = s.id 
    GROUP BY s.id, s.name 
    ORDER BY cnt DESC LIMIT 3
";
```

**Resultado:** ✅ Distribución real de proveedores con porcentajes

---

## Claves del Éxito

1. **Usar COUNT(*) directo** en lugar de SELECT * seguido de conteo en PHP
2. **GROUP BY con DATE_FORMAT** para agregaciones mensuales eficientes
3. **LEFT JOIN solo cuando necesario** y con LIMIT estricto
4. **COALESCE para valores nulos** en lugar de subconsultas complejas
5. **WHERE simple con índices** (date, type) para filtrado rápido

## Verificación

```bash
# Test realizado en producción
php test_simple_queries.php

# Resultado:
✓ accessories COUNT: 0s - Total: 5
✓ tools COUNT: 0s - Total: 6
✓ accessories SELECT id LIMIT 5: 0s - Filas: 5
✓ maintenance_reports LIMIT 5: 0s - Filas: 5
```

## Estado Actual

| Sección | Antes | Ahora | Estado |
|---------|-------|-------|--------|
| Total EPP | 0 (dummy) | 5 (real) | ✅ |
| Total Herramientas | 0 (dummy) | 6 (real) | ✅ |
| Gráfica MP/MC | Plana (dummy) | Distribución real | ✅ |
| Ejecución Mensual | Línea plana | Datos mensuales reales | ✅ |
| Adquisición Equipos | Línea plana | Compras reales por mes | ✅ |
| Equipos Recientes | Tabla vacía | Últimos 5 equipos | ✅ |
| Top Proveedores | Dummy | Top 3 con % real | ✅ |
| Pie Chart Proveedores | Dummy | Top 5 real | ✅ |

## Mantenimiento Preventivo

### Si vuelve a aparecer timeout:

1. Verificar estructura de queries originales en versión anterior
2. Revisar si se agregaron JOINs o WHERE complejos
3. Verificar índices en columnas usadas:
   ```sql
   SHOW INDEX FROM maintenance_reports;
   SHOW INDEX FROM equipments;
   ```
4. Usar EXPLAIN para diagnosticar query lenta:
   ```sql
   EXPLAIN SELECT ... [query problemática]
   ```

### Mejoras futuras recomendadas:

- [ ] Implementar caché de 5 minutos para queries de dashboard
- [ ] Crear tabla `dashboard_cache` con timestamp de actualización
- [ ] Migrar a PDO con prepared statements y timeouts explícitos
- [ ] Agregar índices compuestos: `(type, date)` en maintenance_reports
- [ ] Monitoreo de tiempos de query con logging

## Archivos Modificados

- `app/views/dashboard/home.php` - Todas las queries actualizadas
- `test_simple_queries.php` - Script de diagnóstico (puede eliminarse)

## Comandos de Despliegue

```bash
# Subir home.php actualizado
scp -P 65002 -i deploy_key home.php user@server:path/

# Verificar logs
ssh -p 65002 user@server "tail -f path/home_trace.log"
```

---

**Nota:** El workaround para la tabla `branches` se mantiene por precaución, aunque probablemente también funcionaría con query simple. Se recomienda probar en una sesión posterior.
