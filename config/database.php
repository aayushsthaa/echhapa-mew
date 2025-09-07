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
        // First try to use DATABASE_URL from Replit
        if (isset($_SERVER['DATABASE_URL'])) {
            $url = parse_url($_SERVER['DATABASE_URL']);
            $this->host = $url['host'];
            $this->db_name = ltrim($url['path'], '/');
            $this->username = $url['user'];
            $this->password = $url['pass'];
            $this->port = $url['port'];
        } else {
            // Fallback to individual environment variables with Replit defaults
            $this->host = $_SERVER['PGHOST'] ?? getenv('PGHOST') ?: 'ep-dawn-pond-af0j98yz.c-2.us-west-2.aws.neon.tech';
            $this->db_name = $_SERVER['PGDATABASE'] ?? getenv('PGDATABASE') ?: 'neondb';
            $this->username = $_SERVER['PGUSER'] ?? getenv('PGUSER') ?: 'neondb_owner';
            $this->password = $_SERVER['PGPASSWORD'] ?? getenv('PGPASSWORD') ?: 'npg_OwYltEd9j3kL';
            $this->port = $_SERVER['PGPORT'] ?? getenv('PGPORT') ?: '5432';
        }
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