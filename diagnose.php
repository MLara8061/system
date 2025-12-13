<?php
// diagnose.php - Simple diagnostic page
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Sistema de Diagnóstico</h1>";

define('ROOT', __DIR__);
echo "<p>ROOT: " . ROOT . "</p>";

echo "<h2>Test 1: Load config.php</h2>";
try {
    require_once ROOT . '/config/config.php';
    echo "<p style='color:green'>✓ config.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error loading config.php: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Test 2: Check database connection</h2>";
if (isset($conn)) {
    echo "<p style='color:green'>✓ Connection object exists</p>";
    if ($conn->connect_error) {
        echo "<p style='color:red'>✗ Connection error: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>✓ Connected to database</p>";
    }
} else {
    echo "<p style='color:red'>✗ Connection object not found</p>";
}

echo "<h2>Test 3: Simple query</h2>";
$result = $conn->query("SELECT 1");
if ($result) {
    echo "<p style='color:green'>✓ Simple query works</p>";
} else {
    echo "<p style='color:red'>✗ Query error: " . $conn->error . "</p>";
}

echo "<h2>Test 4: Count equipments</h2>";
$result = $conn->query("SELECT COUNT(*) as total FROM equipments");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color:green'>✓ Equipment count: " . $row['total'] . "</p>";
} else {
    echo "<p style='color:red'>✗ Query error: " . $conn->error . "</p>";
}

echo "<p><a href='index.php?page=home'>Go back to home</a></p>";
?>
