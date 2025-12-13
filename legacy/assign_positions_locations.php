<?php
// Script para asignar ubicación a puestos manualmente
define('ACCESS', true);
require_once 'config/config.php';

echo "<h2>Asignar Ubicaciones a Puestos</h2>";
echo "<p>Asigna la ubicación a cada puesto de trabajo</p>";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_all'])) {
    $updates = $_POST['position_location'] ?? [];
    $success = 0;
    
    foreach($updates as $position_id => $location_id) {
        $position_id = intval($position_id);
        $location_id = intval($location_id);
        
        if($location_id > 0) {
            if($conn->query("UPDATE job_positions SET location_id = $location_id WHERE id = $position_id")) {
                $success++;
            }
        }
    }
    
    echo "<div style='background:#ccffcc; padding:15px; border:2px solid green; margin:20px 0;'>";
    echo "<h3>✅ Actualización completa</h3>";
    echo "<p>Puestos actualizados: $success</p>";
    echo "</div>";
}

// Mostrar formulario
$positions = $conn->query("SELECT j.*, l.name as location_name, d.name as dept_name 
                           FROM job_positions j
                           LEFT JOIN locations l ON l.id = j.location_id
                           LEFT JOIN departments d ON d.id = j.department_id
                           ORDER BY j.name");
?>

<form method="POST" style="max-width: 800px; margin: 20px;">
    <table border="1" style="border-collapse:collapse; width:100%;">
        <thead style="background:#007bff; color:white;">
            <tr>
                <th style="padding:10px;">Puesto</th>
                <th style="padding:10px;">Departamento Actual</th>
                <th style="padding:10px;">Ubicación Actual</th>
                <th style="padding:10px;">Asignar Ubicación</th>
            </tr>
        </thead>
        <tbody>
            <?php while($pos = $positions->fetch_assoc()): ?>
            <tr>
                <td style="padding:8px;"><strong><?= $pos['name'] ?></strong></td>
                <td style="padding:8px;"><?= $pos['dept_name'] ?? '<em style="color:#999;">Sin asignar</em>' ?></td>
                <td style="padding:8px;"><?= $pos['location_name'] ?? '<em style="color:red;">Sin asignar</em>' ?></td>
                <td style="padding:8px;">
                    <select name="position_location[<?= $pos['id'] ?>]" style="width:100%; padding:5px;">
                        <option value="0">-- No cambiar --</option>
                        <?php
                        // Si el puesto tiene departamento, mostrar solo ubicaciones de ese departamento
                        if($pos['department_id']) {
                            $locs = $conn->query("SELECT * FROM locations WHERE department_id = {$pos['department_id']} ORDER BY name");
                        } else {
                            $locs = $conn->query("SELECT * FROM locations ORDER BY name");
                        }
                        
                        while($loc = $locs->fetch_assoc()):
                        ?>
                            <option value="<?= $loc['id'] ?>" <?= ($pos['location_id'] == $loc['id']) ? 'selected' : '' ?>>
                                <?= $loc['name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <button type="submit" name="assign_all" style="margin-top:20px; background:#28a745; color:white; padding:12px 30px; border:none; cursor:pointer; font-size:16px;">
        💾 Guardar Todas las Asignaciones
    </button>
</form>

<hr>
<h3>Estado Actual de Puestos:</h3>
<?php
$positions = $conn->query("SELECT j.*, l.name as location_name, d.name as dept_name 
                           FROM job_positions j
                           LEFT JOIN locations l ON l.id = j.location_id
                           LEFT JOIN departments d ON d.id = j.department_id
                           ORDER BY j.name");

echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
echo "<tr style='background:#007bff; color:white;'><th>Puesto</th><th>Departamento</th><th>Ubicación</th><th>Estado</th></tr>";
while($pos = $positions->fetch_assoc()) {
    $status = ($pos['location_id'] && $pos['department_id']) ? 
        '<span style="color:green;">✅ Completo</span>' : 
        '<span style="color:red;">❌ Incompleto</span>';
    
    echo "<tr>";
    echo "<td style='padding:8px;'>{$pos['name']}</td>";
    echo "<td style='padding:8px;'>" . ($pos['dept_name'] ?? '<em style="color:#999;">Sin asignar</em>') . "</td>";
    echo "<td style='padding:8px;'>" . ($pos['location_name'] ?? '<em style="color:#999;">Sin asignar</em>') . "</td>";
    echo "<td style='padding:8px;'>$status</td>";
    echo "</tr>";
}
echo "</table>";
?>

<div style="background:#fff3cd; padding:15px; margin-top:20px; border-left:4px solid #ffc107;">
    <h4>💡 Importante:</h4>
    <p><strong>Para que la cascada funcione correctamente, cada puesto debe tener:</strong></p>
    <ul>
        <li>✅ <strong>department_id</strong> asignado (Departamento)</li>
        <li>✅ <strong>location_id</strong> asignado (Ubicación)</li>
    </ul>
    <p>Cuando selecciones una ubicación en el formulario de equipos, solo se mostrarán los puestos que tengan esa ubicación asignada.</p>
</div>
