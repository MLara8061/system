<?php include 'db_connect.php'; ?>

<div class="col-lg-12">
  <div class="card shadow-sm">

    <div class="card-body">
      <form id="manage_supplier" enctype="multipart/form-data">

        <div class="row">
          <!-- COLUMNA 1 -->
          <div class="col-md-6">
            <div class="form-group">
              <label for="empresa">Empresa</label>
              <input type="text" id="empresa" name="empresa" class="form-control" required>
            </div>

            <div class="form-group">
              <label for="sitio_web">Sitio Web</label>
              <input type="text"
                name="sitio_web"
                id="sitio_web"
                class="form-control"
                placeholder="Ej: www.google.com"
                value="<?php echo htmlspecialchars($supplier['sitio_web'] ?? '') ?>">
            </div>

            <div class="form-group">
              <label for="rfc">RFC</label>
              <input type="text" id="rfc" name="rfc" class="form-control" maxlength="13" style="text-transform:uppercase;">
            </div>

            <div class="form-group">
              <label for="sector">Sector</label>
              <input type="text" id="sector" name="sector" class="form-control">
            </div>
          </div>

          <!-- COLUMNA 2 -->
          <div class="col-md-6">
            <div class="form-group">
              <label for="representante">Representante</label>
              <input type="text" id="representante" name="representante" class="form-control">
            </div>

            <div class="form-group">
              <label for="telefono">Teléfono</label>
              <input type="text" id="telefono" name="telefono" class="form-control solonumeros" maxlength="10">
            </div>

            <div class="form-group">
              <label for="correo">Correo</label>
              <input type="email" id="correo" name="correo" class="form-control">
            </div>

            <div class="form-group">
              <label for="estado">Estado</label>
              <select id="estado" name="estado" class="form-control">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <div class="form-group">
              <label for="notas">Notas</label>
              <textarea id="notas" name="notas" rows="4" class="form-control"></textarea>
            </div>
          </div>
        </div>

        <hr>
        <div class="text-right">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Guardar Proveedor
          </button>
          <a href="index.php?page=suppliers" class="btn btn-secondary">
            Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // === SOLO NÚMEROS EN TELÉFONO ===
  $('.solonumeros').on('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
  });

  // === LIMPIAR SITIO WEB ===
  $('#sitio_web').on('blur', function() {
    let val = this.value.trim();
    val = val.replace(/^https?:\/\//i, '')
      .replace(/^http?:\/\//i, '')
      .replace(/^www\./i, '');
    this.value = val;
  });

  // === ENVIAR FORMULARIO ===
  $('#manage_supplier').submit(function(e) {
    e.preventDefault();

    if (!$('#empresa').val().trim()) {
      alert_toast('La empresa es obligatoria', 'error');
      return;
    }

    start_load();
    $.ajax({
      url: 'ajax.php?action=save_supplier',
      data: new FormData($(this)[0]),
      cache: false,
      contentType: false,
      processData: false,
      method: 'POST',
      success: function(resp) {
        end_load();
        if (resp == 1) {
          alert_toast('Proveedor guardado correctamente', 'success');
          setTimeout(function() {
            location.replace('index.php?page=suppliers');
          }, 1000);
        } else if (resp == 2) {
          alert_toast('La empresa es obligatoria', 'error');
        } else if (resp == 5) {
          alert_toast('RFC ya registrado', 'error');
        } else {
          alert_toast('Error al guardar: ' + resp, 'error');
        }
      },
      error: function() {
        end_load();
        alert_toast('Error de conexión', 'error');
      }
    });
  });
</script>