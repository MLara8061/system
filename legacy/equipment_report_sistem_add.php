<?php
require_once 'config/config.php';

// === RECIBIR TIPO DE SERVICIO ===
$tipo_servicio = $_POST['tipo_servicio'] ?? '';

// === VALIDAR QUE SEA UNO DE LOS PERMITIDOS ===
$tipos_permitidos = ['Correctivo', 'Preventivo', 'Capacitacion', 'Operativo', 'Programado', 'Incidencias'];
if (!in_array($tipo_servicio, $tipos_permitidos)) {
    $tipo_servicio = 'No especificado';
}

// === RESTO DE CAMPOS ===
$orden_servicio = $_POST['orden_servicio'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$numero_inv = $_POST['numero_inv'] ?? '';
$serie = $_POST['serie'] ?? '';
$modelo = $_POST['modelo'] ?? '';
$marca = $_POST['marca'] ?? '';

$fecha_servicio = $_POST['fecha_servicio'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';
$fecha_entrega = $_POST['fecha_entrega'] ?? '';

$mantenimientoPreventivo = $_POST['mantenimientoPreventivo'] ?? '';
$unidad_riesgo = $_POST['unidad_riesgo'] ?? '';
$componentes = $_POST['componentes'] ?? '';
$toner = $_POST['toner'] ?? '';
$impresiom_pruebas = $_POST['impresiom_pruebas'] ?? '';

// === MATERIALES ===
$materiales = [];
foreach ($_POST['material_qty'] ?? [] as $i => $qty) {
    $mid = (int)($_POST['material_id'][$i] ?? 0);
    $qty = (int)$qty;
    if ($mid > 0 && $qty > 0 && $i < 2) {
        $materiales[] = ['id' => $mid, 'qty' => $qty];
    }
}

// === VALIDAR STOCK ===
foreach ($materiales as $m) {
    $check = $conn->query("SELECT stock, name FROM inventory WHERE id = {$m['id']}")->fetch_array();
    if (!$check || $check['stock'] < $m['qty']) {
        header("Location: index.php?page=equipment_list");
        exit();
    }
}

// === MATERIALES EN CAMPOS ===
$numero1 = $materiales[0]['qty'] ?? '';
$material1 = $materiales[0]['id'] ?? 0 ? ($conn->query("SELECT name FROM inventory WHERE id = {$materiales[0]['id']}")->fetch_array()['name'] ?? '') : '';
$numero2 = $materiales[1]['qty'] ?? '';
$material2 = $materiales[1]['id'] ?? 0 ? ($conn->query("SELECT name FROM inventory WHERE id = {$materiales[1]['id']}")->fetch_array()['name'] ?? '') : '';

// === INSERTAR CON TIPO DE SERVICIO ===
$stmt = $conn->prepare("INSERT INTO equipment_report_sistem 
    (orden_servicio, nombre, numero_inv, serie, modelo, marca, tipo_servicio,
     fecha_servicio, hora_inicio, hora_fin, fecha_entrega,
     mantenimientoPreventivo, unidad_riesgo, componentes, toner, impresiom_pruebas,
     numero1, material1, numero2, material2)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssssssssssssssssss",
    $orden_servicio, $nombre, $numero_inv, $serie, $modelo, $marca, $tipo_servicio,
    $fecha_servicio, $hora_inicio, $hora_fin, $fecha_entrega,
    $mantenimientoPreventivo, $unidad_riesgo, $componentes, $toner, $impresiom_pruebas,
    $numero1, $material1, $numero2, $material2
);

$stmt->execute();
$report_id = $conn->insert_id;

// === DESCONTAR STOCK ===
foreach ($materiales as $m) {
    $conn->query("UPDATE inventory SET stock = stock - {$m['qty']} WHERE id = {$m['id']}");
}

header("Location: index.php?page=equipment_report_sistem_list");
exit;