<?php
  // Validamos los datos recibidos
//   if (!isset($_POST['nombre']) || !isset($_POST['numero_inv']) || !isset($_POST['serie']) || !isset($_POST['modelo']) || !isset($_POST['marca']) || !isset($_POST['dia']) || !isset($_POST['mes']) || !isset($_POST['yea']) || !isset($_POST['inicio']) || !isset($_POST['fin']) || !isset($_POST['mantenimientoPreventivo']) || !isset($_POST['unidad_riesgo']) || !isset($_POST['componentes']) || !isset($_POST['toner']) || !isset($_POST['impresiom_pruebas']) || !isset($_POST['numero1']) || !isset($_POST['material1']) || !isset($_POST['numero2']) || !isset($_POST['material2']))  {
//     // Datos no recibidos
//     header("Location: index.php?page=equipment_list");
//     exit();
//   }

  // Obtenemos los datos del formulario
  $nombre = $_POST['nombre'];
  $numero_inv = $_POST['numero_inv'];

  $serie = $_POST['serie'];
  $modelo = $_POST['modelo'];
  $marca = $_POST['marca'];

  $dia = $_POST['dia'];
  $mes = $_POST['mes'];
  $yea = $_POST['yea'];
  $inicio = $_POST['inicio'];
  $fin = $_POST['fin'];

  $mantenimientoPreventivo = $_POST['mantenimientoPreventivo'];
  $unidad_riesgo = $_POST['unidad_riesgo'];
  $componentes = $_POST['componentes'];
  $toner = $_POST['toner'];
  $impresiom_pruebas = $_POST['impresiom_pruebas'];

  $numero1 = $_POST['numero1'];
  $material1 = $_POST['material1'];
  $numero2 = $_POST['numero2'];
  $material2 = $_POST['material2'];




//   var_dump($_POST);

  // Realizamos la conexión a la base de datos
  include("db_connect.php");

  // Preparamos la consulta
  $stmt = $conn->prepare("INSERT INTO equipment_report_sistem (nombre , numero_inv , serie ,modelo ,marca ,dia ,mes ,yea ,inicio ,fin ,mantenimientoPreventivo ,unidad_riesgo ,componentes ,toner ,impresiom_pruebas ,numero1,material1 ,numero2, material2  ) VALUES(?, ? , ? , ?, ? ,?, ? , ? , ?, ?, ? , ? , ?, ?, ? , ? , ?,?,?)");

  // Vinculamos los parámetros
//   $stmt->bind_param("ii", $nombre, $numbero_inv);
  $stmt->bind_param("sssssssssssssssssss", $nombre, $numero_inv , $serie , $modelo , $marca ,$dia ,$mes ,$yea ,$inicio ,$fin ,$mantenimientoPreventivo ,$unidad_riesgo ,$componentes ,$toner ,$impresiom_pruebas ,$numero1,$material1 ,$numero2, $material2);


  // Ejecutamos la consulta
  $stmt->execute();

  // Comprobamos el número de filas afectadas
  if ($stmt->affected_rows === 0) {
    // No se ha agregado ningún registro
    header("Location: index.php?page=equipment_list");
    exit();
  }

  // Agregamos el mensaje de éxito
  echo "1 Record Added!";

  // Cerramos la conexión a la base de datos
  $stmt->close();

  // Redirigimos a la lista de registros
  header("Location: index.php?page=equipment_list");
?>
