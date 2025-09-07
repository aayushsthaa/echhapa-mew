<?php
// Database configuration
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host = $_SERVER['PGHOST'] ?? 'helium';
        $this->db_name = $_SERVER['PGDATABASE'] ?? 'heliumdb';
        $this->username = $_SERVER['PGUSER'] ?? 'postgres';
        $this->password = $_SERVER['PGPASSWORD'] ?? 'password';
        $this->port = $_SERVER['PGPORT'] ?? '5432';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>