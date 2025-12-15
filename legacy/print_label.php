<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';


if (!isset($_GET['id'])) {
  die('Falta el parámetro id');
}

$id = (int)$_GET['id'];
if ($id <= 0) {
  die('ID inválido');
}

// Asegurar tabla de categorías para evitar errores en instalaciones nuevas
@$conn->query("CREATE TABLE IF NOT EXISTS `equipment_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(3) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_equipment_categories_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function normalize_code($value, $len = 3) {
  $value = strtoupper(trim((string)$value));
  $value = preg_replace('/[^A-Z0-9]/', '', $value);
  return substr($value, 0, $len);
}

function derive_acq_code_from_name($name) {
  $name_u = strtoupper(trim((string)$name));
  if ($name_u === '') return '';
  if (strpos($name_u, 'COM') !== false) return 'COM';
  if (strpos($name_u, 'PRO') !== false) return 'PRO';
  return normalize_code($name_u, 3);
}

function inventory_sequence_from_number($number_inventory) {
  $s = trim((string)$number_inventory);
  if ($s === '') return '';
  $parts = explode('-', $s);
  $last = trim((string)end($parts));
  if ($last === '' || !preg_match('/^[0-9]+$/', $last)) return '';
  return str_pad($last, 4, '0', STR_PAD_LEFT);
}

$sql = "
SELECT
  e.id,
  e.number_inventory,
  e.serie,
  e.branch_id,
  e.acquisition_type,
  e.equipment_category_id,
  b.code AS branch_code,
  b.name AS branch_name,
  at.name AS acquisition_name,
  ec.clave AS category_code,
  ec.description AS category_desc,
  ed.location_id,
  ed.department_id,
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

$qry = $conn->query($sql);
if (!$qry || $qry->num_rows === 0) {
  die('Equipo no encontrado');
}
$eq = $qry->fetch_assoc();

// Generar URL usando BASE_URL de configuración
$qr_url = BASE_URL . '/equipment_public.php?id=' . $id;

// Generar QR en base64 (QR más grande dentro de etiqueta pequeña)
ob_start();
QRcode::png($qr_url, null, QR_ECLEVEL_L, 6);
$imageString = base64_encode(ob_get_contents());
ob_end_clean();

// Logo
$logoPath = 'uploads/logo_print.jpg';
$logoExists = file_exists(__DIR__ . '/../' . $logoPath);

$branch_code = normalize_code($eq['branch_code'] ?? '', 3);
if ($branch_code === '') {
  $branch_code = normalize_code($eq['branch_name'] ?? '', 3);
}

$prop_code = derive_acq_code_from_name($eq['acquisition_name'] ?? '');

$cat_code = normalize_code($eq['category_code'] ?? '', 3);
if ($cat_code !== '' && strlen($cat_code) > 3) $cat_code = substr($cat_code, 0, 3);

$location = trim((string)($eq['location_name'] ?? ''));
if ($location === '') {
  $location = trim((string)($eq['department_name'] ?? ''));
}

$sequence = inventory_sequence_from_number($eq['number_inventory'] ?? '');
$serie = trim((string)($eq['serie'] ?? ''));
?>

<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial, sans-serif; margin:0; }
.label {
  width: 50mm;   /* ancho reducido */
  height: 25mm;  /* alto reducido */
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid #000;
  padding: 2mm;
  box-sizing: border-box;
}
.qr { width: 18mm; height: 18mm; } /* QR más grande dentro de etiqueta pequeña */
.info {
  display: flex;
  flex-direction: column;
  justify-content: center;
  font-size: 5pt;
  margin-left: 2mm;
}
.info div { margin-bottom: 1pt; }
.logo {
  width: 8mm;
  height: 8mm;
  object-fit: contain;
}
</style>
</head>
<body>
<div class="label">
    <!-- QR -->
    <img class="qr" src="data:image/png;base64,<?= $imageString ?>">

    <!-- Información del equipo -->
    <div class="info">
      <div><b>#<?= htmlspecialchars($eq['number_inventory'] ?? '') ?></b></div>
      <div>SUC: <?= htmlspecialchars($branch_code) ?></div>
      <div>PROP: <?= htmlspecialchars($prop_code) ?></div>
      <div>CAT: <?= htmlspecialchars($cat_code) ?></div>
      <div>UBI: <?= htmlspecialchars($location) ?></div>
      <div>CON: <?= htmlspecialchars($sequence) ?></div>
      <div>SER: <?= htmlspecialchars($serie) ?></div>
    </div>

    <!-- Logo -->
    <?php if($logoExists): ?>
        <img class="logo" src="<?= $logoPath ?>" alt="Logo">
    <?php endif; ?>
</div>

<script>
window.onload = function() {
    window.print();
}
</script>
</body>
</html>
