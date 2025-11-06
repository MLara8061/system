<?php include 'db_connect.php'; ?>
<?php
if(isset($_GET['id'])){
  $qry = $conn->query("SELECT * FROM equipment_locations WHERE id=".$_GET['id']);
  foreach($qry->fetch_assoc() as $k => $v){
    $$k = $v;
  }
}
?>
<div class="container-fluid">
  <form id="manage_location">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
    <div class="form-group">
      <label class="control-label">Nombre</label>
      <input type="text" name="name" class="form-control" required value="<?php echo isset($name) ? $name : '' ?>">
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
