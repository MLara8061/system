<?php
define('ACCESS', true);
require_once 'config/config.php';

echo "<h2>🚀 Ejecutar Migración de Base de Datos</h2>";
echo "<p><strong>Este script agregará las columnas necesarias a las tablas.</strong></p>";

$errors = [];
$success = [];

// 1. Agregar department_id a locations
echo "<h3>1. Agregando department_id a locations...</h3>";
$sql = "ALTER TABLE locations ADD COLUMN IF NOT EXISTS department_id INT NULL";
if($conn->query($sql)) {
    $success[] = "✅ Columna department_id agregada a locations";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    if(strpos($conn->error, 'Duplicate column') !== false) {
        $success[] = "✅ Columna department_id ya existe en locations";
        echo "<p style='color:green'>{$success[count($success)-1]}</p>";
    } else {
        $errors[] = "❌ Error en locations.department_id: " . $conn->error;
        echo "<p style='color:red'>{$errors[count($errors)-1]}</p>";
    }
}

// 2. Agregar FK constraint para locations.department_id
echo "<h3>2. Agregando constraint FK locations → departments...</h3>";
$sql = "ALTER TABLE locations ADD CONSTRAINT fk_location_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL";
if($conn->query($sql)) {
    $success[] = "✅ FK constraint agregada a locations";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    if(strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'already exists') !== false) {
        $success[] = "✅ FK constraint ya existe en locations";
        echo "<p style='color:green'>{$success[count($success)-1]}</p>";
    } else {
        $errors[] = "⚠️ Warning en FK locations: " . $conn->error;
        echo "<p style='color:orange'>{$errors[count($errors)-1]}</p>";
    }
}

// 3. Agregar location_id a job_positions
echo "<h3>3. Agregando location_id a job_positions...</h3>";
$sql = "ALTER TABLE job_positions ADD COLUMN IF NOT EXISTS location_id INT NULL";
if($conn->query($sql)) {
    $success[] = "✅ Columna location_id agregada a job_positions";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    if(strpos($conn->error, 'Duplicate column') !== false) {
        $success[] = "✅ Columna location_id ya existe en job_positions";
        echo "<p style='color:green'>{$success[count($success)-1]}</p>";
    } else {
        $errors[] = "❌ Error en job_positions.location_id: " . $conn->error;
        echo "<p style='color:red'>{$errors[count($errors)-1]}</p>";
    }
}

// 4. Agregar department_id a job_positions
echo "<h3>4. Agregando department_id a job_positions...</h3>";
$sql = "ALTER TABLE job_positions ADD COLUMN IF NOT EXISTS department_id INT NULL";
if($conn->query($sql)) {
    $success[] = "✅ Columna department_id agregada a job_positions";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    if(strpos($conn->error, 'Duplicate column') !== false) {
        $success[] = "✅ Columna department_id ya existe en job_positions";
        echo "<p style='color:green'>{$success[count($success)-1]}</p>";
    } else {
        $errors[] = "❌ Error en job_positions.department_id: " . $conn->error;
        echo "<p style='color:red'>{$errors[count($errors)-1]}</p>";
    }
}

// 5. Agregar FK constraint para job_positions.location_id
echo "<h3>5. Agregando constraint FK job_positions → locations...</h3>";
$sql = "ALTER TABLE job_positions ADD CONSTRAINT fk_job_position_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL";
if($conn->query($sql)) {
    $success[] = "✅ FK constraint agregada a job_positions (location)";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    if(strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'already exists') !== false) {
        $success[] = "✅ FK constraint ya existe en job_positions (location)";
        echo "<p style='color:green'>{$success[count($success)-1]}</p>";
    } else {
        $errors[] = "⚠️ Warning en FK job_positions (location): " . $conn->error;
        echo "<p style='color:orange'>{$errors[count($errors)-1]}</p>";
    }
}

// 6. Agregar FK constraint para job_positions.department_id
echo "<h3>6. Agregando constraint FK job_positions → departments...</h3>";
$sql = "ALTER TABLE job_positions ADD CONSTRAINT fk_job_position_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL";
if($conn->query($sql)) {
    $success[] = "✅ FK constraint agregada a job_positions (department)";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    if(strpos($conn->error, 'Duplicate') !== false || strpos($conn->error, 'already exists') !== false) {
        $success[] = "✅ FK constraint ya existe en job_positions (department)";
        echo "<p style='color:green'>{$success[count($success)-1]}</p>";
    } else {
        $errors[] = "⚠️ Warning en FK job_positions (department): " . $conn->error;
        echo "<p style='color:orange'>{$errors[count($errors)-1]}</p>";
    }
}

// 7. Migrar datos de location_positions a job_positions.location_id
echo "<h3>7. Migrando datos existentes de location_positions...</h3>";
$sql = "UPDATE job_positions j 
        INNER JOIN location_positions lp ON lp.job_position_id = j.id 
        SET j.location_id = lp.location_id 
        WHERE j.location_id IS NULL";
if($conn->query($sql)) {
    $affected = $conn->affected_rows;
    $success[] = "✅ Migrados $affected registros de location_positions a job_positions";
    echo "<p style='color:green'>{$success[count($success)-1]}</p>";
} else {
    $errors[] = "⚠️ Error al migrar datos: " . $conn->error;
    echo "<p style='color:orange'>{$errors[count($errors)-1]}</p>";
}

// Resumen final
echo "<hr>";
echo "<h2>📊 Resumen de Migración</h2>";
echo "<div style='background:#ccffcc; padding:15px; border:2px solid green;'>";
echo "<h3>✅ Éxitos (" . count($success) . "):</h3><ul>";
foreach($success as $msg) {
    echo "<li>$msg</li>";
}
echo "</ul></div>";

if(count($errors) > 0) {
    echo "<div style='background:#ffcccc; padding:15px; border:2px solid red; margin-top:10px;'>";
    echo "<h3>❌ Errores/Warnings (" . count($errors) . "):</h3><ul>";
    foreach($errors as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
}

echo "<hr>";
echo "<h2>🎯 Siguientes Pasos</h2>";
echo "<ol>";
echo "<li>Ve a <strong>Configuración → Departamentos</strong></li>";
echo "<li>Edita cada departamento y asigna ubicaciones y puestos</li>";
echo "<li>Ve a <strong>Configuración → Puestos</strong></li>";
echo "<li>Edita cada puesto y asigna departamento y ubicación</li>";
echo "<li>Prueba creando un nuevo equipo</li>";
echo "</ol>";

echo "<p><a href='check_structure.php' style='display:inline-block; background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Ver Estructura Actualizada</a></p>";
?>
