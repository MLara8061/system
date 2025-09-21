<?php include 'config/db_connect.php'; ?>
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Listado de Equipos</h3>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Inventario</th>
          <th>Equipo</th>
          <th>Marca</th>
          <th>Modelo</th>
          <th>Revisión</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = 1;
        $result = $conn->query("SELECT * FROM equipments ORDER BY id DESC");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= $row['date_created'] ?></td>
          <td><?= $row['number_inventory'] ?></td>
          <td><?= $row['name'] ?></td>
          <td><?= $row['brand'] ?></td>
          <td><?= $row['model'] ?></td>
          <td><?= $row['revision'] == 1 ? 'Sí' : 'No' ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
