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
    extract($_POST);
    $data = "";
    $revision = false;
    $new = false;

    // === CAMPOS DE equipments (INCLUYE supplier_id) ===
    $array_cols_equipment = array(
        'number_inventory','serie','amount','date_created','name','brand','model',
        'acquisition_type','mandate_period','revision','characteristics','discipline',
        'supplier_id'
    );

    // === CONSTRUIR $data PARA equipments ===
    foreach($_POST as $k => $v){
        if(!in_array($k, array('id')) && !is_numeric($k)){
            if(in_array($k, $array_cols_equipment)){
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
    }

    // === VERIFICAR SI ES NUEVO ===
    $sql_inventory = "SELECT id FROM equipments WHERE number_inventory = '$number_inventory'";
    $existe_inventario = $this->db->query($sql_inventory);
    $new = ($existe_inventario->num_rows == 0);

    // === REVISIÓN: solo si es edición y es el primer registro con ese inventario ===
    if (!$new && empty($id)) {
        $data .= ", revision=1";
        $revision = true;
    } elseif ($new) {
        $data .= ", revision=0";
    }

    // === GUARDAR EN equipments ===
    if (empty($id)) {
        $save = $this->db->query("INSERT INTO equipments SET $data");
    } else {
        $save = $this->db->query("UPDATE equipments SET $data WHERE id = $id");
    }

    if (!$save) return 2;

    $id = empty($id) ? $this->db->insert_id : $id;
    $_POST['equipment_id'] = $id;

    // === RECEPTION ===
    $data = $this->build_data($_POST, array('state','comments','equipment_id'));
    $this->save_or_update('equipment_reception', $data, $id, $new, $revision);

    // === DELIVERY ===
    $data = $this->build_data($_POST, array('department_id','location_id','responsible_name','responsible_position','date_training','date','equipment_id'));
    $this->save_or_update('equipment_delivery', $data, $id, $new, $revision);

    // === SAFEGUARD ===
    $data = $this->build_data($_POST, array('rfc_id','business_name','phone','email','warranty_time','date_adquisition','equipment_id'));
    $this->save_or_update('equipment_safeguard', $data, $id, $new, $revision);

    // === DOCUMENTOS ===
    $data = $this->build_data($_POST, array('invoice','bailment_file','contract_file','usermanual_file','fast_guide_file','datasheet_file','servicemanual_file','equipment_id'));
    
    // === SUBIR ARCHIVOS DE DOCUMENTOS ===
    foreach ($_FILES as $k => $file) {
        if (!empty($file['tmp_name']) && in_array($k, ['bailment_file','contract_file','usermanual_file','fast_guide_file','datasheet_file','servicemanual_file'])) {
            $dest = 'uploads/' . $file['name'];
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $data .= ", $k='$dest' ";
            }
        }
    }

    // === GESTIÓN DE IMAGEN DEL EQUIPO ===
    $upload_dir = "uploads/equipment/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 1. SI HAY NUEVA IMAGEN → SUBIR
    if (!empty($_FILES['equipment_image']['tmp_name'])) {
        $file = $_FILES['equipment_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $valid_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $valid_ext)) {
            return 3; // Formato inválido
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return 4; // Muy grande
        }

        // Eliminar imagen anterior
        $old_img_qry = $this->db->query("SELECT image FROM equipments WHERE id = $id");
        $old_img = $old_img_qry->fetch_array()['image'] ?? '';
        if ($old_img && file_exists($old_img)) {
            unlink($old_img);
        }

        $filename = $id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $this->db->query("UPDATE equipments SET image = '$destination' WHERE id = $id");
        }
    }

    // 2. SI SE MARCA delete_image = 1 → ELIMINAR IMAGEN
    if (!empty($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        $old_img_qry = $this->db->query("SELECT image FROM equipments WHERE id = $id");
        $old_img = $old_img_qry->fetch_array()['image'] ?? '';
        if ($old_img && file_exists($old_img)) {
            unlink($old_img);
        }
        $this->db->query("UPDATE equipments SET image = NULL WHERE id = $id");
    }

    // === GUARDAR DOCUMENTOS ===
    $exists = $this->db->query("SELECT id FROM equipment_control_documents WHERE equipment_id = $id")->num_rows;
    $new_doc = ($exists == 0);
    $this->save_or_update('equipment_control_documents', $data, $id, $new_doc, false);

    return 1;
}

// ===================================
// 2. SAVE EQUIPMENT REVISION
// ===================================
function save_equipment_revision(){
    extract($_POST);
    $data = $this->build_data($_POST, array("equipment_id","date_revision","frecuencia"));
    
    if (empty($id)) return 2;
    
    $sql = "SELECT id FROM equipments WHERE id = $id";
    if ($this->db->query($sql)->num_rows == 0) return 2;

    $save = $this->db->query("INSERT INTO equipment_revision SET $data");
    return $save ? 1 : 2;
}

// ===================================
// 3. DELETE IMAGE EQUIPMENT
// ===================================
function delete_equipment_image() {
    $id = $_POST['id'];
    $qry = $this->db->query("SELECT image FROM equipments WHERE id = $id");
    $img = $qry->fetch_array()['image'] ?? '';
    if ($img && file_exists($img)) {
        unlink($img);
    }
    $this->db->query("UPDATE equipments SET image = NULL WHERE id = $id");
    return 1;
}

// ===================================
// 3. DELETE EQUIPMENT (en cascada)
// ===================================
function delete_equipment(){
    extract($_POST);
    if (empty($id) || !is_numeric($id)) return 2;

    $tables = [
        'equipment_control_documents',
        'equipment_reception',
        'equipment_delivery',
        'equipment_safeguard',
        'equipment_revision',
        'equipment_unsubscribe'
    ];

    foreach ($tables as $table) {
        $this->db->query("DELETE FROM $table WHERE equipment_id = $id");
    }

    $delete = $this->db->query("DELETE FROM equipments WHERE id = $id");
    return $delete ? 1 : 2;
}

// ===================================
// 4. SAVE EQUIPMENT UNSUBSCRIBE
// ===================================
function save_equipment_unsubscribe(){
    extract($_POST);
    $data = "";
    $array_cols = array('date','equipment_id','withdrawal_reason','description','comments','opinion','destination','responsible');
    $_POST['equipment_id'] = $id;

    foreach($_POST as $k => $v){
        if(!in_array($k, array('id')) && !is_numeric($k)){
            if ($k == 'withdrawal_reason') {
                $reasons = json_encode($_POST['withdrawal_reason']);
                $data .= empty($data) ? " $k='$reasons' " : ", $k='$reasons' ";
            } elseif (in_array($k, $array_cols)) {
                $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
            }
        }
    }

    $exists = $this->db->query("SELECT id FROM equipment_unsubscribe WHERE equipment_id = $id")->num_rows;
    $new = ($exists == 0);

    $save = $new 
        ? $this->db->query("INSERT INTO equipment_unsubscribe SET $data")
        : $this->db->query("UPDATE equipment_unsubscribe SET $data WHERE equipment_id = $id");

    return $save ? 1 : 2;
}

// ===================================
// FUNCIONES AUXILIARES
// ===================================
private function build_data($post, $allowed){
    $data = "";
    foreach($post as $k => $v){
        if(!in_array($k, array('id')) && !is_numeric($k) && in_array($k, $allowed)){
            $data .= empty($data) ? " $k='$v' " : ", $k='$v' ";
        }
    }
    return $data;
}

private function save_or_update($table, $data, $id, $is_new, $force_insert = false){
    if ($is_new || $force_insert) {
        $this->db->query("INSERT INTO $table SET $data");
    } else {
        $exists = $this->db->query("SELECT id FROM $table WHERE equipment_id = $id")->num_rows;
        if ($exists > 0) {
            $this->db->query("UPDATE $table SET $data WHERE equipment_id = $id");
        } else {
            $this->db->query("INSERT INTO $table SET $data");
        }
    }
}

// === GUARDAR / EDITAR PROVEEDOR ===
function save_supplier(){
    extract($_POST);

    // === 1. Validar empresa obligatoria ===
    if (empty(trim($empresa ?? ''))) {
        return 2; // Empresa vacía
    }

    // === 2. Tomar sitio_web  ===
    $sitio_web = $sitio_web ?? '';

    // === 3. Validar RFC duplicado (si se proporciona) ===
    if (!empty(trim($rfc ?? ''))) {
        $rfc_clean = $this->db->real_escape_string(trim($rfc));
        $check_sql = "SELECT id FROM suppliers WHERE UPPER(rfc) = UPPER('$rfc_clean')";
        if (!empty($id)) {
            $check_sql .= " AND id != " . (int)$id;
        }
        if ($this->db->query($check_sql)->num_rows > 0) {
            return 5; // RFC duplicado
        }
    }

    // === 4. Construir consulta segura ===
    $data = [
        'empresa'       => $this->db->real_escape_string(trim($empresa)),
        'rfc'           => $this->db->real_escape_string(trim($rfc ?? '')),
        'representante' => $this->db->real_escape_string(trim($representante ?? '')),
        'telefono'      => $this->db->real_escape_string(trim($telefono ?? '')),
        'correo'        => $this->db->real_escape_string(trim($correo ?? '')),
        'sector'        => $this->db->real_escape_string(trim($sector ?? '')),
        'estado'        => (int)($estado ?? 1),
        'sitio_web'     => $this->db->real_escape_string(trim($sitio_web)), // ← TAL CUAL
        'notas'         => $this->db->real_escape_string(trim($notas ?? ''))
    ];

    $set = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = '$value'";
    }
    $set_clause = implode(', ', $set);

    // === 5. INSERTAR O ACTUALIZAR ===
    if (empty($id)) {
        $save = $this->db->query("INSERT INTO suppliers SET $set_clause");
    } else {
        $save = $this->db->query("UPDATE suppliers SET $set_clause WHERE id = " . (int)$id);
    }

    return $save ? 1 : 0; // 1 = éxito, 0 = error
}

// === ELIMINAR PROVEEDOR  ===
function delete_supplier(){
    extract($_POST);
    $id = (int)$id;

    if (empty($id)) {
        return 0;
    }

    // === ELIMINAR DIRECTAMENTE  ===
    $delete = $this->db->query("DELETE FROM suppliers WHERE id = $id");

    return $delete ? 1 : 0;
}

	
function save_tool(){
    extract($_POST);
    $data = "";

    $allowed = ['nombre', 'marca', 'costo', 'supplier_id', 'estatus', 'fecha_adquisicion', 'fecha_baja', 'caracteristicas'];
    foreach ($allowed as $k) {
        if (isset($_POST[$k])) {
            if (!empty($data)) $data .= ", ";
            $data .= " `$k` = '".addslashes($_POST[$k])."' ";
        }
    }

    // === IMAGEN ===
    if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != '') {
        $fname = time().'_'.$_FILES['imagen']['name'];
        move_uploaded_file($_FILES['imagen']['tmp_name'], 'uploads/'.$fname);
        if (!empty($data)) $data .= ", ";
        $data .= " `imagen` = '$fname' ";
    }

    // === GUARDAR ===
    if (empty($id)) {
        $sql = "INSERT INTO tools SET $data";
    } else {
        $sql = "UPDATE tools SET $data WHERE id = $id";
    }

    $result = $this->db->query($sql);
    if ($result) {
        return 1;
    } else {
        error_log("Error SQL: " . $this->db->error);
        echo "ERROR SQL: " . $this->db->error;
        return 0;
    }
}

function delete_tool(){
    extract($_POST);
    $id = (int)$id;

    // Obtener imagen
    $qry = $this->db->query("SELECT imagen FROM tools WHERE id = $id");
    if ($qry && $qry->num_rows > 0) {
        $img = $qry->fetch_assoc()['imagen'];
        if (!empty($img) && file_exists('uploads/' . $img)) {
            unlink('uploads/' . $img);
        }
    }

    // Eliminar registro
    $delete = $this->db->query("DELETE FROM tools WHERE id = $id");
    return $delete ? 1 : 0;
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

//Mantenimientos
function get_mantenimientos(){
    $events = [];
    $sql = "SELECT m.id, m.fecha_programada, m.descripcion, m.estatus, e.name 
            FROM mantenimientos m 
            JOIN equipments e ON m.equipo_id = e.id 
            ORDER BY m.fecha_programada";
    
    $qry = $this->db->query($sql);
    
    if (!$qry) {
        error_log("Error SQL en get_mantenimientos: " . $this->db->error);
        return json_encode([]);
    }
    
    while($row = $qry->fetch_assoc()){
        $title = $row['name'];
        if (!empty($row['descripcion'])) {
            $title .= ' - ' . substr($row['descripcion'], 0, 25);
        }
        $color = $row['estatus'] == 'completado' ? '#28a745' : '#dc3545';
        
        $events[] = [
            'id' => $row['id'],
            'title' => $title,
            'start' => $row['fecha_programada'],
            'color' => $color
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}

function save_maintenance(){
    extract($_POST);
    $data = "equipo_id='$equipo_id', fecha_programada='$fecha_programada'";
    if(!empty($descripcion)) $data .= ", descripcion='".addslashes($descripcion)."'";
    
    if(empty($id)){
        $sql = "INSERT INTO mantenimientos SET $data";
    } else {
        $sql = "UPDATE mantenimientos SET $data WHERE id=$id";
    }
    return $this->db->query($sql) ? 1 : 0;
}

function complete_maintenance(){
    $id = $_POST['id'];
    return $this->db->query("UPDATE mantenimientos SET estatus='completado' WHERE id=$id") ? 1 : 0;
}
	







}