<?php
require_once 'config/config.php';
require_once 'classes/Comment.php';

header('Content-Type: application/json');

if ($_POST) {
    $article_id = intval($_POST['article_id'] ?? 0);
    $author_name = sanitize($_POST['author_name'] ?? '');
    $author_email = sanitize($_POST['author_email'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $parent_id = intval($_POST['parent_id'] ?? 0) ?: null;

    // Basic validation
    if (empty($author_name) || empty($author_email) || empty($content) || $article_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Validate email
    if (!filter_var($author_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    // Check if article exists
    $db = new Database();
    $conn = $db->getConnection();
    $article_check = $conn->prepare("SELECT id FROM articles WHERE id = ? AND status = 'published'");
    $article_check->execute([$article_id]);
    
    if ($article_check->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Article not found']);
        exit;
    }

    // Create comment
    $comment_data = [
        'article_id' => $article_id,
        'author_name' => $author_name,
        'author_email' => $author_email,
        'content' => $content,
        'parent_id' => $parent_id,
        'status' => 'pending' // Comments require moderation
    ];

    $comment = new Comment();
    if ($comment->create($comment_data)) {
        echo json_encode(['success' => true, 'message' => 'Comment submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit comment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>