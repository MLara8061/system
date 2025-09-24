<?php
include 'db_connect.php';

// Obtener el próximo ID autoincremental para Nro Inventario
$result = $conn->query("SHOW TABLE STATUS LIKE 'equipments'");
$row = $result->fetch_assoc();
$next_id = $row['Auto_increment'];
?>

<div class="col-lg-12">
    <div class="card">
        <div class="card-body">
            <form action="" id="manage_customer" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                
                <div class="row">
                    <div class="col-md-12 border-right">

                        <!-- Nro Inventario -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Nro Inventario</label>
                            <input type="text" name="number_inventory" class="form-control form-control-sm" 
                                   readonly value="<?php echo isset($number_inventory) ? $number_inventory : $next_id; ?>">
                        </div>

                        <!-- Fecha Ingreso -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Fecha Ingreso</label>
                            <input type="date" name="date_created" class="form-control form-control-sm" required 
                                   value="<?php echo isset($date_created) ? $date_created : ''; ?>">
                        </div>

                        <!-- Serie -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Serie</label>
                            <input type="text" name="serie" class="form-control form-control-sm alfanumerico" required 
                                   value="<?php echo isset($serie) ? $serie : ''; ?>">
                        </div>

                        <!-- Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Equipo</label>
                            <input type="text" name="name" class="form-control form-control-sm" required 
                                   value="<?php echo isset($name) ? $name : ''; ?>">
                        </div>

                        <!-- Marca -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Marca</label>
                            <input type="text" name="brand" class="form-control form-control-sm" 
                                   value="<?php echo isset($brand) ? $brand : ''; ?>">
                        </div>

                        <!-- Modelo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Modelo</label>
                            <input type="text" name="model" class="form-control form-control-sm" required 
                                   value="<?php echo isset($model) ? $model : ''; ?>">
                        </div>

                        <!-- Características -->
                        <div class="form-group col-md-6 float-left">
                            <label class="control-label">Características</label>
                            <textarea name="characteristics" class="form-control" style="height: 80px;"><?php echo isset($characteristics) ? $characteristics : ''; ?></textarea>
                        </div>

                        <!-- Valor del Equipo -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Valor del Equipo</label>
                            <input type="text" name="amount" class="form-control form-control-sm solonumeros" required 
                                   value="<?php echo isset($amount) ? $amount : ''; ?>">
                        </div>

                        <!-- Categoría -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Categoría</label>
                            <input type="text" name="discipline" class="form-control form-control-sm" 
                                   value="<?php echo isset($discipline) ? $discipline : ''; ?>">
                        </div>

                        <!-- Tipo de Adquisición -->
<div class="form-group col-md-3 float-left">
    <label class="control-label">Tipo de Adquisición</label>
    <select name="acquisition_type" class="custom-select custom-select-sm select2">
        <?php
        $options = ['Compra', 'Renta', 'Comodato'];
        foreach($options as $opt):
        ?>
        <option value="<?php echo $opt ?>" 
            <?php echo (isset($acquisition_type) && $acquisition_type == $opt) ? 'selected' : ''; ?>>
            <?php echo $opt; ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>


                        <!-- Periodo de Mantenimiento -->
                        <div class="form-group col-md-3 float-left">
                            <label class="control-label">Periodo de Manto</label>
                            <select name="mandate_period" class="custom-select custom-select-sm select2">
                                <option value="1" <?php echo (isset($mandate_period) && $mandate_period==1) ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo (isset($mandate_period) && $mandate_period==2) ? 'selected' : ''; ?>>2</option>
                            </select>
                        </div>

                        <!-- Departamento -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Departamento</label>
                            <select name="department_id" class="custom-select custom-select-sm select2">
                                <?php
                                $departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");
                                while($row = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($department_id) && $department_id==$row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Ubicación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Ubicación</label>
                            <select name="location_id" class="custom-select custom-select-sm select2">
                                <?php
                                $locations = $conn->query("SELECT * FROM equipment_locations ORDER BY name ASC");
                                while($row = $locations->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($location_id) && $location_id==$row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Cargo Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Cargo Responsable</label>
                            <select name="responsible_position" class="custom-select custom-select-sm select2">
                                <?php
                                $positions = $conn->query("SELECT * FROM equipment_responsible_positions ORDER BY name ASC");
                                while($row = $positions->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo (isset($responsible_position) && $responsible_position==$row['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucwords($row['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Nombre Responsable -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Nombre Responsable</label>
                            <input type="text" name="responsible_name" class="form-control form-control-sm" 
                                   value="<?php echo isset($responsible_name) ? $responsible_name : ''; ?>">
                        </div>

                        <!-- Fecha -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha</label>
                            <input type="date" name="date" class="form-control form-control-sm" 
                                   value="<?php echo isset($date) ? $date : ''; ?>">
                        </div>

                        <!-- Fecha Capacitación -->
                        <div class="form-group col-md-4 float-left">
                            <label class="control-label">Fecha Capacitación</label>
                            <input type="date" name="date_training" class="form-control form-control-sm" 
                                   value="<?php echo isset($date_training) ? $date_training : ''; ?>">
                        </div>

                        <!-- Aquí puedes seguir añadiendo los demás campos como documentos y notas -->

                    </div>
                </div>

                <hr>
                <div class="col-lg-12 text-right justify-content-center d-flex">
                    <button class="btn btn-primary mr-2">Guardar</button>
                    <button class="btn btn-secondary" type="reset">Resetear</button>
                </div>
            </form>
        </div>
    </div>
</div>
