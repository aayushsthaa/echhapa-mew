<?php
require_once '../includes/auth_check.php';

$article = new Article();

// Handle bulk actions
if ($_POST && isset($_POST['bulk_action']) && isset($_POST['article_ids'])) {
    $action = sanitize($_POST['bulk_action']);
    $article_ids = array_map('intval', $_POST['article_ids']);
    
    if ($action === 'delete' && !empty($article_ids)) {
        $placeholders = implode(',', array_fill(0, count($article_ids), '?'));
        $stmt = $conn->prepare("DELETE FROM articles WHERE id IN ($placeholders)");
        if ($stmt->execute($article_ids)) {
            $success = count($article_ids) . " articles deleted successfully";
        }
    }
}

// Pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ADMIN_ARTICLES_PER_PAGE;
$offset = ($page - 1) * $limit;
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

// Get articles
$articles = $article->getAllArticles($limit, $offset, $search, $status_filter);

// Get total count for pagination
$total_query = "SELECT COUNT(*) FROM articles a WHERE 1=1";
$params = [];

if ($search) {
    $total_query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $total_query .= " AND a.status = ?";
    $params[] = $status_filter;
}

$total_stmt = $conn->prepare($total_query);
$total_stmt->execute($params);
$total_articles = $total_stmt->fetchColumn();
$total_pages = ceil($total_articles / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-newspaper"></i> News Admin</h4>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li class="menu-header">Content Management</li>
                <li class="menu-item active">
                    <a href="articles.php"><i class="fas fa-file-alt"></i> Articles</a>
                </li>
                <li class="menu-item">
                    <a href="article-create.php"><i class="fas fa-plus-circle"></i> New Article</a>
                </li>
                <li class="menu-item">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0">Manage Articles</h5>
                </div>
                <div class="topbar-right">
                    <a href="article-create.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New Article
                    </a>
                </div>
            </div>

            <div class="content-area">
                <div class="container-fluid">
                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Articles List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($articles)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5>No articles found</h5>
                                    <p class="text-muted">Start by creating your first article</p>
                                    <a href="article-create.php" class="btn btn-primary">Create Article</a>
                                </div>
                            <?php else: ?>
                                <form method="POST" id="articlesForm">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="40">
                                                        <input type="checkbox" id="selectAll">
                                                    </th>
                                                    <th>Title</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Views</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($articles as $art): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="article_ids[]" value="<?php echo $art['id']; ?>">
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($art['title']); ?></strong>
                                                            <?php if ($art['is_featured']): ?>
                                                                <span class="badge bg-warning ms-1">Featured</span>
                                                            <?php endif; ?>
                                                            <?php if ($art['is_breaking']): ?>
                                                                <span class="badge bg-danger ms-1">Breaking</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">by <?php echo htmlspecialchars($art['first_name'] . ' ' . $art['last_name']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($art['category_name']): ?>
                                                            <span class="badge" style="background-color: #007bff">
                                                                <?php echo htmlspecialchars($art['category_name']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Uncategorized</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $art['status'] === 'published' ? 'success' : ($art['status'] === 'draft' ? 'secondary' : 'info'); ?>">
                                                            <?php echo ucfirst($art['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($art['views']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($art['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="article-edit.php?id=<?php echo $art['id']; ?>" 
                                                               class="btn btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($art['status'] === 'published'): ?>
                                                            <a href="../article.php?slug=<?php echo $art['slug']; ?>" 
                                                               class="btn btn-outline-success" title="View" target="_blank">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Bulk Actions -->
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center">
                                                <select name="bulk_action" class="form-select me-2" style="width: auto;">
                                                    <option value="">Bulk Actions</option>
                                                    <option value="delete">Delete</option>
                                                </select>
                                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                        onclick="return confirm('Are you sure?')">Apply</button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- Pagination -->
                                            <?php if ($total_pages > 1): ?>
                                            <nav aria-label="Articles pagination">
                                                <ul class="pagination pagination-sm justify-content-end mb-0">
                                                    <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">Previous</a>
                                                    </li>
                                                    <?php endif; ?>

                                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                    <?php endfor; ?>

                                                    <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">Next</a>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </nav>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="article_ids[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    </script>
</body>
</html>