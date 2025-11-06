<?php
ob_start();
$action = $_GET['action'];
include 'admin_class.php';
$crud = new Action();
if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login;
}
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save;
}
if($action == 'save_page_img'){
	$save = $crud->save_page_img();
	if($save)
		echo $save;
}
if($action == 'delete_user'){
	$save = $crud->delete_user();
	if($save)
		echo $save;
}
if($action == "save_customer"){
	$save = $crud->save_customer();
	if($save)
		echo $save;
}
if($action == "delete_customer"){
	$delete = $crud->delete_customer();
	if($delete)
		echo $delete;
}
if($action == "save_staff"){
	$save = $crud->save_staff();
	if($save)
		echo $save;
}
if($action == "delete_staff"){
	$delete = $crud->delete_staff();
	if($delete)
		echo $delete;
}
if($action == "save_department"){
	$save = $crud->save_department();
	if($save)
		echo $save;
}
if($action == "delete_department"){
	$delete = $crud->delete_department();
	if($delete)
		echo $delete;
}
if($action == "save_ticket"){
	$save = $crud->save_ticket();
	if($save)
		echo $save;
}
if($action == "delete_ticket"){
	$delsete = $crud->delete_ticket();
	if($delsete)
		echo $delsete;
}

if($action == "update_ticket"){
	$save = $crud->update_ticket();
	if($save)
		echo $save;
}
if($action == "save_comment"){
	$save = $crud->save_comment();
	if($save)
		echo $save;
}
if($action == "delete_comment"){
	$delsete = $crud->delete_comment();
	if($delsete)
		echo $delsete;
}
if($action == "save_equipment"){
	$save = $crud->save_equipment();
	if($save){
		echo $save;
	}

	if ($action == "delete_equipment_image") {
		$delete = $crud->delete_equipment_image();
		if ($delete) echo $delete;
	}

}
if($action == "delete_equipment"){
	$delete = $crud->delete_equipment();
	if($delete){
		echo $delete;
	}
}
if($action == "save_equipment_unsubscribe"){
	$save = $crud->save_equipment_unsubscribe();
	if($save){
		echo $save;
	}
}

if($action == "save_equipment_revision"){
	$save = $crud->save_equipment_revision();
	if($save){
		echo $save;
	}
}

if($action == "save_supplier"){
	$save = $crud->save_supplier();
	if($save){
		echo $save;
	}
}

if($action == "delete_supplier"){
	$delete = $crud->delete_supplier();
	if($delete){
		echo $delete;
	}
}

// GUARDAR HERRAMIENTA
if($action == "save_tool"){
    error_log("AJAX save_tool llamado");
    $save = $crud->save_tool();
    error_log("Respuesta save_tool: $save");
    echo $save;
}

// ELIMINAR HERRAMIENTA
if($action == "delete_tool"){
    $delete = $crud->delete_tool();
    if($delete){
        echo $delete; // 1 = éxito
    }
}
//Epp
if($_GET['action'] == 'save_epp'){
    echo $crud->save_epp();
}
if($_GET['action'] == 'delete_epp'){
    echo $crud->delete_epp();
}

//  MANTENIMIENTOS
if($action == "get_mantenimientos"){
    echo $crud->get_mantenimientos();
    exit;
}

if($action == "save_maintenance"){
    echo $crud->save_maintenance();
    exit;
}

if($action == "complete_maintenance"){
    echo $crud->complete_maintenance();
    exit;
}

// GUARDAR / EDITAR UBICACIÓN
if($action == "save_equipment_location"){
    echo $crud->save_equipment_location();
  }

  if($action == "delete_equipment_location"){
    echo $crud->delete_equipment_location();
  }


ob_end_flush();
?>
