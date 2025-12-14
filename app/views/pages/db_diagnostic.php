<?php
// Este archivo debe cargarse desde index.php con ?page=db_diagnostic
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-database"></i> Diagnóstico de Base de Datos</h3>
        </div>
        <div class="card-body">
            
            <?php
            // Listar todas las tablas
            $tables = $conn->query("SHOW TABLES");
            echo "<h4>Tablas en la base de datos:</h4>";
            echo "<div class='row'>";
            $count = 0;
            while ($table = $tables->fetch_array()) {
                echo "<div class='col-md-3 mb-2'><span class='badge badge-info'>{$table[0]}</span></div>";
                $count++;
            }
            echo "</div>";
            echo "<p class='text-muted'>Total: $count tablas</p>";
            echo "<hr>";

            // Estructura de equipments
            echo "<h4>Estructura de tabla 'equipments':</h4>";
            $cols = $conn->query("SHOW COLUMNS FROM equipments");
            echo "<div class='table-responsive'>";
            echo "<table class='table table-sm table-bordered'>";
            echo "<thead class='thead-dark'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
            echo "<tbody>";
            while($col = $cols->fetch_assoc()) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                echo "<td>" . ($col['Default'] ?: '-') . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
            echo "<hr>";

            // Verificar si existe tabla categories o services_category
            $cat_check = $conn->query("SHOW TABLES LIKE 'categories'");
            $servcat_check = $conn->query("SHOW TABLES LIKE 'services_category'");
            
            if ($servcat_check->num_rows > 0) {
                echo "<h4>Estructura de tabla 'services_category':</h4>";
                $cols = $conn->query("SHOW COLUMNS FROM services_category");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-bordered'>";
                echo "<thead class='thead-dark'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                while($col = $cols->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                    echo "<td>" . ($col['Default'] ?: '-') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
                
                // Datos de services_category
                echo "<h5>Datos en 'services_category' (primeros 5):</h5>";
                $cats = $conn->query("SELECT * FROM services_category LIMIT 5");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-striped'>";
                $first = true;
                while($cat = $cats->fetch_assoc()) {
                    if ($first) {
                        echo "<thead><tr>";
                        foreach ($cat as $key => $val) {
                            echo "<th>" . htmlspecialchars($key) . "</th>";
                        }
                        echo "</tr></thead><tbody>";
                        $first = false;
                    }
                    echo "<tr>";
                    foreach ($cat as $val) {
                        echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
            } elseif ($cat_check->num_rows > 0) {
                echo "<h4>Estructura de tabla 'categories':</h4>";
                $cols = $conn->query("SHOW COLUMNS FROM categories");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-bordered'>";
                echo "<thead class='thead-dark'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                while($col = $cols->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                    echo "<td>" . ($col['Default'] ?: '-') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
            }
            echo "<hr>";

            // Verificar si existe tabla locations
            $location_check = $conn->query("SHOW TABLES LIKE 'locations'");
            if ($location_check->num_rows > 0) {
                echo "<h4>Estructura de tabla 'locations':</h4>";
                $cols = $conn->query("SHOW COLUMNS FROM locations");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-bordered'>";
                echo "<thead class='thead-dark'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                while($col = $cols->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                    echo "<td>" . ($col['Default'] ?: '-') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
                echo "<hr>";
            }

            // Verificar si existe tabla accessories
            $acc_check = $conn->query("SHOW TABLES LIKE 'accessories'");
            if ($acc_check->num_rows > 0) {
                echo "<h4>Estructura de tabla 'accessories':</h4>";
                $cols = $conn->query("SHOW COLUMNS FROM accessories");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-bordered'>";
                echo "<thead class='thead-dark'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                while($col = $cols->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                    echo "<td>" . ($col['Default'] ?: '-') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
                echo "<hr>";
            }

            // Verificar si existe tabla acquisition_types
            $acq_check = $conn->query("SHOW TABLES LIKE 'acquisition_types'");
            if ($acq_check->num_rows > 0) {
                echo "<h4>Datos en tabla 'acquisition_types':</h4>";
                $data = $conn->query("SELECT * FROM acquisition_types");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-striped'>";
                echo "<thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th></tr></thead>";
                echo "<tbody>";
                while($row = $data->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td>{$row['name']}</td>";
                    echo "<td>" . ($row['description'] ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
                echo "<hr>";
            }

            // Verificar si existe tabla inventory
            $inv_check = $conn->query("SHOW TABLES LIKE 'inventory'");
            if ($inv_check->num_rows > 0) {
                echo "<h4>Estructura de tabla 'inventory':</h4>";
                $cols = $conn->query("SHOW COLUMNS FROM inventory");
                echo "<div class='table-responsive'>";
                echo "<table class='table table-sm table-bordered'>";
                echo "<thead class='thead-dark'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                while($col = $cols->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>" . ($col['Key'] ?: '-') . "</td>";
                    echo "<td>" . ($col['Default'] ?: '-') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
            }
            ?>

        </div>
    </div>
</div>
