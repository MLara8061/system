<?php
require_once 'lib/tcpdf/tcpdf.php';
include 'db_connect.php';
$id = (int)$_GET['id'];
$qry = $conn->query("SELECT * FROM equipments WHERE id = $id");
$eq = $qry->fetch_assoc();
$url = "http://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/view_equipment.php?id=$id";

$pdf = new TCPDF('P', 'mm', [50, 70]);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'ACTIVO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, $eq['number_inventory'], 0, 1, 'C');
$pdf->Cell(0, 4, $eq['name'], 0, 1, 'C');
$pdf->write2DBarcode($url, 'QRCODE,L', 15, 30, 20, 20);
$pdf->Output('etiqueta_'.$id.'.pdf', 'I');
?>