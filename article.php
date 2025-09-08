<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

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
        SELECT a.*, c.name as category_name, c.slug as category_slug, c.color as category_color
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.category_id = ? AND a.slug != ? AND a.status = 'published'
        ORDER BY a.published_at DESC 
        LIMIT 5
    ");
    $related_stmt->execute([$article_data['category_id'], $slug]);
    $related_articles = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get categories for navigation
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article_data['meta_title'] ?: $article_data['title']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($article_data['meta_description'] ?: substr(strip_tags($article_data['content']), 0, 160)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($article_data['meta_keywords']); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($article_data['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($article_data['meta_description'] ?: substr(strip_tags($article_data['content']), 0, 160)); ?>">
    <?php if ($article_data['featured_image']): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($article_data['featured_image']); ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($article_data['title']); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($article_data['meta_description'] ?: substr(strip_tags($article_data['content']), 0, 160)); ?>">
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
    
    <!-- Newspaper Header -->
    <header class="newspaper-header">
        <!-- Top Header Section -->
        <div class="header-top">
            <div class="container-fluid">
                <div class="logo-section">
                    <a href="index.php" class="logo-link">
                        <img src="public/images/eechnapa-logo.png" alt="Eechnapa" class="newspaper-logo">
                    </a>
                    <div class="current-date">
                        <?php echo date('l, F j, Y'); ?>
                    </div>
                    <div class="header-search">
                        <form method="GET" action="search.php">
                            <div class="input-group">
                                <input class="form-control" type="search" name="q" placeholder="Search news...">
                                <button class="btn" type="submit">
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Categories Navigation -->
        <div class="categories-nav">
            <div class="container-fluid">
                <div class="categories-scroll-container">
                    <div class="categories-list">
                        <a href="index.php" class="category-link">Home</a>
                        <?php foreach ($categories as $category): ?>
                        <a href="category.php?slug=<?php echo $category['slug']; ?>" 
                           class="category-link <?php echo $category['id'] == $article_data['category_id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

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
                            <span class="badge" style="background-color: <?php echo $article_data['category_color']; ?>; font-size: 0.9rem; padding: 0.4rem 0.8rem;">
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
                            <?php echo $article_data['content']; ?>
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
    <section class="comments-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="comments-container">
                        <h4 class="comments-title">
                            <i class="fas fa-comments"></i> 
                            Comments 
                            <span class="comments-count" id="commentsCount">
                                <?php 
                                require_once 'classes/Comment.php';
                                $commentClass = new Comment();
                                $commentCount = $commentClass->getCommentCount($article['id']);
                                echo "($commentCount)";
                                ?>
                            </span>
                        </h4>
                        
                        <!-- Comment Form -->
                        <div class="comment-form-section">
                            <h5>Leave a Comment</h5>
                            <form id="commentForm" class="comment-form">
                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <input type="text" class="form-control" name="author_name" placeholder="Your Name *" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <input type="email" class="form-control" name="author_email" placeholder="Your Email *" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" placeholder="Write your comment here..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Post Comment
                                </button>
                            </form>
                        </div>
                        
                        <!-- Comments List -->
                        <div class="comments-list" id="commentsList">
                            <?php 
                            $comments = $commentClass->getCommentsByArticle($article['id']);
                            foreach ($comments as $comment): 
                            ?>
                            <div class="comment-item" data-comment-id="<?php echo $comment['id']; ?>">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <i class="fas fa-user-circle"></i>
                                        <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                                    </div>
                                    <div class="comment-date">
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                                <div class="comment-actions">
                                    <button class="btn btn-link btn-sm reply-btn" data-comment-id="<?php echo $comment['id']; ?>">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                </div>
                                
                                <!-- Replies -->
                                <?php if (!empty($comment['replies'])): ?>
                                <div class="comment-replies">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                    <div class="comment-item reply">
                                        <div class="comment-header">
                                            <div class="comment-author">
                                                <i class="fas fa-user-circle"></i>
                                                <strong><?php echo htmlspecialchars($reply['author_name']); ?></strong>
                                            </div>
                                            <div class="comment-date">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="comment-content">
                                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Reply Form (hidden by default) -->
                                <div class="reply-form" id="replyForm<?php echo $comment['id']; ?>" style="display: none;">
                                    <form class="comment-reply-form" data-parent-id="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                        <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" class="form-control" name="author_name" placeholder="Your Name *" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <input type="email" class="form-control" name="author_email" placeholder="Your Email *" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <textarea class="form-control" name="content" rows="3" placeholder="Write your reply..." required></textarea>
                                        </div>
                                        <div class="reply-actions">
                                            <button type="submit" class="btn btn-primary btn-sm">Post Reply</button>
                                            <button type="button" class="btn btn-secondary btn-sm cancel-reply">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
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
            const commentForm = document.getElementById('commentForm');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitComment(this);
                });
            }
            
            // Reply button handlers
            document.querySelectorAll('.reply-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const commentId = this.dataset.commentId;
                    toggleReplyForm(commentId);
                });
            });
            
            // Cancel reply buttons
            document.querySelectorAll('.cancel-reply').forEach(btn => {
                btn.addEventListener('click', function() {
                    const replyForm = this.closest('.reply-form');
                    replyForm.style.display = 'none';
                });
            });
            
            // Reply form submissions
            document.querySelectorAll('.comment-reply-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitComment(this);
                });
            });
        });
        
        function submitComment(form) {
            const formData = new FormData(form);
            
            fetch('submit-comment.php', {
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
                    
                    // Hide reply form if it's a reply
                    if (form.classList.contains('comment-reply-form')) {
                        form.closest('.reply-form').style.display = 'none';
                    }
                } else {
                    showMessage(data.message || 'Failed to submit comment. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
            });
        }
        
        function toggleReplyForm(commentId) {
            const replyForm = document.getElementById('replyForm' + commentId);
            if (replyForm.style.display === 'none' || !replyForm.style.display) {
                replyForm.style.display = 'block';
                replyForm.querySelector('input[name="author_name"]').focus();
            } else {
                replyForm.style.display = 'none';
            }
        }
        
        function showMessage(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const commentsSection = document.querySelector('.comments-section');
            commentsSection.insertBefore(alert, commentsSection.firstChild);
            
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