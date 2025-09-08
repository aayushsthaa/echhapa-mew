<?php
require_once '../includes/auth_check.php';

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';
$category_id = intval($_GET['id'] ?? 0);

if ($category_id <= 0) {
    header('Location: categories.php');
    exit;
}

// Get category data
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: categories.php');
    exit;
}

// Get all categories for parent selection (excluding current category and its children)
$parent_categories = $conn->query("SELECT * FROM categories WHERE id != $category_id AND (parent_id IS NULL OR parent_id != $category_id) ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $color = sanitize($_POST['color'] ?? '#007bff');
    $parent_id = intval($_POST['parent_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
    $display_order = intval($_POST['display_order'] ?? 0);
    
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        // Generate slug if name changed
        $slug = generateSlug($name);
        if ($slug !== $category['slug']) {
            // Check if new slug exists
            $slug_check = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $slug_check->execute([$slug, $category_id]);
            if ($slug_check->rowCount() > 0) {
                $slug .= '-' . time();
            }
        } else {
            $slug = $category['slug'];
        }
        
        // Set parent_id to NULL if 0
        $parent_id = $parent_id > 0 ? $parent_id : null;
        
        // Calculate level based on parent
        $level = 0;
        if ($parent_id) {
            $parent_stmt = $conn->prepare("SELECT level FROM categories WHERE id = ?");
            $parent_stmt->execute([$parent_id]);
            $parent_level = $parent_stmt->fetchColumn();
            $level = $parent_level + 1;
        }
        
        $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, color = ?, parent_id = ?, level = ?, status = ?, show_in_menu = ?, display_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        
        if ($stmt->execute([$name, $slug, $description, $color, $parent_id, $level, $status, $show_in_menu, $display_order, $category_id])) {
            $success = 'Category updated successfully';
            // Refresh category data
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to update category';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="homepage-layout.php"><i class="fas fa-home"></i> Homepage Layout</a>
                </li>
                <li class="menu-header">Marketing</li>
                <li class="menu-item">
                    <a href="ads.php"><i class="fas fa-bullhorn"></i> Ads</a>
                </li>
                <li class="menu-header">Settings</li>
                <li class="menu-item">
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                </li>
                <li class="menu-item">
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                </li>
                <li class="menu-item">
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">Edit Category</h1>
                        <p class="text-muted">Modify category details and subcategory settings</p>
                    </div>
                    <div>
                        <a href="categories.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Categories
                        </a>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit"></i> Category Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Category Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="color" class="form-label">Color</label>
                                                <input type="color" class="form-control form-control-color" id="color" name="color" 
                                                       value="<?php echo htmlspecialchars($category['color']); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="parent_id" class="form-label">Parent Category</label>
                                                <select class="form-select" id="parent_id" name="parent_id">
                                                    <option value="0">None (Main Category)</option>
                                                    <?php foreach ($parent_categories as $parent): ?>
                                                    <option value="<?php echo $parent['id']; ?>" 
                                                            <?php echo ($category['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($parent['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Select a parent to make this a subcategory</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="display_order" class="form-label">Display Order</label>
                                                <input type="number" class="form-control" id="display_order" name="display_order" 
                                                       value="<?php echo $category['display_order']; ?>" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="active" <?php echo $category['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo $category['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="show_in_menu" name="show_in_menu" 
                                                   <?php echo $category['show_in_menu'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="show_in_menu">
                                                Show in main navigation menu
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Category
                                        </button>
                                        <a href="categories.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Category Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-5">Slug:</dt>
                                    <dd class="col-7"><code><?php echo htmlspecialchars($category['slug']); ?></code></dd>
                                    
                                    <dt class="col-5">Level:</dt>
                                    <dd class="col-7">
                                        <?php if ($category['level'] == 0): ?>
                                            <span class="badge bg-primary">Main Category</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Subcategory (Level <?php echo $category['level']; ?>)</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-5">Created:</dt>
                                    <dd class="col-7"><?php echo date('M j, Y', strtotime($category['created_at'])); ?></dd>
                                    
                                    <dt class="col-5">Updated:</dt>
                                    <dd class="col-7"><?php echo date('M j, Y', strtotime($category['updated_at'])); ?></dd>
                                </dl>
                            </div>
                        </div>

                        <?php if ($category['level'] == 0): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-sitemap"></i> Subcategories
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $subcats = $conn->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY display_order, name");
                                $subcats->execute([$category_id]);
                                $subcategories = $subcats->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php if (count($subcategories) > 0): ?>
                                    <ul class="list-unstyled">
                                        <?php foreach ($subcategories as $subcat): ?>
                                        <li class="mb-2">
                                            <span class="badge" style="background-color: <?php echo $subcat['color']; ?>">
                                                <?php echo htmlspecialchars($subcat['name']); ?>
                                            </span>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No subcategories yet</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/admin.js"></script>
</body>
</html>