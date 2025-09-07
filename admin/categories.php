<?php
require_once '../includes/auth_check.php';

$success = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $color = sanitize($_POST['color'] ?? '#007bff');
        
        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $slug = generateSlug($name);
            
            // Check if slug exists
            $slug_check = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
            $slug_check->execute([$slug]);
            if ($slug_check->rowCount() > 0) {
                $slug .= '-' . time();
            }
            
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, color, display_order, status) VALUES (?, ?, ?, ?, 0, 'active')");
            if ($stmt->execute([$name, $slug, $description, $color])) {
                $success = 'Category created successfully';
            } else {
                $error = 'Failed to create category';
            }
        }
    }
}

// Get all categories
$categories_stmt = $conn->query("SELECT * FROM categories ORDER BY display_order, name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - <?php echo SITE_NAME; ?> Admin</title>
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
                <li class="menu-item">
                    <a href="articles.php"><i class="fas fa-file-alt"></i> Articles</a>
                </li>
                <li class="menu-item">
                    <a href="article-create.php"><i class="fas fa-plus-circle"></i> New Article</a>
                </li>
                <li class="menu-item active">
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
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
                    <h5 class="mb-0">Manage Categories</h5>
                </div>
            </div>

            <div class="content-area">
                <div class="container-fluid">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Categories List -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Categories</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categories)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                            <h5>No categories found</h5>
                                            <p class="text-muted">Create your first category to organize articles</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Slug</th>
                                                        <th>Color</th>
                                                        <th>Articles</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                            <?php if ($category['description']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <code><?php echo htmlspecialchars($category['slug']); ?></code>
                                                        </td>
                                                        <td>
                                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($category['color']); ?>">
                                                                <?php echo htmlspecialchars($category['color']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $count_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
                                                            $count_stmt->execute([$category['id']]);
                                                            echo $count_stmt->fetchColumn();
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo ucfirst($category['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="category-edit.php?id=<?php echo $category['id']; ?>" 
                                                                   class="btn btn-outline-primary" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="../category.php?slug=<?php echo $category['slug']; ?>" 
                                                                   class="btn btn-outline-success" title="View" target="_blank">
                                                                    <i class="fas fa-external-link-alt"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Add New Category -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Add New Category</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="create">
                                        
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="color" class="form-label">Color</label>
                                            <input type="color" class="form-control form-control-color" id="color" name="color" 
                                                   value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : '#007bff'; ?>">
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Category
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>