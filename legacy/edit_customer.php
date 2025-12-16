<?php
require_once 'config/config.php';
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qry = $customer_id > 0 ? $conn->query("SELECT * FROM customers where id = {$customer_id}") : false;
$row = ($qry && $qry->num_rows > 0) ? $qry->fetch_array() : [];
foreach($row as $k => $v){
	$$k = $v;
}
$id = isset($id) ? $id : $customer_id;
include 'new_customer.php';
?>