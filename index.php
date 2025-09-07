<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

$article = new Article();

// Get published articles for homepage
$featured_articles = $article->getFeaturedArticles(5);
$recent_articles = $article->getPublishedArticles(10);

// Get categories
$db = new Database();
$conn = $db->getConnection();
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Latest News & Updates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="topbar">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="topbar-left">
                            <span class="date"><?php echo date('l, F j, Y'); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="topbar-right">
                            <div class="social-links">
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <h2><i class="fas fa-newspaper text-primary"></i> <?php echo SITE_NAME; ?></h2>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">Home</a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="category.php?slug=<?php echo $category['slug']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="navbar-nav">
                        <form class="d-flex" method="GET" action="search.php">
                            <div class="input-group">
                                <input class="form-control" type="search" name="q" placeholder="Search news...">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Main Column -->
                <div class="col-lg-8">
                    <!-- Featured Articles -->
                    <?php if (!empty($featured_articles)): ?>
                    <section class="featured-section mb-5">
                        <h3 class="section-title">Featured News</h3>
                        <div class="row">
                            <?php $first_featured = array_shift($featured_articles); ?>
                            <div class="col-md-8 mb-4">
                                <article class="featured-article">
                                    <?php if ($first_featured['featured_image']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo htmlspecialchars($first_featured['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($first_featured['featured_image_alt'] ?? $first_featured['title']); ?>">
                                        <?php if ($first_featured['is_breaking']): ?>
                                        <span class="breaking-badge">Breaking</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="article-content">
                                        <div class="article-meta">
                                            <span class="category" style="color: <?php echo $first_featured['category_color']; ?>">
                                                <?php echo htmlspecialchars($first_featured['category_name']); ?>
                                            </span>
                                            <span class="date"><?php echo date('M j, Y', strtotime($first_featured['published_at'] ?? $first_featured['created_at'])); ?></span>
                                        </div>
                                        <h2><a href="article.php?slug=<?php echo $first_featured['slug']; ?>"><?php echo htmlspecialchars($first_featured['title']); ?></a></h2>
                                        <p><?php echo htmlspecialchars(substr($first_featured['excerpt'] ?? $first_featured['content'], 0, 200)) . '...'; ?></p>
                                        <div class="article-stats">
                                            <span><i class="fas fa-eye"></i> <?php echo number_format($first_featured['views']); ?></span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                            
                            <div class="col-md-4">
                                <?php foreach ($featured_articles as $featured): ?>
                                <article class="featured-small mb-3">
                                    <?php if ($featured['featured_image']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo htmlspecialchars($featured['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($featured['featured_image_alt'] ?? $featured['title']); ?>">
                                    </div>
                                    <?php endif; ?>
                                    <div class="article-content">
                                        <h6><a href="article.php?slug=<?php echo $featured['slug']; ?>"><?php echo htmlspecialchars($featured['title']); ?></a></h6>
                                        <div class="article-meta">
                                            <span class="date"><?php echo date('M j', strtotime($featured['published_at'] ?? $featured['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Recent Articles -->
                    <section class="recent-section">
                        <h3 class="section-title">Latest News</h3>
                        <div class="row">
                            <?php foreach ($recent_articles as $article_item): ?>
                            <div class="col-md-6 mb-4">
                                <article class="news-item">
                                    <?php if ($article_item['featured_image']): ?>
                                    <div class="article-image">
                                        <img src="<?php echo htmlspecialchars($article_item['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($article_item['featured_image_alt'] ?? $article_item['title']); ?>">
                                        <?php if ($article_item['is_breaking']): ?>
                                        <span class="breaking-badge">Breaking</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="article-content">
                                        <div class="article-meta">
                                            <?php if ($article_item['category_name']): ?>
                                            <span class="category" style="color: <?php echo $article_item['category_color']; ?>">
                                                <?php echo htmlspecialchars($article_item['category_name']); ?>
                                            </span>
                                            <?php endif; ?>
                                            <span class="date"><?php echo date('M j, Y', strtotime($article_item['published_at'] ?? $article_item['created_at'])); ?></span>
                                        </div>
                                        <h5><a href="article.php?slug=<?php echo $article_item['slug']; ?>"><?php echo htmlspecialchars($article_item['title']); ?></a></h5>
                                        <p><?php echo htmlspecialchars(substr($article_item['excerpt'] ?? $article_item['content'], 0, 150)) . '...'; ?></p>
                                        <div class="article-stats">
                                            <span><i class="fas fa-eye"></i> <?php echo number_format($article_item['views']); ?></span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <?php if (empty($recent_articles) && empty($featured_articles)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                        <h4>No News Available</h4>
                        <p class="text-muted">News articles will appear here once they are published.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <aside class="sidebar">
                        <!-- Trending -->
                        <div class="widget">
                            <h4 class="widget-title">Trending Now</h4>
                            <div class="trending-list">
                                <!-- Trending articles would be populated here -->
                                <p class="text-muted">Trending articles will appear here.</p>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="widget">
                            <h4 class="widget-title">Categories</h4>
                            <div class="category-list">
                                <?php foreach ($categories as $category): ?>
                                <a href="category.php?slug=<?php echo $category['slug']; ?>" class="category-item">
                                    <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                    <span class="category-count">
                                        <?php
                                        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published'");
                                        $count_stmt->execute([$category['id']]);
                                        echo $count_stmt->fetchColumn();
                                        ?>
                                    </span>
                                </a>
                                <?php endforeach; ?>
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
                    <p>
                        <a href="admin/login.php">Admin Login</a> |
                        <a href="contact.php">Contact</a> |
                        <a href="privacy.php">Privacy</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>
</html>