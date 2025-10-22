<?php include 'db_connect.php'; ?>

<div class="col-lg-12">
    <div class="card shadow-sm">
        
        <div class="card-body">
            <form id="manage-tool" enctype="multipart/form-data">

                <div class="row">
                    <!-- COLUMNA 1 -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre de la Herramienta</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="marca">Marca</label>
                            <input type="text" id="marca" name="marca" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="cantidad">Cantidad</label>
                            <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="costo">Costo (MXN)</label>
                            <input type="number" step="0.01" id="costo" name="costo" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="fecha_adquisicion">Fecha de Adquisición</label>
                            <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control" required>
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
                                    <option value="<?php echo $row['id']; ?>"><?php echo $row['empresa']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estatus">Estatus</label>
                            <select id="estatus" name="estatus" class="form-control">
                                <option value="Activa">Activa</option>
                                <option value="Inactiva">Inactiva</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_baja">Fecha de Baja (si aplica)</label>
                            <input type="date" id="fecha_baja" name="fecha_baja" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="caracteristicas">Características</label>
                            <textarea id="caracteristicas" name="caracteristicas" rows="4" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="imagen">Imagen</label>
                            <input type="file" id="imagen" name="imagen" class="form-control-file" accept="image/*" onchange="displayImg(this)">
                            <img id="preview-img" src="" alt="" class="img-thumbnail mt-2" style="display:none; max-width:150px;">
                        </div>
                    </div>
                </div>

                <hr>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar Herramienta</button>
                    <a href="index.php?page=tool_list" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Vista previa de imagen
    function displayImg(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-img').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Enviar formulario con AJAX
    $('#manage-tool').submit(function(e){
        e.preventDefault();
        start_load();
        $.ajax({
            url: 'ajax.php?action=save_tool',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            success: function(resp){
                end_load();
                if(resp == 1){
                    alert_toast('Herramienta guardada correctamente','success');
                    setTimeout(function(){
                        location.replace('index.php?page=tools_list');
                    }, 1000);
                } else {
                    alert_toast('Error al guardar herramienta','error');
                }
            },
            error: function(){
                end_load();
                alert_toast('Error de conexión','error');
            }
        });
    });
</script>
