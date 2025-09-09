<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

// Content rendering function for JSON content blocks
function renderContent($content) {
    // If content is already HTML (old format), return as is
    if (!isJsonString($content)) {
        return $content;
    }
    
    $blocks = json_decode($content, true);
    if (!$blocks || !is_array($blocks)) {
        return '<p>No content available.</p>';
    }
    
    $html = '';
    foreach ($blocks as $block) {
        switch ($block['type']) {
            case 'text':
                $html .= '<div class="content-block text-block">';
                $html .= $block['content'] ?? '';
                $html .= '</div>';
                break;
                
            case 'image':
                $html .= '<div class="content-block image-block" style="text-align: ' . ($block['alignment'] ?? 'center') . '; margin: 2rem 0;">';
                if (!empty($block['link'])) {
                    $html .= '<a href="' . htmlspecialchars($block['link']) . '" target="_blank">';
                }
                $html .= '<img src="' . htmlspecialchars($block['url']) . '" ';
                $html .= 'alt="' . htmlspecialchars($block['alt'] ?? '') . '" ';
                $html .= 'class="img-fluid" style="max-width: 100%; height: auto; border-radius: 8px;">';
                if (!empty($block['link'])) {
                    $html .= '</a>';
                }
                if (!empty($block['caption'])) {
                    $html .= '<p class="image-caption text-muted mt-2" style="font-style: italic; font-size: 0.9rem;">';
                    $html .= htmlspecialchars($block['caption']);
                    $html .= '</p>';
                }
                $html .= '</div>';
                break;
                
            case 'video':
                $html .= '<div class="content-block video-block" style="text-align: ' . ($block['alignment'] ?? 'center') . '; margin: 2rem 0;">';
                if (!empty($block['embed_code'])) {
                    $html .= $block['embed_code'];
                } else if (!empty($block['url'])) {
                    // Handle YouTube, Vimeo, etc.
                    $html .= '<div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">';
                    $html .= '<iframe src="' . htmlspecialchars($block['url']) . '" ';
                    $html .= 'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" ';
                    $html .= 'frameborder="0" allowfullscreen></iframe>';
                    $html .= '</div>';
                }
                if (!empty($block['caption'])) {
                    $html .= '<p class="video-caption text-muted mt-2" style="font-style: italic; font-size: 0.9rem;">';
                    $html .= htmlspecialchars($block['caption']);
                    $html .= '</p>';
                }
                $html .= '</div>';
                break;
                
            case 'quote':
                $html .= '<blockquote class="content-block quote-block" style="border-left: 4px solid var(--primary-color); padding-left: 1.5rem; margin: 2rem 0; font-style: italic; font-size: 1.2rem; color: #555;">';
                $html .= '<p>' . nl2br(htmlspecialchars($block['content'] ?? '')) . '</p>';
                if (!empty($block['author'])) {
                    $html .= '<footer class="blockquote-footer mt-2">';
                    $html .= '<cite>' . htmlspecialchars($block['author']) . '</cite>';
                    $html .= '</footer>';
                }
                $html .= '</blockquote>';
                break;
        }
    }
    
    return $html;
}

function isJsonString($string) {
    if (!is_string($string)) return false;
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

$slug = sanitize($_GET['slug'] ?? '');

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

$article = new Article();
$article_data = $article->getArticleBySlug($slug);

if (!$article_data) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Get related articles from same category
$related_articles = [];
if ($article_data['category_id']) {
    $db = new Database();
    $conn = $db->getConnection();
    $related_stmt = $conn->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.category_id = ? AND a.slug != ? AND a.status = 'published'
        ORDER BY a.published_at DESC 
        LIMIT 5
    ");
    $related_stmt->execute([$article_data['category_id'], $slug]);
    $related_articles = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Categories will be loaded in header include
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article_data['title']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($article_data['excerpt'] ?: substr(strip_tags($article_data['content']), 0, 160)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($article_data['category_name'] ?: ''); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($article_data['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($article_data['excerpt'] ?: substr(strip_tags($article_data['content']), 0, 160)); ?>">
    <?php if ($article_data['featured_image']): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($article_data['featured_image']); ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($article_data['title']); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($article_data['excerpt'] ?: substr(strip_tags($article_data['content']), 0, 160)); ?>">
    <?php if ($article_data['featured_image']): ?>
    <meta property="twitter:image" content="<?php echo htmlspecialchars($article_data['featured_image']); ?>">
    <?php endif; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    
    <style>
        .article-header {
            background: var(--light-color);
            padding: 3rem 0;
        }
        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-dark);
        }
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .article-content p {
            margin-bottom: 1.5rem;
        }
        .article-content h2,
        .article-content h3,
        .article-content h4 {
            margin: 2rem 0 1rem 0;
            color: var(--text-dark);
        }
        .share-buttons {
            position: sticky;
            top: 100px;
        }
        .share-btn {
            display: block;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: white;
            text-align: center;
            line-height: 50px;
            margin-bottom: 0.5rem;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        .share-btn:hover {
            transform: scale(1.1);
            color: white;
        }
        .share-facebook { background: #3b5998; }
        .share-twitter { background: #1da1f2; }
        .share-linkedin { background: #0077b5; }
        .share-whatsapp { background: #25d366; }
        
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 1050;
            transition: width 0.1s ease;
        }
    </style>
</head>
<body>
    <div class="reading-progress"></div>
    
    <?php include 'includes/header.php'; ?>
    
    <!-- Article Header -->
    <section class="article-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <?php if ($article_data['is_breaking']): ?>
                        <span class="badge bg-danger mb-3" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-bolt"></i> Breaking News
                        </span>
                    <?php endif; ?>
                    
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($article_data['title']); ?></h1>
                    
                    <?php if ($article_data['excerpt']): ?>
                        <p class="lead text-muted mb-4"><?php echo htmlspecialchars($article_data['excerpt']); ?></p>
                    <?php endif; ?>
                    
                    <div class="article-meta d-flex justify-content-center align-items-center flex-wrap gap-4">
                        <?php if ($article_data['category_name']): ?>
                            <span class="badge bg-secondary category-badge">
                                <?php echo htmlspecialchars($article_data['category_name']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="text-muted">
                            <i class="fas fa-user"></i>
                            By <?php echo htmlspecialchars($article_data['first_name'] . ' ' . $article_data['last_name']); ?>
                        </span>
                        
                        <span class="text-muted">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('F j, Y', strtotime($article_data['published_at'] ?? $article_data['created_at'])); ?>
                        </span>
                        
                        <span class="text-muted">
                            <i class="fas fa-eye"></i>
                            <?php echo number_format($article_data['views']); ?> views
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="row">
                <!-- Article Content -->
                <div class="col-lg-8">
                    <article>
                        <?php if ($article_data['featured_image']): ?>
                            <div class="featured-image mb-4">
                                <img src="<?php echo htmlspecialchars($article_data['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($article_data['featured_image_alt'] ?: $article_data['title']); ?>"
                                     class="img-fluid rounded">
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-content">
                            <?php echo renderContent($article_data['content']); ?>
                        </div>
                        
                        <!-- Social Sharing -->
                        <div class="row mt-5 py-4 border-top">
                            <div class="col-12">
                                <h6 class="mb-3">Share this article:</h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="#" class="btn btn-outline-primary btn-sm share-btn" 
                                       data-platform="facebook" data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" 
                                       data-title="<?php echo htmlspecialchars($article_data['title']); ?>">
                                        <i class="fab fa-facebook-f"></i> Facebook
                                    </a>
                                    <a href="#" class="btn btn-outline-info btn-sm share-btn" 
                                       data-platform="twitter" data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" 
                                       data-title="<?php echo htmlspecialchars($article_data['title']); ?>">
                                        <i class="fab fa-twitter"></i> Twitter
                                    </a>
                                    <a href="#" class="btn btn-outline-primary btn-sm share-btn" 
                                       data-platform="linkedin" data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" 
                                       data-title="<?php echo htmlspecialchars($article_data['title']); ?>">
                                        <i class="fab fa-linkedin"></i> LinkedIn
                                    </a>
                                    <a href="#" class="btn btn-outline-success btn-sm share-btn" 
                                       data-platform="whatsapp" data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" 
                                       data-title="<?php echo htmlspecialchars($article_data['title']); ?>">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Related Articles -->
                    <?php if (!empty($related_articles)): ?>
                    <section class="related-articles mt-5">
                        <h4 class="section-title">Related Articles</h4>
                        <div class="row">
                            <?php foreach ($related_articles as $related): ?>
                            <div class="col-md-6 mb-3">
                                <article class="news-item">
                                    <?php if ($related['featured_image']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['title']); ?>">
                                    </div>
                                    <?php endif; ?>
                                    <div class="article-content">
                                        <div class="article-meta">
                                            <span class="date"><?php echo date('M j', strtotime($related['published_at'] ?? $related['created_at'])); ?></span>
                                        </div>
                                        <h6><a href="article.php?slug=<?php echo $related['slug']; ?>"><?php echo htmlspecialchars($related['title']); ?></a></h6>
                                    </div>
                                </article>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <aside class="sidebar">
                        <!-- Floating Share Buttons -->
                        <div class="share-buttons d-none d-lg-block">
                            <a href="#" class="share-btn share-facebook" data-platform="facebook" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="share-btn share-twitter" data-platform="twitter" title="Share on Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="share-btn share-linkedin" data-platform="linkedin" title="Share on LinkedIn">
                                <i class="fab fa-linkedin"></i>
                            </a>
                            <a href="#" class="share-btn share-whatsapp" data-platform="whatsapp" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>

                        <!-- Popular Articles -->
                        <div class="widget">
                            <h4 class="widget-title">Popular Articles</h4>
                            <div class="popular-articles">
                                <p class="text-muted">Popular articles will appear here.</p>
                            </div>
                        </div>

                        <!-- Advertisement -->
                        <div class="widget">
                            <div class="ad-placeholder">
                                <h6>Advertisement</h6>
                                <p class="text-muted">Ad space available</p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p><a href="admin/login.php">Admin Login</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Comments Section -->
    <section class="comments-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="comments-container">
                        <h4 class="comments-title mb-4">
                            <i class="fas fa-comments"></i>
                            <span>Comments</span>
                        </h4>
                        
                        <!-- Comment Form -->
                        <div class="comment-form-container mb-5">
                            <h5>Leave a Comment</h5>
                            <form action="submit-comment.php" method="POST" class="comment-form">
                                <input type="hidden" name="article_id" value="<?php echo $article_data['id']; ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" name="author_name" placeholder="Your Name *" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" class="form-control" name="author_email" placeholder="Your Email *" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" placeholder="Write your comment..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Post Comment
                                </button>
                            </form>
                        </div>
                        
                        <!-- Comments List -->
                        <div class="comments-list">
                            <p class="text-muted">Comments system is ready for implementation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Comments functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Comment form submission
            const commentForm = document.querySelector('.comment-form');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitComment(this);
                });
            }
        });
        
        function submitComment(form) {
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage('Comment submitted successfully! It will appear after moderation.', 'success');
                    
                    // Reset form
                    form.reset();
                } else {
                    showMessage(data.message || 'Error submitting comment. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
            });
        }
        
        function showMessage(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show mt-3`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.comment-form-container');
            if (container) {
                container.appendChild(alert);
            }
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>