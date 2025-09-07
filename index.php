<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

$article = new Article();

// Get homepage sections configuration
$db = new Database();
$conn = $db->getConnection();
$sections_stmt = $conn->query("SELECT * FROM homepage_sections WHERE is_enabled = true ORDER BY display_order");
$homepage_sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get content for each enabled section
$section_content = [];
foreach ($homepage_sections as $section) {
    switch ($section['section_name']) {
        case 'breaking':
            $section_content['breaking'] = $article->getBreakingNews($section['article_limit']);
            break;
        case 'featured':
            $section_content['featured'] = $article->getFeaturedArticles($section['article_limit']);
            break;
        case 'latest':
            $section_content['latest'] = $article->getLatestArticles($section['article_limit']);
            break;
        case 'trending':
            $section_content['trending'] = $article->getTrendingArticles($section['article_limit']);
            break;
    }
}

// Get categories for navigation and category sections
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories that should show on homepage
$homepage_categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_on_homepage = true ORDER BY homepage_priority");
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
                    <?php foreach ($homepage_sections as $section): ?>
                        <?php 
                        $section_articles = $section_content[$section['section_name']] ?? [];
                        if (!empty($section_articles)): 
                        ?>
                        <section class="<?php echo $section['section_name']; ?>-section mb-5">
                            <h3 class="section-title">
                                <?php if ($section['section_name'] === 'breaking'): ?>
                                    <i class="fas fa-bolt text-danger"></i>
                                <?php elseif ($section['section_name'] === 'trending'): ?>
                                    <i class="fas fa-fire text-warning"></i>
                                <?php elseif ($section['section_name'] === 'latest'): ?>
                                    <i class="fas fa-clock text-info"></i>
                                <?php elseif ($section['section_name'] === 'featured'): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($section['section_title']); ?>
                            </h3>
                            
                            <?php if ($section['layout_style'] === 'banner' && $section['section_name'] === 'breaking'): ?>
                                <!-- Breaking News Banner Layout -->
                                <div class="breaking-banner">
                                    <?php foreach (array_slice($section_articles, 0, 3) as $item): ?>
                                    <div class="breaking-item">
                                        <span class="breaking-label">BREAKING</span>
                                        <a href="article.php?slug=<?php echo $item['slug']; ?>" class="breaking-title">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                        <span class="breaking-time"><?php echo date('g:i A', strtotime($item['published_at'])); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            
                            <?php elseif ($section['layout_style'] === 'grid'): ?>
                                <!-- Grid Layout -->
                                <div class="row">
                                    <?php foreach ($section_articles as $index => $item): ?>
                                    <div class="col-md-<?php echo ($index === 0 && $section['section_name'] === 'featured') ? '8' : '6'; ?> mb-4">
                                        <article class="news-item">
                                            <?php if ($item['featured_image']): ?>
                                            <div class="article-image">
                                                <img src="<?php echo htmlspecialchars($item['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['featured_image_alt'] ?? $item['title']); ?>">
                                                <?php if ($item['is_breaking']): ?>
                                                <span class="breaking-badge">Breaking</span>
                                                <?php endif; ?>
                                                <?php if ($item['is_featured']): ?>
                                                <span class="featured-badge">Featured</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            <div class="article-content">
                                                <div class="article-meta">
                                                    <?php if ($item['category_name']): ?>
                                                    <span class="category" style="color: <?php echo $item['category_color']; ?>">
                                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <span class="date"><?php echo date('M j, Y', strtotime($item['published_at'] ?? $item['created_at'])); ?></span>
                                                    <?php if ($section['section_name'] === 'trending'): ?>
                                                    <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($item['views']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <h5><a href="article.php?slug=<?php echo $item['slug']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h5>
                                                <p><?php echo htmlspecialchars(substr($item['excerpt'] ?? $item['content'], 0, 150)) . '...'; ?></p>
                                                <div class="article-stats">
                                                    <span><i class="fas fa-eye"></i> <?php echo number_format($item['views']); ?></span>
                                                    <span><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($item['published_at'])); ?></span>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                    <?php if ($index === 0 && $section['section_name'] === 'featured'): ?>
                                    <div class="col-md-4">
                                        <?php foreach (array_slice($section_articles, 1, 3) as $featured): ?>
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
                                    <?php break; ?>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            
                            <?php elseif ($section['layout_style'] === 'list'): ?>
                                <!-- List Layout -->
                                <div class="list-layout">
                                    <?php foreach ($section_articles as $item): ?>
                                    <article class="list-item">
                                        <div class="row align-items-center">
                                            <?php if ($item['featured_image']): ?>
                                            <div class="col-md-3">
                                                <div class="article-image">
                                                    <img src="<?php echo htmlspecialchars($item['featured_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['featured_image_alt'] ?? $item['title']); ?>">
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="col-md-<?php echo $item['featured_image'] ? '9' : '12'; ?>">
                                                <div class="article-content">
                                                    <div class="article-meta">
                                                        <?php if ($item['category_name']): ?>
                                                        <span class="category" style="color: <?php echo $item['category_color']; ?>">
                                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                        <span class="date"><?php echo date('M j, Y', strtotime($item['published_at'] ?? $item['created_at'])); ?></span>
                                                        <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($item['views']); ?></span>
                                                    </div>
                                                    <h5><a href="article.php?slug=<?php echo $item['slug']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h5>
                                                    <p><?php echo htmlspecialchars(substr($item['excerpt'] ?? $item['content'], 0, 120)) . '...'; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Category Sections -->
                    <?php foreach ($homepage_categories as $category): ?>
                        <?php 
                        $category_articles = $article->getPublishedArticles(4, 0, $category['id']);
                        if (!empty($category_articles)): 
                        ?>
                        <section class="category-section mb-5">
                            <h3 class="section-title">
                                <span style="color: <?php echo $category['color']; ?>">
                                    <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category['name']); ?>
                                </span>
                                <a href="category.php?slug=<?php echo $category['slug']; ?>" class="view-all">View All</a>
                            </h3>
                            <div class="row">
                                <?php foreach ($category_articles as $category_article): ?>
                                <div class="col-md-6 mb-3">
                                    <article class="category-item">
                                        <?php if ($category_article['featured_image']): ?>
                                        <div class="article-image">
                                            <img src="<?php echo htmlspecialchars($category_article['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($category_article['featured_image_alt'] ?? $category_article['title']); ?>">
                                        </div>
                                        <?php endif; ?>
                                        <div class="article-content">
                                            <h6><a href="article.php?slug=<?php echo $category_article['slug']; ?>"><?php echo htmlspecialchars($category_article['title']); ?></a></h6>
                                            <div class="article-meta">
                                                <span class="date"><?php echo date('M j, Y', strtotime($category_article['published_at'] ?? $category_article['created_at'])); ?></span>
                                                <span class="views"><i class="fas fa-eye"></i> <?php echo number_format($category_article['views']); ?></span>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>
                    <?php endforeach; ?>
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