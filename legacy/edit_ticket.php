<?php
require_once 'config/config.php';
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qry = $ticket_id > 0 ? $conn->query("SELECT * FROM tickets where id = {$ticket_id}") : false;
$row = ($qry && $qry->num_rows > 0) ? $qry->fetch_array() : [];
foreach($row as $k => $v){
	$$k = $v;
}
$id = isset($id) ? $id : $ticket_id;
include __DIR__ . '/new_ticket.php';
?>