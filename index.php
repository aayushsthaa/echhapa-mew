<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

$articleClass = new Article();

// Get different types of content for modern layout
$breaking_news = $articleClass->getBreakingNews(3);
$featured_articles = $articleClass->getFeaturedArticles(6);
$latest_articles = $articleClass->getLatestArticles(8);
$trending_articles = $articleClass->getTrendingArticles(5);

// Get categories for navigation
$db = new Database();
$conn = $db->getConnection();
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get homepage categories
$homepage_categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_on_homepage = true ORDER BY homepage_priority LIMIT 4");
$homepage_categories = $homepage_categories_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <!-- Modern Newspaper Header -->
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
                                    <i class="fas fa-search"></i>
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
                        <a href="index.php" class="category-link active">Home</a>
                        <?php foreach ($categories as $category): ?>
                        <a href="category.php?slug=<?php echo $category['slug']; ?>" class="category-link">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Breaking News Banner -->
    <?php if (!empty($breaking_news)): ?>
    <section class="breaking-news-banner">
        <div class="container-fluid">
            <div class="breaking-ticker">
                <span class="breaking-label">
                    <i class="fas fa-bolt"></i> BREAKING
                </span>
                <div class="breaking-content">
                    <?php foreach ($breaking_news as $index => $news): ?>
                        <a href="article.php?slug=<?php echo $news['slug']; ?>" class="breaking-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($news['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Hero Section -->
            <?php if (!empty($featured_articles)): ?>
            <section class="hero-section">
                <div class="row g-4">
                    <!-- Main Featured Article -->
                    <div class="col-lg-6">
                        <?php $main_featured = $featured_articles[0]; ?>
                        <article class="hero-article">
                            <?php if ($main_featured['featured_image']): ?>
                            <div class="hero-image">
                                <img src="<?php echo htmlspecialchars($main_featured['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($main_featured['title']); ?>" 
                                     class="img-fluid">
                                <div class="hero-overlay">
                                    <div class="hero-meta">
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($main_featured['category_name']); ?>
                                        </span>
                                        <span class="read-time">
                                            <i class="fas fa-clock"></i> 5 min read
                                        </span>
                                    </div>
                                    <h1 class="hero-title">
                                        <a href="article.php?slug=<?php echo $main_featured['slug']; ?>">
                                            <?php echo htmlspecialchars($main_featured['title']); ?>
                                        </a>
                                    </h1>
                                    <p class="hero-excerpt">
                                        <?php echo htmlspecialchars($main_featured['excerpt']); ?>
                                    </p>
                                    <div class="hero-author">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($main_featured['first_name'] . ' ' . $main_featured['last_name']); ?>
                                        <span class="mx-2">•</span>
                                        <?php echo date('M j, Y', strtotime($main_featured['published_at'] ?? $main_featured['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </article>
                    </div>
                    
                    <!-- Secondary Featured Articles -->
                    <div class="col-lg-6">
                        <div class="secondary-featured">
                            <?php foreach (array_slice($featured_articles, 1, 4) as $article): ?>
                            <article class="featured-item mb-3">
                                <div class="row g-3">
                                    <div class="<?php echo $article['featured_image'] ? 'col-8' : 'col-12'; ?>">
                                        <div class="featured-content">
                                            <div class="article-meta">
                                                <span class="category-tag">
                                                    <?php echo htmlspecialchars($article['category_name']); ?>
                                                </span>
                                                <span class="date"><?php echo date('M j', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                                            </div>
                                            <h3 class="featured-title">
                                                <a href="article.php?slug=<?php echo $article['slug']; ?>">
                                                    <?php echo htmlspecialchars($article['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="featured-excerpt">
                                                <?php 
                                                $excerpt = $article['excerpt'];
                                                echo htmlspecialchars(strlen($excerpt) > 80 ? substr($excerpt, 0, 80) . '...' : $excerpt); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ($article['featured_image']): ?>
                                    <div class="col-4">
                                        <div class="featured-image">
                                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                                 class="img-fluid">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Content Grid Section -->
            <section class="content-grid">
                <div class="row g-4">
                    <!-- Main Content Column -->
                    <div class="col-lg-8">
                        <!-- Latest News Section -->
                        <?php if (!empty($latest_articles)): ?>
                        <section class="latest-news-section">
                            <div class="section-header">
                                <h2 class="section-title">Latest News</h2>
                                <a href="category.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
                            </div>
                            
                            <div class="news-grid">
                                <?php foreach (array_slice($latest_articles, 0, 6) as $index => $article): ?>
                                <article class="news-card <?php echo $index === 0 ? 'featured-large' : ''; ?>">
                                    <?php if ($article['featured_image']): ?>
                                    <div class="news-image">
                                        <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                             class="img-fluid">
                                    </div>
                                    <?php endif; ?>
                                    <div class="news-content">
                                        <div class="news-meta">
                                            <span class="category-badge">
                                                <?php echo htmlspecialchars($article['category_name']); ?>
                                            </span>
                                            <span class="time-ago"><?php echo date('H:i', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                                        </div>
                                        <h3 class="news-title">
                                            <a href="article.php?slug=<?php echo $article['slug']; ?>">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                        </h3>
                                        <?php if ($index === 0): ?>
                                        <p class="news-excerpt">
                                            <?php echo htmlspecialchars(substr($article['excerpt'], 0, 150)); ?>...
                                        </p>
                                        <?php endif; ?>
                                        <div class="news-footer">
                                            <span class="author"><?php echo htmlspecialchars($article['first_name'] . ' ' . $article['last_name']); ?></span>
                                            <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?></span>
                                        </div>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <!-- Category Sections -->
                        <?php foreach ($homepage_categories as $category): ?>
                        <?php 
                        $category_articles = $articleClass->getPublishedArticles(4, 0, $category['id']);
                        if (!empty($category_articles)): 
                        ?>
                        <section class="category-section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h2>
                                <a href="category.php?slug=<?php echo $category['slug']; ?>" class="view-all-link">
                                    View All <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            
                            <div class="category-articles">
                                <?php foreach ($category_articles as $cat_article): ?>
                                <article class="category-item mb-4">
                                    <div class="row g-3">
                                        <div class="<?php echo $cat_article['featured_image'] ? 'col-md-8' : 'col-12'; ?>">
                                            <div class="category-content">
                                                <div class="article-meta">
                                                    <span class="date"><?php echo date('M j, Y', strtotime($cat_article['published_at'] ?? $cat_article['created_at'])); ?></span>
                                                    <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($cat_article['views']); ?></span>
                                                </div>
                                                <h3 class="category-title">
                                                    <a href="article.php?slug=<?php echo $cat_article['slug']; ?>">
                                                        <?php echo htmlspecialchars($cat_article['title']); ?>
                                                    </a>
                                                </h3>
                                                <p class="category-excerpt">
                                                    <?php 
                                                    $excerpt = $cat_article['excerpt'];
                                                    echo htmlspecialchars(strlen($excerpt) > 100 ? substr($excerpt, 0, 100) . '...' : $excerpt); 
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if ($cat_article['featured_image']): ?>
                                        <div class="col-md-4">
                                            <div class="category-image">
                                                <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                     class="img-fluid">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <aside class="main-sidebar">
                            <!-- Trending Section -->
                            <?php if (!empty($trending_articles)): ?>
                            <div class="sidebar-widget trending-widget">
                                <h3 class="widget-title">Trending Now</h3>
                                <div class="trending-list">
                                    <?php foreach ($trending_articles as $index => $trending): ?>
                                    <article class="trending-item">
                                        <span class="trending-number"><?php echo $index + 1; ?></span>
                                        <div class="trending-content">
                                            <h4 class="trending-title">
                                                <a href="article.php?slug=<?php echo $trending['slug']; ?>">
                                                    <?php echo htmlspecialchars($trending['title']); ?>
                                                </a>
                                            </h4>
                                            <div class="trending-meta">
                                                <span class="category">
                                                    <?php echo htmlspecialchars($trending['category_name']); ?>
                                                </span>
                                                <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($trending['views']); ?></span>
                                            </div>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Newsletter Signup -->
                            <div class="sidebar-widget newsletter-widget">
                                <h3 class="widget-title">Stay Updated</h3>
                                <p>Get the latest news delivered straight to your inbox.</p>
                                <form class="newsletter-form">
                                    <div class="input-group">
                                        <input type="email" class="form-control" placeholder="Your email address">
                                        <button class="btn btn-primary" type="submit">Subscribe</button>
                                    </div>
                                </form>
                            </div>

                            <!-- Popular Articles -->
                            <div class="sidebar-widget popular-widget">
                                <h3 class="widget-title">Most Read</h3>
                                <div class="popular-list">
                                    <?php 
                                    $popular_articles = $articleClass->getPopularArticles(5);
                                    foreach ($popular_articles as $popular): 
                                    ?>
                                    <article class="popular-item">
                                        <?php if ($popular['featured_image']): ?>
                                        <div class="popular-image">
                                            <img src="<?php echo htmlspecialchars($popular['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($popular['title']); ?>" 
                                                 class="img-fluid">
                                        </div>
                                        <?php endif; ?>
                                        <div class="popular-content">
                                            <h5 class="popular-title">
                                                <a href="article.php?slug=<?php echo $popular['slug']; ?>">
                                                    <?php echo htmlspecialchars($popular['title']); ?>
                                                </a>
                                            </h5>
                                            <div class="popular-meta">
                                                <span class="date"><?php echo date('M j', strtotime($popular['published_at'] ?? $popular['created_at'])); ?></span>
                                                <span class="views"><?php echo number_format($popular['views']); ?> views</span>
                                            </div>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Advertisement -->
                            <div class="sidebar-widget ad-widget">
                                <div class="ad-placeholder">
                                    <h6>Advertisement</h6>
                                    <p class="text-muted">Ad space available</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>
</html>