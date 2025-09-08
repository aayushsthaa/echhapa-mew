<?php
// Quick Article Creation Helper
// This file provides utility functions to make article creation with images easier

require_once '../config/config.php';

class QuickArticleHelper {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Get random image URL from preset for a category
     */
    public function getRandomImageForCategory($categoryId) {
        $stmt = $this->conn->prepare("SELECT image_urls FROM image_presets WHERE category_id = ? AND is_active = true");
        $stmt->execute([$categoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['image_urls'])) {
            // Parse PostgreSQL array
            $imageUrls = explode(',', trim($result['image_urls'], '{}'));
            $randomImage = $imageUrls[array_rand($imageUrls)];
            return 'https://images.pexels.com/photos/' . trim($randomImage, '"');
        }
        
        // Fallback to default image
        return 'https://images.pexels.com/photos/28468503/pexels-photo-28468503.jpeg';
    }
    
    /**
     * Create article using template
     */
    public function createArticleFromTemplate($templateId, $customData = []) {
        // Get template
        $stmt = $this->conn->prepare("SELECT * FROM article_templates WHERE id = ? AND is_active = true");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            return false;
        }
        
        // Generate content from template
        $title = $customData['title'] ?? 'New Article from ' . $template['template_name'];
        $slug = generateSlug($title);
        $categoryId = $customData['category_id'] ?? $template['default_category_id'];
        $excerpt = $customData['excerpt'] ?? $template['excerpt_template'];
        $content = $customData['content'] ?? $template['content_structure'];
        $featuredImage = $customData['featured_image'] ?? $this->getRandomImageForCategory($categoryId);
        
        // Insert article
        $stmt = $this->conn->prepare("
            INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, status, featured_image, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'published', ?, NOW()) 
            RETURNING id
        ");
        
        $stmt->execute([
            $title, $slug, $excerpt, $content, $categoryId, 
            $customData['author_id'] ?? 1, $featuredImage
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    /**
     * Quick article creation with automatic image assignment
     */
    public function quickCreateArticle($title, $excerpt, $content, $categoryId, $authorId = 1, $isBreaking = false, $isFeatured = false) {
        $slug = generateSlug($title);
        $featuredImage = $this->getRandomImageForCategory($categoryId);
        
        $stmt = $this->conn->prepare("
            INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, status, featured_image, is_breaking, is_featured, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'published', ?, ?, ?, NOW()) 
            RETURNING id
        ");
        
        $stmt->execute([
            $title, $slug, $excerpt, $content, $categoryId, $authorId, 
            $featuredImage, $isBreaking ? 't' : 'f', $isFeatured ? 't' : 'f'
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    /**
     * Get all available templates
     */
    public function getTemplates() {
        $stmt = $this->conn->query("SELECT * FROM article_templates WHERE is_active = true ORDER BY template_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available images for a category
     */
    public function getImagesForCategory($categoryId) {
        $stmt = $this->conn->prepare("SELECT image_urls FROM image_presets WHERE category_id = ? AND is_active = true");
        $stmt->execute([$categoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['image_urls'])) {
            $imageUrls = explode(',', trim($result['image_urls'], '{}'));
            return array_map(function($url) {
                return 'https://images.pexels.com/photos/' . trim($url, '"');
            }, $imageUrls);
        }
        
        return ['https://images.pexels.com/photos/28468503/pexels-photo-28468503.jpeg'];
    }
}

// Example usage (commented out - uncomment to test):
/*
$helper = new QuickArticleHelper();

// Quick article creation
$articleId = $helper->quickCreateArticle(
    "Test Article with Auto Image", 
    "This is a test article with automatically assigned image based on category.",
    "This is the full content of the test article. It includes detailed information and uses the category-appropriate image automatically.",
    1, // Politics category
    1, // Author ID
    false, // Not breaking
    true   // Featured
);

echo "Created article ID: " . $articleId;
*/
?>