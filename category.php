<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

$slug = sanitize($_GET['slug'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ARTICLES_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get category
$db = new Database();
$conn = $db->getConnection();
$category_stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active'");
$category_stmt->execute([$slug]);
$category = $category_stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}
$article = new Article();
$articles = $article->getPublishedArticles($limit, $offset, $category['id']);

// Get all categories for navigation
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' AND show_in_menu = true ORDER BY display_order");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - <?php echo SITE_NAME; ?></title>
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
                        <?php foreach ($categories as $cat): ?>
                        <a href="category.php?slug=<?php echo $cat['slug']; ?>" 
                           class="category-link <?php echo $cat['slug'] === $slug ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
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
                <div class="col-12">
                    <!-- Category Header -->
                    <div class="newspaper-section">
                        <h3 class="section-title" style="background-color: <?php echo htmlspecialchars($category['color']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?> News
                        </h3>
                        <?php if ($category['description']): ?>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($articles)): ?>
                        <div class="text-center py-5">
                            <h4>No articles in this category</h4>
                            <p class="text-muted">Check back later for updates</p>
                            <a href="index.php" class="category-item">Back to Home</a>
                        </div>
                    <?php else: ?>
                        <!-- Newspaper-style Article List -->
                        <div class="list-layout">
                            <?php foreach ($articles as $article_item): ?>
                            <article class="list-item">
                                <div class="row align-items-center">
                                    <?php if ($article_item['featured_image']): ?>
                                    <div class="col-md-3">
                                        <div class="article-image">
                                            <img src="<?php echo htmlspecialchars($article_item['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($article_item['title']); ?>">
                                            <?php if ($article_item['is_breaking']): ?>
                                            <span class="breaking-badge">Breaking</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-<?php echo $article_item['featured_image'] ? '9' : '12'; ?>">
                                        <div class="article-content">
                                            <div class="article-meta">
                                                <span class="category" style="color: <?php echo htmlspecialchars($category['color']); ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </span>
                                                <span class="date"><?php echo date('M j, Y', strtotime($article_item['published_at'] ?? $article_item['created_at'])); ?></span>
                                                <span class="views"><?php echo number_format($article_item['views']); ?> views</span>
                                            </div>
                                            <h5><a href="article.php?slug=<?php echo $article_item['slug']; ?>"><?php echo htmlspecialchars($article_item['title']); ?></a></h5>
                                            <p><?php echo htmlspecialchars(substr($article_item['excerpt'] ?? $article_item['content'], 0, 120)) . '...'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </article>
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