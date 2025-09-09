<?php
require_once 'config/config.php';
require_once 'classes/Article.php';

$slug = sanitize($_GET['slug'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');
$date_filter = sanitize($_GET['date_filter'] ?? '');
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
// Build article query with search and date filters
$where_conditions = ["a.status = 'published'", "a.category_id = " . intval($category['id'])];
$params = [];

if ($search) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(a.published_at) = CURRENT_DATE";
            break;
        case 'week':
            $where_conditions[] = "a.published_at >= CURRENT_DATE - INTERVAL '7 days'";
            break;
        case 'month':
            $where_conditions[] = "a.published_at >= CURRENT_DATE - INTERVAL '30 days'";
            break;
    }
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$query = "SELECT a.*, c.name as category_name, c.slug as category_slug,
                 u.first_name, u.last_name
          FROM articles a
          LEFT JOIN categories c ON a.category_id = c.id
          LEFT JOIN users u ON a.author_id = u.id
          $where_clause
          ORDER BY a.is_breaking DESC, a.is_featured DESC, a.published_at DESC
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categories will be loaded in header include
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
    <?php include 'includes/header.php'; ?>
    
    <!-- Category Content -->

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Category Header -->
                    <div class="newspaper-section">
                        <h3 class="section-title category-header">
                            <?php echo htmlspecialchars($category['name']); ?> News
                        </h3>
                        <?php if ($category['description']): ?>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        <?php endif; ?>
                        
                        <!-- Search and Filter Controls -->
                        <div class="category-controls mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <form method="GET" class="search-form">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                                        <input type="hidden" name="date_filter" value="<?php echo htmlspecialchars($date_filter); ?>">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="search" 
                                                   placeholder="Search in <?php echo htmlspecialchars($category['name']); ?>..." 
                                                   value="<?php echo htmlspecialchars($search); ?>">
                                            <button class="btn btn-outline-secondary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-3">
                                    <form method="GET" class="filter-form">
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <select name="date_filter" class="form-select" onchange="this.form.submit()">
                                            <option value="">All Time</option>
                                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                                        </select>
                                    </form>
                                </div>
                                <div class="col-md-3">
                                    <?php if ($search || $date_filter): ?>
                                        <a href="category.php?slug=<?php echo htmlspecialchars($slug); ?>" class="btn btn-outline-danger">
                                            <i class="fas fa-times"></i> Clear Filters
                                        </a>
                                    <?php endif; ?>
                                    <span class="text-muted ms-2">
                                        <?php echo count($articles); ?> articles found
                                    </span>
                                </div>
                            </div>
                        </div>
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
                            <article class="list-item mb-4">
                                <div class="row align-items-center g-3">
                                    <div class="<?php echo $article_item['featured_image'] ? 'col-md-8' : 'col-12'; ?>">
                                        <div class="article-content">
                                            <div class="article-meta mb-2">
                                                <span class="category-badge">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </span>
                                                <span class="date ms-3"><?php echo date('M j, Y', strtotime($article_item['published_at'] ?? $article_item['created_at'])); ?></span>
                                                <span class="views ms-3"><?php echo number_format($article_item['views']); ?> views</span>
                                                <?php if ($article_item['is_breaking']): ?>
                                                <span class="breaking-badge ms-3">Breaking</span>
                                                <?php endif; ?>
                                            </div>
                                            <h5><a href="article.php?slug=<?php echo $article_item['slug']; ?>"><?php echo htmlspecialchars($article_item['title']); ?></a></h5>
                                            <p><?php 
                                            $length = $article_item['featured_image'] ? 120 : 200;
                                            echo htmlspecialchars(substr($article_item['excerpt'] ?? $article_item['content'], 0, $length)) . '...'; 
                                            ?></p>
                                        </div>
                                    </div>
                                    <?php if ($article_item['featured_image']): ?>
                                    <div class="col-md-4">
                                        <div class="article-image">
                                            <a href="article.php?slug=<?php echo $article_item['slug']; ?>" class="image-link">
                                                <img src="<?php echo htmlspecialchars($article_item['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($article_item['title']); ?>"
                                                     class="img-fluid rounded">
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
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