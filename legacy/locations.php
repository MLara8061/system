<?php require_once 'config/config.php'; ?>
<div class="col-lg-12">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <div class="card-tools">
        <button class="btn btn-sm btn-primary btn-block" type='button' id="new_location">
          <i class="fa fa-plus"></i> Agregar UbicaciÃ³n
        </button>
      </div>
    </div>
    <div class="card-body">
      <table class="table table-hover table-bordered" id="list">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>AcciÃ³n</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $qry = $conn->query("SELECT * FROM locations ORDER BY name ASC");
          while ($row = $qry->fetch_assoc()):
          ?>
            <tr>
              <th class="text-center"><?php echo $i++ ?></th>
              <td><b><?php echo ucwords($row['name']) ?></b></td>
              <td class="text-center">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                  AcciÃ³n
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item edit_location" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Editar</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item delete_location" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">Eliminar</a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    $('#list').dataTable();

    $('#new_location').click(function() {
      uni_modal("Agregar UbicaciÃ³n", "manage_equipment_location.php");
    });

    $('.edit_location').click(function() {
      uni_modal("Editar UbicaciÃ³n", "manage_equipment_location.php?id=" + $(this).attr('data-id'));
    });

    $('.delete_location').click(function() {
      _conf("Â¿Deseas eliminar esta ubicaciÃ³n?", "delete_location", [$(this).attr('data-id')]);
    });
  });

  function delete_location(id) {
    start_load();
    $.ajax({
      url: 'public/ajax/action.php?action=delete_equipment_location',
      method: 'POST',
      data: { id: id },
      success: function(resp) {
        if (resp == 1) {
          alert_toast("UbicaciÃ³n eliminada correctamente", 'success');
          setTimeout(function() {
            location.reload();
          }, 1500);
        }
      }
    });
  }
</script>

