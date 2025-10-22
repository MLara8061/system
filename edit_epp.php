<?php include 'db_connect.php'; ?>

<?php
$id = isset($_GET['id']) ? $_GET['id'] : 0;
$epp = $conn->query("SELECT * FROM equipment_epp WHERE id = $id")->fetch_assoc();
?>

<div class="col-lg-12">
    <div class="card shadow-sm">
        <div class="card-body">
            <form id="manage-epp" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $epp['id']; ?>">
                <input type="hidden" name="keep_image" value="1">

                <div class="row">
                    <!-- COLUMNA 1 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre del Equipo EPP</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo $epp['nombre']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" class="form-control" value="<?php echo $epp['marca']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="modelo">Modelo</label>
                            <input type="text" id="modelo" name="modelo" class="form-control" value="<?php echo $epp['modelo']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="serie">Serie</label>
                            <input type="text" id="serie" name="serie" class="form-control" value="<?php echo $epp['serie']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="costo">Costo (MXN)</label>
                            <input type="number" step="0.01" id="costo" name="costo" class="form-control" value="<?php echo $epp['costo']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_adquisicion">Fecha de Adquisición</label>
                            <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control" value="<?php echo $epp['fecha_adquisicion']; ?>" required>
                        </div>
                    </div>

                    <!-- COLUMNA 2 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="propiedad">Propiedad</label>
                            <select id="propiedad" name="propiedad" class="form-control" required>
                                <option value="Adquisición" <?php echo ($epp['propiedad'] == 'Adquisición') ? 'selected' : ''; ?>>Adquisición</option>
                                <option value="Renta" <?php echo ($epp['propiedad'] == 'Renta') ? 'selected' : ''; ?>>Renta</option>
                                <option value="Comodato" <?php echo ($epp['propiedad'] == 'Comodato') ? 'selected' : ''; ?>>Comodato</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="numero_inventario">Número de Inventario</label>
                            <input type="number" id="numero_inventario" name="numero_inventario" class="form-control" value="<?php echo $epp['numero_inventario']; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="area_id">Área</label>
                            <select id="area_id" name="area_id" class="form-control" required>
                                <option value="">Seleccione un área</option>
                                <?php
                                $areas = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
                                while ($row = $areas->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($epp['area_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo $row['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Estatus</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Activo" <?php echo ($epp['status'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo ($epp['status'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" rows="4" class="form-control"><?php echo $epp['observaciones']; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Imagen</label>
                            <div id="image-wrapper">
                                <?php if (!empty($epp['imagen']) && file_exists('uploads/' . $epp['imagen'])): ?>
                                    <div id="current-image">
                                        <img src="uploads/<?php echo $epp['imagen']; ?>" alt="Imagen actual"
                                             class="img-thumbnail mt-2" style="max-width:150px;">
                                        <br>
                                        <button type="button" class="btn btn-danger btn-sm mt-2" id="remove-image">
                                            Eliminar imagen
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <input type="file" id="imagen" name="imagen" class="form-control-file" accept="image/*" onchange="displayImg(this)">
                                    <img id="preview-img" src="" alt="" class="img-thumbnail mt-2" style="display:none; max-width:150px;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Actualizar Equipo EPP
                    </button>
                    <a href="index.php?page=epp_list" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function displayImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#preview-img').attr('src', e.target.result).show();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$(document).on('click', '#remove-image', function() {
    if (confirm('¿Deseas eliminar esta imagen?')) {
        $('#current-image').remove();
        $('#image-wrapper').append(`
            <input type="file" id="imagen" name="imagen" class="form-control-file" accept="image/*" onchange="displayImg(this)">
            <img id="preview-img" src="" alt="" class="img-thumbnail mt-2" style="display:none; max-width:150px;">
        `);
        $('input[name="keep_image"]').val('0');
    }
});

$('#manage-epp').submit(function(e){
    e.preventDefault();
    start_load();
    $.ajax({
        url: 'ajax.php?action=save_epp',
        data: new FormData($(this)[0]),
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        success: function(resp){
            end_load();
            if(resp == 1){
                alert_toast('Equipo EPP actualizado correctamente','success');
                setTimeout(function(){
                    location.replace('index.php?page=epp_list');
                }, 1000);
            } else {
                alert_toast('Error al actualizar equipo','error');
            }
        },
        error: function(){
            end_load();
            alert_toast('Error de conexión','error');
        }
    });
});
</script>
