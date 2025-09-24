<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h5 class="card-title">Lista de Clientes</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="customerTable" class="table table-striped table-bordered w-100">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Avatar</th>
                  <th>Nombre</th>
                  <th>Usuario</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
	$('#customerTable').DataTable({
	  "processing": true,
	  "serverSide": true,
	  "ajax": {
		"url": "fetch_customers.php",
		"type": "POST"
	  },
	  "columns": [
		{ "data": "id" },
		{ 
		  "data": "avatar",
		  "render": function(data, type, row) {
			return '<img src="' + data + '" alt="Avatar" class="img-thumbnail" width="50" height="50">';
		  }
		},
		{ "data": "name" },
		{ "data": "username" },
		{ 
		  "data": null,
		  "render": function(data, type, row) {
			return '<button class="btn btn-primary btn-sm view-details" data-id="' + row.id + '">Ver Detalles</button>';
		  }
		}
	  ],
	  "order": [[0, 'asc']]
	});

	$('#customerTable').on('click', '.view-details', function() {
	  var customerId = $(this).data('id');
	  // Aquí puedes agregar la lógica para mostrar los detalles del cliente
	  alert('Mostrar detalles para el cliente ID: ' + customerId);
	});
  });