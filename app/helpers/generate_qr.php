<?php
$root = dirname(__DIR__, 2);
require_once $root . '/config/session.php';
if (!validate_session()) {
    http_response_code(401);
    exit;
}

require_once $root . '/lib/phpqrcode/qrlib.php';

// Aseguramos que recibimos el parámetro "id"
if (!isset($_GET['id'])) {
    die("Falta el parámetro 'id'");
}

$id = intval($_GET['id']);
if ($id <= 0) {
    die("ID inválido");
}

// Ruta donde se guardarán los códigos QR
$dir = $root . '/uploads/qrcodes/';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// URL que se codificará dentro del QR - usar URL base de configuración
require_once $root . '/config/config.php';

function normalize_code_qr($value, $len = 3) {
    $value = strtoupper(trim((string)$value));
    $value = preg_replace('/[^A-Z0-9]/', '', $value);
    return substr($value, 0, $len);
}

function derive_acq_code_qr($name) {
    $name_u = strtoupper(trim((string)$name));
    if ($name_u === '') return '';
    if (strpos($name_u, 'COM') !== false) return 'COM';
    if (strpos($name_u, 'PRO') !== false) return 'PRO';
    return normalize_code_qr($name_u, 3);
}

function inventory_sequence_qr($number_inventory) {
    $s = trim((string)$number_inventory);
    if ($s === '') return '';

    // Soporta formatos:
    // - LEGACY: PREFIX-001
    // - NUEVO:   PREFIX+001
    // - Fallback: extraer últimos dígitos
    $parts = preg_split('/[\-\+]/', $s);
    $last = trim((string)end($parts));
    if ($last !== '' && preg_match('/^[0-9]+$/', $last)) {
        return str_pad($last, 4, '0', STR_PAD_LEFT);
    }

    if (preg_match('/(\d+)\s*$/', $s, $m)) {
        return str_pad($m[1], 4, '0', STR_PAD_LEFT);
    }

    return '';
}

$extra_qs = [];
try {
    // Reusar conexión mysqli de config/config.php
    if (isset($conn) && $conn) {
        // Asegurar tabla de categorías (si no existe, crearla para no fallar el JOIN)
        @$conn->query("CREATE TABLE IF NOT EXISTS `equipment_categories` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `clave` VARCHAR(3) NOT NULL,
            `description` VARCHAR(255) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_equipment_categories_clave` (`clave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $sql = "
        SELECT
            e.number_inventory,
            e.serie,
            b.code AS branch_code,
            b.name AS branch_name,
            at.name AS acquisition_name,
            ec.clave AS category_code,
            l.name AS location_name,
            d.name AS department_name
        FROM equipments e
        LEFT JOIN branches b ON b.id = e.branch_id
        LEFT JOIN acquisition_type at ON at.id = e.acquisition_type
        LEFT JOIN equipment_categories ec ON ec.id = e.equipment_category_id
        LEFT JOIN equipment_delivery ed ON ed.equipment_id = e.id
        LEFT JOIN locations l ON l.id = ed.location_id
        LEFT JOIN departments d ON d.id = ed.department_id
        WHERE e.id = {$id}
        LIMIT 1";

        $qry = @$conn->query($sql);
        if ($qry && $qry->num_rows > 0) {
            $row = $qry->fetch_assoc();
            $suc = normalize_code_qr($row['branch_code'] ?? '', 3);
            if ($suc === '') $suc = normalize_code_qr($row['branch_name'] ?? '', 3);
            $prop = derive_acq_code_qr($row['acquisition_name'] ?? '');
            $cat = normalize_code_qr($row['category_code'] ?? '', 3);
            $ubi = trim((string)($row['location_name'] ?? ''));
            if ($ubi === '') $ubi = trim((string)($row['department_name'] ?? ''));
            $con = inventory_sequence_qr($row['number_inventory'] ?? '');
            $ser = trim((string)($row['serie'] ?? ''));

            if ($suc !== '') $extra_qs['suc'] = $suc;
            if ($prop !== '') $extra_qs['prop'] = $prop;
            if ($cat !== '') $extra_qs['cat'] = $cat;
            if ($ubi !== '') $extra_qs['ubi'] = $ubi;
            if ($con !== '') $extra_qs['con'] = $con;
            if ($ser !== '') $extra_qs['ser'] = $ser;
        }
    }
} catch (Exception $e) {
    // No-op: si falla, se mantiene QR con URL simple
}

// Usar siempre BASE_URL que ya detecta automáticamente el dominio correcto
$url = BASE_URL . '/legacy/equipment_public.php?id=' . $id;
if (!empty($extra_qs)) {
    $url .= '&' . http_build_query($extra_qs);
}

// Nombre del archivo QR
$filename = $dir . 'equipment_' . $id . '.png';

// SIEMPRE REGENERAR para asegurar que usa la URL correcta
// Comentar estas líneas después de confirmar que funciona
if (file_exists($filename)) {
    unlink($filename);
}

// Generamos el QR
QRcode::png($url, $filename, QR_ECLEVEL_L, 5);

// Mostramos la imagen directamente en el navegador con headers anti-cache
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($filename);
exit;
?>
