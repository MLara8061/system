<?php
include 'db_connect.php';
$qry = $conn->query("SELECT * FROM equipments where id = " . $_GET['id'])->fetch_array();
foreach ($qry as $k => $v) {
    $$k = $v;
}

// var_dump($qry);
?>

<div class="container col-10">
<div class="row">
    <div class="col-sm-9">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Reporte de Sstemas</h1>


            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title col-12">Ordenes de Trabajo</h5>
                <input class="card-text col-12">
            </div>
        </div>
    </div>
</div>

<!-- Segunda  seccion-->

<div class="row">
    <!-- Primera Columna -->
    <div class="col-md-6 p-2 mb-2">
        <h2>Servicios a Realizar</h2>
        <form>
            <!-- Fila 1 de Radio Buttons -->
            <div class="row">
                <div class="form-check form-check-inline col-5">
                    <input class="form-check-input" type="radio" name="opcion1" id="radio1">
                    <label class="form-check-label" for="radio1">Correctivo</label>
                </div>
                <div class="form-check form-check-inline col-5">
                    <input class="form-check-input" type="radio" name="opcion1" id="radio2">
                    <label class="form-check-label" for="radio2">Preventivo</label>
                </div>
            </div>
            <div class="row">
                <!-- Fila 2 de Radio Buttons -->
                <div class="form-check form-check-inline col-5">
                    <input class="form-check-input" type="radio" name="opcion2" id="radio3">
                    <label class="form-check-label" for="radio3">Capacitacion</label>
                </div>
                <div class="form-check form-check-inline col-5">
                    <input class="form-check-input" type="radio" name="opcion2" id="radio4">
                    <label class="form-check-label" for="radio4">Servicio Operativo</label>
                </div>
            </div>
            <div class="row">
                <!-- Fila 3 de Radio Buttons -->
                <div class="form-check form-check-inline col-5">
                    <input class="form-check-input" type="radio" name="opcion3" id="radio5">
                    <label class="form-check-label" for="radio5">Servicio Porgramado</label>
                </div>
                <div class="form-check form-check-inline col-5">
                    <input class="form-check-input" type="radio" name="opcion3" id="radio6">
                    <label class="form-check-label" for="radio6">Incidencias</label>
                </div>
            </div>
        </form>
    </div>

    <hr>
    <!-- Segunda Columna -->
    <div class="col-md-6">
        <h3>Datos del Equipo</h3>
        <table class="table">
            <tbody>
                <tr>
                    <th>Nombre del Equipo:</th>
                    <td>
                        <input type="text" class="form-control" placeholder="Impresora" readonly value="<?php echo isset($name) ? $name : '' ?>" name="nombre">
                </tr>
                       

                <tr>
                    <th>N° de Inventario:</th>
                    <td>
                        <input type="text" class="form-control" placeholder="HAC-001" readonly value="<?php echo isset($number_inventory) ? $number_inventory : '' ?>" name="numero_inv">
                </td>
                </tr>
                <tr>
                    <th>N° de Serie:</th>
                    <td><input type="text" class="form-control" placeholder="VR89434700" value="<?php echo isset($serie) ? $serie : '' ?>" name="serie"></td>
                </tr>
                <tr>
                    <th>Modelo:</th>
                    <td><input type="text" class="form-control" placeholder="M2040" value="<?php echo isset($model) ? $model : '' ?>" name="modelo"></td>
                </tr>
                <tr>
                    <th>Marca:</th>
                    <td><input type="text" class="form-control" placeholder="KYOCERA" value="<?php echo isset($brand) ? $brand : '' ?>" name="marca"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<hr>

<!-- Tercera  seccion-->

<div class="row">
    <!-- Primera Columna -->
    <div class="col-md-6 p-2 mb-2 bg.">
        <h3>Tiempos de Servicio</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th colspan="2">Tiempo</th>
                </tr>
                <tr>
                    <th>D</th>
                    <th>M</th>
                    <th>A</th>
                    <th>Inicio</th>
                    <th>Término</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input name="dia" type="text" class="form-control" placeholder="06"></td>
                    <td><input name="mes" type="text" class="form-control" placeholder="11"></td>
                    <td><input name="yea" type="text" class="form-control" placeholder="23"></td>
                    <td><input name="inicio" type="text" class="form-control" placeholder="13:00"></td>
                    <td><input name="fin" type="text" class="form-control" placeholder="13:10"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Segunda Columna -->
    <div class="col-md-6">
        <h2>Evaluiacion de Riegos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Evaluación</th>
                    <th>Sí</th>
                    <th>No</th>
                </tr>
            </thead>
            <tbody>
                <!-- Fila de Ejemplo -->
                <tr>
                    <td>Elaboracion de Formato de Obra</td>
                    <td><input type="radio" name="eval1" id="eval1-yes"></td>
                    <td><input type="radio" name="eval1" id="eval1-no"></td>
                </tr>

                <tr>
                    <td>Elaboracion de formato de elaaluacion de incendio</td>
                    <td><input type="radio" name="eval1" id="eval1-yes"></td>
                    <td><input type="radio" name="eval1" id="eval1-no"></td>
                </tr>

                <tr>
                    <td>Delimitacion de Area</td>
                    <td><input type="radio" name="eval1" id="eval1-yes"></td>
                    <td><input type="radio" name="eval1" id="eval1-no"></td>
                </tr>
                <!-- Agrega más filas según sea necesario -->
            </tbody>
        </table>
    </div>
</div>

<!-- Cuarta seccion-->
<hr>
<div class="container col-12">
    <h3>Descripción Completa del Servicio</h3>
    <div class="form-group">
        <label for="mantenimientoPreventivo">Mantenimiento preventivo:</label>
        <input name="mantenimientoPreventivo" type="text" class="form-control" id="mantenimientoPreventivo">
    </div>
    <div class="form-group">
        <label for="unidad_riesgo">limpieza de Unidad de Riesgo:</label>
        <input name="unidad_riesgo" type="text" class="form-control" id="unidad_riesgo">
    </div>
    <div class="form-group">
        <label for="componentes">Limpieza de Componetes toner residual:</label>
        <input name="componentes" type="text" class="form-control" id="componentes">
    </div>
    <div class="form-group">
        <label for="toner">Extraccion de toner residual:</label>
        <input name="toner" type="text" class="form-control" id="toner">
    </div>
    <div class="form-group">
        <label for="impresiom_pruebas">Impresion prueba:</label>
        <input name="impresiom_pruebas" type="text" class="form-control" id="impresiom_pruebas">
    </div>
    <!-- Repite el elemento anterior para cada ítem de servicio que necesites -->
    <hr>
    <h3>Condiciones en las que se deja el equipo:</h3>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="estadoEquipo" id="funcionando" value="funcionando">
        <label class="form-check-label" for="funcionando">Funcionando</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="estadoEquipo" id="funcionandoParcialmente" value="funcionandoParcialmente">
        <label class="form-check-label" for="funcionandoParcialmente">Funcionando Parcialmente</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="estadoEquipo" id="retirado" value="retirado">
        <label class="form-check-label" for="retirado">Retirado</label>
    </div>
</div>

<!-- Quita seccion-->
<hr>
<div class="container mt-3">
    <h3>Material Utilizado</h3>
    <table class="table">
        <thead>
            <tr>
                <th style="width:10%">Cantidad</th>
                <th>Nombre</th>

                <th style="width:10%">Cantidad</th>
                <th>Nombre</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input name="numero1" type="number" class="form-control" value="1"></td>
                <td><input name="material1" type="text" class="form-control" placeholder="bolsas para basura"></td>
                <td><input name="numero2" type="text" class="form-control" placeholder=""></td>
                <td><input name="material2" type="text" class="form-control" placeholder=""></td>
            </tr>
            <tr>
                <td><input type="number" class="form-control" value="1"></td>
                <td><input type="text" class="form-control" placeholder="paño azul de limpieza"></td>
                <td><input type="text" class="form-control" placeholder=""></td>
                <td><input type="text" class="form-control" placeholder=""></td>
            </tr>
            <tr>
                <td><input type="number" class="form-control" value="1"></td>
                <td><input type="text" class="form-control" placeholder="brocha"></td>
                <td><input type="text" class="form-control" placeholder=""></td>
                <td><input type="text" class="form-control" placeholder=""></td>
            </tr>
            <!-- Agrega más filas según sea necesario -->
        </tbody>
    </table>
</div>

<hr>
<!-- Sexta seccion-->

<p class="p-2 mb-2">Observaciones</p>
<textarea name="" id="" cols="150" rows="5"></textarea>
<br><br>
<hr>
<label for="">FEHCA DE ENTREGA</label>
<input type="date" />
<hr>
<br>
<br>
<!-- Septima seccion-->
<h3>Datos de control</h3>
<br>
<br>

<table class="table">
    <thead>
        <tr>
            <th>Realizo</th>
            <th>Superviso</th>
            <th>Aceptacion Usuario</th>
            <th>Visto Bueno</th>

        </tr>
    </thead>

</table>

<hr>
<div class="col-lg-12 text-right justify-content-center d-flex">
    <button type="button" class="btn btn-primary" onclick="equipment_report_sistem_add()">Agregar</button>
    <button class="btn btn-secondary" type="button" onclick="location.href = 'index.php?page=equipment_list'">Cancelar</button>
</div>

</div>

<script>
    // Añadir datos para enviarlos a tabla detalle de encuestas
    function equipment_report_sistem_add() {
        // Validamos los datos del formulario
        var nombre = $("input[name='nombre']").val();
        //   if (name === "") {
        //     alert("El campo 'id' es obligatorio");
        //     return;
        //   }

        //   var numbero_inv = $("input[name='numbero_inv']").val();
        //   var numbero_inv = $("input[name='numbero_inv']").val();
          var numero_inv = $("input[name='numero_inv']").val();
        //   var serie = $("input[name='serie']").val();

        var serie = $("input[name='serie']").val();
        var modelo = $("input[name='modelo']").val();
        var marca = $("input[name='marca']").val();
         
        
        var dia = $("input[name='dia']").val();
        var mes = $("input[name='mes']").val();
        var yea = $("input[name='yea']").val();
        var inicio = $("input[name='inicio']").val();
        var fin = $("input[name='fin']").val();

        var mantenimientoPreventivo = $("input[name='mantenimientoPreventivo']").val();
        var unidad_riesgo = $("input[name='unidad_riesgo']").val();
        var componentes = $("input[name='componentes']").val();
        var toner = $("input[name='toner']").val();
        var impresiom_pruebas = $("input[name='impresiom_pruebas']").val();

        var numero1 = $("input[name='numero1']").val();
        var material1 = $("input[name='material1']").val();
        var numero2 = $("input[name='numero2']").val();
        var material2 = $("input[name='material2']").val();




         

        //   if (numbero_inv === "") {
        //     alert("El campo 'id_user' es obligatorio");
        //     return;
        //   }

        console.log("Nombre:", nombre);
        console.log("N° de Inventario:", numero_inv);

        console.log("N° de Inventario:", serie);
        console.log("N° de Inventario:", modelo);
        console.log("N° de Inventario:", marca);

        console.log("N° de Inventario:", dia);
        console.log("N° de Inventario:", mes);
        console.log("N° de Inventario:", yea);
        console.log("N° de Inventario:", inicio);
        console.log("N° de Inventario:", fin);

        console.log("N° de Inventario:", mantenimientoPreventivo);
        console.log("N° de Inventario:", unidad_riesgo);
        console.log("N° de Inventario:", componentes);
        console.log("N° de Inventario:", toner);
        console.log("N° de Inventario:", impresiom_pruebas);

        console.log("N° de Inventario:", numero1);
        console.log("N° de Inventario:", material1);
        console.log("N° de Inventario:", numero2);
        console.log("N° de Inventario:", material2);

    

        
        // Enviamos la solicitud POST

        $.post("equipment_report_sistem_add.php", {
            nombre: nombre,
            numero_inv:numero_inv,

            serie:serie,
            modelo:modelo,
            marca:marca,

            dia:dia,
            mes:mes,
            yea:yea,
            inicio:inicio,
            fin:fin,

            mantenimientoPreventivo:mantenimientoPreventivo,
            unidad_riesgo:unidad_riesgo,
            componentes:componentes,
            toner:toner,
            impresiom_pruebas:impresiom_pruebas,

            numero1:numero1,
            material1:material1,
            numero2:numero2,
            material2:material2





            // id_user: id_user
        }, function(data, status) {
            // Procesamos la respuesta
            if (status === "success") {
                // Registro agregado correctamente
                //   alert("Registro agregado correctamente 2024");
                window.location.href = 'index.php?page=equipment_report_sistem_list';
            } else {
                // Error al agregar el registro
                alert("Error al agregar el registro");
            }
        });
    }
</script>