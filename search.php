<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

$query = sanitize($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ARTICLES_PER_PAGE;
$offset = ($page - 1) * $limit;

$article = new Article();
$articles = [];

if (!empty($query)) {
    $articles = $article->getAllArticles($limit, $offset, $query, 'published');
}

// Get categories for navigation
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
    <title>Search: <?php echo htmlspecialchars($query); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
                                <input class="form-control" type="search" name="q" placeholder="Search news..." value="<?php echo htmlspecialchars($query); ?>">
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
                        <?php 
                        // Get categories with subcategories for dropdown navigation
                        $nav_categories = [];
                        foreach ($categories as $category) {
                            if ($category['parent_id'] === null) {
                                if (!isset($nav_categories[$category['id']])) {
                                    $nav_categories[$category['id']] = $category;
                                    $nav_categories[$category['id']]['subcategories'] = [];
                                }
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="section-title">Search Results for: "<?php echo htmlspecialchars($query); ?>"</h2>
                    
                    <?php if (empty($query)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4>Enter a search term</h4>
                            <p class="text-muted">Use the search box above to find articles</p>
                        </div>
                    <?php elseif (empty($articles)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4>No results found</h4>
                            <p class="text-muted">Try different keywords or browse our categories</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($articles as $article_item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <article class="news-item">
                                    <?php if ($article_item['featured_image']): ?>
                                    <div class="article-image">
                                        <a href="article.php?slug=<?php echo $article_item['slug']; ?>" class="image-link">
                                            <img src="<?php echo htmlspecialchars($article_item['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($article_item['title']); ?>">
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <div class="article-content">
                                        <div class="article-meta">
                                            <span class="date"><?php echo date('M j, Y', strtotime($article_item['created_at'])); ?></span>
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
                    <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>
</html>