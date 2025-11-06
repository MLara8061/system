<?php include 'db_connect.php'; ?>

<?php
 
$id = isset($_GET['id']) ? $_GET['id'] : 0;

$tool = $conn->query("SELECT * FROM tools WHERE id = $id")->fetch_assoc();
if (!$tool) {
    echo "<script>alert('Herramienta no encontrada'); location.href='index.php?page=tools_list';</script>";
    exit;
}
?>
<div class="col-lg-12">
    <div class="card shadow-sm">
        <div class="card-body">
            <form id="manage-tool" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                <input type="hidden" name="keep_image" value="1" id="keep_image">

                <div class="row">
                    <!-- COLUMNA 1 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre de la Herramienta</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                   value="<?php echo htmlspecialchars($tool['nombre']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" class="form-control" 
                                   value="<?php echo htmlspecialchars($tool['marca']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="costo">Costo (MXN)</label>
                            <input type="number" step="0.01" id="costo" name="costo" class="form-control" 
                                   value="<?php echo $tool['costo']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_adquisicion">Fecha de Adquisición</label>
                            <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control" 
                                   value="<?php echo $tool['fecha_adquisicion']; ?>" required>
                        </div>
                    </div>

                    <!-- COLUMNA 2 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supplier_id">Proveedor</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php
                                $proveedores = $conn->query("SELECT id, empresa FROM suppliers WHERE estado = 1 ORDER BY empresa ASC");
                                while ($row = $proveedores->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo ($tool['supplier_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo $row['empresa']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estatus">Estatus</label>
                            <select id="estatus" name="estatus" class="form-control">
                                <option value="Activa" <?php echo ($tool['estatus'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                                <option value="Inactiva" <?php echo ($tool['estatus'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_baja">Fecha de Baja (si aplica)</label>
                            <input type="date" id="fecha_baja" name="fecha_baja" class="form-control" 
                                   value="<?php echo ($tool['fecha_baja'] && $tool['fecha_baja'] != '0000-00-00') ? $tool['fecha_baja'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="caracteristicas">Características</label>
                            <textarea id="caracteristicas" name="caracteristicas" rows="4" class="form-control"><?php echo htmlspecialchars($tool['caracteristicas']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Imagen</label>
                            <div id="image-wrapper">
                                <?php if (!empty($tool['imagen']) && file_exists('uploads/' . $tool['imagen'])): ?>
                                    <div id="current-image">
                                        <img src="uploads/<?php echo $tool['imagen']; ?>" alt="Imagen actual" 
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
                        <i class="fas fa-save mr-1"></i> Actualizar Herramienta
                    </button>
                    <a href="index.php?page=tools_list" class="btn btn-secondary">Cancelar</a>
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

// === ELIMINAR IMAGEN ===
$(document).on('click', '#remove-image', function() {
    if (confirm('¿Deseas eliminar esta imagen?')) {
        $('#current-image').remove();
        $('#image-wrapper').append(`
            <input type="file" id="imagen" name="imagen" class="form-control-file" accept="image/*" onchange="displayImg(this)">
            <img id="preview-img" src="" alt="" class="img-thumbnail mt-2" style="display:none; max-width:150px;">
        `);
        $('#keep_image').val('0');
    }
});

// === ENVIAR FORMULARIO ===
$('#manage-tool').submit(function(e){
    e.preventDefault();
    start_load();
    
    $.ajax({
        url: 'ajax.php?action=save_tool',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false,
        method: 'POST',
        success: function(resp){
            end_load();
            console.log("RESPUESTA:", resp);
            if(resp == 1){
                alert_toast('Herramienta actualizada correctamente','success');
                setTimeout(() => location.replace('index.php?page=tools_list'), 1000);
            } else {
                alert_toast('Error al actualizar: ' + resp,'error');
            }
        },
        error: function(xhr){
            end_load();
            alert_toast('Error de conexión','error');
            console.error(xhr.responseText);
        }
    });
});
</script>