<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Login</h2>";

// Test 1: Verificar sesión
echo "<h3>1. Test de Sesión</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "✓ Sesión iniciada<br>";
} else {
    echo "✓ Sesión ya estaba activa<br>";
}

// Test 2: Verificar conexión DB
echo "<h3>2. Test de Base de Datos</h3>";
try {
    require_once 'config/config.php';
    if ($conn) {
        echo "✓ Conexión a BD establecida<br>";
        echo "Base de datos: " . DB_NAME . "<br>";
    } else {
        echo "✗ Error de conexión<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Verificar tabla users
echo "<h3>3. Test de Tabla Users</h3>";
$test_query = $conn->query("SELECT COUNT(*) as total FROM users");
if ($test_query) {
    $result = $test_query->fetch_assoc();
    echo "✓ Tabla users existe - Total usuarios: " . $result['total'] . "<br>";
} else {
    echo "✗ Error consultando tabla users: " . $conn->error . "<br>";
}

// Test 4: Verificar estructura de password
echo "<h3>4. Test de Passwords</h3>";
$user_query = $conn->query("SELECT username, password, SUBSTRING(password, 1, 4) as pwd_start FROM users LIMIT 3");
if ($user_query) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Tipo Password</th></tr>";
    while ($row = $user_query->fetch_assoc()) {
        $tipo = (strpos($row['pwd_start'], '$2y$') === 0) ? "bcrypt" : "MD5";
        echo "<tr><td>" . htmlspecialchars($row['username']) . "</td><td>$tipo</td></tr>";
    }
    echo "</table>";
} else {
    echo "✗ Error: " . $conn->error . "<br>";
}

// Test 5: Simular login con usuario específico
echo "<h3>5. Test de Login (Ingresa datos)</h3>";
?>
<form method="POST" style="border: 1px solid #ccc; padding: 10px; max-width: 400px;">
    <div style="margin-bottom: 10px;">
        <label>Username:</label><br>
        <input type="text" name="test_username" style="width: 100%; padding: 5px;">
    </div>
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="password" name="test_password" style="width: 100%; padding: 5px;">
    </div>
    <button type="submit" name="test_login" style="padding: 5px 20px;">Probar Login</button>
</form>

<?php
if (isset($_POST['test_login'])) {
    echo "<h3>Resultado del Test:</h3>";
    
    $username = $_POST['test_username'];
    $password = $_POST['test_password'];
    
    echo "Username ingresado: " . htmlspecialchars($username) . "<br>";
    
    $username_escaped = $conn->real_escape_string($username);
    $query = "SELECT id, username, password, firstname, lastname FROM users WHERE username = '$username_escaped'";
    
    echo "Query: " . htmlspecialchars($query) . "<br><br>";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "✓ Usuario encontrado: " . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) . "<br>";
        
        // Verificar password
        $password_valid = false;
        if (strpos($user['password'], '$2y$') === 0) {
            $password_valid = password_verify($password, $user['password']);
            echo "Tipo: bcrypt - ";
        } else {
            $password_valid = ($user['password'] === md5($password));
            echo "Tipo: MD5 - ";
        }
        
        if ($password_valid) {
            echo "<strong style='color: green;'>✓ CONTRASEÑA CORRECTA</strong><br>";
            echo "Login sería exitoso (código 1)<br>";
        } else {
            echo "<strong style='color: red;'>✗ CONTRASEÑA INCORRECTA</strong><br>";
            echo "Login fallaría (código 3)<br>";
        }
    } else {
        echo "<strong style='color: red;'>✗ Usuario no encontrado</strong><br>";
        echo "Login fallaría (código 2)<br>";
        if ($result) {
            echo "Num rows: " . $result->num_rows . "<br>";
        } else {
            echo "Error en query: " . $conn->error . "<br>";
        }
    }
}
?>

<hr>
<p><strong>Nota:</strong> Este archivo es solo para diagnóstico. Bórralo después de identificar el problema.</p>
