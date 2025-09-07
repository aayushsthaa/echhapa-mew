<?php
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if ($_FILES && isset($_FILES['image'])) {
    $upload_dir = '../public/images/articles/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Validate file type
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP allowed.']);
        exit;
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
        exit;
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $public_url = '/public/images/articles/' . $filename;
        echo json_encode([
            'success' => true, 
            'url' => $public_url,
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
?>