<?php
// debug.php - Archivo temporal para ver errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>Test de Debug - Assets Dragon</h2>";

// Test 1: Verificar PHP básico
echo "<h3>✅ PHP está funcionando</h3>";
echo "Versión PHP: " . phpversion() . "<br>";
echo "Servidor: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "Fecha actual: " . date('Y-m-d H:i:s') . "<br>";

// Test 2: Verificar extensión mysqli
echo "<h3>Test de MySQL:</h3>";
if (extension_loaded('mysqli')) {
    echo "✅ Extensión MySQLi está cargada<br>";
} else {
    echo "❌ Extensión MySQLi NO está cargada<br>";
}

// Test 3: Probar conexión a la base de datos
echo "<h3>Test de Conexión a Base de Datos:</h3>";
try {
    // CAMBIA ESTOS DATOS POR LOS REALES DE TU HOSTINGER:
    $host = 'localhost';
    $username = 'Arla';          // Tu usuario real
    $password = 'Mlara806*';    // Tu contraseña real
    $database = 'u228864460_assets_dragon'; // Tu BD real
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Conexión exitosa a la base de datos<br>";
        
        // Test 4: Verificar tabla users
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ Tabla 'users' encontrada. Total registros: " . $row['total'] . "<br>";
        } else {
            echo "❌ Error al consultar tabla users: " . $conn->error . "<br>";
        }
        
        $conn->close();
    }
    
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "<br>";
}

// Test 5: Verificar archivos importantes
echo "<h3>Test de Archivos:</h3>";
$archivos_importantes = [
    'db_connect.php',
    'login.php',
    'index.php'
];

foreach ($archivos_importantes as $archivo) {
    if (file_exists($archivo)) {
        echo "✅ $archivo existe<br>";
    } else {
        echo "❌ $archivo NO encontrado<br>";
    }
}

// Test 6: Mostrar contenido de db_connect.php (censurado)
echo "<h3>Contenido de db_connect.php:</h3>";
if (file_exists('db_connect.php')) {
    $contenido = file_get_contents('db_connect.php');
    // Censurar contraseñas por seguridad
    $contenido = preg_replace("/('[^']*password[^']*')/i", "'***CENSURADO***'", $contenido);
    echo "<pre>" . htmlspecialchars($contenido) . "</pre>";
} else {
    echo "❌ db_connect.php no encontrado<br>";
}

echo "<hr>";
echo "<p><strong>Instrucciones:</strong></p>";
echo "<p>1. Si ves errores aquí, cópialos y envíamelos</p>";
echo "<p>2. Si todo está ✅, el problema está en login.php</p>";
echo "<p>3. IMPORTANTE: Borra este archivo después de usarlo por seguridad</p>";
?>

<?php
// Test adicional: Probar include de db_connect.php
echo "<h3>Test de Include:</h3>";
try {
    include 'db_connect.php';
    echo "✅ db_connect.php incluido sin errores<br>";
} catch (Exception $e) {
    echo "❌ Error al incluir db_connect.php: " . $e->getMessage() . "<br>";
}
?>