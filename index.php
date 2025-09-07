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
                        <section class="newspaper-section <?php echo $section['section_name']; ?>-section">
                            <h3 class="section-title">
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
                                        <article class="newspaper-article">
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
                                                    <span class="views"> <?php echo number_format($item['views']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <h5><a href="article.php?slug=<?php echo $item['slug']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h5>
                                                <p><?php echo htmlspecialchars(substr($item['excerpt'] ?? $item['content'], 0, 150)) . '...'; ?></p>
                                                <div class="article-stats">
                                                    <span> <?php echo number_format($item['views']); ?></span>
                                                    <span> <?php echo date('g:i A', strtotime($item['published_at'])); ?></span>
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
                                                        <span class="views"> <?php echo number_format($item['views']); ?></span>
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
                                                <span class="views"> <?php echo number_format($category_article['views']); ?></span>
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