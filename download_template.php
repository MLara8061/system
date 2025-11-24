<?php
/**
 * Generador de plantilla Excel para carga masiva de equipos
 * Crea un archivo Excel con encabezados y formato correcto
 */

// Configurar encabezados para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="plantilla_equipos_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Iniciar búfer de salida
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .example {
            background-color: #f0f0f0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Serie</th>
                <th>Nombre</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Tipo de Adquisición</th>
                <th>Características</th>
                <th>Disciplina</th>
                <th>Proveedor</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <!-- Fila de ejemplo -->
            <tr class="example">
                <td>EQ-001-2024</td>
                <td>Laptop Dell</td>
                <td>Dell</td>
                <td>Latitude 5520</td>
                <td>Compra</td>
                <td>Intel i5, 16GB RAM, 512GB SSD</td>
                <td>Informática</td>
                <td>Dell México</td>
                <td>1</td>
            </tr>
            <tr class="example">
                <td>EQ-002-2024</td>
                <td>Proyector</td>
                <td>Epson</td>
                <td>PowerLite X49</td>
                <td>Donación</td>
                <td>3LCD, 3600 lúmenes, HDMI</td>
                <td>Audiovisual</td>
                <td>Epson</td>
                <td>1</td>
            </tr>
            <tr class="example">
                <td>EQ-003-2024</td>
                <td>Impresora</td>
                <td>HP</td>
                <td>LaserJet Pro M404dn</td>
                <td>Comodato</td>
                <td>Blanco y negro, 38 ppm, dúplex</td>
                <td>Oficina</td>
                <td></td>
                <td>2</td>
            </tr>
            <!-- Filas vacías para llenar -->
            <?php for ($i = 0; $i < 20; $i++): ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
exit;
?>
