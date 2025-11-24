<?php require_once 'config/config.php'; ?>
<div class="col-lg-12">
  <div class="card card-outline card-primary">
    <div class="card-header">
      <div class="card-tools">
        <button class="btn btn-sm btn-primary btn-block" type='button' id="new_position">
          <i class="fa fa-plus"></i> Agregar Puesto
        </button>
      </div>
    </div>
    <div class="card-body">
      <table class="table table-hover table-bordered" id="positions_list">
        <thead>
          <tr>
            <th>#</th>
            <th>Puesto</th>
            <th>Ubicación</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $i = 1;
          $qry = $conn->query("SELECT j.id as job_id, j.name as job_name, l.name as location_name, l.id as location_id 
                               FROM job_positions j 
                               LEFT JOIN location_positions elp ON elp.job_position_id = j.id
                               LEFT JOIN locations l ON l.id = elp.location_id
                               ORDER BY j.name ASC");
          while ($row = $qry->fetch_assoc()):
          ?>
            <tr>
              <th class="text-center"><?php echo $i++ ?></th>
              <td><b><?php echo ucwords($row['job_name']) ?></b></td>
              <td><b><?php echo ucwords($row['location_name'] ?? 'Sin asignar') ?></b></td>
              <td class="text-center">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                  Acción
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item edit_position" href="javascript:void(0)" data-id="<?php echo $row['job_id'] ?>">Editar</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item delete_position" href="javascript:void(0)" data-id="<?php echo $row['job_id'] ?>">Eliminar</a>
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
    $('#positions_list').dataTable();

    $('#new_position').click(function() {
        uni_modal("Agregar Puesto", "manage_job_position.php");
    });

    $('.edit_position').click(function() {
        uni_modal("Editar Puesto", "manage_job_position.php?id=" + $(this).attr('data-id'));
    });

    $('.delete_position').click(function() {
        _conf("¿Deseas eliminar este puesto?", "delete_position", [$(this).attr('data-id')]);
    });
});

function delete_position(id) {
    start_load();
    $.ajax({
        url: 'ajax.php?action=delete_job_position',
        method: 'POST',
        data: { id: id },
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Puesto eliminado correctamente", 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            }
        }
    });
}
</script>
