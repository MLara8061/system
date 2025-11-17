<?php
require_once 'config/config.php';
require_once 'lib/phpqrcode/qrlib.php';

$id = (int)$_GET['id'];
$qry = $conn->query("SELECT * FROM equipments WHERE id = $id");
$eq = $qry->fetch_assoc();

// Generar URL dinámica para QR
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$qr_url = $protocol . $host . $script_dir . '/equipment_public.php?id=' . $id;

// Generar QR en base64 (QR más grande dentro de etiqueta pequeña)
ob_start();
QRcode::png($qr_url, null, QR_ECLEVEL_L, 6);
$imageString = base64_encode(ob_get_contents());
ob_end_clean();

// Logo
$logoPath = 'uploads/logo_print.jpg';
$logoExists = file_exists($logoPath);
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
  font-size: 6pt;
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
        <div><b>#<?= htmlspecialchars($eq['number_inventory']) ?></b></div>
        <div><?= htmlspecialchars($eq['name']) ?></div>
        <div>Serie: <?= htmlspecialchars($eq['serie']) ?></div>
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
