<?php
require_once '../includes/auth_check.php';
require_once '../classes/Article.php';

$article = new Article();

// Get dashboard statistics
$db = new Database();
$conn = $db->getConnection();

$stats = [];
$stats['total_articles'] = $conn->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$stats['published_articles'] = $conn->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
$stats['draft_articles'] = $conn->query("SELECT COUNT(*) FROM articles WHERE status = 'draft'")->fetchColumn();
$stats['total_views'] = $conn->query("SELECT COALESCE(SUM(views), 0) FROM articles")->fetchColumn();

// Get recent articles
$recent_articles = $article->getAllArticles(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-newspaper"></i> News Admin</h4>
                <p class="text-muted">v1.0</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="menu-header">Content Management</li>
                <li class="menu-item">
                    <a href="articles.php">
                        <i class="fas fa-file-alt"></i>
                        <span>Articles</span>
                        <span class="badge bg-primary"><?php echo $stats['total_articles']; ?></span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="article-create.php">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Article</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                
                <li class="menu-header">Layout & Design</li>
                <li class="menu-item">
                    <a href="homepage-layout.php">
                        <i class="fas fa-th-large"></i>
                        <span>Homepage Layout</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="ads.php">
                        <i class="fas fa-ad"></i>
                        <span>Advertisements</span>
                    </a>
                </li>
                
                <li class="menu-header">Configuration</li>
                <li class="menu-item">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Site Settings</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="seo-settings.php">
                        <i class="fas fa-search-plus"></i>
                        <span>SEO & Social</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                
                <li class="menu-header">System</li>
                <li class="menu-item">
                    <a href="comments.php">
                        <i class="fas fa-comments"></i>
                        <span>Comments</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Site</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link sidebar-toggle d-lg-none">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="mb-0">Dashboard Overview</h5>
                </div>
                <div class="topbar-right">
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                <div class="container-fluid">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo number_format($stats['total_articles']); ?></h3>
                                    <p>Total Articles</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo number_format($stats['published_articles']); ?></h3>
                                    <p>Published</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo number_format($stats['draft_articles']); ?></h3>
                                    <p>Drafts</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo number_format($stats['total_views']); ?></h3>
                                    <p>Total Views</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-2 col-6 mb-3">
                                            <a href="article-create.php" class="quick-action">
                                                <i class="fas fa-plus-circle text-primary"></i>
                                                <span>New Article</span>
                                            </a>
                                        </div>
                                        <div class="col-md-2 col-6 mb-3">
                                                <i class="fas fa-upload text-success"></i>
                                                <span>Upload Media</span>
                                            </a>
                                        </div>
                                        <div class="col-md-2 col-6 mb-3">
                                            <a href="categories.php" class="quick-action">
                                                <i class="fas fa-tags text-warning"></i>
                                                <span>Categories</span>
                                            </a>
                                        </div>
                                        <div class="col-md-2 col-6 mb-3">
                                            <a href="homepage-layout.php" class="quick-action">
                                                <i class="fas fa-th-large text-info"></i>
                                                <span>Layout</span>
                                            </a>
                                        </div>
                                        <div class="col-md-2 col-6 mb-3">
                                            <a href="ads.php" class="quick-action">
                                                <i class="fas fa-ad text-danger"></i>
                                                <span>Ads</span>
                                            </a>
                                        </div>
                                        <div class="col-md-2 col-6 mb-3">
                                            <a href="settings.php" class="quick-action">
                                                <i class="fas fa-cog text-secondary"></i>
                                                <span>Settings</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Articles -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Recent Articles</h5>
                                    <a href="articles.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_articles)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                            <h5>No articles found</h5>
                                            <p class="text-muted">Start by creating your first article</p>
                                            <a href="article-create.php" class="btn btn-primary">Create Article</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Category</th>
                                                        <th>Status</th>
                                                        <th>Views</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_articles as $art): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($art['title']); ?></strong>
                                                            <?php if ($art['is_featured']): ?>
                                                                <span class="badge bg-warning ms-1">Featured</span>
                                                            <?php endif; ?>
                                                            <?php if ($art['is_breaking']): ?>
                                                                <span class="badge bg-danger ms-1">Breaking</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($art['category_name']): ?>
                                                                <span class="badge bg-secondary">
                                                                    <?php echo htmlspecialchars($art['category_name']); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">Uncategorized</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_class = [
                                                                'draft' => 'bg-secondary',
                                                                'published' => 'bg-success',
                                                                'scheduled' => 'bg-info',
                                                                'archived' => 'bg-dark'
                                                            ];
                                                            ?>
                                                            <span class="badge <?php echo $status_class[$art['status']] ?? 'bg-secondary'; ?>">
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
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/admin.js"></script>
</body>
</html>