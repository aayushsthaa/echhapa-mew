<?php
// Authentication check for admin pages
require_once '../config/config.php';
require_once '../classes/User.php';

$user = new User();

// Check if user is logged in and is admin
if (!$user->isLoggedIn() || !$user->isAdmin()) {
    redirect('login.php');
}
?>