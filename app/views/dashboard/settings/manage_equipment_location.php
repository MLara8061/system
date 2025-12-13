<?php require_once 'config/config.php'; ?>
<?php
$department_id = '';
if(isset($_GET['id'])){
  $qry = $conn->query("SELECT * FROM locations WHERE id=".$_GET['id']);
  $data = $qry->fetch_assoc();
  foreach($data as $k => $v){
    $$k = $v;
  }
  $department_id = $data['department_id'] ?? '';
}
?>
<div class="container-fluid">
  <form id="manage_location">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
    
    <div class="form-group">
      <label class="control-label">Nombre de la Ubicación</label>
      <input type="text" name="name" class="form-control" required value="<?php echo isset($name) ? $name : '' ?>">
    </div>
    
    <div class="form-group">
      <label class="control-label">Departamento</label>
      <select name="department_id" class="form-control">
        <option value="">Sin departamento</option>
        <?php
        $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
        while($row = $departments->fetch_assoc()):
        ?>
          <option value="<?php echo $row['id'] ?>" <?php echo (isset($department_id) && $department_id == $row['id']) ? 'selected' : '' ?>>
            <?php echo ucwords($row['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <small class="form-text text-muted">Opcional: Asigna esta ubicación a un departamento</small>
    </div>
  </form>
</div>

<script>
  $('#manage_location').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
      url:'ajax.php?action=save_equipment_location',
      data: new FormData($(this)[0]),
      cache: false,
      contentType: false,
      processData: false,
      method: 'POST',
      success:function(resp){
        if(resp == 1){
          alert_toast("Ubicación agregada correctamente", 'success');
          setTimeout(function(){
            location.reload();
          },1500);
        } else if(resp == 2){
          alert_toast("Ubicación actualizada correctamente", 'success');
          setTimeout(function(){
            location.reload();
          },1500);
        }
      }
    });
  });
</script>
