<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        $query = "SELECT id, username, email, password, first_name, last_name, role, status 
                  FROM " . $this->table . " 
                  WHERE (username = ? OR email = ?) AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username, $username]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                // Update last login
                $update_query = "UPDATE " . $this->table . " SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->execute([$user['id']]);
                
                return $user;
            }
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }

    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    public function logout() {
        session_destroy();
        redirect('../index.php');
    }
}
?>