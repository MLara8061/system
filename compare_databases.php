<?php
/**
 * Script para comparar estructura de base de datos LOCAL vs PRODUCCIÓN
 * Genera SQL para sincronizar producción con local
 */

// ========== CONFIGURACIÓN ==========
$local = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'db'   => 'soporte_db' // Ajusta el nombre de tu BD local
];

$produccion = [
    'host' => 'servidor_produccion.com', // Cambia esto
    'user' => 'usuario_prod',             // Cambia esto
    'pass' => 'password_prod',            // Cambia esto
    'db'   => 'nombre_bd_prod'            // Cambia esto
];

// ========== CONEXIONES ==========
try {
    $connLocal = new PDO(
        "mysql:host={$local['host']};dbname={$local['db']};charset=utf8mb4",
        $local['user'],
        $local['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $connProd = new PDO(
        "mysql:host={$produccion['host']};dbname={$produccion['db']};charset=utf8mb4",
        $produccion['user'],
        $produccion['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Conexiones establecidas\n\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// ========== FUNCIONES ==========
function getTables($conn) {
    $stmt = $conn->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getTableStructure($conn, $table) {
    $stmt = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['Create Table'] ?? '';
}

function getColumns($conn, $table) {
    $stmt = $conn->query("DESCRIBE `$table`");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ========== COMPARACIÓN ==========
$tablesLocal = getTables($connLocal);
$tablesProd = getTables($connProd);

$newTables = array_diff($tablesLocal, $tablesProd);
$missingTables = array_diff($tablesProd, $tablesLocal);

$migration = [];

// 1. TABLAS NUEVAS en local que no existen en producción
if (!empty($newTables)) {
    echo "📋 TABLAS NUEVAS EN LOCAL (no existen en producción):\n";
    foreach ($newTables as $table) {
        echo "  - $table\n";
        $createStmt = getTableStructure($connLocal, $table);
        $migration[] = "-- Crear tabla: $table";
        $migration[] = $createStmt . ";\n";
    }
    echo "\n";
}

// 2. COLUMNAS DIFERENTES en tablas comunes
$commonTables = array_intersect($tablesLocal, $tablesProd);
echo "🔍 COMPARANDO COLUMNAS EN TABLAS COMUNES:\n";

foreach ($commonTables as $table) {
    $colsLocal = getColumns($connLocal, $table);
    $colsProd = getColumns($connProd, $table);
    
    $colNamesLocal = array_column($colsLocal, 'Field');
    $colNamesProd = array_column($colsProd, 'Field');
    
    $newCols = array_diff($colNamesLocal, $colNamesProd);
    
    if (!empty($newCols)) {
        echo "  ⚠ Tabla '$table' tiene columnas nuevas:\n";
        foreach ($newCols as $colName) {
            $colInfo = array_filter($colsLocal, fn($c) => $c['Field'] === $colName);
            $colInfo = array_values($colInfo)[0];
            
            echo "    + $colName ({$colInfo['Type']})\n";
            
            $alter = "ALTER TABLE `$table` ADD COLUMN `{$colInfo['Field']}` {$colInfo['Type']}";
            if ($colInfo['Null'] === 'NO') $alter .= " NOT NULL";
            if ($colInfo['Default'] !== null) $alter .= " DEFAULT '{$colInfo['Default']}'";
            if ($colInfo['Extra']) $alter .= " {$colInfo['Extra']}";
            
            $migration[] = "-- Agregar columna $colName a $table";
            $migration[] = $alter . ";\n";
        }
    }
}

echo "\n";

// 3. GENERAR ARCHIVO SQL DE MIGRACIÓN
if (!empty($migration)) {
    $filename = 'migration_' . date('Y-m-d_His') . '.sql';
    $content = "-- Script de migración generado: " . date('Y-m-d H:i:s') . "\n";
    $content .= "-- Ejecutar este script en la base de datos de PRODUCCIÓN\n\n";
    $content .= implode("\n", $migration);
    
    file_put_contents($filename, $content);
    
    echo "✅ ARCHIVO DE MIGRACIÓN GENERADO: $filename\n\n";
    echo "📝 INSTRUCCIONES:\n";
    echo "   1. Revisa el contenido de '$filename'\n";
    echo "   2. Sube el archivo al servidor de producción\n";
    echo "   3. Ejecuta: mysql -u usuario -p nombre_bd < $filename\n";
    echo "   O importa desde phpMyAdmin\n\n";
} else {
    echo "✓ No se encontraron diferencias en la estructura\n";
}

// 4. ADVERTENCIAS
if (!empty($missingTables)) {
    echo "⚠️  ADVERTENCIA: Tablas en PRODUCCIÓN que NO están en local:\n";
    foreach ($missingTables as $table) {
        echo "  - $table (no se tocará)\n";
    }
    echo "\n";
}
?>
