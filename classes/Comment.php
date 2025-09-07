<?php
require_once __DIR__ . '/../config/config.php';

class Comment {
    private $conn;
    private $table = 'comments';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!$this->conn) {
            throw new Exception("Database connection failed");
        }
    }

    public function getCommentsByArticle($article_id, $status = 'approved') {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE article_id = ? AND status = ? AND parent_id IS NULL
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$article_id, $status]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get replies for each comment
        foreach ($comments as &$comment) {
            $comment['replies'] = $this->getReplies($comment['id'], $status);
        }
        
        return $comments;
    }

    public function getReplies($parent_id, $status = 'approved') {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE parent_id = ? AND status = ?
                  ORDER BY created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$parent_id, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (article_id, author_name, author_email, content, parent_id, status)
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['article_id'],
            $data['author_name'],
            $data['author_email'],
            $data['content'],
            $data['parent_id'] ?? null,
            $data['status'] ?? 'pending'
        ]);
    }

    public function getCommentCount($article_id) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " 
                  WHERE article_id = ? AND status = 'approved'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$article_id]);
        return $stmt->fetchColumn();
    }

    public function getAllComments($limit = 50, $offset = 0, $status = null) {
        $where_clause = $status ? "WHERE status = ?" : "";
        $query = "SELECT c.*, a.title as article_title, a.slug as article_slug
                  FROM " . $this->table . " c
                  LEFT JOIN articles a ON c.article_id = a.id
                  $where_clause
                  ORDER BY c.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params = $status ? [$status, $limit, $offset] : [$limit, $offset];
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $id]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
?>