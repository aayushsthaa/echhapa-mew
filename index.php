<?php
require_once 'config/config.php';
require_once 'classes/Article.php';
require_once 'includes/ads_helper.php';

$articleClass = new Article();

// Get layout settings from database
$db = new Database();
$conn = $db->getConnection();

// Fetch layout settings
$layout_settings = [];
$settings_stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($setting = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
    $layout_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Set defaults if settings don't exist
$main_layout = $layout_settings['main_layout'] ?? 'mixed';
$sidebar_layout = $layout_settings['sidebar_layout'] ?? 'widgets';
$articles_per_section = (int)($layout_settings['articles_per_section'] ?? 5);
$show_breaking_news = (bool)($layout_settings['show_breaking_news'] ?? true);
$show_trending = (bool)($layout_settings['show_trending'] ?? true);

// Get different types of content based on layout settings
$breaking_news = $show_breaking_news ? $articleClass->getBreakingNews(3) : [];
$featured_articles = $articleClass->getFeaturedArticles(6);
$latest_articles = $articleClass->getLatestArticles($articles_per_section + 3);
$trending_articles = $show_trending ? $articleClass->getTrendingArticles(5) : [];

// Get categories for navigation
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for homepage display with layout settings
$homepage_categories_stmt = $conn->query("
    SELECT c.*, 
           COALESCE(cds.layout_style, 'list') as layout_style,
           COALESCE(cds.show_on_homepage, true) as show_on_homepage,
           COALESCE(cds.display_order, c.display_order) as display_order,
           COALESCE(cds.articles_limit, 4) as articles_limit,
           COALESCE(cds.show_excerpts, true) as show_excerpts,
           COALESCE(cds.show_images, true) as show_images
    FROM categories c
    LEFT JOIN category_display_settings cds ON c.id = cds.category_id
    WHERE c.status = 'active' AND COALESCE(cds.show_on_homepage, true) = true
    ORDER BY COALESCE(cds.display_order, c.display_order)
");
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
                        <?php 
                        // Get categories with subcategories for dropdown navigation
                        $nav_categories = [];
                        foreach ($categories as $category) {
                            if (!isset($nav_categories[$category['id']])) {
                                $nav_categories[$category['id']] = $category;
                                $nav_categories[$category['id']]['subcategories'] = [];
                            }
                        }
                        
                        // Get subcategories
                        $subcategories_stmt = $conn->query("SELECT * FROM categories WHERE parent_id IS NOT NULL AND show_in_menu = true ORDER BY parent_id, display_order");
                        $subcategories = $subcategories_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($subcategories as $subcat) {
                            if (isset($nav_categories[$subcat['parent_id']])) {
                                $nav_categories[$subcat['parent_id']]['subcategories'][] = $subcat;
                            }
                        }
                        ?>
                        
                        <?php foreach ($nav_categories as $category): ?>
                        <?php if (!empty($category['subcategories'])): ?>
                        <div class="category-dropdown">
                            <a href="category.php?slug=<?php echo $category['slug']; ?>" class="category-link dropdown-toggle" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <i class="fas fa-chevron-down ms-1"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="category.php?slug=<?php echo $category['slug']; ?>">All <?php echo htmlspecialchars($category['name']); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php foreach ($category['subcategories'] as $subcat): ?>
                                <li><a class="dropdown-item" href="category.php?slug=<?php echo $subcat['slug']; ?>"><?php echo htmlspecialchars($subcat['name']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                        <a href="category.php?slug=<?php echo $category['slug']; ?>" class="category-link">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endif; ?>
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
    <main class="main-content" data-layout="<?php echo $main_layout; ?>">
        <div class="container-fluid">
            <?php if ($main_layout === 'mixed'): ?>
            <!-- Mixed Layout - Hero Section with Featured Articles -->
            <?php if (!empty($featured_articles)): ?>
            <section class="hero-section mb-5">
                <div class="row g-4">
                    <!-- Main Featured Article - NEWS STYLE -->
                    <div class="col-lg-6">
                        <?php $main_featured = $featured_articles[0]; ?>
                        <article class="hero-article mb-4">
                            <?php if ($main_featured['featured_image']): ?>
                            <div class="hero-image">
                                <img src="<?php echo htmlspecialchars($main_featured['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($main_featured['title']); ?>" 
                                     class="img-fluid">
                            </div>
                            <?php endif; ?>
                            <div class="hero-content">
                                        <div class="hero-meta mb-2">
                                            <span class="category-badge">
                                                <?php echo htmlspecialchars($main_featured['category_name']); ?>
                                            </span>
                                            <span class="read-time ms-2">
                                                <i class="fas fa-clock"></i> 5 min read
                                            </span>
                                        </div>
                                        <h1 class="hero-title">
                                            <a href="article.php?slug=<?php echo $main_featured['slug']; ?>">
                                                <?php echo htmlspecialchars($main_featured['title']); ?>
                                            </a>
                                        </h1>
                                        <p class="hero-excerpt">
                                            <?php 
                                            $excerpt = $main_featured['excerpt'];
                                            echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt); 
                                            ?>
                                        </p>
                                        <div class="hero-author">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($main_featured['first_name'] . ' ' . $main_featured['last_name']); ?>
                                            <span class="mx-2">•</span>
                                            <?php echo date('M j, Y', strtotime($main_featured['published_at'] ?? $main_featured['created_at'])); ?>
                                        </div>
                            </div>
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
            
            <?php elseif ($main_layout === 'clean'): ?>
            <!-- Clean Layout - Simple List View -->
            
            <?php elseif ($main_layout === 'magazine'): ?>
            <!-- Magazine Layout - Grid Style -->
            <?php if (!empty($featured_articles)): ?>
            <section class="magazine-hero mb-5">
                <div class="row g-4">
                    <?php foreach (array_slice($featured_articles, 0, 3) as $index => $article): ?>
                    <div class="col-lg-4">
                        <article class="magazine-card">
                            <?php if ($article['featured_image']): ?>
                            <div class="magazine-image">
                                <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                     class="img-fluid">
                            </div>
                            <?php endif; ?>
                            <div class="magazine-content p-3">
                                <span class="category-badge mb-2"><?php echo htmlspecialchars($article['category_name']); ?></span>
                                <h3 class="magazine-title">
                                    <a href="article.php?slug=<?php echo $article['slug']; ?>">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h3>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- Default Layout -->
            
            <?php endif; ?>

            <!-- Content Grid Section -->
            <section class="content-grid">
                <div class="row g-4">
                    <!-- Main Content Column -->
                    <div class="<?php echo $sidebar_layout === 'none' ? 'col-12' : 'col-lg-9'; ?>">
                        <!-- Latest News Section -->
                        <?php if (!empty($latest_articles) && ($main_layout === 'mixed' || $main_layout === 'clean')): ?>
                        <section class="latest-news-section">
                            <div class="section-header">
                                <h2 class="section-title">Latest News</h2>
                                <a href="category.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
                            </div>
                            
                            <div class="news-grid">
                                <?php foreach (array_slice($latest_articles, 0, $articles_per_section + 2) as $index => $article): ?>
                                <article class="news-card mb-4 <?php echo $index === 0 ? 'featured-large' : ''; ?>">
                                    <div class="row g-3">
                                        <div class="<?php echo $article['featured_image'] ? 'col-8' : 'col-12'; ?>">
                                            <div class="news-content">
                                                <div class="news-meta mb-2">
                                                    <span class="category-badge">
                                                        <?php echo htmlspecialchars($article['category_name']); ?>
                                                    </span>
                                                    <span class="time-ago ms-2"><?php echo date('H:i', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                                                </div>
                                                <h3 class="news-title">
                                                    <a href="article.php?slug=<?php echo $article['slug']; ?>">
                                                        <?php echo htmlspecialchars($article['title']); ?>
                                                    </a>
                                                </h3>
                                                <p class="news-excerpt">
                                                    <?php 
                                                    $excerpt = $article['excerpt'];
                                                    $length = $index === 0 ? 120 : 80;
                                                    echo htmlspecialchars(strlen($excerpt) > $length ? substr($excerpt, 0, $length) . '...' : $excerpt); 
                                                    ?>
                                                </p>
                                                <div class="news-footer">
                                                    <span class="author"><?php echo htmlspecialchars($article['first_name'] . ' ' . $article['last_name']); ?></span>
                                                    <span class="views ms-3"><i class="fas fa-eye"></i> <?php echo number_format($article['views']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($article['featured_image']): ?>
                                        <div class="col-4">
                                            <div class="news-image">
                                                <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                                     class="img-fluid rounded">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <!-- Category Sections -->
                        <?php foreach ($homepage_categories as $category): ?>
                        <?php 
                        $category_articles = $articleClass->getPublishedArticles($category['articles_limit'], 0, $category['id']);
                        if (!empty($category_articles)): 
                        ?>
                        <section class="category-section" data-layout="<?php echo $category['layout_style']; ?>">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h2>
                                <a href="category.php?slug=<?php echo $category['slug']; ?>" class="view-all-link">
                                    View All <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            
                            <?php if ($category['layout_style'] === 'grid'): ?>
                            <!-- Grid Layout -->
                            <div class="row g-4">
                                <?php foreach ($category_articles as $cat_article): ?>
                                <div class="col-md-6 col-lg-4">
                                    <article class="news-card h-100">
                                        <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
                                        <div class="news-image">
                                            <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                 class="img-fluid">
                                        </div>
                                        <?php endif; ?>
                                        <div class="news-content p-3">
                                            <div class="news-meta mb-2">
                                                <span class="date"><?php echo date('M j', strtotime($cat_article['published_at'] ?? $cat_article['created_at'])); ?></span>
                                            </div>
                                            <h3 class="news-title h5">
                                                <a href="article.php?slug=<?php echo $cat_article['slug']; ?>">
                                                    <?php echo htmlspecialchars($cat_article['title']); ?>
                                                </a>
                                            </h3>
                                            <?php if ($category['show_excerpts']): ?>
                                            <p class="news-excerpt small">
                                                <?php 
                                                $excerpt = $cat_article['excerpt'];
                                                echo htmlspecialchars(strlen($excerpt) > 80 ? substr($excerpt, 0, 80) . '...' : $excerpt); 
                                                ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php elseif ($category['layout_style'] === 'carousel'): ?>
                            <!-- Carousel Layout -->
                            <div class="carousel-container">
                                <div id="carousel-<?php echo $category['id']; ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach (array_chunk($category_articles, 3) as $chunk_index => $chunk): ?>
                                        <div class="carousel-item <?php echo $chunk_index === 0 ? 'active' : ''; ?>">
                                            <div class="row g-4">
                                                <?php foreach ($chunk as $cat_article): ?>
                                                <div class="col-md-4">
                                                    <article class="carousel-card">
                                                        <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
                                                        <div class="carousel-image">
                                                            <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                                 class="img-fluid">
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="carousel-content p-3">
                                                            <h4 class="carousel-title">
                                                                <a href="article.php?slug=<?php echo $cat_article['slug']; ?>">
                                                                    <?php echo htmlspecialchars($cat_article['title']); ?>
                                                                </a>
                                                            </h4>
                                                            <?php if ($category['show_excerpts']): ?>
                                                            <p class="carousel-excerpt">
                                                                <?php 
                                                                $excerpt = $cat_article['excerpt'];
                                                                echo htmlspecialchars(strlen($excerpt) > 100 ? substr($excerpt, 0, 100) . '...' : $excerpt); 
                                                                ?>
                                                            </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </article>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?php echo $category['id']; ?>" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?php echo $category['id']; ?>" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                            </div>
                            
                            <?php elseif ($category['layout_style'] === 'cards'): ?>
                            <!-- Modern Cards Layout -->
                            <div class="cards-layout">
                                <div class="row g-4">
                                    <?php foreach ($category_articles as $cat_article): ?>
                                    <div class="col-lg-6">
                                        <article class="modern-card">
                                            <div class="row g-0">
                                                <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
                                                <div class="col-4">
                                                    <div class="modern-card-image">
                                                        <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                             class="img-fluid h-100">
                                                    </div>
                                                </div>
                                                <div class="col-8">
                                                <?php else: ?>
                                                <div class="col-12">
                                                <?php endif; ?>
                                                    <div class="modern-card-content p-3">
                                                        <div class="card-meta mb-2">
                                                            <span class="date"><?php echo date('M j', strtotime($cat_article['published_at'] ?? $cat_article['created_at'])); ?></span>
                                                        </div>
                                                        <h4 class="modern-card-title">
                                                            <a href="article.php?slug=<?php echo $cat_article['slug']; ?>">
                                                                <?php echo htmlspecialchars($cat_article['title']); ?>
                                                            </a>
                                                        </h4>
                                                        <?php if ($category['show_excerpts']): ?>
                                                        <p class="modern-card-excerpt">
                                                            <?php 
                                                            $excerpt = $cat_article['excerpt'];
                                                            echo htmlspecialchars(strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt); 
                                                            ?>
                                                        </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php elseif ($category['layout_style'] === 'magazine'): ?>
                            <!-- Magazine Layout -->
                            <div class="magazine-layout">
                                <div class="row g-4">
                                    <?php foreach ($category_articles as $index => $cat_article): ?>
                                    <div class="col-md-6 <?php echo $index === 0 ? 'col-lg-8' : 'col-lg-4'; ?>">
                                        <article class="magazine-article <?php echo $index === 0 ? 'magazine-featured' : ''; ?>">
                                            <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
                                            <div class="magazine-image">
                                                <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                     class="img-fluid">
                                            </div>
                                            <?php endif; ?>
                                            <div class="magazine-content p-3">
                                                <h4 class="magazine-title">
                                                    <a href="article.php?slug=<?php echo $cat_article['slug']; ?>">
                                                        <?php echo htmlspecialchars($cat_article['title']); ?>
                                                    </a>
                                                </h4>
                                                <?php if ($category['show_excerpts'] && $index === 0): ?>
                                                <p class="magazine-excerpt">
                                                    <?php 
                                                    $excerpt = $cat_article['excerpt'];
                                                    echo htmlspecialchars(strlen($excerpt) > 150 ? substr($excerpt, 0, 150) . '...' : $excerpt); 
                                                    ?>
                                                </p>
                                                <?php endif; ?>
                                                <div class="magazine-meta">
                                                    <span class="date"><?php echo date('M j', strtotime($cat_article['published_at'] ?? $cat_article['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php elseif ($category['layout_style'] === 'banner'): ?>
                            <!-- Banner Layout -->
                            <div class="banner-layout">
                                <?php if (!empty($category_articles)): ?>
                                <?php $banner_article = $category_articles[0]; ?>
                                <article class="banner-article position-relative">
                                    <?php if ($category['show_images'] && $banner_article['featured_image']): ?>
                                    <div class="banner-image">
                                        <img src="<?php echo htmlspecialchars($banner_article['featured_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($banner_article['title']); ?>" 
                                             class="img-fluid w-100">
                                        <div class="banner-overlay"></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="banner-content position-absolute">
                                        <h3 class="banner-title text-white">
                                            <a href="article.php?slug=<?php echo $banner_article['slug']; ?>" class="text-white text-decoration-none">
                                                <?php echo htmlspecialchars($banner_article['title']); ?>
                                            </a>
                                        </h3>
                                        <?php if ($category['show_excerpts']): ?>
                                        <p class="banner-excerpt text-white">
                                            <?php 
                                            $excerpt = $banner_article['excerpt'];
                                            echo htmlspecialchars(strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt); 
                                            ?>
                                        </p>
                                        <?php endif; ?>
                                        <div class="banner-meta text-white">
                                            <span class="date"><?php echo date('M j, Y', strtotime($banner_article['published_at'] ?? $banner_article['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </article>
                                <?php endif; ?>
                            </div>
                            
                            <?php elseif ($category['layout_style'] === 'featured'): ?>
                            <!-- Featured Layout -->
                            <div class="featured-layout">
                                <div class="row g-4">
                                    <?php foreach ($category_articles as $index => $cat_article): ?>
                                    <div class="col-md-6 col-lg-<?php echo $index === 0 ? '12' : '6'; ?>">
                                        <article class="featured-article <?php echo $index === 0 ? 'featured-main' : 'featured-secondary'; ?>">
                                            <div class="row g-0 h-100">
                                                <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
                                                <div class="col-<?php echo $index === 0 ? '6' : '12'; ?>">
                                                    <div class="featured-image">
                                                        <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                             class="img-fluid h-100">
                                                    </div>
                                                </div>
                                                <div class="col-<?php echo $index === 0 ? '6' : '12'; ?>">
                                                <?php else: ?>
                                                <div class="col-12">
                                                <?php endif; ?>
                                                    <div class="featured-content p-4">
                                                        <h3 class="featured-title <?php echo $index === 0 ? 'h2' : 'h5'; ?>">
                                                            <a href="article.php?slug=<?php echo $cat_article['slug']; ?>">
                                                                <?php echo htmlspecialchars($cat_article['title']); ?>
                                                            </a>
                                                        </h3>
                                                        <?php if ($category['show_excerpts']): ?>
                                                        <p class="featured-excerpt">
                                                            <?php 
                                                            $excerpt = $cat_article['excerpt'];
                                                            $length = $index === 0 ? 200 : 100;
                                                            echo htmlspecialchars(strlen($excerpt) > $length ? substr($excerpt, 0, $length) . '...' : $excerpt); 
                                                            ?>
                                                        </p>
                                                        <?php endif; ?>
                                                        <div class="featured-meta">
                                                            <span class="date"><?php echo date('M j, Y', strtotime($cat_article['published_at'] ?? $cat_article['created_at'])); ?></span>
                                                            <span class="views ms-3"><i class="fas fa-eye"></i> <?php echo number_format($cat_article['views']); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php elseif ($category['layout_style'] === 'minimal'): ?>
                            <!-- Minimal Layout -->
                            <div class="minimal-layout">
                                <div class="minimal-list">
                                    <?php foreach ($category_articles as $cat_article): ?>
                                    <article class="minimal-item py-3 border-bottom">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="minimal-content flex-grow-1">
                                                <h4 class="minimal-title h6 mb-1">
                                                    <a href="article.php?slug=<?php echo $cat_article['slug']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($cat_article['title']); ?>
                                                    </a>
                                                </h4>
                                                <div class="minimal-meta text-muted small">
                                                    <span class="date"><?php echo date('M j', strtotime($cat_article['published_at'] ?? $cat_article['created_at'])); ?></span>
                                                    <span class="views ms-3"><?php echo number_format($cat_article['views']); ?> views</span>
                                                </div>
                                            </div>
                                            <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
                                            <div class="minimal-image ms-3">
                                                <img src="<?php echo htmlspecialchars($cat_article['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($cat_article['title']); ?>" 
                                                     class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php else: ?>
                            <!-- List Layout (Default) -->
                            <div class="category-articles">
                                <?php foreach ($category_articles as $cat_article): ?>
                                <article class="category-item mb-4">
                                    <div class="row g-3">
                                        <div class="<?php echo ($category['show_images'] && $cat_article['featured_image']) ? 'col-md-8' : 'col-12'; ?>">
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
                                                <?php if ($category['show_excerpts']): ?>
                                                <p class="category-excerpt">
                                                    <?php 
                                                    $excerpt = $cat_article['excerpt'];
                                                    echo htmlspecialchars(strlen($excerpt) > 100 ? substr($excerpt, 0, 100) . '...' : $excerpt); 
                                                    ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($category['show_images'] && $cat_article['featured_image']): ?>
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
                            <?php endif; ?>
                        </section>
                        
                        <!-- Ad Between Categories -->
                        <?php 
                        static $ad_counter = 0;
                        if ($ad_counter % 2 === 1) { // Show ad after every second category
                            $between_ads = getBetweenCategoriesAds();
                            if (!empty($between_ads)) {
                                echo '<div class="ad-section mb-4">';
                                echo displayAd($between_ads[0]);
                                echo '</div>';
                            }
                        }
                        $ad_counter++;
                        ?>
                        
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Sidebar -->
                    <?php if ($sidebar_layout !== 'none'): ?>
                    <div class="col-lg-3">
                        <aside class="main-sidebar" data-layout="<?php echo $sidebar_layout; ?>">
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

                            <!-- Sidebar Ads -->
                            <?php 
                            $sidebar_ads = getSidebarAds();
                            if (!empty($sidebar_ads)) {
                                echo '<div class="sidebar-widget ad-widget">';
                                echo displayAd($sidebar_ads[0]);
                                echo '</div>';
                            }
                            ?>

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
                                    <article class="popular-item mb-3">
                                        <div class="row g-2">
                                            <div class="<?php echo $popular['featured_image'] ? 'col-8' : 'col-12'; ?>">
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
                                            </div>
                                            <?php if ($popular['featured_image']): ?>
                                            <div class="col-4">
                                                <div class="popular-image">
                                                    <img src="<?php echo htmlspecialchars($popular['featured_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($popular['title']); ?>" 
                                                         class="img-fluid rounded">
                                                </div>
                                            </div>
                                            <?php endif; ?>
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
                    <?php endif; ?>
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