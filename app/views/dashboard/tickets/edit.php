<?php
require_once 'config/config.php';

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ticketId <= 0) {
	?>
	<div class="container-fluid">
		<div class="alert alert-danger mb-0">ID de ticket inválido.</div>
	</div>
	<?php
	return;
}

$qryRes = $conn->query("SELECT * FROM tickets WHERE id = {$ticketId} LIMIT 1");
if (!$qryRes || $qryRes->num_rows === 0) {
	?>
	<div class="container-fluid">
		<div class="alert alert-warning mb-0">Ticket no encontrado.</div>
	</div>
	<?php
	return;
}

$qry = $qryRes->fetch_assoc();
foreach ($qry as $k => $v) {
	$$k = $v;
}

include 'new.php';
?>