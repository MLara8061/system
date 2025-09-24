<?php
class DBConnection {

    public $conn;

    public function __construct() {
        // Detectar si estamos en local o producción
        $host = $_SERVER['HTTP_HOST'];

        if ($host === 'localhost') {
            // Configuración local (XAMPP)
            $this->conn = new mysqli('localhost', 'root', '', 'system');
        } else {
            // Configuración producción (Hostinger u otro hosting)
            $this->conn = new mysqli('localhost', 'u228864460_Arla', 'Mlara806*', 'u228864460_assets_dragon');
        }

        // Verificar conexión
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        // Ajustar modo SQL
        $this->conn->query("SET SESSION sql_mode=''");
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
