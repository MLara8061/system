<?php
// Exportar estructura de la base de datos a un archivo SQL (CREATE TABLEs)
// Uso: acceder por navegador (protegido por login/maintenance) o por CLI

define('ACCESS', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$timestamp = date('Ymd_His');
$filename = "$backupDir/schema_{$timestamp}.sql";

$fp = fopen($filename, 'w');
if (!$fp) {
    http_response_code(500);
    echo "No se pudo crear el archivo de respaldo.";
    exit;
}

fwrite($fp, "-- Export de estructura de la base de datos\n");
fwrite($fp, "-- Generated: " . date('c') . "\n\n");

// Obtener todas las tablas
$tablesRes = $conn->query("SHOW TABLES");
if (!$tablesRes) {
    fwrite($fp, "-- Error: " . $conn->error . "\n");
    fclose($fp);
    exit("Error consultando tablas: " . $conn->error);
}

while ($row = $tablesRes->fetch_array()) {
    $table = $row[0];
    fwrite($fp, "-- ------------------------------------------------------------\n");
    fwrite($fp, "-- Tabla: $table\n");
    fwrite($fp, "-- ------------------------------------------------------------\n\n");

    $createRes = $conn->query("SHOW CREATE TABLE `{$table}`");
    if ($createRes && $createRow = $createRes->fetch_assoc()) {
        $createSql = $createRow['Create Table'] ?? $createRow['Create View'] ?? null;
        if ($createSql) {
            fwrite($fp, $createSql . ";\n\n");
        } else {
            fwrite($fp, "-- No se obtuvo CREATE para $table\n\n");
        }
    } else {
        fwrite($fp, "-- Error obteniendo CREATE TABLE para $table: " . $conn->error . "\n\n");
    }
}

fclose($fp);

header('Content-Type: text/plain; charset=utf-8');
echo "Respaldo creado: $filename\n";
echo "Descargar: /" . ltrim(str_replace('\\\\', '/', $filename), '/') . "\n";
