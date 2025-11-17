<?php
require_once 'config/config.php';
$qry = $conn->query("SELECT * FROM equipments where id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_reception where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}

$qry = $conn->query("SELECT d.*,p.name as responsible_position_name,l.name as location_name from equipment_delivery d inner join `responsible_positions` p on d.`responsible_position`=p.`id` inner join locations l on d.`location_id`=l.id  where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_safeguard where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}

$qry = $conn->query("SELECT * FROM equipment_control_documents where equipment_id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}
$mesesN = array(1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

//Seteamos el id de nuevo por toda las consultas anteriores;
$id = $_GET['id'];

?>
<style type="text/css" media="print">
    @media print {
        @page {
            margin-left: 0.5in;
            margin-right: 0.5in;
            margin-top: 0;
            margin-bottom: 0;
        }


    }
</style>
<div class="col-lg-12">
    <div class="card">
        <div class="card-body" style="width:814px; margin: auto;">
            <form action="" id="manage_customer">
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                <input type="hidden" name="equipment_id" value="<?php echo isset($id) ? $id : '' ?>">
                <div class="row">
                    <p><img src="data:image/png;base64,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LdeOc3zj3w4OKTpsLAmQAAmQAAmQAAn0I4G4CTOctq6OV/radCcd65sY3+ML+pEXqyIBEiABEiABEiCBuBGIizDTdfiRWVqWL6OiIm4tp2ESIAESIAESIAESSDACMfUxU35kUtOueK/W6aQoS7DfFHaHBEiABEiABEgg7gRiMmLmdOkiFJKP1ek7Z89Jf7M27q1mBSRAAiRAAiRAAiSQgAT6JMwiU5bi0/qQfuKc9LKPE5APu0QCJEACJEACJEAC/UagT8LMEtppmLJ8vt9ay4pIgARIgARIgARIIIEJ9NrHzJ1sCGnJ3znSD3T40ijKEvh3g10jARIgARIgARLoZwI9HjFTjv3BoHysYaecl5dVuaOf28nqSIAESIAESIAESCDhCXQrzHR1hFJYfh42rWxf+isfJTwRdpAESIAESIAESIAEBohAl8JMOffLkHWSb0rl6gFqH6slARIgARIgARIggWFDoENhlpxiiIZ681pP2rrbhw0JdpQESIAESIAESIAEBphAK2GmHPuDDdZjod3hn3unVG4f4LaxehIgARIgARIgARIYVgRsYdZ00PimUF3obO/kqneHFQF2lgRIgARIgARIgAQGCQElzEJm0JoFP7LHB0mb2AwSIAESIAESIAESGJ4EVq7MMYZnz9lrEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiABEiCBbgj8PwnmU8bEnYU8AAAAAElFTkSuQmCC" width="814" height="101" style="margin-top: -20px"></p>
                    <div class="col-md-12" style="margin-left:0px;">




                        <p style="margin-top: -60px;margin-left: 120px">
                            FECHA <?php echo date('d') ?> de <?php echo $mesesN[date('m')] ?> de <?php echo date('Y') ?></p>
                        <br></p>
                        <div style="margin-left: 40px">
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong>RESPONSIVA DE EQUIPO DE C&Oacute;MPUTO</strong></p>


                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><span style="text-decoration:none;">&nbsp;</span></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><strong>DATOS DE RESPONSABLE DE EQUIPO</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong>&nbsp;</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>NOMBRE: <?php echo strtoupper($responsible_name) ?></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>&Aacute;REA: <?php echo strtoupper($location_name) ?></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>PUESTO:&nbsp; <?php echo strtoupper($responsible_position_name) ?></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>EXT. OFICINA: </p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><strong>&nbsp;</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><strong>DATOS DEL EQUIPO</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong>&nbsp;</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>MARCA: <?php echo strtoupper($brand) ?></< /p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>MODELO: <?php echo $model ?></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>No DE SERIE: <?php echo $serie ?></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'>INVENTARIO: <?php echo $number_inventory ?>;</p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><strong><em>&nbsp;</em></strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;line-height:115%;'><span style='font-family:"Arial",sans-serif;'>El equipo de c&oacute;mputo forma parte de tu herramienta de trabajo para el desarrollo de tus funciones que desempe&ntilde;as, por lo cual se te ha asignado y configurado de acuerdo a las necesidades del puesto.</span></p>
                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;margin-left:36.0pt;font-size:11.0pt;font-family:"Helvetica Neue";text-align:justify;'><strong>&nbsp;</strong></p>
                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;margin-left:36.0pt;font-size:11.0pt;font-family:"Helvetica Neue";text-align:center;'>
                                <br />
                                <strong><em>RESPONSABILIDADES DEL USUARIO</em></strong>
                            </p>
                            <ol style="list-style-type: decimal;margin-left:44px;">
                                <br />
                                <li><strong><em>Uso exclusivo para cuesti&oacute;n laboral y de emergencias internas y externas</em></strong></li>
                                <li><strong><em>El equipo es parte de la herramienta de trabajo&nbsp;</em></strong></li>
                                <li><strong><em>Cuidar el equipo y mantenerlo en buen estado</em></strong></li>
                                <li><strong><em>Al terminar labores se debe apagar el equipo.</em></strong></li>
                            </ol>
                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:8.0pt;margin-left:54.0pt;font-size:11.0pt;font-family:"Helvetica Neue";'><strong>&nbsp;</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><strong>RECOMENDACIONES DE USO:</strong></p>
                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;'><strong>&nbsp;</strong></p>
                            <ol style="list-style-type: decimal;">
                                <li><span style="font-size:12.0pt;line-height:107%;">No exponer contenido l&iacute;quidos cerca del equipo de c&oacute;mputo.</span></li>
                                <li><span style="font-size:12.0pt;line-height:107%;">Mant&eacute;ngalo conectado en el regulador de voltaje.</span></li>
                                <li><span style="font-size:12.0pt;line-height:107%;">No cargue la bater&iacute;a por m&aacute;s de 5 horas seguidas.</span></li>
                            </ol>
                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;margin-left:36.0pt;font-size:11.0pt;font-family:"Helvetica Neue";'><span style="font-size:16px;">&nbsp;</span></p>
                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:8.0pt;margin-left:36.0pt;font-size:11.0pt;font-family:"Helvetica Neue";'><span style="font-size:16px;">Los da&ntilde;os ocasionados por mal manejo o imprudencia por el usuario se aplicar&aacute; un pago correspondiente a los da&ntilde;os.</span></p>


                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:8.0pt;margin-left:36.0pt;font-size:11.0pt;font-family:"Helvetica Neue";'><br></p>
                            <table style="width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="width: 40.0000%;">
                                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'>____________________________</p>
                                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong><span style="font-size:12px;">RECIBO EQUIPO FUNCIONANDO</span></strong></p>
                                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong><span style="font-size:12px;">Y EN OPTIMAS CONDICIONES</span></strong></p>
                                        </td>
                                        <td style="width: 40.0000%;">
                                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong><span style="font-size:12px;">__________________________________</span></strong></p>
                                            <p style='margin:0cm;font-size:16px;font-family:"Times New Roman",serif;border:none;text-align:center;'><strong><span style="font-size:12px;">ENTREGA EQUIPO&nbsp;</span></strong><br><strong><span style="font-size:12px;">DE SISTEMAS</span></strong></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p style='margin-top:0cm;margin-right:0cm;margin-bottom:8.0pt;margin-left:36.0pt;font-size:11.0pt;font-family:"Helvetica Neue";'><br></p>

                        </div>

            </form>
        </div>
    </div>
</div>