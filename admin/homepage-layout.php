<?php
require_once '../includes/auth_check.php';

$success = '';
$error = '';

$db = new Database();
$conn = $db->getConnection();

// Handle layout updates
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_layout':
            $main_layout = sanitize($_POST['main_layout'] ?? '');
            $sidebar_layout = sanitize($_POST['sidebar_layout'] ?? '');
            $featured_categories = $_POST['featured_categories'] ?? [];
            $articles_per_section = intval($_POST['articles_per_section'] ?? 5);
            $show_breaking_news = isset($_POST['show_breaking_news']) ? 1 : 0;
            $show_trending = isset($_POST['show_trending']) ? 1 : 0;
            
            // Save layout settings
            $settings = [
                'main_layout' => $main_layout,
                'sidebar_layout' => $sidebar_layout,
                'featured_categories' => json_encode($featured_categories),
                'articles_per_section' => $articles_per_section,
                'show_breaking_news' => $show_breaking_news,
                'show_trending' => $show_trending
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?, updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$key, $value, $value]);
            }
            
            // Handle category display settings
            if (isset($_POST['category_settings'])) {
                foreach ($_POST['category_settings'] as $cat_id => $settings) {
                    $layout_style = sanitize($settings['layout_style'] ?? 'list');
                    $show_on_homepage = isset($settings['show_on_homepage']) ? 1 : 0;
                    $display_order = intval($settings['display_order'] ?? 0);
                    $articles_limit = intval($settings['articles_limit'] ?? 4);
                    $show_excerpts = isset($settings['show_excerpts']) ? 1 : 0;
                    $show_images = isset($settings['show_images']) ? 1 : 0;
                    
                    $stmt = $conn->prepare("INSERT INTO category_display_settings (category_id, layout_style, show_on_homepage, display_order, articles_limit, show_excerpts, show_images) VALUES (?, ?, ?, ?, ?, ?, ?) ON CONFLICT (category_id) DO UPDATE SET layout_style = ?, show_on_homepage = ?, display_order = ?, articles_limit = ?, show_excerpts = ?, show_images = ?, updated_at = CURRENT_TIMESTAMP");
                    $stmt->execute([$cat_id, $layout_style, $show_on_homepage, $display_order, $articles_limit, $show_excerpts, $show_images, $layout_style, $show_on_homepage, $display_order, $articles_limit, $show_excerpts, $show_images]);
                }
            }
            
            $success = 'Homepage layout updated successfully';
            break;
            
        case 'reset_layout':
            // Reset to default layout
            $defaults = [
                'main_layout' => 'mixed',
                'sidebar_layout' => 'widgets',
                'featured_categories' => json_encode([]),
                'articles_per_section' => 5,
                'show_breaking_news' => 1,
                'show_trending' => 1
            ];
            
            foreach ($defaults as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?, updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success = 'Layout reset to default settings';
            break;
    }
}

// Get current settings
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

$current_main_layout = getSetting($conn, 'main_layout', 'mixed');
$current_sidebar_layout = getSetting($conn, 'sidebar_layout', 'widgets');
$featured_categories = json_decode(getSetting($conn, 'featured_categories', '[]'), true);
$articles_per_section = intval(getSetting($conn, 'articles_per_section', '5'));
$show_breaking_news = intval(getSetting($conn, 'show_breaking_news', '1'));
$show_trending = intval(getSetting($conn, 'show_trending', '1'));

// Get all categories with display settings
$categories_stmt = $conn->query("
    SELECT c.*, 
           COALESCE(cds.layout_type, 'list') as layout_style,
           COALESCE(cds.homepage_visible, true) as show_on_homepage,
           COALESCE(cds.display_order, c.display_order) as display_order,
           COALESCE(cds.max_articles, 4) as articles_limit,
           true as show_excerpts,
           true as show_images
    FROM categories c
    LEFT JOIN category_display_settings cds ON c.id = cds.category_id
    WHERE c.status = 'active' 
    ORDER BY COALESCE(cds.display_order, c.display_order)
");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Layout Manager - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
    <style>
        .layout-preview {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
            margin-top: 20px;
        }
        .preview-section {
            background: white;
            border: 2px dashed #dee2e6;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            text-align: center;
            color: #6c757d;
        }
        .layout-option {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        .layout-option:hover {
            border-color: #007bff;
        }
        .layout-option.active {
            border-color: #007bff;
            background: #e3f2fd;
        }
        .layout-grid {
            display: grid;
            gap: 10px;
            height: 100px;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .layout-mixed { grid-template-columns: 2fr 1fr; }
        .layout-featured { grid-template-columns: 1fr; }
        .layout-category { grid-template-columns: 1fr 1fr; }
        .layout-latest { grid-template-columns: 1fr; }
        
        .grid-item {
            background: #007bff;
            border-radius: 3px;
            opacity: 0.7;
        }
    </style>
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
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="menu-item">
                </li>
                <li class="menu-item">
                    <a href="ads.php"><i class="fas fa-bullhorn"></i> Advertisements</a>
                </li>
                <li class="menu-header">Site Settings</li>
                <li class="menu-item active">
                    <a href="homepage-layout.php"><i class="fas fa-layout"></i> Homepage Layout</a>
                </li>
                <li class="menu-item">
                    <a href="seo-settings.php"><i class="fas fa-search"></i> SEO Settings</a>
                </li>
                <li class="menu-item">
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
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
                    <h5 class="mb-0">Homepage Layout Manager</h5>
                </div>
                <div class="topbar-right">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="reset_layout">
                        <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Reset layout to defaults?')">
                            <i class="fas fa-undo"></i> Reset to Default
                        </button>
                    </form>
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

                    <form method="POST">
                        <input type="hidden" name="action" value="update_layout">
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Main Content Layout -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Main Content Layout</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="layout-option <?php echo $current_main_layout === 'mixed' ? 'active' : ''; ?>" 
                                                     onclick="selectLayout('main', 'mixed')">
                                                    <div class="layout-grid layout-mixed">
                                                        <div class="grid-item"></div>
                                                        <div class="grid-item"></div>
                                                    </div>
                                                    <label>
                                                        <input type="radio" name="main_layout" value="mixed" 
                                                               <?php echo $current_main_layout === 'mixed' ? 'checked' : ''; ?> style="display: none;">
                                                        <strong>Mixed Layout</strong><br>
                                                        <small class="text-muted">Featured articles + Latest articles in two columns</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="layout-option <?php echo $current_main_layout === 'featured' ? 'active' : ''; ?>" 
                                                     onclick="selectLayout('main', 'featured')">
                                                    <div class="layout-grid layout-featured">
                                                        <div class="grid-item"></div>
                                                    </div>
                                                    <label>
                                                        <input type="radio" name="main_layout" value="featured" 
                                                               <?php echo $current_main_layout === 'featured' ? 'checked' : ''; ?> style="display: none;">
                                                        <strong>Featured Only</strong><br>
                                                        <small class="text-muted">Large featured articles only</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="layout-option <?php echo $current_main_layout === 'category' ? 'active' : ''; ?>" 
                                                     onclick="selectLayout('main', 'category')">
                                                    <div class="layout-grid layout-category">
                                                        <div class="grid-item"></div>
                                                        <div class="grid-item"></div>
                                                    </div>
                                                    <label>
                                                        <input type="radio" name="main_layout" value="category" 
                                                               <?php echo $current_main_layout === 'category' ? 'checked' : ''; ?> style="display: none;">
                                                        <strong>Category Based</strong><br>
                                                        <small class="text-muted">Articles grouped by categories</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="layout-option <?php echo $current_main_layout === 'latest' ? 'active' : ''; ?>" 
                                                     onclick="selectLayout('main', 'latest')">
                                                    <div class="layout-grid layout-latest">
                                                        <div class="grid-item"></div>
                                                    </div>
                                                    <label>
                                                        <input type="radio" name="main_layout" value="latest" 
                                                               <?php echo $current_main_layout === 'latest' ? 'checked' : ''; ?> style="display: none;">
                                                        <strong>Latest Articles</strong><br>
                                                        <small class="text-muted">Latest articles in chronological order</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Featured Categories -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Featured Categories</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3">Select categories to highlight on the homepage</p>
                                        <div class="row">
                                            <?php foreach ($categories as $category): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="featured_categories[]" 
                                                           value="<?php echo $category['id']; ?>" 
                                                           id="cat_<?php echo $category['id']; ?>"
                                                           <?php echo in_array($category['id'], $featured_categories) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="cat_<?php echo $category['id']; ?>">
                                                        <span class="badge bg-primary me-2">&nbsp;</span>
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Individual Category Display Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Category Display Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-4">Configure how each category appears on the homepage</p>
                                        <?php foreach ($categories as $category): ?>
                                        <div class="border rounded p-3 mb-3" style="background: #f8f9fa;">
                                            <h6 class="mb-3">
                                                <span class="badge bg-primary me-2">&nbsp;</span>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </h6>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Layout Style</label>
                                                        <select class="form-select form-select-sm" name="category_settings[<?php echo $category['id']; ?>][layout_style]">
                                                            <option value="list" <?php echo $category['layout_style'] === 'list' ? 'selected' : ''; ?>>List View</option>
                                                            <option value="grid" <?php echo $category['layout_style'] === 'grid' ? 'selected' : ''; ?>>Grid View</option>
                                                            <option value="banner" <?php echo $category['layout_style'] === 'banner' ? 'selected' : ''; ?>>Banner Style</option>
                                                            <option value="card" <?php echo $category['layout_style'] === 'card' ? 'selected' : ''; ?>>Card Layout</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Display Order</label>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="category_settings[<?php echo $category['id']; ?>][display_order]" 
                                                               value="<?php echo $category['display_order']; ?>" min="0" max="100">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Articles Limit</label>
                                                        <select class="form-select form-select-sm" name="category_settings[<?php echo $category['id']; ?>][articles_limit]">
                                                            <option value="2" <?php echo $category['articles_limit'] == 2 ? 'selected' : ''; ?>>2 Articles</option>
                                                            <option value="4" <?php echo $category['articles_limit'] == 4 ? 'selected' : ''; ?>>4 Articles</option>
                                                            <option value="6" <?php echo $category['articles_limit'] == 6 ? 'selected' : ''; ?>>6 Articles</option>
                                                            <option value="8" <?php echo $category['articles_limit'] == 8 ? 'selected' : ''; ?>>8 Articles</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check-group">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="category_settings[<?php echo $category['id']; ?>][show_on_homepage]" 
                                                                   id="show_<?php echo $category['id']; ?>"
                                                                   <?php echo $category['show_on_homepage'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="show_<?php echo $category['id']; ?>">
                                                                Show on Homepage
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="category_settings[<?php echo $category['id']; ?>][show_excerpts]" 
                                                                   id="excerpts_<?php echo $category['id']; ?>"
                                                                   <?php echo $category['show_excerpts'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="excerpts_<?php echo $category['id']; ?>">
                                                                Show Excerpts
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="category_settings[<?php echo $category['id']; ?>][show_images]" 
                                                                   id="images_<?php echo $category['id']; ?>"
                                                                   <?php echo $category['show_images'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="images_<?php echo $category['id']; ?>">
                                                                Show Images
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Sidebar Layout -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Sidebar Layout</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="layout-option <?php echo $current_sidebar_layout === 'widgets' ? 'active' : ''; ?>" 
                                             onclick="selectLayout('sidebar', 'widgets')">
                                            <label>
                                                <input type="radio" name="sidebar_layout" value="widgets" 
                                                       <?php echo $current_sidebar_layout === 'widgets' ? 'checked' : ''; ?> style="display: none;">
                                                <strong>Widgets</strong><br>
                                                <small class="text-muted">Popular articles, categories, ads</small>
                                            </label>
                                        </div>
                                        
                                        <div class="layout-option <?php echo $current_sidebar_layout === 'trending' ? 'active' : ''; ?>" 
                                             onclick="selectLayout('sidebar', 'trending')">
                                            <label>
                                                <input type="radio" name="sidebar_layout" value="trending" 
                                                       <?php echo $current_sidebar_layout === 'trending' ? 'checked' : ''; ?> style="display: none;">
                                                <strong>Trending Only</strong><br>
                                                <small class="text-muted">Focus on trending/popular content</small>
                                            </label>
                                        </div>
                                        
                                        <div class="layout-option <?php echo $current_sidebar_layout === 'minimal' ? 'active' : ''; ?>" 
                                             onclick="selectLayout('sidebar', 'minimal')">
                                            <label>
                                                <input type="radio" name="sidebar_layout" value="minimal" 
                                                       <?php echo $current_sidebar_layout === 'minimal' ? 'checked' : ''; ?> style="display: none;">
                                                <strong>Minimal</strong><br>
                                                <small class="text-muted">Clean sidebar with essential widgets only</small>
                                            </label>
                                        </div>
                                        
                                        <div class="layout-option <?php echo $current_sidebar_layout === 'social' ? 'active' : ''; ?>" 
                                             onclick="selectLayout('sidebar', 'social')">
                                            <label>
                                                <input type="radio" name="sidebar_layout" value="social" 
                                                       <?php echo $current_sidebar_layout === 'social' ? 'checked' : ''; ?> style="display: none;">
                                                <strong>Social Focus</strong><br>
                                                <small class="text-muted">Social media feeds and sharing</small>
                                            </label>
                                        </div>
                                        
                                        <div class="layout-option <?php echo $current_sidebar_layout === 'newsletter' ? 'active' : ''; ?>" 
                                             onclick="selectLayout('sidebar', 'newsletter')">
                                            <label>
                                                <input type="radio" name="sidebar_layout" value="newsletter" 
                                                       <?php echo $current_sidebar_layout === 'newsletter' ? 'checked' : ''; ?> style="display: none;">
                                                <strong>Newsletter Focus</strong><br>
                                                <small class="text-muted">Newsletter signup and popular articles</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- General Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Display Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="articles_per_section" class="form-label">Articles per Section</label>
                                            <select class="form-select" name="articles_per_section" id="articles_per_section">
                                                <option value="3" <?php echo $articles_per_section == 3 ? 'selected' : ''; ?>>3 Articles</option>
                                                <option value="5" <?php echo $articles_per_section == 5 ? 'selected' : ''; ?>>5 Articles</option>
                                                <option value="8" <?php echo $articles_per_section == 8 ? 'selected' : ''; ?>>8 Articles</option>
                                                <option value="10" <?php echo $articles_per_section == 10 ? 'selected' : ''; ?>>10 Articles</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_breaking_news" 
                                                       id="show_breaking_news" <?php echo $show_breaking_news ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="show_breaking_news">
                                                    Show Breaking News Banner
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_trending" 
                                                       id="show_trending" <?php echo $show_trending ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="show_trending">
                                                    Show Trending Section
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Layout Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Layout Preview -->
                        <div class="layout-preview">
                            <h6><i class="fas fa-eye"></i> Layout Preview</h6>
                            <div class="row">
                                <div class="col-8">
                                    <div class="preview-section" id="mainPreview">
                                        Main Content Area - <?php echo ucfirst($current_main_layout); ?> Layout
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="preview-section">
                                        Sidebar - <?php echo ucfirst($current_sidebar_layout); ?> Layout
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectLayout(type, layout) {
            // Update radio buttons
            const radios = document.querySelectorAll(`input[name="${type}_layout"]`);
            radios.forEach(radio => {
                radio.checked = radio.value === layout;
            });
            
            // Update visual selection
            const options = document.querySelectorAll('.layout-option');
            options.forEach(option => {
                option.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update preview
            updatePreview();
        }
        
        function updatePreview() {
            const mainLayout = document.querySelector('input[name="main_layout"]:checked')?.value || 'mixed';
            const sidebarLayout = document.querySelector('input[name="sidebar_layout"]:checked')?.value || 'widgets';
            
            const mainPreview = document.getElementById('mainPreview');
            mainPreview.textContent = `Main Content Area - ${mainLayout.charAt(0).toUpperCase() + mainLayout.slice(1)} Layout`;
        }
        
        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
</body>
</html>