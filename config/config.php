<?php
// Start session
session_start();

// Site configuration
define('SITE_NAME', 'Professional News Portal');
define('SITE_URL', $_SERVER['HTTP_HOST'] ?? 'localhost:5000');
define('ADMIN_EMAIL', 'admin@news.com');

// Upload configuration
define('UPLOAD_PATH', 'public/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Pagination
define('ARTICLES_PER_PAGE', 10);
define('ADMIN_ARTICLES_PER_PAGE', 20);

// Cache settings
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include database
require_once 'database.php';

// Initialize database with tables and sample data
$database = new Database();
$database->initializeDatabase();

// Utility functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}
?>