<?php
define('ACCESS', true);
require_once 'config/config.php';

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
//  TABLA DE LISTADO
// =======================
echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th colspan='" . count($fields) . "' style='background:#007bff;color:white;text-align:center;'>Listado de Equipos</th></tr>";

// Encabezados
echo "<tr style='background-color:#007bff;color:white;'>";
foreach($fields as $field){
    echo "<th>" . strtoupper(str_replace('_', ' ', $field)) . "</th>";
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

exit;
?>
