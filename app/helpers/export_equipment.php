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
    ? (can('export', 'equipments') || can('export', 'equipment') || can('export', 'reports'))
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

// =======================
//  CONSULTA PRINCIPAL
// =======================
$qry = $conn->query("SELECT * FROM equipments");

if(!$qry){
    die("Error en la consulta a la tabla equipments: " . $conn->error);
}

if($qry->num_rows == 0){
    die("No se encontraron registros en la tabla equipments.");
}

// =======================
//  CONFIGURAR DESCARGA EXCEL
// =======================
$filename = "equipos_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

// =======================
//  OBTENER COLUMNAS
// =======================
$fields = array_keys($qry->fetch_assoc());
$qry->data_seek(0); // Volver al inicio de los resultados

// =======================
//  TABLA DE LISTADO CON ANCHO DINÁMICO
// =======================
echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th colspan='" . count($fields) . "' style='background:#007bff;color:white;text-align:center;width:100%;'>Listado de Equipos</th></tr>";

// Encabezados con ancho dinámico
echo "<tr style='background-color:#007bff;color:white;'>";
foreach($fields as $field){
    // Ajustar ancho según tipo de campo
    $width = 120; // default
    if (in_array($field, ['name', 'modelo', 'brand', 'description', 'characteristics'])) $width = 150;
    elseif (in_array($field, ['id', 'number_inventory', 'serie'])) $width = 100;
    elseif (in_array($field, ['amount'])) $width = 80;
    echo "<th style='width:" . $width . "px; min-width:" . $width . "px;'>" . strtoupper(str_replace('_', ' ', $field)) . "</th>";
}
echo "</tr>";

// Filas de datos
while($row = $qry->fetch_assoc()){
    echo "<tr>";
    foreach($fields as $field){
        $valor = $row[$field];

        // Formateos comunes
        if(strpos($field, 'fecha') !== false && !empty($valor)){
            $valor = date('d/m/Y', strtotime($valor));
        }
        if($field == 'valor' && is_numeric($valor)){
            $valor = '$' . number_format($valor, 2);
        }
        if($field == 'imagen' && !empty($valor)){
            $valor = "<a href='uploads/$valor' target='_blank'>$valor</a>";
        }
        if($field == 'supplier_id'){
            $proveedor = $conn->query("SELECT empresa FROM suppliers WHERE id = '{$valor}'");
            $valor = ($proveedor && $proveedor->num_rows > 0) ? $proveedor->fetch_assoc()['empresa'] : 'Sin proveedor';
        }

        echo "<td style='mso-number-format:\"\\@\";'>$valor</td>";
    }
    echo "</tr>";
}
echo "</table>";
ob_end_flush();
exit;
?>
