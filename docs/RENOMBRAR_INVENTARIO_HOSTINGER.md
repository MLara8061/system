# Renumeración masiva de inventario (Hostinger)

Este proyecto incluye el script CLI [utilities/rebuild_inventory_numbers.php](../utilities/rebuild_inventory_numbers.php) para recalcular y (opcionalmente) actualizar `equipments.number_inventory` usando el esquema nuevo:

`SUC + ADQ + CAT + "+" + NNN` (por ejemplo `ABCDEF+001`).

> El script es **dry-run por defecto** (no escribe). Solo modifica datos si se ejecuta con `--apply`.

## 1) Requisitos

- Acceso a **SSH/Terminal** en Hostinger (o un **Cron Job**).
- Variables de entorno de BD de producción configuradas en `config/.env`:
  - `DB_HOST_PROD`, `DB_USER_PROD`, `DB_PASS_PROD`, `DB_NAME_PROD`

### Nota importante sobre CLI en Hostinger

Se ajustó la detección de entorno para que, al ejecutar por CLI (SSH/Cron), si existen variables `*_PROD` se use **production** automáticamente.

Si quieres forzarlo explícitamente:

- `APP_ENV=production php utilities/rebuild_inventory_numbers.php`

## 2) Backup recomendado (antes de aplicar)

Opción A (rápida vía SQL):

- En phpMyAdmin (o consola MySQL), ejecutar:
  - `CREATE TABLE equipments_backup_20251215 AS SELECT * FROM equipments;`
  - `CREATE TABLE inventory_config_backup_20251215 AS SELECT * FROM inventory_config;` (si existe)

Opción B: exportar tablas desde phpMyAdmin.

## 3) Ejecutar en modo simulación (dry-run)

Desde la raíz del proyecto (carpeta donde existe `utilities/`):

- `php utilities/rebuild_inventory_numbers.php --limit=20`

El script genera un log JSON en:

- `logs/inventory_renumber_YYYY-mm-dd_HHMMSS.json`

Revisa especialmente:

- `skipped` (equipos sin sucursal/adquisición/categoría o sin códigos)
- `to_update` (cantidad que cambiaría)

## 4) Aplicar cambios

Cuando el dry-run se vea correcto:

- `php utilities/rebuild_inventory_numbers.php --apply`

Si necesitas renumerar aunque ya parezcan estar en formato nuevo:

- `php utilities/rebuild_inventory_numbers.php --apply --force`

## 5) Sugerencias operativas

- Ejecuta primero con `--limit=50` para validar.
- Corre en horario de baja carga.
- Si la app está en uso, considera poner mantenimiento para evitar que se creen nuevos equipos durante la renumeración.
