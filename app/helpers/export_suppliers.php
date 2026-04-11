<?php
if (!defined('ROOT')) define('ROOT', realpath(__DIR__ . '/../..'));
if (!defined('ACCESS')) define('ACCESS', true);

require_once ROOT . '/config/session.php';
require_once ROOT . '/config/config.php';
require_once ROOT . '/app/helpers/permissions.php';

if (!isset($_SESSION['login_id']) || !validate_session()) {
    http_response_code(401);
    die('Sesion expirada');
}

$canExport = function_exists('can')
    ? (can('export', 'suppliers') || can('export', 'provider') || can('export', 'reports'))
    : ((int)($_SESSION['login_type'] ?? 0) === 1);
if (!$canExport && (int)($_SESSION['login_type'] ?? 0) !== 1) {
    http_response_code(403);
    die('Sin permisos para exportar');
}

// Silencia errores en la salida y limpia cualquier buffer previo
if (function_exists('ini_set')) {
    ini_set('display_errors', '0');
}
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Nombre del archivo
$filename = "proveedores_" . date('Y-m-d_His') . ".xls";

// Encabezados HTTP para forzar la descarga
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// Consulta todos los registros
$qry = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");

// Si no hay registros
if ($qry->num_rows == 0) {
    echo "No se encontraron datos.";
    exit;
}

// Obtener nombres de columnas dinámicamente
$fields = array_keys($qry->fetch_assoc());
$qry->data_seek(0); // Reiniciar el puntero después de fetch_assoc()

// Título
echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th colspan='" . count($fields) . "' style='background:#343a40;color:white;text-align:center;width:100%;'>Listado de Proveedores</th></tr>";

// Encabezados con ancho dinámico
echo "<tr style='background-color:#007bff; color:white;'>";
foreach ($fields as $field) {
    // Ajustar ancho según tipo de campo
    $width = 120; // default
    if (in_array($field, ['correo', 'email', 'pagina_web', 'website', 'descripcion'])) $width = 180;
    elseif (in_array($field, ['telefono', 'phone', 'fax'])) $width = 100;
    elseif (in_array($field, ['id'])) $width = 50;
    elseif (in_array($field, ['nombre', 'name', 'empresa', 'company'])) $width = 150;
    echo "<th style='width:" . $width . "px; min-width:" . $width . "px;'>" . strtoupper(str_replace('_', ' ', $field)) . "</th>";
}
echo "</tr>";

// Filas de datos
while ($row = $qry->fetch_assoc()) {
    echo "<tr>";
    foreach ($fields as $field) {
        $valor = $row[$field];

        // Formatear algunos campos específicos
        if ($field == 'estado') {
            $valor = $valor == 1 ? 'Activo' : 'Inactivo';
        } elseif ($field == 'correo' || $field == 'email') {
            $valor = "<a href='mailto:$valor'>$valor</a>";
        } elseif ($field == 'pagina_web' || $field == 'website') {
            $valor = "<a href='$valor' target='_blank'>$valor</a>";
        }

        echo "<td style='mso-number-format:\"\\@\";'>$valor</td>";
    }
    echo "</tr>";
}

echo "</table>";
ob_end_flush();
exit;
?>
