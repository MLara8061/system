<?php
session_start();
ini_set('display_errors', 1);
Class Action {
	private $db;

	public function __construct() {
		ob_start();
   	include 'db_connect.php';
    
    $this->db = $conn;
	}
	function __destruct() {
	    $this->db->close();
	    ob_end_flush();
	}
	function getDb() {
    return $this->db;
}


	function login(){
		extract($_POST);
		if($type ==1)
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM users where username = '".$username."' and password = '".md5($password)."' ");
		elseif($type ==2)
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM staff where email = '".$username."' and password = '".md5($password)."' ");
		elseif($type ==3)
			$qry = $this->db->query("SELECT *,concat(lastname,', ',firstname,' ',middlename) as name FROM customers where email = '".$username."' and password = '".md5($password)."' ");
		if($qry->num_rows > 0){
			foreach ($qry->fetch_array() as $key => $value) {
				if($key != 'password' && !is_numeric($key))
					$_SESSION['login_'.$key] = $value;
			}
			$_SESSION['login_type'] = $type;
				return 1;
		}else{
			return 3;
		}
	}
	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}

	function save_user(){
		extract($_POST);
		$ue = $_SESSION['login_type'] == 1 ? 'username' : 'email';
		$data = " firstname = '$firstname' ";
		$data = " middlename = '$middlename' ";
		$data = " lastname = '$lastname' ";
		$data .= ", $ue = '$username' ";
		if(!empty($password))
		$data .= ", password = '".md5($password)."' ";
		$chk = $this->db->query("Select * from $table where $ue = '$username' and id !='$id' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO $table set ".$data);
		}else{
			$save = $this->db->query("UPDATE $table set ".$data." where id = ".$id);
		}
		if($save){
			$_SESSION['login_firstname'] = $firstname;
			$_SESSION['login_middlename'] = $middlename;
			$_SESSION['login_lastname'] = $lastname;
			return 1;
		}
	}
	function delete_user(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM users where id = ".$id);
		if($delete)
			return 1;
	}
	function save_page_img(){
		extract($_POST);
		if($_FILES['img']['tmp_name'] != ''){
				$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
				$move = move_uploaded_file($_FILES['img']['tmp_name'],'assets/uploads/'. $fname);
				if($move){
					$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
					$hostName = $_SERVER['HTTP_HOST'];
						$path =explode('/',$_SERVER['PHP_SELF']);
						$currentPath = '/'.$path[1]; 
   						 // $pathInfo = pathinfo($currentPath); 

					return json_encode(array('link'=>$protocol.'://'.$hostName.$currentPath.'/admin/assets/uploads/'.$fname));

				}
		}
	}

	function save_customer(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password')
					$v = md5($v);
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM customers where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO customers set $data");
		}else{
			$save = $this->db->query("UPDATE customers set $data where id = $id");
		}

		if($save)
			return 1;
	}
	function delete_customer(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM customers where id = ".$id);
		if($delete){
			return 1;
		}
	}
	function save_staff(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id','cpass')) && !is_numeric($k)){
				if($k =='password')
					$v = md5($v);
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM staff where email ='$email' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO staff set $data");
		}else{
			$save = $this->db->query("UPDATE staff set $data where id = $id");
		}

		if($save)
			return 1;
	}
	function delete_staff(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM staff where id = ".$id);
		if($delete){
			return 1;
		}
	}
	function save_department(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		$check = $this->db->query("SELECT * FROM departments where name ='$name' ".(!empty($id) ? " and id != {$id} " : ''))->num_rows;
		if($check > 0){
			return 2;
			exit;
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO departments set $data");
		}else{
			$save = $this->db->query("UPDATE departments set $data where id = $id");
		}

		if($save)
			return 1;
	}
	function delete_department(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM departments where id = ".$id);
		if($delete){
			return 1;
		}
	}
	function save_ticket(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'description'){
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
		if(!isset($customer_id)){
			$data .= ", customer_id={$_SESSION['login_id']} ";
		}
		if($_SESSION['login_type'] == 1){
			$data .= ", admin_id={$_SESSION['login_id']} ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO tickets set $data");
		}else{
			$save = $this->db->query("UPDATE tickets set $data where id = $id");
		}

		if($save)
			return 1;
	}
	function update_ticket(){
		extract($_POST);
			$data = " status=$status ";
		if($_SESSION['login_type'] == 2)
			$data .= ", staff_id={$_SESSION['login_id']} ";
		$save = $this->db->query("UPDATE tickets set $data where id = $id");
		if($save)
			return 1;
	}
	function delete_ticket(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM tickets where id = ".$id);
		if($delete){
			return 1;
		}
	}
	function save_comment(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				if($k == 'comment'){
					$v = htmlentities(str_replace("'","&#x2019;",$v));
				}
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					$data .= ", $k='$v' ";
				}
			}
		}
			$data .= ", user_type={$_SESSION['login_type']} ";
			$data .= ", user_id={$_SESSION['login_id']} ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO comments set $data");
		}else{
			$save = $this->db->query("UPDATE comments set $data where id = $id");
		}

		if($save)
			return 1;
	}
	function delete_comment(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM comments where id = ".$id);
		if($delete){
			return 1;
		}
	}

	function save_equipment(){
		// DEPURACIÓN: VER QUÉ LLEGA
    error_log("=== SAVE_EQUIPMENT DEBUG ===");
    error_log("POST: " . print_r($_POST, true));
    error_log("FILES: " . print_r($_FILES, true));
    extract($_POST);
    $data = "";
    $revision_value = 0;
    $new = false;

    // === CAMPOS PERMITIDOS EN LA TABLA equipments ===
    $array_cols_equipment = array(
        'number_inventory','serie','amount','date_created','name','brand','model',
        'acquisition_type','mandate_period','revision','characteristics','discipline',
        'supplier_id'  // AÑADIDO: ahora se guarda
    );

    // === CONSTRUIR $data CON TODOS LOS CAMPOS ===
    foreach($_POST as $k => $v){
        if(!in_array($k, array('id')) && !is_numeric($k)){
            if(in_array($k, $array_cols_equipment)){
                if(empty($data)){
                    $data .= " $k='$v' ";
                } else {
                    $data .= ", $k='$v' ";
                }
            }
        }
    }

    // === DETERMINAR SI ES NUEVO O EDICIÓN ===
    if (empty($id)) {
        // NUEVO EQUIPO
        $new = true;
        $revision_value = 0;

        // Verificar si ya existe un equipo con ese número de inventario
        $check_inventory = $this->db->query("SELECT id FROM equipments WHERE number_inventory = '$number_inventory'");
        if ($check_inventory->num_rows > 0) {
            echo 2; // Error: número de inventario duplicado
            return;
        }
    } else {
        // EDICIÓN
        $new = false;

        // Verificar si este número de inventario ya existe en otro equipo
        $check_inventory = $this->db->query("SELECT id FROM equipments WHERE number_inventory = '$number_inventory' AND id != $id");
        if ($check_inventory->num_rows > 0) {
            echo 2; // Error: número de inventario duplicado
            return;
        }

        // Mantener el valor actual de revision, o forzar a 1 si es el primer registro
        $current = $this->db->query("SELECT revision FROM equipments WHERE id = $id")->fetch_array();
        $revision_value = $current['revision'];

        // Si es el primer registro con este número de inventario → revision = 1
        $first_time = $this->db->query("SELECT id FROM equipments WHERE number_inventory = '$number_inventory' AND id = $id")->num_rows;
        if ($first_time == 1 && $revision_value == 0) {
            $revision_value = 1;
        }
    }

    // === REEMPLAZAR O AGREGAR revision EN $data ===
    $data = preg_replace("/revision='[^']*'/", "revision='$revision_value'", $data);
    if (!preg_match("/revision=/", $data)) {
        $data .= ", revision='$revision_value'";
    }

    // === GUARDAR EN equipments ===
    if ($new) {
        $save = $this->db->query("INSERT INTO equipments SET $data");
    } else {
        $save = $this->db->query("UPDATE equipments SET $data WHERE id = $id");
    }

    if (!$save) {
        error_log("Error SQL equipments: " . $this->db->error);
        echo 2;
        return;
    }

    // === OBTENER ID DEL EQUIPO ===
    $equipment_id = $new ? $this->db->insert_id : $id;
    $_POST['equipment_id'] = $equipment_id;

    // === RECEPTION ===
    $data = "";
    $array_reception = array('state','comments','equipment_id');
    foreach($_POST as $k => $v){
        if(in_array($k, $array_reception) && !is_numeric($k)){
            $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
        }
    }

    if ($new) {
        $save_receipt = $this->db->query("INSERT INTO equipment_reception SET $data");
    } else {
        $exists = $this->db->query("SELECT id FROM equipment_reception WHERE equipment_id = $equipment_id")->num_rows;
        if ($exists > 0) {
            $save_receipt = $this->db->query("UPDATE equipment_reception SET $data WHERE equipment_id = $equipment_id");
        } else {
            $save_receipt = $this->db->query("INSERT INTO equipment_reception SET $data");
        }
    }

    if (!$save_receipt) { echo 2; return; }

    // === DELIVERY ===
    $data = "";
    $array_delivery = array('department_id','location_id','responsible_name','responsible_position','date_training','date','equipment_id');
    foreach($_POST as $k => $v){
        if(in_array($k, $array_delivery) && !is_numeric($k)){
            $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
        }
    }

    if ($new) {
        $save_delivery = $this->db->query("INSERT INTO equipment_delivery SET $data");
    } else {
        $exists = $this->db->query("SELECT id FROM equipment_delivery WHERE equipment_id = $equipment_id")->num_rows;
        if ($exists > 0) {
            $save_delivery = $this->db->query("UPDATE equipment_delivery SET $data WHERE equipment_id = $equipment_id");
        } else {
            $save_delivery = $this->db->query("INSERT INTO equipment_delivery SET $data");
        }
    }

    if (!$save_delivery) { echo 2; return; }

    // === SAFEGUARD ===
    $data = "";
    $array_safeguard = array('rfc_id','business_name','phone','email','warranty_time','date_adquisition','equipment_id');
    foreach($_POST as $k => $v){
        if(in_array($k, $array_safeguard) && !is_numeric($k)){
            $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
        }
    }

    if ($new) {
        $save_safeguard = $this->db->query("INSERT INTO equipment_safeguard SET $data");
    } else {
        $exists = $this->db->query("SELECT id FROM equipment_safeguard WHERE equipment_id = $equipment_id")->num_rows;
        if ($exists > 0) {
            $save_safeguard = $this->db->query("UPDATE equipment_safeguard SET $data WHERE equipment_id = $equipment_id");
        } else {
            $save_safeguard = $this->db->query("INSERT INTO equipment_safeguard SET $data");
        }
    }

    if (!$save_safeguard) { echo 2; return; }

    // === DOCUMENTOS ===
    $data = "";
    $array_docs = array('invoice','bailment_file','contract_file','usermanual_file','fast_guide_file','datasheet_file','servicemanual_file','equipment_id');
    foreach($_POST as $k => $v){
        if(in_array($k, $array_docs) && !is_numeric($k)){
            $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
        }
    }

    // Subir archivos
    foreach ($_FILES as $k => $file) {
        if (!empty($file['tmp_name'])) {
            $dest = 'uploads/' . $file['name'];
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $data .= ", $k='$dest' ";
            }
        }
    }

    $exists_docs = $this->db->query("SELECT id FROM equipment_control_documents WHERE equipment_id = $equipment_id")->num_rows;
    if ($new || $exists_docs == 0) {
        $save_docs = $this->db->query("INSERT INTO equipment_control_documents SET $data");
    } else {
        $save_docs = $this->db->query("UPDATE equipment_control_documents SET $data WHERE equipment_id = $equipment_id");
    }

    if ($save_docs) {
        echo 1; // ÉXITO
    } else {
        error_log("Error documentos: " . $this->db->error);
        echo 2;
    }
}

function toggle_supplier_status(){
    extract($_POST);
    
    // Validar que id exista
    if (!isset($id) || !is_numeric($id)) {
        echo 2;
        return;
    }

    // Obtener estado actual
    $qry = $this->db->query("SELECT estado FROM suppliers WHERE id = $id");
    if ($qry->num_rows == 0) {
        echo 2;
        return;
    }
    
    $current = $qry->fetch_array();
    $new_status = ($current['estado'] == 1) ? 0 : 1;

    // Actualizar estado
    $update = $this->db->query("UPDATE suppliers SET estado = $new_status WHERE id = $id");
    
    if ($update) {
        echo 1;
    } else {
        error_log("Error toggle_supplier_status: " . $this->db->error);
        echo 2;
    }
}
/*---------------- HERRAMIENTAS ----------------*/
function save_tool(){
    extract($_POST);
    $data = "";

    // Recorremos los campos POST y preparamos la cadena SQL
    foreach($_POST as $k => $v){
        if(!in_array($k, array('id','keep_image')) && !is_array($v)){
            if(!empty($data)) $data .= ", ";
            $data .= " {$k} = '".addslashes($v)."' ";
        }
    }

    // Manejar eliminación de imagen existente
    if(isset($_POST['keep_image']) && $_POST['keep_image'] == '0'){
        // Obtener imagen actual
        $qry = $this->db->query("SELECT imagen FROM tools WHERE id = $id");
        if($qry && $qry->num_rows > 0){
            $img = $qry->fetch_assoc()['imagen'];
            if(!empty($img) && file_exists('uploads/'.$img)){
                unlink('uploads/'.$img);
            }
        }
        $data .= ", imagen = '' ";
    }

    // Subida de nueva imagen
    if(isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != ''){
        $fname = time().'_'.$_FILES['imagen']['name'];
        move_uploaded_file($_FILES['imagen']['tmp_name'], 'uploads/'.$fname);
        $data .= ", imagen = '$fname' ";
    }

    // Insertar o actualizar
    if(empty($id)){
        $sql = "INSERT INTO tools SET $data";
    }else{
        $sql = "UPDATE tools SET $data WHERE id = $id";
    }

    if($this->db->query($sql)){
        return 1;
    } else {
        error_log("Error SQL en save_tool: " . $this->db->error);
        echo "Error SQL: " . $this->db->error;
        return 0;
    }
}

function delete_tool(){
    extract($_POST);
    
    // Obtener la imagen para eliminarla
    $qry = $this->db->query("SELECT imagen FROM tools WHERE id = $id");
    $img = $qry && $qry->num_rows > 0 ? $qry->fetch_assoc()['imagen'] : '';

    // Eliminar imagen del servidor si existe
    if(!empty($img) && file_exists('uploads/'.$img)){
        unlink('uploads/'.$img);
    }

    // Eliminar registro de la BD
    $delete = $this->db->query("DELETE FROM tools WHERE id = $id");
    if($delete){
        return 1;
    } else {
        return 0;
    }
}

//Guardar datos de Epp
function save_epp(){
    extract($_POST);
    $data = "";

    // Validar que el número de inventario no esté duplicado (excepto si es actualización)
    if(empty($id)){
        $check = $this->db->query("SELECT id FROM equipment_epp WHERE numero_inventario = '$numero_inventario'");
        if($check && $check->num_rows > 0){
            echo "Número de inventario duplicado";
            return 0;
        }
    } else {
        $check = $this->db->query("SELECT id FROM equipment_epp WHERE numero_inventario = '$numero_inventario' AND id != $id");
        if($check && $check->num_rows > 0){
            echo "Número de inventario duplicado";
            return 0;
        }
    }

    // Construir cadena de datos
    foreach($_POST as $k => $v){
        if(!in_array($k, array('id','keep_image')) && !is_array($v)){
            if(!empty($data)) $data .= ", ";
            $data .= " {$k} = '".addslashes($v)."' ";
        }
    }

    // Manejar eliminación de imagen existente
    if(isset($_POST['keep_image']) && $_POST['keep_image'] == '0'){
        $qry = $this->db->query("SELECT imagen FROM equipment_epp WHERE id = $id");
        if($qry && $qry->num_rows > 0){
            $img = $qry->fetch_assoc()['imagen'];
            if(!empty($img) && file_exists('uploads/'.$img)){
                unlink('uploads/'.$img);
            }
        }
        $data .= ", imagen = '' ";
    }

    // Subida de nueva imagen
    if(isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != ''){
        $fname = time().'_'.$_FILES['imagen']['name'];
        move_uploaded_file($_FILES['imagen']['tmp_name'], 'uploads/'.$fname);
        $data .= ", imagen = '$fname' ";
    }

    // Insertar o actualizar
    if(empty($id)){
        $sql = "INSERT INTO equipment_epp SET $data";
    } else {
        $sql = "UPDATE equipment_epp SET $data WHERE id = $id";
    }

    if($this->db->query($sql)){
        return 1;
    } else {
        error_log("Error SQL en save_epp: " . $this->db->error);
        echo "Error SQL: " . $this->db->error;
        return 0;
    }
}

//ELiminar Epp
function delete_epp(){
    extract($_POST);

    // Obtener la imagen para eliminarla
    $qry = $this->db->query("SELECT imagen FROM equipment_epp WHERE id = $id");
    $img = $qry && $qry->num_rows > 0 ? $qry->fetch_assoc()['imagen'] : '';

    // Eliminar imagen del servidor si existe
    if(!empty($img) && file_exists('uploads/'.$img)){
        unlink('uploads/'.$img);
    }

    // Eliminar registro de la BD
    $delete = $this->db->query("DELETE FROM equipment_epp WHERE id = $id");
    if($delete){
        return 1;
    } else {
        return 0;
    }
}


	







}