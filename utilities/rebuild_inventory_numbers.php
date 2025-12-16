<?php
/**
 * Recalcula y actualiza numbers de inventario para equipos existentes
 * usando el nuevo esquema:
 *   SUC(3) + ADQ(2/3) + CAT(2/3) + "+" + 001
 *
 * Uso:
 *   php utilities/rebuild_inventory_numbers.php                # dry-run
 *   php utilities/rebuild_inventory_numbers.php --apply        # aplica cambios
 *   php utilities/rebuild_inventory_numbers.php --apply --force
 *
 * Flags:
 *   --apply    Ejecuta UPDATEs (por defecto solo simula)
 *   --force    Renumera incluso si ya parece estar en formato nuevo
 *   --limit=N  Limita cantidad de registros procesados (útil para pruebas)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
if (!defined('ROOT')) {
    define('ROOT', $root);
}

try {
    // config/config.php define $conn vía config/db_connect.php
    // En entornos locales sin MySQL, puede lanzar mysqli_sql_exception.
    require_once ROOT . '/config/config.php';
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base de datos desde este entorno.\n");
    fwrite(STDERR, "Detalle: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Sugerencia: ejecuta este script en el servidor/host donde MySQL esté accesible con la misma config.\n");
    exit(2);
}

if (!isset($conn) || !$conn) {
    fwrite(STDERR, "No hay conexión a la base de datos (\$conn no disponible).\n");
    exit(2);
}

$options = [
    'apply' => false,
    'force' => false,
    'limit' => null,
];

foreach ($argv as $arg) {
    if ($arg === '--apply') $options['apply'] = true;
    if ($arg === '--force') $options['force'] = true;
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) $options['limit'] = (int)$m[1];
}

$runMode = $options['apply'] ? 'APPLY' : 'DRY-RUN';
$startedAt = date('Y-m-d_His');
$logPath = ROOT . '/logs/inventory_renumber_' . $startedAt . '.json';

function normalize_code($value, $len = 3) {
    $value = strtoupper(trim((string)$value));
    $value = preg_replace('/[^A-Z0-9]/', '', $value);
    return substr($value, 0, $len);
}

function derive_acq_code($row) {
    // Preferir code si existe; si no, derivar desde name.
    $code = normalize_code($row['code'] ?? '', 3);
    if ($code !== '') return $code;

    $name = strtoupper(trim((string)($row['name'] ?? '')));
    if ($name === '') return '';
    $nameAlnum = preg_replace('/[^A-Z0-9]/', '', $name);
    return substr($nameAlnum, 0, 3);
}

function looks_new_format($number) {
    $s = trim((string)$number);
    if ($s === '') return false;
    // Ej: ABCDEF001
    return (bool)preg_match('/^[A-Z0-9]{6,9}\d{3,}$/', $s);
}

function ensure_inventory_config_schema(mysqli $conn) {
    // Crea/ajusta inventory_config para que exista y tenga el índice único por combinación.
    @$conn->query("CREATE TABLE IF NOT EXISTS `inventory_config` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `branch_id` INT NOT NULL,
        `acquisition_type_id` INT NULL,
        `equipment_category_id` INT NULL,
        `prefix` VARCHAR(64) NOT NULL,
        `current_number` INT NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Agregar columnas solo si no existen
    $chk1 = @$conn->query("SHOW COLUMNS FROM `inventory_config` LIKE 'acquisition_type_id'");
    if (!$chk1 || $chk1->num_rows === 0) {
        @$conn->query("ALTER TABLE `inventory_config` ADD COLUMN `acquisition_type_id` INT NULL AFTER `branch_id`");
    }
    
    $chk2 = @$conn->query("SHOW COLUMNS FROM `inventory_config` LIKE 'equipment_category_id'");
    if (!$chk2 || $chk2->num_rows === 0) {
        @$conn->query("ALTER TABLE `inventory_config` ADD COLUMN `equipment_category_id` INT NULL AFTER `acquisition_type_id`");
    }
    
    // Agregar índice único solo si no existe
    $chk_idx = @$conn->query("SHOW INDEX FROM `inventory_config` WHERE Key_name = 'uniq_inventory_cfg'");
    if (!$chk_idx || $chk_idx->num_rows === 0) {
        @$conn->query("ALTER TABLE `inventory_config` ADD UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`)");
    }
}

function ensure_equipments_schema(mysqli $conn) {
    // Agregar columnas necesarias a equipments si no existen
    $chk = @$conn->query("SHOW COLUMNS FROM `equipments` LIKE 'equipment_category_id'");
    if (!$chk || $chk->num_rows === 0) {
        echo "Agregando columna equipment_category_id a equipments...\n";
        @$conn->query("ALTER TABLE `equipments` ADD COLUMN `equipment_category_id` INT NULL AFTER `discipline`");
    }
    
    $chk2 = @$conn->query("SHOW COLUMNS FROM `equipments` LIKE 'inventario_anterior'");
    if (!$chk2 || $chk2->num_rows === 0) {
        echo "Agregando columna inventario_anterior a equipments...\n";
        @$conn->query("ALTER TABLE `equipments` ADD COLUMN `inventario_anterior` VARCHAR(128) NULL AFTER `number_inventory`");
    }
}

// Asegurar esquema de equipments
ensure_equipments_schema($conn);

// Pre-cargar mapas de nomenclaturas
$branches = [];
$br = @$conn->query("SELECT id, code, name FROM branches");
if ($br) {
    while ($r = $br->fetch_assoc()) {
        $branches[(int)$r['id']] = [
            'code' => normalize_code($r['code'] ?? '', 3) ?: normalize_code($r['name'] ?? '', 3),
        ];
    }
}

$acqTypes = [];
$hasAcqCode = false;
$chk = @$conn->query("SHOW COLUMNS FROM acquisition_type LIKE 'code'");
if ($chk && $chk->num_rows > 0) $hasAcqCode = true;
$acqSql = $hasAcqCode ? "SELECT id, name, code FROM acquisition_type" : "SELECT id, name FROM acquisition_type";
$aq = @$conn->query($acqSql);
if ($aq) {
    while ($r = $aq->fetch_assoc()) {
        $acqTypes[(int)$r['id']] = [
            'code' => derive_acq_code($r),
        ];
    }
}

$categories = [];
$cat = @$conn->query("SELECT id, clave FROM equipment_categories");
if ($cat) {
    while ($r = $cat->fetch_assoc()) {
        $categories[(int)$r['id']] = [
            'code' => normalize_code($r['clave'] ?? '', 3),
        ];
    }
}

// Cargar equipos
$limitSql = $options['limit'] ? (' LIMIT ' . (int)$options['limit']) : '';
$eqSql = "SELECT id, number_inventory, inventario_anterior, branch_id, acquisition_type, equipment_category_id, date_created
          FROM equipments
          ORDER BY COALESCE(date_created, '1970-01-01') ASC, id ASC" . $limitSql;

$eqRes = @$conn->query($eqSql);
if (!$eqRes) {
    fwrite(STDERR, "Query falló: " . $conn->error . "\n");
    exit(2);
}

$rows = [];
while ($r = $eqRes->fetch_assoc()) {
    $rows[] = $r;
}

// Preparar asignación por combinación
$groups = []; // key => [rows]
$skipped = [];

foreach ($rows as $r) {
    $id = (int)$r['id'];
    $branchId = (int)($r['branch_id'] ?? 0);
    $acqId = (int)($r['acquisition_type'] ?? 0);
    $catId = (int)($r['equipment_category_id'] ?? 0);

    if ($branchId <= 0 || $acqId <= 0 || $catId <= 0) {
        $skipped[] = ['id' => $id, 'reason' => 'Faltan datos (branch/acq/cat)'];
        continue;
    }

    $branchCode = $branches[$branchId]['code'] ?? '';
    $acqCode = $acqTypes[$acqId]['code'] ?? '';
    $catCode = $categories[$catId]['code'] ?? '';

    if ($branchCode === '' || $acqCode === '' || $catCode === '') {
        $skipped[] = ['id' => $id, 'reason' => 'Faltan nomenclaturas (code/clave)'];
        continue;
    }

    $prefix = $branchCode . $acqCode . $catCode;
    $key = $branchId . '|' . $acqId . '|' . $catId . '|' . $prefix;

    $groups[$key][] = $r;
}

$changes = [];
$stats = [
    'mode' => $runMode,
    'total_loaded' => count($rows),
    'groups' => count($groups),
    'skipped' => count($skipped),
    'to_update' => 0,
    'already_ok' => 0,
    'updated' => 0,
    'inventory_config_updates' => 0,
];

// Construir cambios deterministas: secuencia desde 001 por grupo
foreach ($groups as $key => $list) {
    $parts = explode('|', $key);
    $branchId = (int)$parts[0];
    $acqId = (int)$parts[1];
    $catId = (int)$parts[2];
    $prefix = (string)$parts[3];

    $seq = 1;
    foreach ($list as $r) {
        $id = (int)$r['id'];
        $current = (string)($r['number_inventory'] ?? '');

        $newNumber = $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
        $seq++;

        $isOk = looks_new_format($current) && str_starts_with($current, $prefix);
        if ($isOk && !$options['force']) {
            $stats['already_ok']++;
            continue;
        }

        if ($current === $newNumber) {
            $stats['already_ok']++;
            continue;
        }

        $changes[] = [
            'id' => $id,
            'branch_id' => $branchId,
            'acquisition_type' => $acqId,
            'equipment_category_id' => $catId,
            'prefix' => $prefix,
            'old_number_inventory' => $current,
            'old_inventario_anterior' => (string)($r['inventario_anterior'] ?? ''),
            'new_number_inventory' => $newNumber,
        ];
    }
}

$stats['to_update'] = count($changes);

// Aplicar cambios si corresponde
if ($options['apply']) {
    ensure_inventory_config_schema($conn);

    // Transacción
    @$conn->query('START TRANSACTION');

    $upd = $conn->prepare('UPDATE equipments SET number_inventory = ?, inventario_anterior = CASE WHEN (inventario_anterior IS NULL OR inventario_anterior = \'\') THEN ? ELSE inventario_anterior END WHERE id = ?');
    if (!$upd) {
        @$conn->query('ROLLBACK');
        fwrite(STDERR, "No se pudo preparar UPDATE equipments: {$conn->error}\n");
        exit(2);
    }

    // Para recalibrar inventory_config
    $maxSeqByCombo = []; // key: b|a|c|prefix => max

    foreach ($changes as $ch) {
        $id = (int)$ch['id'];
        $new = (string)$ch['new_number_inventory'];
        $old = (string)$ch['old_number_inventory'];

        // Guardar anterior solo si está vacío
        $prev = $old;

        $upd->bind_param('ssi', $new, $prev, $id);
        $ok = $upd->execute();
        if (!$ok) {
            @$conn->query('ROLLBACK');
            fwrite(STDERR, "Fallo al actualizar id={$id}: {$upd->error}\n");
            exit(2);
        }
        $stats['updated']++;

        $comboKey = $ch['branch_id'] . '|' . $ch['acquisition_type'] . '|' . $ch['equipment_category_id'] . '|' . $ch['prefix'];
        if (!isset($maxSeqByCombo[$comboKey])) $maxSeqByCombo[$comboKey] = 0;

        // extraer seq (últimos 3 dígitos del new)
        if (preg_match('/\+(\d+)$/', $new, $m)) {
            $n = (int)$m[1];
            if ($n > $maxSeqByCombo[$comboKey]) $maxSeqByCombo[$comboKey] = $n;
        }
    }

    // Ajustar inventory_config para que el siguiente número no choque
    foreach ($maxSeqByCombo as $comboKey => $max) {
        $p = explode('|', $comboKey);
        $b = (int)$p[0];
        $a = (int)$p[1];
        $c = (int)$p[2];
        $prefix = (string)$p[3];

        $prefixEsc = $conn->real_escape_string($prefix);
        $sql = "INSERT INTO inventory_config (branch_id, acquisition_type_id, equipment_category_id, prefix, current_number)
                VALUES ({$b}, {$a}, {$c}, '{$prefixEsc}', {$max})
                ON DUPLICATE KEY UPDATE prefix = VALUES(prefix), current_number = GREATEST(current_number, VALUES(current_number))";

        $ok = @$conn->query($sql);
        if (!$ok) {
            @$conn->query('ROLLBACK');
            fwrite(STDERR, "Fallo inventory_config ({$comboKey}): {$conn->error}\n");
            exit(2);
        }
        $stats['inventory_config_updates']++;
    }

    @$conn->query('COMMIT');
}

$payload = [
    'stats' => $stats,
    'skipped' => $skipped,
    'changes_sample' => array_slice($changes, 0, 50),
];

@file_put_contents($logPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Salida consola
fwrite(STDOUT, "[{$runMode}] Equipos cargados: {$stats['total_loaded']}\n");
fwrite(STDOUT, "[{$runMode}] Grupos (branch+acq+cat): {$stats['groups']}\n");
fwrite(STDOUT, "[{$runMode}] Omitidos: {$stats['skipped']}\n");
fwrite(STDOUT, "[{$runMode}] Para actualizar: {$stats['to_update']}\n");
if ($options['apply']) {
    fwrite(STDOUT, "[APPLY] Actualizados: {$stats['updated']}\n");
    fwrite(STDOUT, "[APPLY] inventory_config ajustados: {$stats['inventory_config_updates']}\n");
}
fwrite(STDOUT, "Log: {$logPath}\n");

exit(0);
