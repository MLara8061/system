<?php



// include Database connection file
include("db_connect.php");

// check request
if(isset($_POST))
{
    // get values
    $id = $_POST['id'];
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


    // Updaste User details
    $query = "UPDATE equipment_report_sistem SET 
    nombre='$nombre', 
    numero_inv='$numero_inv', 
    serie='$serie', 
    modelo='$modelo', 
    marca='$marca', 
    dia='$dia', mes='$mes', yea='$yea', inicio='$inicio', fin='$fin', mantenimientoPreventivo='$mantenimientoPreventivo', unidad_riesgo='$idalumno', componentes='$idalumno', toner='$toner', impresiom_pruebas='$impresiom_pruebas', numero1='$numero1', material1='$material1', numero2='$numero2', material2='$material2' WHERE id='$id'";         
    
    
    
    // idalumno='$idalumno', codalumno='$codalumno', obs = '$obs' WHERE idobs = '$id'";


    if (!$result = mysqli_query($conn, $query)) {
        exit(mysqli_error($conn));
    }
}


?>

/*
equipment_report_sistem SET nombre=?, numero_inv=?, serie=?, modelo=?, marca=?, dia=?, mes=?, yea=?, inicio=?, fin=?, mantenimientoPreventivo=?, unidad_riesgo=?, componentes=?, toner=?, impresiom_pruebas=?, numero1=?, material1=?, numero2=?, material2=? WHERE id=?

include("db_connect.php");




// Verificar si se recibió un ID de equipo válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit("ID de equipo no recibido");
}

// Obtener el ID del equipo a editar
$id = $_GET['id'];

// Verificar si se recibieron los demás datos del formulario por POST
// if (empty($_POST['nombre']) || empty($_POST['numero_inv']) || empty($_POST['serie']) || empty($_POST['modelo']) || empty($_POST['marca']) || empty($_POST['dia']) || empty($_POST['mes']) || empty($_POST['yea']) || empty($_POST['inicio']) || empty($_POST['fin']) || empty($_POST['mantenimientoPreventivo']) || empty($_POST['unidad_riesgo']) || empty($_POST['componentes']) || empty($_POST['toner']) || empty($_POST['impresiom_pruebas']) || empty($_POST['numero1']) || empty($_POST['material1']) || empty($_POST['numero2']) || empty($_POST['material2'])) {
//     exit("Por favor, complete todos los campos del formulario");
// }

// Obtener los datos del formulario por POST
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

// Construir la consulta SQL de actualización
$sql = "UPDATE equipment_report_sistem SET nombre=?, numero_inv=?, serie=?, modelo=?, marca=?, dia=?, mes=?, yea=?, inicio=?, fin=?, mantenimientoPreventivo=?, unidad_riesgo=?, componentes=?, toner=?, impresiom_pruebas=?, numero1=?, material1=?, numero2=?, material2=? WHERE id=?";

// Preparar la consulta
$stmt = $conn->prepare($sql);

// Vincular los parámetros
$stmt->bind_param("ssssssssssssssssss", $nombre, $numero_inv, $serie, $modelo, $marca, $dia, $mes, $yea, $inicio, $fin, $mantenimientoPreventivo, $unidad_riesgo, $componentes, $toner, $impresiom_pruebas, $numero1, $material1, $numero2, $material2, $id);

// Ejecutar la consulta
if ($stmt->execute()) {
    // La actualización fue exitosa
    echo "Actualización exitosa";
} else {
    // Hubo un error en la actualización
    echo "Error en la actualización: " . $conn->error;
}

// Cerrar la conexión y liberar recursos
$stmt->close();
$conn->close();
?>
*/