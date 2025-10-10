<?php include 'db_connect.php'; ?>
<div class="col-lg-12">
  <div class="card">
    <div class="card-body">
      <form id="manage-supplier">
        <div class="row">
          <!-- Columna izquierda -->
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label for="empresa">Empresa</label>
              <input type="text" id="empresa" name="empresa" class="form-control" required>
            </div>

            <div class="form-group mb-3">
              <label for="rfc">RFC</label>
              <input type="text" id="rfc" name="rfc" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label for="representante">Representante</label>
              <input type="text" id="representante" name="representante" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label for="telefono">Teléfono</label>
              <input type="text" id="telefono" name="telefono" class="form-control solonumeros">
            </div>

            <div class="form-group mb-3">
              <label for="correo">Correo</label>
              <input type="email" id="correo" name="correo" class="form-control">
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="col-md-6">
            <div class="form-group mb-3">
              <label for="sitio_web">Sitio Web</label>
              <input type="url" id="sitio_web" name="sitio_web" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label for="sector">Sector</label>
              <input type="text" id="sector" name="sector" class="form-control">
            </div>

            <div class="form-group mb-3">
              <label for="imagen">Imagen</label>
              <input type="file" id="imagen" name="imagen" class="form-control" onchange="displayImg(this)">
              <div class="mt-2 text-center">
                <img id="cimg" src="" alt="Vista previa" width="100" style="display:none; border-radius:8px; border:1px solid #ccc; padding:4px;">
              </div>
            </div>

            <div class="form-group mb-3">
              <label for="status">Estado</label>
              <select id="status" name="status" class="form-control">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <div class="form-group mb-3">
              <label for="notas">Notas</label>
              <textarea id="notas" name="notas" class="form-control" rows="4"></textarea>
            </div>
          </div>
        </div>

        <div class="text-center mt-4">
          <button type="submit" class="btn btn-primary px-5"> Guardar </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$('.solonumeros').on('input', function() {
  this.value = this.value.replace(/[^0-9]/g, '');
});

function displayImg(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      $('#cimg').attr('src', e.target.result).show();
    }
    reader.readAsDataURL(input.files[0]);
  }
}

$('#manage-supplier').submit(function(e){
  e.preventDefault();
  start_load();
  $.ajax({
    url: 'ajax.php?action=save_supplier',
    data: new FormData($(this)[0]),
    cache: false,
    contentType: false,
    processData: false,
    method: 'POST',
    success: function(resp){
      if(resp == 1){
        alert_toast('Proveedor guardado correctamente','success');
        setTimeout(function(){
          location.replace('index.php?page=supplier_list');
        },750);
      }else{
        alert_toast('Error al guardar proveedor','error');
        end_load();
      }
    }
  });
});
</script>
