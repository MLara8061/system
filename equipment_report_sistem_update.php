<?php
require_once 'config/config.php';

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: index.php?page=equipment_report_sistem_list");
    exit();
}
$id = (int)$_POST['id'];

// === RECUPERAR DATOS ANTERIORES PARA STOCK ===
$old = $conn->query("SELECT numero1, material1, numero2, material2 FROM equipment_report_sistem WHERE id = $id")->fetch_array();

// === NUEVOS MATERIALES ===
$materiales = [];
foreach ($_POST['material_qty'] ?? [] as $i => $qty) {
    $mid = (int)($_POST['material_id'][$i] ?? 0);
    $qty = (int)$qty;
    if ($mid > 0 && $qty > 0 && $i < 2) {
        $materiales[] = ['id' => $mid, 'qty' => $qty];
    }
}

// === RESTAURAR STOCK ANTERIOR ===
if ($old['numero1']) {
    $name = $old['material1'];
    $inv = $conn->query("SELECT id FROM inventory WHERE name = '$name'")->fetch_array();
    if ($inv) $conn->query("UPDATE inventory SET stock = stock + {$old['numero1']} WHERE id = {$inv['id']}");
}
if ($old['numero2']) {
    $name = $old['material2'];
    $inv = $conn->query("SELECT id FROM inventory WHERE name = '$name'")->fetch_array();
    if ($inv) $conn->query("UPDATE inventory SET stock = stock + {$old['numero2']} WHERE id = {$inv['id']}");
}

// === VALIDAR NUEVO STOCK ===
foreach ($materiales as $m) {
    $check = $conn->query("SELECT stock FROM inventory WHERE id = {$m['id']}")->fetch_array();
    if (!$check || $check['stock'] < $m['qty']) {
        header("Location: index.php?page=equipment_report_sistem_view&id=$id");
        exit();
    }
}

// === CAMPOS ===
$tipo_servicio = $_POST['tipo_servicio'] ?? '';
$fecha_servicio = $_POST['fecha_servicio'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';
$fecha_entrega = $_POST['fecha_entrega'] ?? '';

$mantenimientoPreventivo = $_POST['mantenimientoPreventivo'] ?? '';
$unidad_riesgo = $_POST['unidad_riesgo'] ?? '';
$componentes = $_POST['componentes'] ?? '';
$toner = $_POST['toner'] ?? '';
$impresiom_pruebas = $_POST['impresiom_pruebas'] ?? '';

$numero1 = $materiales[0]['qty'] ?? '';
$material1 = $materiales[0]['id'] ?? 0 ? ($conn->query("SELECT name FROM inventory WHERE id = {$materiales[0]['id']}")->fetch_array()['name'] ?? '') : '';
$numero2 = $materiales[1]['qty'] ?? '';
$material2 = $materiales[1]['id'] ?? 0 ? ($conn->query("SELECT name FROM inventory WHERE id = {$materiales[1]['id']}")->fetch_array()['name'] ?? '') : '';

// === ACTUALIZAR ===
$stmt = $conn->prepare("UPDATE equipment_report_sistem SET
    tipo_servicio = ?, fecha_servicio = ?, hora_inicio = ?, hora_fin = ?, fecha_entrega = ?,
    mantenimientoPreventivo = ?, unidad_riesgo = ?, componentes = ?, toner = ?, impresiom_pruebas = ?,
    numero1 = ?, material1 = ?, numero2 = ?, material2 = ?
    WHERE id = ?");

$stmt->bind_param("ssssssssssssssi",
    $tipo_servicio, $fecha_servicio, $hora_inicio, $hora_fin, $fecha_entrega,
    $mantenimientoPreventivo, $unidad_riesgo, $componentes, $toner, $impresiom_pruebas,
    $numero1, $material1, $numero2, $material2, $id
);

$stmt->execute();

// === DESCONTAR NUEVO STOCK ===
foreach ($materiales as $m) {
    $conn->query("UPDATE inventory SET stock = stock - {$m['qty']} WHERE id = {$m['id']}");
}

header("Location: index.php?page=equipment_report_sistem_list");
exit;