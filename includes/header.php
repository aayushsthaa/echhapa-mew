<?php
// Header include file - contains navigation and header HTML
// This file should be included in all pages for consistent navigation

// Get database connection if not already available
if (!isset($conn)) {
    if (!isset($db)) {
        $db = new Database();
    }
    $conn = $db->getConnection();
}

// Get categories for navigation
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Get breaking news for header banner
$breaking_news = [];
if (class_exists('Article')) {
    $articleClass = new Article();
    $breaking_news = $articleClass->getBreakingNews(3);
}
?>

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
                    
                    <?php foreach ($nav_categories as $category): ?>
                    <?php if (!empty($category['subcategories'])): ?>
                    <div class="category-dropdown dropdown">
                        <button class="category-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($category['name']); ?>
                            <i class="fas fa-chevron-down ms-1"></i>
                        </button>
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