<?php include 'db_connect.php'; ?>
<?php
$position_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$location_id = '';

if($position_id){
    // Obtener info del puesto
    $qry = $conn->query("SELECT j.*, elp.location_id 
                         FROM job_positions j 
                         LEFT JOIN equipment_location_positions elp 
                         ON elp.job_position_id = j.id 
                         WHERE j.id = $position_id");
    if($qry->num_rows > 0){
        $data = $qry->fetch_assoc();
        foreach($data as $k => $v){
            $$k = $v;
        }
        $location_id = $data['location_id'];
    }
}
?>

<div class="container-fluid">
  <form id="manage_job_position">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">

    <div class="form-group">
      <label class="control-label">Nombre del Puesto</label>
      <input type="text" name="name" class="form-control" required value="<?php echo isset($name) ? $name : '' ?>">
    </div>

    <div class="form-group">
      <label class="control-label">Ubicación</label>
      <select name="location_id" class="form-control" required>
        <option value="">Seleccionar Ubicación</option>
        <?php
        $locations = $conn->query("SELECT * FROM equipment_locations ORDER BY name ASC");
        while($row = $locations->fetch_assoc()):
        ?>
          <option value="<?php echo $row['id'] ?>" <?php echo (isset($location_id) && $location_id == $row['id']) ? 'selected' : '' ?>>
            <?php echo ucwords($row['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
  </form>
</div>

<script>
$('#manage_job_position').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
        url:'ajax.php?action=save_job_position',
        data: new FormData($(this)[0]),
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        success:function(resp){
            end_load();
            if(resp == 1){
                alert_toast("Puesto agregado correctamente", 'success');
                setTimeout(function(){
                    location.reload();
                },1500);
            } else if(resp == 2){
                alert_toast("Puesto actualizado correctamente", 'success');
                setTimeout(function(){
                    location.reload();
                },1500);
            } else {
                alert_toast("Ocurrió un error", 'error');
            }
        },
        error: function(){
            end_load();
            alert_toast("Error de conexión", 'error');
        }
    });
});
</script>
