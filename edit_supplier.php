<?php include 'db_connect.php' ?>

<?php
if (isset($_GET['id'])) {
    $qry = $conn->query("SELECT * FROM suppliers WHERE id = " . $_GET['id'])->fetch_assoc();
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-edit mr-2"></i> Editar Proveedor
            </h3>
        </div>
        <div class="card-body">
            <form id="edit_supplier_form">
                <input type="hidden" name="id" value="<?php echo isset($qry['id']) ? $qry['id'] : '' ?>">

                <div class="row">
                    <!-- Columna izquierda -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="empresa">Empresa</label>
                            <input type="text" id="empresa" name="empresa" required class="form-control" value="<?php echo isset($qry['empresa']) ? $qry['empresa'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="rfc">RFC</label>
                            <input type="text" id="rfc" name="rfc" class="form-control" value="<?php echo isset($qry['rfc']) ? $qry['rfc'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="representante">Representante</label>
                            <input type="text" id="representante" name="representante" class="form-control" value="<?php echo isset($qry['representante']) ? $qry['representante'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo isset($qry['telefono']) ? $qry['telefono'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="correo">Correo Electrónico</label>
                            <input type="email" id="correo" name="correo" class="form-control" value="<?php echo isset($qry['correo']) ? $qry['correo'] : '' ?>">
                        </div>
                    </div>

                    <!-- Columna derecha -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sitio_web">Sitio Web</label>
                            <input type="url" id="sitio_web" name="sitio_web" class="form-control" placeholder="https://www.ejemplo.com" value="<?php echo isset($qry['sitio_web']) ? $qry['sitio_web'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="sector">Sector</label>
                            <input type="text" id="sector" name="sector" class="form-control" value="<?php echo isset($qry['sector']) ? $qry['sector'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="notas">Notas</label>
                            <textarea id="notas" name="notas" class="form-control" rows="3"><?php echo isset($qry['notas']) ? $qry['notas'] : '' ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="imagen">Imagen / Logo</label>
                            <input type="file" id="imagen" name="imagen" class="form-control-file" onchange="displayImg(this)">
                            <?php if (!empty($qry['imagen'])): ?>
                                <div class="mt-2">
                                    <img id="cimg" src="assets/uploads/<?php echo $qry['imagen'] ?>" alt="Logo actual" width="100" class="img-thumbnail">
                                    <p class="text-muted small mt-1">Logo actual del proveedor</p>
                                </div>
                            <?php else: ?>
                                <img id="cimg" style="display:none;" width="100" class="img-thumbnail mt-2">
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="1" <?php echo (isset($qry['estado']) && $qry['estado'] == 1) ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?php echo (isset($qry['estado']) && $qry['estado'] == 0) ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

            </form>
        </div>
        <div class="card-footer text-right">
            <button type="button" class="btn btn-primary" id="save_supplier">
                <i class="fas fa-save mr-1"></i> Guardar Cambios
            </button>
            <a href="./index.php?page=suppliers" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Cancelar
            </a>
        </div>
    </div>
</div>

<script>
function displayImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#cimg').attr('src', e.target.result).show();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$('#save_supplier').click(function() {
    var form = $('#edit_supplier_form')[0];
    var formData = new FormData(form);

    start_load();
    $.ajax({
        url: 'ajax.php?action=save_supplier',
        method: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        success: function(resp) {
            if (resp == 1) {
                alert_toast("Proveedor actualizado correctamente", 'success');
                setTimeout(function() { location.href = './index.php?page=suppliers'; }, 1500);
            } else {
                alert_toast("Error al guardar los cambios", 'danger');
                end_load();
            }
        }
    });
});
</script>
