<?php
define('ACCESS', true);
require_once 'config/config.php';

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
echo "<table border='1'>";
echo "<tr><th colspan='" . count($fields) . "' style='background:#343a40;color:white;text-align:center;'>Listado de Proveedores</th></tr>";

// Encabezados
echo "<tr style='background-color:#007bff; color:white;'>";
foreach ($fields as $field) {
    echo "<th>" . strtoupper(str_replace('_', ' ', $field)) . "</th>";
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
exit;
?>
