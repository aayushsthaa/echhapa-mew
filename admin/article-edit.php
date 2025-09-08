<?php
require_once '../includes/auth_check.php';
require_once '../classes/Article.php';

$article_id = intval($_GET['id'] ?? 0);

if ($article_id <= 0) {
    redirect('articles.php');
}

$article = new Article();
$article_data = $article->getById($article_id);

if (!$article_data) {
    redirect('articles.php');
}

// Get categories
$db = new Database();
$conn = $db->getConnection();
$categories_stmt = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if ($_POST) {
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    $featured_image = sanitize($_POST['featured_image'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_breaking = isset($_POST['is_breaking']) ? 1 : 0;
    $scheduled_at = $status === 'scheduled' ? sanitize($_POST['scheduled_at'] ?? '') : null;
    
    // Basic validation
    if (empty($title) || empty($content)) {
        $error = 'Please fill in title and content';
    } else {
        // Generate slug from title if changed
        $slug = $article_data['slug'];
        if ($title !== $article_data['title']) {
            $new_slug = generateSlug($title);
            
            // Check if new slug exists (exclude current article)
            $slug_check = $conn->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
            $slug_check->execute([$new_slug, $article_id]);
            if ($slug_check->rowCount() > 0) {
                $new_slug .= '-' . time();
            }
            $slug = $new_slug;
        }
        
        $update_data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featured_image,
            'featured_image_alt' => $title,
            'category_id' => $category_id ?: null,
            'status' => $status,
            'published_at' => ($status === 'published' && !$article_data['published_at']) ? date('Y-m-d H:i:s') : $article_data['published_at'],
            'scheduled_at' => $scheduled_at,
            'meta_title' => sanitize($_POST['meta_title'] ?? $title),
            'meta_description' => sanitize($_POST['meta_description'] ?? $excerpt),
            'meta_keywords' => sanitize($_POST['meta_keywords'] ?? ''),
            'is_featured' => $is_featured,
            'is_breaking' => $is_breaking,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($article->update($article_id, $update_data)) {
            $success = 'Article updated successfully!';
            // Refresh article data
            $article_data = $article->getById($article_id);
        } else {
            $error = 'Failed to update article';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Article - <?php echo SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../public/css/admin.css" rel="stylesheet">
    <link href="../public/css/editor.css" rel="stylesheet">
    <style>
        .editor-container {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .editor-toolbar {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 8px;
        }
        .editor-toolbar button {
            background: none;
            border: 1px solid #dee2e6;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            cursor: pointer;
        }
        .editor-toolbar button:hover {
            background: #e9ecef;
        }
        .editor-toolbar button.active {
            background: #007bff;
            color: white;
        }
        .editor-content {
            min-height: 400px;
            padding: 16px;
            outline: none;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Include sidebar navigation (same as create page) -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-newspaper"></i> News Admin</h4>
                <p class="text-muted">v1.0</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item">
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
                    </a>
                </li>
                <li class="menu-item active">
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
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0">Edit Article</h5>
                    <span class="badge bg-info ms-2"><?php echo ucfirst($article_data['status']); ?></span>
                </div>
                <div class="topbar-right">
                    <a href="articles.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="fas fa-arrow-left"></i> Back to Articles
                    </a>
                    <?php if ($article_data['status'] === 'published'): ?>
                    <a href="../article.php?slug=<?php echo $article_data['slug']; ?>" target="_blank" class="btn btn-outline-success btn-sm me-2">
                        <i class="fas fa-external-link-alt"></i> View Article
                    </a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?php echo $_SESSION['user_name'] ?? 'Admin'; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
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
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" data-validate>
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Article Content</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Title *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($article_data['title']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="excerpt" class="form-label">Excerpt</label>
                                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3" 
                                                      placeholder="Brief summary of the article"><?php echo htmlspecialchars($article_data['excerpt'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Article Content *</label>
                                            <p class="text-muted mb-3">Use the content blocks below to create rich articles with text, images, videos, and quotes.</p>
                                            <div id="content-blocks-editor"></div>
                                            <textarea name="content" id="finalContent" style="display:none;" required><?php echo htmlspecialchars($article_data['content']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Publish Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" onchange="toggleScheduleOptions()">
                                                <option value="draft" <?php echo $article_data['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                <option value="published" <?php echo $article_data['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                                <option value="scheduled" <?php echo $article_data['status'] === 'scheduled' ? 'selected' : ''; ?>>Schedule for Later</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3" id="scheduleOptions" style="display: <?php echo $article_data['status'] === 'scheduled' ? 'block' : 'none'; ?>;">
                                            <label for="scheduled_at" class="form-label">Publish Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" 
                                                   value="<?php echo $article_data['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($article_data['scheduled_at'])) : ''; ?>">
                                            <small class="text-muted">Article will be automatically published at this time</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo $article_data['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="featured_image" class="form-label">Featured Image</label>
                                            <div class="featured-image-upload">
                                                <div class="upload-area" id="featuredImageUpload">
                                                    <?php if ($article_data['featured_image']): ?>
                                                        <img src="<?php echo htmlspecialchars($article_data['featured_image']); ?>" 
                                                             alt="Featured image" style="max-width: 100%; height: auto;">
                                                    <?php else: ?>
                                                        <div class="upload-placeholder">
                                                            <i class="fas fa-image"></i>
                                                            <p>Click to upload featured image</p>
                                                            <p class="text-muted">Recommended: 1200x600px</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" id="featuredImageFile" accept="image/*" style="display: none;">
                                                <input type="hidden" name="featured_image" id="featuredImageUrl" 
                                                       value="<?php echo htmlspecialchars($article_data['featured_image'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                       <?php echo $article_data['is_featured'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    Featured Article
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_breaking" name="is_breaking" 
                                                       <?php echo $article_data['is_breaking'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_breaking">
                                                    Breaking News
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Article
                                            </button>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="article-meta">
                                            <small class="text-muted">
                                                <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($article_data['created_at'])); ?><br>
                                                <strong>Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($article_data['updated_at'])); ?><br>
                                                <strong>Views:</strong> <?php echo number_format($article_data['views']); ?><br>
                                                <strong>Author:</strong> <?php echo htmlspecialchars($article_data['first_name'] . ' ' . $article_data['last_name']); ?>
                                            </small>
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
    <script src="../public/js/admin.js"></script>
    <script src="../public/js/content-blocks.js"></script>
    <script>
        // Featured Image Upload Handler
        document.addEventListener('DOMContentLoaded', function() {
            const featuredImageUpload = document.getElementById('featuredImageUpload');
            const featuredImageFile = document.getElementById('featuredImageFile');
            const featuredImageUrl = document.getElementById('featuredImageUrl');
            
            if (featuredImageUpload && featuredImageFile) {
                featuredImageUpload.onclick = function() {
                    featuredImageFile.click();
                };
                
                featuredImageFile.onchange = async function() {
                    const file = this.files[0];
                    if (!file) return;
                    
                    featuredImageUpload.innerHTML = '<div class="upload-progress"><i class="fas fa-spinner fa-spin"></i> Uploading...</div>';
                    
                    const formData = new FormData();
                    formData.append('image', file);
                    
                    try {
                        const response = await fetch('upload-image.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            featuredImageUpload.innerHTML = `<img src="${result.url}" alt="Featured image" style="max-width: 100%; height: auto;">`;
                            featuredImageUrl.value = result.url;
                        } else {
                            featuredImageUpload.innerHTML = `<div class="upload-error"><i class="fas fa-exclamation-triangle"></i><p>Upload failed: ${result.message}</p></div>`;
                        }
                    } catch (error) {
                        featuredImageUpload.innerHTML = `<div class="upload-error"><i class="fas fa-exclamation-triangle"></i><p>Upload failed: Network error</p></div>`;
                    }
                };
            }
            
            // Form Submission Handler
            const form = document.querySelector('form[data-validate]');
            if (form) {
                form.onsubmit = function(e) {
                    // Collect content from blocks editor
                    if (typeof contentEditor !== 'undefined') {
                        const blocks = contentEditor.getContent();
                        const contentJson = JSON.stringify(blocks);
                        document.getElementById('finalContent').value = contentJson;
                    }
                    
                    // Validate required fields
                    const title = document.getElementById('title').value.trim();
                    const finalContent = document.getElementById('finalContent').value.trim();
                    
                    if (!title) {
                        alert('Please enter a title.');
                        e.preventDefault();
                        return false;
                    }
                    
                    if (!finalContent || finalContent === '[]') {
                        alert('Please add some content to your article.');
                        e.preventDefault();
                        return false;
                    }
                    
                    return true;
                };
            }
        });
        
        // Schedule Options Toggle
        function toggleScheduleOptions() {
            const status = document.getElementById('status').value;
            const scheduleOptions = document.getElementById('scheduleOptions');
            if (status === 'scheduled') {
                scheduleOptions.style.display = 'block';
            } else {
                scheduleOptions.style.display = 'none';
            }
        }
    </script>
</body>
</html>