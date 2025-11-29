<?php
// Script simple para asignar relaciones manualmente
define('ACCESS', true);
require_once 'config/config.php';

echo "<h2>Asignar Relaciones Manualmente</h2>";
echo "<p>Usa este formulario para asignar ubicaciones y puestos a departamentos directamente</p>";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = intval($_POST['department_id']);
    $location_ids = isset($_POST['locations']) ? $_POST['locations'] : [];
    $position_ids = isset($_POST['positions']) ? $_POST['positions'] : [];
    
    // Limpiar relaciones existentes
    $conn->query("UPDATE locations SET department_id = NULL WHERE department_id = $department_id");
    $conn->query("UPDATE job_positions SET department_id = NULL WHERE department_id = $department_id");
    
    // Asignar nuevas relaciones
    $success_locs = 0;
    foreach($location_ids as $loc_id) {
        $loc_id = intval($loc_id);
        if($conn->query("UPDATE locations SET department_id = $department_id WHERE id = $loc_id")) {
            $success_locs++;
        }
    }
    
    $success_pos = 0;
    foreach($position_ids as $pos_id) {
        $pos_id = intval($pos_id);
        if($conn->query("UPDATE job_positions SET department_id = $department_id WHERE id = $pos_id")) {
            $success_pos++;
        }
    }
    
    echo "<div style='background:#ccffcc; padding:15px; border:2px solid green; margin:20px 0;'>";
    echo "<h3>✅ Relaciones actualizadas</h3>";
    echo "<p>Ubicaciones asignadas: $success_locs</p>";
    echo "<p>Puestos asignados: $success_pos</p>";
    echo "</div>";
}

// Mostrar formulario
?>
<form method="POST" style="max-width: 600px; margin: 20px;">
    <div style="margin-bottom: 15px;">
        <label style="display:block; font-weight:bold; margin-bottom:5px;">Departamento:</label>
        <select name="department_id" required style="width:100%; padding:8px;">
            <option value="">Seleccionar...</option>
            <?php
            $depts = $conn->query("SELECT * FROM departments ORDER BY name");
            while($dept = $depts->fetch_assoc()) {
                echo "<option value='{$dept['id']}'>{$dept['name']}</option>";
            }
            ?>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display:block; font-weight:bold; margin-bottom:5px;">Ubicaciones:</label>
        <select name="locations[]" multiple size="8" style="width:100%; padding:8px;">
            <?php
            $locs = $conn->query("SELECT l.*, d.name as dept_name FROM locations l 
                                  LEFT JOIN departments d ON d.id = l.department_id 
                                  ORDER BY l.name");
            while($loc = $locs->fetch_assoc()) {
                $current_dept = $loc['dept_name'] ? " (actual: {$loc['dept_name']})" : "";
                echo "<option value='{$loc['id']}'>{$loc['name']}{$current_dept}</option>";
            }
            ?>
        </select>
        <small style="display:block; color:#666; margin-top:5px;">Mantén Ctrl presionado para seleccionar múltiples</small>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display:block; font-weight:bold; margin-bottom:5px;">Puestos:</label>
        <select name="positions[]" multiple size="8" style="width:100%; padding:8px;">
            <?php
            $poss = $conn->query("SELECT j.*, d.name as dept_name FROM job_positions j 
                                  LEFT JOIN departments d ON d.id = j.department_id 
                                  ORDER BY j.name");
            while($pos = $poss->fetch_assoc()) {
                $current_dept = $pos['dept_name'] ? " (actual: {$pos['dept_name']})" : "";
                echo "<option value='{$pos['id']}'>{$pos['name']}{$current_dept}</option>";
            }
            ?>
        </select>
        <small style="display:block; color:#666; margin-top:5px;">Mantén Ctrl presionado para seleccionar múltiples</small>
    </div>
    
    <button type="submit" style="background:#007bff; color:white; padding:10px 20px; border:none; cursor:pointer; font-size:16px;">
        Guardar Relaciones
    </button>
</form>

<hr>
<h3>Relaciones Actuales:</h3>
<?php
$depts = $conn->query("SELECT * FROM departments ORDER BY name");
while($dept = $depts->fetch_assoc()) {
    echo "<h4>{$dept['name']}</h4>";
    
    echo "<p><strong>Ubicaciones:</strong> ";
    $locs = $conn->query("SELECT name FROM locations WHERE department_id = {$dept['id']} ORDER BY name");
    $loc_names = [];
    while($loc = $locs->fetch_assoc()) {
        $loc_names[] = $loc['name'];
    }
    echo count($loc_names) > 0 ? implode(', ', $loc_names) : '<em>Ninguna</em>';
    echo "</p>";
    
    echo "<p><strong>Puestos:</strong> ";
    $poss = $conn->query("SELECT name FROM job_positions WHERE department_id = {$dept['id']} ORDER BY name");
    $pos_names = [];
    while($pos = $poss->fetch_assoc()) {
        $pos_names[] = $pos['name'];
    }
    echo count($pos_names) > 0 ? implode(', ', $pos_names) : '<em>Ninguno</em>';
    echo "</p>";
    echo "<hr>";
}
?>
