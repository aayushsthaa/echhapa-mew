<?php
require_once '../includes/auth_check.php';

// Helper function for file size formatting
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

$success = '';
$error = '';

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_POST) {
    $site_settings = [
        'site_name' => sanitize($_POST['site_name'] ?? ''),
        'site_tagline' => sanitize($_POST['site_tagline'] ?? ''),
        'admin_email' => sanitize($_POST['admin_email'] ?? ''),
        'timezone' => sanitize($_POST['timezone'] ?? ''),
        'date_format' => sanitize($_POST['date_format'] ?? ''),
        'articles_per_page' => intval($_POST['articles_per_page'] ?? 10),
        'enable_comments' => isset($_POST['enable_comments']) ? '1' : '0',
        'enable_registration' => isset($_POST['enable_registration']) ? '1' : '0',
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'facebook_url' => sanitize($_POST['facebook_url'] ?? ''),
        'twitter_url' => sanitize($_POST['twitter_url'] ?? ''),
        'instagram_url' => sanitize($_POST['instagram_url'] ?? ''),
        'contact_email' => sanitize($_POST['contact_email'] ?? ''),
        'contact_phone' => sanitize($_POST['contact_phone'] ?? ''),
        'contact_address' => sanitize($_POST['contact_address'] ?? '')
    ];
    
    foreach ($site_settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value, $value]);
    }
    
    $success = 'Site settings updated successfully';
}

// Helper function to get settings
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}

// Get current settings
$current_settings = [
    'site_name' => getSetting($conn, 'site_name', SITE_NAME),
    'site_tagline' => getSetting($conn, 'site_tagline', 'Your trusted news source'),
    'admin_email' => getSetting($conn, 'admin_email', 'admin@example.com'),
    'timezone' => getSetting($conn, 'timezone', 'UTC'),
    'date_format' => getSetting($conn, 'date_format', 'F j, Y'),
    'articles_per_page' => getSetting($conn, 'articles_per_page', '10'),
    'enable_comments' => getSetting($conn, 'enable_comments', '1'),
    'enable_registration' => getSetting($conn, 'enable_registration', '0'),
    'maintenance_mode' => getSetting($conn, 'maintenance_mode', '0'),
    'facebook_url' => getSetting($conn, 'facebook_url', ''),
    'twitter_url' => getSetting($conn, 'twitter_url', ''),
    'instagram_url' => getSetting($conn, 'instagram_url', ''),
    'contact_email' => getSetting($conn, 'contact_email', ''),
    'contact_phone' => getSetting($conn, 'contact_phone', ''),
    'contact_address' => getSetting($conn, 'contact_address', '')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - <?php echo SITE_NAME; ?> Admin</title>
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
                    <a href="media.php"><i class="fas fa-images"></i> Media Library</a>
                </li>
                <li class="menu-item">
                    <a href="ads.php"><i class="fas fa-bullhorn"></i> Advertisements</a>
                </li>
                <li class="menu-header">Site Settings</li>
                <li class="menu-item">
                    <a href="homepage-layout.php"><i class="fas fa-layout"></i> Homepage Layout</a>
                </li>
                <li class="menu-item">
                    <a href="seo-settings.php"><i class="fas fa-search"></i> SEO Settings</a>
                </li>
                <li class="menu-item">
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                </li>
                <li class="menu-item active">
                    <a href="settings.php"><i class="fas fa-cogs"></i> General Settings</a>
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
                    <h5 class="mb-0">General Settings</h5>
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
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Basic Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" name="site_name" id="site_name" 
                                                   value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_tagline" class="form-label">Site Tagline</label>
                                            <input type="text" class="form-control" name="site_tagline" id="site_tagline" 
                                                   value="<?php echo htmlspecialchars($current_settings['site_tagline']); ?>">
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="admin_email" class="form-label">Admin Email</label>
                                                    <input type="email" class="form-control" name="admin_email" id="admin_email" 
                                                           value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="timezone" class="form-label">Timezone</label>
                                                    <select class="form-select" name="timezone" id="timezone">
                                                        <option value="UTC" <?php echo $current_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                                        <option value="America/New_York" <?php echo $current_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                                        <option value="America/Chicago" <?php echo $current_settings['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                                        <option value="America/Denver" <?php echo $current_settings['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                                        <option value="America/Los_Angeles" <?php echo $current_settings['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Display Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Display Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="date_format" class="form-label">Date Format</label>
                                                    <select class="form-select" name="date_format" id="date_format">
                                                        <option value="F j, Y" <?php echo $current_settings['date_format'] === 'F j, Y' ? 'selected' : ''; ?>>January 1, 2024</option>
                                                        <option value="Y-m-d" <?php echo $current_settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>2024-01-01</option>
                                                        <option value="m/d/Y" <?php echo $current_settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                                                        <option value="d/m/Y" <?php echo $current_settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="articles_per_page" class="form-label">Articles per Page</label>
                                                    <select class="form-select" name="articles_per_page" id="articles_per_page">
                                                        <option value="5" <?php echo $current_settings['articles_per_page'] === '5' ? 'selected' : ''; ?>>5</option>
                                                        <option value="10" <?php echo $current_settings['articles_per_page'] === '10' ? 'selected' : ''; ?>>10</option>
                                                        <option value="15" <?php echo $current_settings['articles_per_page'] === '15' ? 'selected' : ''; ?>>15</option>
                                                        <option value="20" <?php echo $current_settings['articles_per_page'] === '20' ? 'selected' : ''; ?>>20</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Contact Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="contact_email" class="form-label">Contact Email</label>
                                                    <input type="email" class="form-control" name="contact_email" id="contact_email" 
                                                           value="<?php echo htmlspecialchars($current_settings['contact_email']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="contact_phone" class="form-label">Contact Phone</label>
                                                    <input type="text" class="form-control" name="contact_phone" id="contact_phone" 
                                                           value="<?php echo htmlspecialchars($current_settings['contact_phone']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="contact_address" class="form-label">Contact Address</label>
                                            <textarea class="form-control" name="contact_address" id="contact_address" rows="3"><?php echo htmlspecialchars($current_settings['contact_address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Social Media -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Social Media Links</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="facebook_url" class="form-label">Facebook URL</label>
                                            <input type="url" class="form-control" name="facebook_url" id="facebook_url" 
                                                   value="<?php echo htmlspecialchars($current_settings['facebook_url']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="twitter_url" class="form-label">Twitter URL</label>
                                            <input type="url" class="form-control" name="twitter_url" id="twitter_url" 
                                                   value="<?php echo htmlspecialchars($current_settings['twitter_url']); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="instagram_url" class="form-label">Instagram URL</label>
                                            <input type="url" class="form-control" name="instagram_url" id="instagram_url" 
                                                   value="<?php echo htmlspecialchars($current_settings['instagram_url']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- System Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">System Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_comments" 
                                                       id="enable_comments" <?php echo $current_settings['enable_comments'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_comments">
                                                    Enable Comments
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="enable_registration" 
                                                       id="enable_registration" <?php echo $current_settings['enable_registration'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_registration">
                                                    Allow User Registration
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                                       id="maintenance_mode" <?php echo $current_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="maintenance_mode">
                                                    <span class="text-warning">Maintenance Mode</span>
                                                </label>
                                            </div>
                                            <small class="text-muted">Site will show maintenance message to visitors</small>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Information -->
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">System Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <small class="text-muted">
                                            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                                            <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?><br>
                                            <strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                                            <strong>Disk Space:</strong> <?php echo formatFileSize(disk_free_space('.')); ?> free
                                        </small>
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