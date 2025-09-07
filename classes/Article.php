<?php
require_once __DIR__ . '/../config/config.php';

class Article {
    private $conn;
    private $table = 'articles';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!$this->conn) {
            throw new Exception("Database connection failed");
        }
    }

    public function getPublishedArticles($limit = 10, $offset = 0, $category_id = null) {
        $where_clause = "WHERE a.status = 'published' AND (a.published_at IS NULL OR a.published_at <= CURRENT_TIMESTAMP)";
        if ($category_id) {
            $where_clause .= " AND a.category_id = " . intval($category_id);
        }

        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                         u.first_name, u.last_name
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  $where_clause
                  ORDER BY a.is_breaking DESC, a.is_featured DESC, a.published_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeaturedArticles($limit = 5) {
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  WHERE a.status = 'published' AND a.is_featured = true
                  AND (a.published_at IS NULL OR a.published_at <= CURRENT_TIMESTAMP)
                  ORDER BY a.published_at DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticleBySlug($slug) {
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                         u.first_name, u.last_name
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  WHERE a.slug = ? AND a.status = 'published'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$slug]);
        
        if ($stmt->rowCount() > 0) {
            // Increment views
            $update_query = "UPDATE " . $this->table . " SET views = views + 1 WHERE slug = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->execute([$slug]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function getAllArticles($limit = 20, $offset = 0, $search = null, $status = null) {
        $where_conditions = [];
        $params = [];

        if ($search) {
            $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($status) {
            $where_conditions[] = "a.status = ?";
            $params[] = $status;
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $query = "SELECT a.*, c.name as category_name, u.first_name, u.last_name
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  $where_clause
                  ORDER BY a.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (title, slug, excerpt, content, featured_image, featured_image_alt, 
                   author_id, category_id, status, published_at, scheduled_at, 
                   meta_title, meta_description, meta_keywords, is_featured, is_breaking)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['slug'], $data['excerpt'], $data['content'],
            $data['featured_image'], $data['featured_image_alt'], $data['author_id'],
            $data['category_id'], $data['status'], $data['published_at'],
            $data['scheduled_at'], $data['meta_title'], $data['meta_description'],
            $data['meta_keywords'], $data['is_featured'], $data['is_breaking']
        ]);
    }

    public function getById($id) {
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                         u.first_name, u.last_name
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  WHERE a.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET title = ?, slug = ?, excerpt = ?, content = ?, 
                      featured_image = ?, featured_image_alt = ?, category_id = ?, 
                      status = ?, published_at = ?, scheduled_at = ?, 
                      meta_title = ?, meta_description = ?, meta_keywords = ?, 
                      is_featured = ?, is_breaking = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['title'], $data['slug'], $data['excerpt'], $data['content'],
            $data['featured_image'], $data['featured_image_alt'], $data['category_id'],
            $data['status'], $data['published_at'], $data['scheduled_at'],
            $data['meta_title'], $data['meta_description'], $data['meta_keywords'],
            $data['is_featured'], $data['is_breaking'], $id
        ]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getTrendingArticles($limit = 10) {
        // Calculate trending based on views and recency
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                         u.first_name, u.last_name,
                         (a.views * 0.7 + (EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - a.published_at)) / -86400) * 0.3) as trending_score
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  WHERE a.status = 'published' 
                  AND a.published_at >= CURRENT_TIMESTAMP - INTERVAL '7 days'
                  ORDER BY trending_score DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBreakingNews($limit = 5) {
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                         u.first_name, u.last_name
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  WHERE a.status = 'published' AND a.is_breaking = true
                  AND (a.published_at IS NULL OR a.published_at <= CURRENT_TIMESTAMP)
                  ORDER BY a.published_at DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestArticles($limit = 10) {
        $query = "SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                         u.first_name, u.last_name
                  FROM " . $this->table . " a
                  LEFT JOIN categories c ON a.category_id = c.id
                  LEFT JOIN users u ON a.author_id = u.id
                  WHERE a.status = 'published'
                  AND (a.published_at IS NULL OR a.published_at <= CURRENT_TIMESTAMP)
                  ORDER BY a.published_at DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>