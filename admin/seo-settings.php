<?php
require_once '../includes/auth_check.php';

$success = '';
$error = '';

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_seo':
            $seo_settings = [
                'site_title' => sanitize($_POST['site_title'] ?? ''),
                'site_description' => sanitize($_POST['site_description'] ?? ''),
                'site_keywords' => sanitize($_POST['site_keywords'] ?? ''),
                'google_analytics_id' => sanitize($_POST['google_analytics_id'] ?? ''),
                'google_search_console' => sanitize($_POST['google_search_console'] ?? ''),
                'facebook_app_id' => sanitize($_POST['facebook_app_id'] ?? ''),
                'twitter_site' => sanitize($_POST['twitter_site'] ?? ''),
                'enable_sitemap' => isset($_POST['enable_sitemap']) ? '1' : '0',
                'enable_robots' => isset($_POST['enable_robots']) ? '1' : '0',
                'default_og_image' => sanitize($_POST['default_og_image'] ?? ''),
                'canonical_url' => sanitize($_POST['canonical_url'] ?? ''),
                'enable_schema_markup' => isset($_POST['enable_schema_markup']) ? '1' : '0'
            ];
            
            foreach ($seo_settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?, updated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success = 'SEO settings updated successfully';
            break;
            
        case 'generate_sitemap':
            // Generate XML sitemap
            generateSitemap($conn);
            $success = 'Sitemap generated successfully';
            break;
            
        case 'generate_robots':
            // Generate robots.txt
            generateRobotsTxt($conn);
            $success = 'Robots.txt generated successfully';
            break;
    }
}

// Helper functions
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

function generateSitemap($conn) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Add homepage
    $xml .= '<url><loc>' . 'http://' . $_SERVER['HTTP_HOST'] . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>' . "\n";
    
    // Add articles
    $articles_stmt = $conn->query("SELECT slug, updated_at FROM articles WHERE status = 'published' ORDER BY updated_at DESC");
    $articles = $articles_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($articles as $article) {
        $xml .= '<url>';
        $xml .= '<loc>http://' . $_SERVER['HTTP_HOST'] . '/article.php?slug=' . htmlspecialchars($article['slug']) . '</loc>';
        $xml .= '<lastmod>' . date('c', strtotime($article['updated_at'])) . '</lastmod>';
        $xml .= '<changefreq>monthly</changefreq>';
        $xml .= '<priority>0.8</priority>';
        $xml .= '</url>' . "\n";
    }
    
    // Add categories
    $categories_stmt = $conn->query("SELECT slug FROM categories WHERE status = 'active'");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categories as $category) {
        $xml .= '<url>';
        $xml .= '<loc>http://' . $_SERVER['HTTP_HOST'] . '/category.php?slug=' . htmlspecialchars($category['slug']) . '</loc>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.6</priority>';
        $xml .= '</url>' . "\n";
    }
    
    $xml .= '</urlset>';
    
    file_put_contents('../sitemap.xml', $xml);
}

function generateRobotsTxt($conn) {
    $robots = "User-agent: *\n";
    $robots .= "Allow: /\n";
    $robots .= "Disallow: /admin/\n";
    $robots .= "Disallow: /includes/\n";
    $robots .= "Disallow: /config/\n";
    $robots .= "\n";
    $robots .= "Sitemap: http://" . $_SERVER['HTTP_HOST'] . "/sitemap.xml\n";
    
    file_put_contents('../robots.txt', $robots);
}

// Get current settings
$current_settings = [
    'site_title' => getSetting($conn, 'site_title', SITE_NAME),
    'site_description' => getSetting($conn, 'site_description', ''),
    'site_keywords' => getSetting($conn, 'site_keywords', ''),
    'google_analytics_id' => getSetting($conn, 'google_analytics_id', ''),
    'google_search_console' => getSetting($conn, 'google_search_console', ''),
    'facebook_app_id' => getSetting($conn, 'facebook_app_id', ''),
    'twitter_site' => getSetting($conn, 'twitter_site', ''),
    'enable_sitemap' => getSetting($conn, 'enable_sitemap', '1'),
    'enable_robots' => getSetting($conn, 'enable_robots', '1'),
    'default_og_image' => getSetting($conn, 'default_og_image', ''),
    'canonical_url' => getSetting($conn, 'canonical_url', 'http://' . $_SERVER['HTTP_HOST']),
    'enable_schema_markup' => getSetting($conn, 'enable_schema_markup', '1')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Settings - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="menu-item">
                </li>
                <li class="menu-item">
                    <a href="ads.php"><i class="fas fa-bullhorn"></i> Advertisements</a>
                </li>
                <li class="menu-header">Site Settings</li>
                <li class="menu-item">
                    <a href="homepage-layout.php"><i class="fas fa-layout"></i> Homepage Layout</a>
                </li>
                <li class="menu-item active">
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
                    <h5 class="mb-0">SEO Settings</h5>
                </div>
                <div class="topbar-right">
                    <div class="btn-group">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="generate_sitemap">
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-sitemap"></i> Generate Sitemap
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="generate_robots">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-robot"></i> Generate Robots.txt
                            </button>
                        </form>
                    </div>
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
                        <input type="hidden" name="action" value="update_seo">
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Basic SEO Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic SEO Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="site_title" class="form-label">Site Title</label>
                                            <input type="text" class="form-control" name="site_title" id="site_title" 
                                                   value="<?php echo htmlspecialchars($current_settings['site_title']); ?>" required>
                                            <small class="text-muted">This will appear in browser titles and search results</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_description" class="form-label">Site Description</label>
                                            <textarea class="form-control" name="site_description" id="site_description" rows="3"><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                                            <small class="text-muted">Brief description of your news site (150-160 characters recommended)</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_keywords" class="form-label">Site Keywords</label>
                                            <input type="text" class="form-control" name="site_keywords" id="site_keywords" 
                                                   value="<?php echo htmlspecialchars($current_settings['site_keywords']); ?>">
                                            <small class="text-muted">Comma-separated keywords (e.g., news, politics, sports)</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="canonical_url" class="form-label">Canonical URL</label>
                                            <input type="url" class="form-control" name="canonical_url" id="canonical_url" 
                                                   value="<?php echo htmlspecialchars($current_settings['canonical_url']); ?>">
                                            <small class="text-muted">Your site's main URL without trailing slash</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="default_og_image" class="form-label">Default Open Graph Image</label>
                                            <input type="url" class="form-control" name="default_og_image" id="default_og_image" 
                                                   value="<?php echo htmlspecialchars($current_settings['default_og_image']); ?>">
                                            <small class="text-muted">Default image for social sharing (1200x630px recommended)</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analytics & Tracking -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Analytics & Tracking</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="google_analytics_id" class="form-label">Google Analytics ID</label>
                                            <input type="text" class="form-control" name="google_analytics_id" id="google_analytics_id" 
                                                   value="<?php echo htmlspecialchars($current_settings['google_analytics_id']); ?>"
                                                   placeholder="G-XXXXXXXXXX">
                                            <small class="text-muted">Google Analytics 4 Measurement ID</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="google_search_console" class="form-label">Google Search Console Verification</label>
                                            <input type="text" class="form-control" name="google_search_console" id="google_search_console" 
                                                   value="<?php echo htmlspecialchars($current_settings['google_search_console']); ?>"
                                                   placeholder="google-site-verification content">
                                            <small class="text-muted">Meta tag content for Google Search Console verification</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Social Media Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Social Media Integration</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="facebook_app_id" class="form-label">Facebook App ID</label>
                                            <input type="text" class="form-control" name="facebook_app_id" id="facebook_app_id" 
                                                   value="<?php echo htmlspecialchars($current_settings['facebook_app_id']); ?>">
                                            <small class="text-muted">For Facebook social plugins and Open Graph</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="twitter_site" class="form-label">Twitter Site Handle</label>
                                            <div class="input-group">
                                                <span class="input-group-text">@</span>
                                                <input type="text" class="form-control" name="twitter_site" id="twitter_site" 
                                                       value="<?php echo htmlspecialchars($current_settings['twitter_site']); ?>"
                                                       placeholder="yournewssite">
                                            </div>
                                            <small class="text-muted">Your site's Twitter handle for Twitter Cards</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- SEO Features -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">SEO Features</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_sitemap" 
                                                       id="enable_sitemap" <?php echo $current_settings['enable_sitemap'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_sitemap">
                                                    Enable XML Sitemap
                                                </label>
                                            </div>
                                            <small class="text-muted">Generate XML sitemap for search engines</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_robots" 
                                                       id="enable_robots" <?php echo $current_settings['enable_robots'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_robots">
                                                    Enable Robots.txt
                                                </label>
                                            </div>
                                            <small class="text-muted">Control search engine crawling</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_schema_markup" 
                                                       id="enable_schema_markup" <?php echo $current_settings['enable_schema_markup'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_schema_markup">
                                                    Enable Schema Markup
                                                </label>
                                            </div>
                                            <small class="text-muted">Rich snippets for articles and organization</small>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save SEO Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEO Status -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">SEO Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Sitemap.xml</span>
                                            <?php if (file_exists('../sitemap.xml')): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Found</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Robots.txt</span>
                                            <?php if (file_exists('../robots.txt')): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Not Found</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Google Analytics</span>
                                            <?php if (!empty($current_settings['google_analytics_id'])): ?>
                                                <span class="badge bg-success">Configured</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Set</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Social Integration</span>
                                            <?php if (!empty($current_settings['facebook_app_id']) || !empty($current_settings['twitter_site'])): ?>
                                                <span class="badge bg-success">Configured</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Set</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Links -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">Quick Links</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <a href="../sitemap.xml" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-external-link-alt"></i> View Sitemap
                                            </a>
                                            <a href="../robots.txt" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-external-link-alt"></i> View Robots.txt
                                            </a>
                                            <a href="https://search.google.com/search-console" target="_blank" class="btn btn-outline-success btn-sm">
                                                <i class="fab fa-google"></i> Google Search Console
                                            </a>
                                            <a href="https://analytics.google.com" target="_blank" class="btn btn-outline-info btn-sm">
                                                <i class="fab fa-google"></i> Google Analytics
                                            </a>
                                        </div>
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
</body>
</html>