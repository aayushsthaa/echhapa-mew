<?php
require_once '../includes/auth_check.php';

$success = '';
$error = '';

$db = new Database();
$conn = $db->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = sanitize($_POST['title'] ?? '');
                $type = sanitize($_POST['ad_type'] ?? 'banner');
                $position = sanitize($_POST['position'] ?? 'sidebar');
                $content = $_POST['description'] ?? '';
                $click_url = sanitize($_POST['click_url'] ?? '');
                $image_url = sanitize($_POST['image_url'] ?? '');
                $status = sanitize($_POST['status'] ?? 'active');
                $start_date = sanitize($_POST['start_date'] ?? '');
                $end_date = sanitize($_POST['end_date'] ?? '');
                
                if (empty($title)) {
                    $error = 'Ad title is required';
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO ads (title, ad_type, position, description, click_url, image_url, status, start_date, end_date, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    if ($stmt->execute([
                        $title, $type, $position, $content, $click_url, $image_url, 
                        $status, $start_date ?: null, $end_date ?: null, $_SESSION['user_id']
                    ])) {
                        $success = 'Advertisement created successfully';
                    } else {
                        $error = 'Failed to create advertisement';
                    }
                }
                break;
                
            case 'delete':
                $ad_id = intval($_POST['ad_id'] ?? 0);
                if ($ad_id > 0) {
                    $stmt = $conn->prepare("DELETE FROM ads WHERE id = ?");
                    if ($stmt->execute([$ad_id])) {
                        $success = 'Advertisement deleted successfully';
                    } else {
                        $error = 'Failed to delete advertisement';
                    }
                }
                break;
                
            case 'toggle_status':
                $ad_id = intval($_POST['ad_id'] ?? 0);
                $status = sanitize($_POST['status'] ?? 'active');
                
                if ($ad_id > 0) {
                    $stmt = $conn->prepare("UPDATE ads SET status = ? WHERE id = ?");
                    if ($stmt->execute([$status, $ad_id])) {
                        $success = 'Advertisement status updated';
                    } else {
                        $error = 'Failed to update status';
                    }
                }
                break;
        }
    }
}

// Get all ads
$ads_stmt = $conn->query("
    SELECT a.*, u.first_name, u.last_name, 
           (SELECT COUNT(*) FROM ad_clicks WHERE ad_id = a.id) as clicks,
           (SELECT COUNT(*) FROM ad_impressions WHERE ad_id = a.id) as impressions
    FROM ads a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
");
$ads = $ads_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Management - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
    <style>
        .ad-preview {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            background: #f8f9fa;
        }
        .ad-stats {
            display: flex;
            gap: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 6px;
            flex: 1;
        }
        .ad-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .ad-type-banner { background: #e3f2fd; color: #1976d2; }
        .ad-type-square { background: #f3e5f5; color: #7b1fa2; }
        .ad-type-text { background: #e8f5e8; color: #388e3c; }
        .ad-type-video { background: #fff3e0; color: #f57c00; }
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
                    <a href="article-create.php"><i class="fas fa-plus-circle"></i> New Article</a>
                </li>
                <li class="menu-item">
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li class="menu-item">
                </li>
                <li class="menu-item active">
                    <a href="ads.php"><i class="fas fa-bullhorn"></i> Advertisements</a>
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
                    <h5 class="mb-0">Advertisement Management</h5>
                </div>
                <div class="topbar-right">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAdModal">
                        <i class="fas fa-plus"></i> New Advertisement
                    </button>
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

                    <!-- Ads List -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($ads)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                    <h5>No advertisements found</h5>
                                    <p class="text-muted">Create your first advertisement to start monetizing</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdModal">
                                        <i class="fas fa-plus"></i> Create Advertisement
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Type</th>
                                                <th>Position</th>
                                                <th>Performance</th>
                                                <th>Status</th>
                                                <th>Schedule</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ads as $ad): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ad['title']); ?></strong>
                                                    <br><small class="text-muted">by <?php echo htmlspecialchars($ad['first_name'] . ' ' . $ad['last_name']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="ad-type-badge ad-type-<?php echo $ad['ad_type']; ?>">
                                                        <?php echo ucfirst($ad['ad_type'] ?? 'banner'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo ucfirst($ad['position']); ?></td>
                                                <td>
                                                    <div class="ad-stats">
                                                        <div class="stat-item">
                                                            <div class="fw-bold text-primary"><?php echo number_format($ad['impressions']); ?></div>
                                                            <small>Impressions</small>
                                                        </div>
                                                        <div class="stat-item">
                                                            <div class="fw-bold text-success"><?php echo number_format($ad['clicks']); ?></div>
                                                            <small>Clicks</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;" onchange="this.submit()">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm">
                                                            <option value="active" <?php echo $ad['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="paused" <?php echo $ad['status'] === 'paused' ? 'selected' : ''; ?>>Paused</option>
                                                            <option value="expired" <?php echo $ad['status'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td>
                                                    <?php if ($ad['start_date']): ?>
                                                        <small>
                                                            <strong>Start:</strong> <?php echo date('M j, Y', strtotime($ad['start_date'])); ?><br>
                                                            <?php if ($ad['end_date']): ?>
                                                                <strong>End:</strong> <?php echo date('M j, Y', strtotime($ad['end_date'])); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Always Active</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-info" 
                                                                onclick="previewAd(<?php echo htmlspecialchars(json_encode($ad)); ?>)" 
                                                                title="Preview">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="editAd(<?php echo $ad['id']; ?>)" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Are you sure you want to delete this ad?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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

    <!-- Create Ad Modal -->
    <div class="modal fade" id="createAdModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Advertisement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Ad Title *</label>
                                    <input type="text" class="form-control" name="title" id="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ad_type" class="form-label">Ad Type *</label>
                                    <select class="form-select" name="ad_type" id="ad_type" onchange="updateAdPreview()" required>
                                        <option value="banner">Banner (728x90)</option>
                                        <option value="square">Square (300x300)</option>
                                        <option value="text">Text Only</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position *</label>
                                    <select class="form-select" name="position" id="position" required>
                                        <option value="header">Header</option>
                                        <option value="sidebar">Sidebar</option>
                                        <option value="footer">Footer</option>
                                        <option value="content-top">Content Top</option>
                                        <option value="content-bottom">Content Bottom</option>
                                        <option value="between-articles">Between Articles</option>
                                        <option value="between-categories">Between Categories</option>
                                        <option value="inline">Inline Content</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="active">Active</option>
                                        <option value="paused">Paused</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Ad Content</label>
                            <textarea class="form-control" name="description" id="description" rows="4" 
                                      placeholder="HTML content for your ad (for text/banner ads)"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image URL</label>
                            <div class="input-group">
                                <input type="url" class="form-control" name="image_url" id="image_url" 
                                       placeholder="https://example.com/ad-image.jpg" onchange="updateAdPreview()">
                                    <i class="fas fa-images"></i> Browse
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="click_url" class="form-label">Click URL</label>
                            <input type="url" class="form-control" name="click_url" id="click_url" 
                                   placeholder="https://example.com/landing-page">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date (Optional)</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date (Optional)</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ad Preview -->
                        <div class="ad-preview" id="adPreview">
                            <h6>Ad Preview</h6>
                            <div id="previewContent">
                                <p class="text-muted">Fill in the fields above to see a preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Advertisement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateAdPreview() {
            const title = document.getElementById('title').value;
            const type = document.getElementById('ad_type').value;
            const imageUrl = document.getElementById('image_url').value;
            const content = document.getElementById('description').value;
            const previewContent = document.getElementById('previewContent');
            
            let previewHTML = '';
            
            if (type === 'banner' || type === 'square') {
                if (imageUrl) {
                    previewHTML = `<img src="${imageUrl}" alt="${title}" style="max-width: 100%; height: auto; border: 1px solid #ddd;">`;
                } else if (content) {
                    previewHTML = content;
                } else {
                    previewHTML = `<div style="background: #f0f0f0; padding: 20px; text-align: center; border: 2px dashed #ccc;">${title || 'Ad Title'}</div>`;
                }
            } else if (type === 'text') {
                previewHTML = content || title || 'Text advertisement content';
            } else if (type === 'video') {
                previewHTML = '<div style="background: #000; color: white; padding: 20px; text-align: center;"><i class="fas fa-play"></i> Video Advertisement</div>';
            }
            
            previewContent.innerHTML = previewHTML;
        }
        
        function previewAd(adData) {
            alert('Ad Preview:\n\nTitle: ' + adData.title + '\nType: ' + adData.type + '\nPosition: ' + adData.position);
        }
        
        function editAd(adId) {
            alert('Edit functionality will be implemented');
        }
        
        }
        
        // Auto-update preview on input change
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['title', 'ad_type', 'image_url', 'description'];
            inputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', updateAdPreview);
                    element.addEventListener('change', updateAdPreview);
                }
            });
        });
    </script>
</body>
</html>