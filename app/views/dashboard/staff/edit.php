<?php
require_once 'config/config.php';
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qry = $staff_id > 0 ? $conn->query("SELECT * FROM staff where id = {$staff_id}") : false;
$row = ($qry && $qry->num_rows > 0) ? $qry->fetch_array() : [];
foreach($row as $k => $v){
	$$k = $v;
}
$id = isset($id) ? $id : $staff_id;
include 'new_staff.php';
?>