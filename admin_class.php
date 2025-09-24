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
		$revision=false;
		$array_cols_equipment=array('number_inventory','serie','amount','date_created','name','brand','model','acquisition_type','mandate_period','revision',"characteristics","discipline");
		$new=false;

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					if(in_array($k, $array_cols_equipment)){
						$data .= ", $k='$v' ";
					}
				}
			}
		}
		$sql_inventory="select id from equipments where number_inventory = $number_inventory";

		$existe_inventario=$this->db->query($sql_inventory);
		
		if($existe_inventario->num_rows==0){
			$new=true;
		}

		if(!$new && empty($id)){
			$data.=' ,revision=1';
			$revision=true;
		}else{
			if($new){
				$data.=' ,revision=0';
			}
			
		}
		
		if(empty($id)){
			$save = $this->db->query("INSERT INTO equipments set $data");
		}else{
			$save = $this->db->query("UPDATE equipments set $data where id = $id");
		}
		
		if($save){
			

			if(empty($id)){
				$id=$this->db->insert_id;
				$new=true;
			}
			else{
				$id=$id;
				$new=false;
			}

			$_POST['equipment_id']=$id;

			$array_cols_equipment_reception=array('state','comments','equipment_id');

			$data="";
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id')) && !is_numeric($k)){
					if(in_array($k, $array_cols_equipment_reception)){
						if(empty($data)){
							$data .= " $k='$v' ";
						}else{
							$data .= ", $k='$v' ";
						}	
					}
				}
			}

			
			if(empty($id) || $new || $revision){
				$save_equipment_receipt = $this->db->query("INSERT INTO equipment_reception set $data ");
			}else{

				$save_equipment_receipt = $this->db->query("UPDATE equipment_reception set $data where equipment_id = $id ");
			}

		}

		
		if($save_equipment_receipt){
			$array_cols_equipment_delivery=array('department_id','location_id',
												'equipment_id','responsible_name','responsible_position',
												'date_training','date');
			$data="";
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id')) && !is_numeric($k)){
					if(in_array($k, $array_cols_equipment_delivery)){
						if(empty($data)){
							$data .= " $k='$v' ";
						}else{
							$data .= ", $k='$v' ";
						}	
					}
				}
			}

			if(empty($id) || $new || $revision){
				$save_equipment_delivery = $this->db->query("INSERT INTO equipment_delivery set $data ");
			}else{
				$save_equipment_delivery = $this->db->query("UPDATE equipment_delivery set $data where equipment_id = $id ");
			}

		}else{
		
		}
		
		
		if($save_equipment_delivery){

			$array_cols_equipment_safeguard=array('equipment_id','rfc_id','business_name',
												'phone','email',
												'warranty_time','date_adquisition');
			$data="";
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id')) && !is_numeric($k)){
					if(in_array($k, $array_cols_equipment_safeguard)){
						if(empty($data)){
							$data .= " $k='$v' ";
						}else{
							$data .= ", $k='$v' ";
						}	
					}
				}
			}

			if(empty($id) || $new || $revision){
				$equipment_safeguarding = $this->db->query("INSERT INTO equipment_safeguard set $data ");
			}else{
				$equipment_safeguarding = $this->db->query("UPDATE equipment_safeguard set $data where equipment_id = $id ");
			}
		}

		
		if($equipment_safeguarding){


			$array_cols_equipment_control_documents=array('invoice','bailment_file','contract_file','usermanual_file','fast_guide_file','datasheet_file','servicemanual_file','equipment_id');
			$data="";

			foreach($_POST as $k => $v){
				if(!in_array($k, array('id')) && !is_numeric($k)){
					if(in_array($k, $array_cols_equipment_control_documents)){
						if(empty($data)){
							$data .= " $k='$v' ";
						}else{
							
							$data .= ", $k='$v' ";
						}	
					}
				}
			}
			foreach ($_FILES as $k => $val) {

				$file=$_FILES[$k];
				$tmp_name=$_FILES[$k]['tmp_name'];
				if($tmp_name!=""){
					$dest='uploads/'.$file['name'];
					if (move_uploaded_file($tmp_name, $dest)){
						$v=$dest;
					}
					$data .= ", $k='$v' ";
				}
			}

			$sql_existe=$this->db->query("select id from equipment_control_documents where equipment_id = $id ");
			if($sql_existe->num_rows==0){
				$new=true;
			}
			
				
			if(empty($id) || $new || $revision){
				$equipment_save_documents = $this->db->query("INSERT INTO equipment_control_documents set $data ");
			}else{
				$equipment_save_documents = $this->db->query("UPDATE equipment_control_documents set $data where equipment_id = $id ");
			}
		


		}else{
			var_dump($this->db->error);
		}

		if($equipment_save_documents){
			echo 1;
		}else{
			echo 2;
		}
		

	}

	function save_equipment_revision(){

		extract($_POST);

		$data = "";
		$revision=false;
		$array_cols_equipment=array("equipment_id","date_revision","frecuencia");
		$new=false;
		$equipment_save_revision=false;

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					if(in_array($k, $array_cols_equipment)){
						$data .= ", $k='$v' ";
					}
				}
			}
		}
		
		$sql_equipment="select id from equipments where id = $id";
		$sql_existe=$this->db->query("select id from equipments where id = $id ");
		$equipment=false;
		if($sql_existe->num_rows==0){
			
		}else{
			$equipment = $sql_existe->fetch_object();

		}
		if(is_object($equipment)){
			$equipment_save_revision = $this->db->query("INSERT INTO equipment_revision set $data ");
		}
		if($equipment_save_revision){
			echo 1;
		}else{
			echo 2;
		}
	}

	function delete_equipment(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM equipments where id = ".$id);
		if($delete)
			return 1;
	}

	function save_equipment_unsubscribe(){

		extract($_POST);
		$data = "";
		$array_cols_equipment=array('date','equipment_id','withdrawal_reason','description','comments',
			'opinion','destination','responsible');
		
		$new=false;

		$_POST['equipment_id']=$id;

		foreach($_POST as $k => $v){
			if(!in_array($k, array('id')) && !is_numeric($k)){
				
				if(empty($data)){
					$data .= " $k='$v' ";
				}else{
					if($k=='withdrawal_reason'){
						$reasons=json_encode($_POST['withdrawal_reason']);
						$data .= ", $k='$reasons' ";
					}else{
						if(in_array($k, $array_cols_equipment)){
							$data .= ", $k='$v' ";
						}	
					}
					

				}
			}
		}

		$sql_existe=$this->db->query("select id from equipment_unsubscribe where equipment_id = $id ");
		if($sql_existe->num_rows==0){
			$new=true;
		}
		
			
		if(empty($id) || $new){
			$equipment_save_unsubscribe = $this->db->query("INSERT INTO equipment_unsubscribe set $data ");
		}else{
			$equipment_save_unsubscribe = $this->db->query("UPDATE equipment_unsubscribe set $data where equipment_id = $id ");
		}
		if($equipment_save_unsubscribe){
			echo 1;
		}else{
			echo 2;
		}
	}




}